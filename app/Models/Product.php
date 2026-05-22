<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'ProductID';    
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    protected $casts = [
        'editable' => 'boolean',
    ];

    protected $fillable = [
        'OrderID',
        'productName',
        'totalQuantity',
        'materialRemark',
        'productRemark',
        'location',
        'date_time',
        'permit',
    ];

    // ── Relations
    public function order() { 
        return $this->belongsTo(Order::class, 'OrderID', 'id');
    }

    public function items()
    {
        return $this->hasMany(ProductItem::class, 'ProductID', 'ProductID');
    }

    public function deliveryBreakdowns()
    {
        return $this->hasMany(DeliveryBreakdown::class, 'ProductID', 'ProductID')
                    ->orderBy('BreakdownID');
    }

    public function progress()
    {
        return $this->hasMany(FulfillmentProgress::class, 'ProductID', 'ProductID');
    }

    public function remarks()
    {
        return $this->hasMany(ProductRemark::class, 'ProductID', 'ProductID')
                ->orderBy('RemarkID');
    }

    public function getRouteKeyName()
    {
        return 'ProductID';
    }

    protected static function baseForFulfillment(?User $user): Builder
    {
        $q = static::query()                         // Eloquent builder
            ->from('products as p')                 // give table alias
            ->join('orders as o', 'o.id', '=', 'p.OrderID')
            ->where(function ($w) {
                $w->whereNull('o.status')->orWhere('o.status', 0);
            });

        if ($user && ($user->role ?? null) !== 'head-artist') {
            $uid = (int) $user->id;
            $q->where(function ($w) use ($uid) {
                $w->where('o.artist_id', $uid)
                ->orWhere('o.salesperson_id', $uid);
            });
        }

        return $q->whereIn(DB::raw('LOWER(p.taskType)'), ['printing','furnishing','installation']);
    }

    public static function fulfillmentBreakdown(?User $user = null): array
    {
        $rows = static::baseForFulfillment($user)
            ->selectRaw("
                LOWER(p.taskType) AS task,
                SUM(CASE WHEN o.orderStatus = 'completed'   THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN o.orderStatus = 'in_progress' THEN 1 ELSE 0 END) AS in_progress
            ")
            ->groupBy('task')
            ->get()
            ->keyBy('task');

        $fmt = fn ($t) => [
            'completed'   => (int) ($rows[$t]->completed   ?? 0),
            'in_progress' => (int) ($rows[$t]->in_progress ?? 0),
        ];

        return [
            'printing'     => $fmt('printing'),
            'furnishing'   => $fmt('furnishing'),
            'installation' => $fmt('installation'),
        ];
    }

    public static function fulfillmentCounts(?User $user = null): array
    {
        $rows = static::baseForFulfillment($user)
            ->selectRaw('LOWER(p.taskType) AS task, COUNT(*) AS total')
            ->groupBy('task')
            ->pluck('total', 'task');

        return [
            'printing'     => (int) ($rows['printing']     ?? 0),
            'furnishing'   => (int) ($rows['furnishing']   ?? 0),
            'installation' => (int) ($rows['installation'] ?? 0),
        ];
    }

    public function syncTaskTypeFromSpecs(): void
    {
        $pid = $this->getAttribute('ProductID') ?? $this->getKey();

        // 1) Does this product already exist in DB?
        $existsInDb = $this->exists
            ? true
            : ($pid ? DB::table('products')->where('ProductID', $pid)->exists() : false);

        // We’ll keep all changes here
        $changes = [];

        // 2) Make sure we have the real status/accepted from DB (sometimes model is “half-filled”)
        $currentStatus   = strtolower((string) $this->getAttribute('status'));
        $currentAccepted = $this->getAttribute('accepted'); // 0 / 1 / null
        $editable        = $this->getAttribute('editable');   // 0 / 1 / null
        $redoOf          = $this->getAttribute('redoOf');

        if ($existsInDb && ($currentStatus === '' || $currentStatus === null || $editable === null)) {
            // reload minimal columns from DB
            $fresh = DB::table('products')
                ->where('ProductID', $pid)
                ->select('status', 'accepted', 'taskType', 'editable', 'redoOf')
                ->first();

            if ($fresh) {
                $currentStatus   = strtolower(trim((string) $fresh->status));
                $currentAccepted = $fresh->accepted;
                $redoOf          = (int) ($fresh->redoOf ?? 0);
                if ($this->getAttribute('taskType') === null && !empty($fresh->taskType)) {
                    $this->setAttribute('taskType', $fresh->taskType);
                }

                if ($editable === null && isset($fresh->editable)) {
                    $editable = (int) $fresh->editable;
                    $this->setAttribute('editable', $editable);
                }

                if ($redoOf === null && isset($fresh->redoOf)) {
                    $redoOf = $fresh->redoOf;
                    $this->setAttribute('redoOf', $redoOf);
                }
            }
        }

        // normalise editable (default 1 if still null)
        $editable = (int)($editable ?? 1);
        $redoOf   = $redoOf === null ? null : (int) $redoOf;

        /**
         * ==============================
         * CASE 1: special “revive only” rule
         * Only for:
         *   - row already exists
         *   - status = rejected
         *   - accepted = 0
         *   - editable = 0   (non-editable original)
         *   - redoOf = 0     (original, NOT a redo copy)
         * ==============================
         */
        $isReviveCase =
            $existsInDb &&
            $currentStatus === 'rejected' &&
            (int) $currentAccepted === 0 &&
            $editable === 1 && ($redoOf === null || $redoOf === 0);

        if ($isReviveCase) {
            $keepTaskType = $this->getAttribute('taskType');

            DB::table('products')
                ->where('ProductID', $pid)
                ->update([
                    'status'   => 'in_progress',
                    'accepted' => null,
                    'taskType' => $keepTaskType,
                ]);

            $this->setAttribute('status', 'in_progress');
            $this->setAttribute('accepted', null);
            $this->setAttribute('taskType', $keepTaskType);

            // 🔴 IMPORTANT: stop here for revive case
            return;
        }

        /**
         * ==============================
         * CASE 2: normal path
         * Infer taskType ONLY when:
         *   - NOT rejected
         *   - editable = 1
         *   - redoOf > 0 (this is a redo copy)
         *   - taskType is empty
         * ==============================
         */

        $currentType = strtolower((string) $this->getAttribute('taskType'));
        $newType     = $currentType;

        // helper to detect a REAL printer
        $detectRealPrinter = function (int $pid): bool {
            return DB::table('product_items as pi')
                ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
                ->where('pi.ProductID', $pid)
                ->whereNotNull('s.printer')
                ->whereRaw("TRIM(s.printer) <> ''")
                ->whereRaw("LOWER(TRIM(s.printer)) NOT IN ('no','none','n','0')")
                ->whereRaw("LOWER(TRIM(s.printer)) <> 'null'")
                ->exists();
        };

        // ----- decide when we WANT to infer -----
        $isRedo        = ($redoOf !== null);          // redo copy when redoOf has value
        $canChangeType = ($editable === 1 && $currentStatus !== 'rejected');

        // REDO: always re-check from specs
        $shouldInferForRedo = $canChangeType && $isRedo;

        // NEW: only infer when no taskType yet
        $shouldInferForNew  = $canChangeType && !$isRedo &&
            ($currentType === '' || $currentType === null);

        // helper: does this product have *any* spec filled?
        $hasAnySpec = function (int $pid): bool {
            return DB::table('product_items as pi')
                ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
                ->where('pi.ProductID', $pid)
                ->where(function ($q) {
                    $q->whereNotNull('s.printer')
                      ->orWhereNotNull('s.cutter')
                      ->orWhereNotNull('s.lamination');
                })
                ->exists();
        };

        if (($shouldInferForRedo || $shouldInferForNew) && $pid) {
            $pid = (int) $pid;

            // ⛔ No specs at all → clear taskType
            if (!$hasAnySpec($pid)) {
                $newType = null;
            } else {
                // ✅ At least one spec → infer like before
                $hasRealPrinter = $detectRealPrinter($pid);
                $newType        = $hasRealPrinter ? 'printing' : 'furnishing';
            }
        }

        // ----- decide when to SAVE the new taskType -----
        if (
            ($shouldInferForRedo || $shouldInferForNew) &&
            $newType !== '' &&
            $newType !== $currentType
        ) {
            $changes['taskType'] = $newType;
        }

        // For existing rows (non-rejected case): ensure status in_progress & accepted null
        if ($existsInDb && $currentStatus !== 'rejected' && $editable === 1) {
            if ($currentStatus !== 'in_progress') {
                $changes['status'] = 'in_progress';
            }
            if ($currentAccepted !== null) {
                $changes['accepted'] = null;
            }
        }

        if (!empty($changes)) {
            $this->forceFill($changes)->save();
        }
    }
    
    public function getDisplayCodeAttribute(): string
    {
        $base = $this->redoOf ?: $this->ProductID;

        $suffix = '';
        if ((int)($this->editable ?? 0) === 1 && optional($this->order)->redo) {
            $orderNo = (string) optional($this->order)->order_number;
            if (preg_match('/R\d*$/', $orderNo, $m)) {
                $suffix = $m[0];          
            } else {
                $suffix = 'R';      
            }
        }

        return sprintf('%04d%s', (int)$base, $suffix);
    }

    public function original()
    {
        return $this->belongsTo(self::class, 'redoOf', 'ProductID')
            ->select(['ProductID','OrderID']);
    }

}