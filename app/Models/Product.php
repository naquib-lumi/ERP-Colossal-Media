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

    // 1) Does this product already exist?
    $existsInDb = $this->exists
        ? true
        : ($pid ? DB::table('products')->where('ProductID', $pid)->exists() : false);

    // 2) Decide taskType
    //    - If taskType already set on the model, KEEP it (do not override).
    //    - Only infer from specs when taskType is empty.
    $currentType = strtolower((string) $this->getAttribute('taskType'));
    $newType = $currentType; // default: keep existing

    if ($newType === '' || $newType === '0' || $newType === 'null' || $newType === null) {
        $hasPrinter = $pid
            ? DB::table('product_items as pi')
                ->leftJoin('specifications as s', 's.ItemID', '=', 'pi.ItemID')
                ->where('pi.ProductID', $pid)
                ->whereNotNull('s.printer')
                ->whereRaw("TRIM(s.printer) <> ''")
                ->whereRaw("LOWER(TRIM(s.printer)) <> 'null'")
                ->exists()
            : false;

        $newType = $hasPrinter ? 'printing' : 'furnishing';
    }

    $changes = [];

    // Only write taskType if it actually changes
    if ($this->getAttribute('taskType') !== $newType) {
        $changes['taskType'] = $newType;
    }

    // 3) If the row exists, move it back to in_progress and clear accepted
    if ($existsInDb) {
        if ($this->getAttribute('status') !== 'in_progress') {
            $changes['status'] = 'in_progress';
        }
        // Always clear accepted to NULL
        if ($this->getAttribute('accepted') !== null) {
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