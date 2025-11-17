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

        // 🔹 also track editable
        $editable = $this->getAttribute('editable');

        if ($existsInDb && ($currentStatus === '' || $currentStatus === null || $editable === null)) {
            // reload minimal columns from DB
            $fresh = DB::table('products')
                ->where('ProductID', $pid)
                ->select('status', 'accepted', 'taskType', 'editable')
                ->first();

            if ($fresh) {
                $currentStatus   = strtolower((string) $fresh->status);
                $currentAccepted = $fresh->accepted;
                // also sync taskType from db if model doesn't have it
                if ($this->getAttribute('taskType') === null && !empty($fresh->taskType)) {
                    $this->setAttribute('taskType', $fresh->taskType);
                }

                // 🔹 sync editable from DB if model doesn't have it
                if ($editable === null && isset($fresh->editable)) {
                    $editable = (int) $fresh->editable;
                    $this->setAttribute('editable', $editable);
                }
            }
        }

        // normalise editable (default 1 if still null)
        $editable = (int)($editable ?? 1);

        /**
         * ==============================
         * CASE 1: revive-only scenario
         * product exists + was rejected + (accepted = 0 OR accepted is null)
         * ==============================
         */
        if (
            $existsInDb &&
            $currentStatus === 'rejected' &&
            // sometimes you set 0, sometimes you set null → treat both as “needs revive”
            ($currentAccepted === 0 || $currentAccepted === '0')
        ) {
            // revive only
            if ($currentStatus !== 'in_progress') {
                $changes['status'] = 'in_progress';
            }
            // always clear accepted
            if ($currentAccepted !== null) {
                $changes['accepted'] = null;
            }

            if (!empty($changes)) {
                $this->forceFill($changes)->save();
            }

            // IMPORTANT: STOP HERE → do NOT touch taskType, do NOT check specs
            return;
        }

        /**
         * ==============================
         * CASE 2: normal path
         * - new product
         * - OR existing product but not the "rejected+needs-revive" case
         * → we can (re)derive taskType from specs, BUT only if taskType is empty
         * ==============================
         */

        // current task type on model (might be empty)
        $currentType = strtolower((string) $this->getAttribute('taskType'));
        $newType     = $currentType;

        // helper to detect a REAL printer
        $detectRealPrinter = function (int $pid): bool {
            return DB::table('product_items as pi')
                ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
                ->where('pi.ProductID', $pid)
                ->whereNotNull('s.printer')
                ->whereRaw("TRIM(s.printer) <> ''")
                // ignore “no”, “none”, “0”, “n”
                ->whereRaw("LOWER(TRIM(s.printer)) NOT IN ('no','none','n','0')")
                // ignore literal “null”
                ->whereRaw("LOWER(TRIM(s.printer)) <> 'null'")
                ->exists();
        };

        // only infer when empty
        if ($newType !== 'rejected') {
            $hasRealPrinter = $pid ? $detectRealPrinter((int)$pid) : false;
            $newType        = $hasRealPrinter ? 'printing' : 'furnishing';
        }

        // 🔹 write taskType only if changed AND editable != 0
        if ($editable !== 0 && $this->getAttribute('taskType') !== $newType) {
            $changes['taskType'] = $newType;
        }


        /**
         * For existing rows (non-rejected case):
         * - make sure status is in_progress
         * - clear accepted
         */
        if ($existsInDb) {
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