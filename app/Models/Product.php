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

        $hasPrinter = DB::table('product_items as pi')
            ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
            ->where('pi.ProductID', $pid)
            ->where(function ($q) {
                $q->whereNotNull('s.printer')
                  ->whereRaw("TRIM(s.printer) <> ''")
                  ->whereRaw("LOWER(TRIM(s.printer)) <> 'null'");
            })
            ->exists();

        $newType = $hasPrinter ? 'printing' : 'furnishing';

        if ($this->taskType !== $newType) {
            $this->forceFill(['taskType' => $newType])->save();
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
}