<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'ProductID';    
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

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

    public static function fulfillmentBreakdown(): array
    {
        $tasks    = ['printing','furnishing','installation'];     // ignore "delivery"
        $statuses = ['completed','in_progress','pending','rejected'];

        $base = [];
        foreach ($tasks as $t) { $base[$t] = array_fill_keys($statuses, 0); }

        $rows = static::query()
            ->whereNotNull('taskType')
            ->whereIn(DB::raw('LOWER(taskType)'), $tasks)
            ->selectRaw('LOWER(taskType) AS task, LOWER(status) AS stat, COUNT(*) AS c')
            ->groupBy('task','stat')
            ->get();

        foreach ($rows as $r) {
            if (isset($base[$r->task][$r->stat])) $base[$r->task][$r->stat] = (int)$r->c;
        }
        return $base;
    }

    public static function fulfillmentCounts(): array
    {
        $rows = static::query()
            ->whereNotNull('taskType')
            ->selectRaw('LOWER(taskType) AS task, COUNT(*) AS total')
            ->whereIn(DB::raw('LOWER(taskType)'), ['printing','furnishing','installation'])
            ->groupBy('task')
            ->pluck('total','task');

        return [
            'printing'     => (int)($rows['printing'] ?? 0),
            'furnishing'   => (int)($rows['furnishing'] ?? 0),
            'installation' => (int)($rows['installation'] ?? 0),
        ];
    }

    /**
     * Decide taskType from request item payload (or Eloquent items).
     * Will set to 'printing' if ANY item has a selected printer, else 'furnishing'.
     *
     * @param  array|\Illuminate\Support\Collection|null $itemsPayload  // e.g. $request->input('items')
     */
    public function updateTaskTypeFromItems($itemsPayload = null): void
    {
        // Accept either request payload or existing relation
        $items = collect($itemsPayload ?? $this->items);

        $hasPrinter = $items->contains(function ($i) {
            // handle both array payload and model instance
            $printer = is_array($i)
                ? ($i['printer'] ?? $i['printer_id'] ?? $i['printerId'] ?? null)
                : ($i->printer ?? $i->printer_id ?? null);

            return !empty($printer); // any non-empty value counts as “selected”
        });

        $this->taskType = $hasPrinter ? 'printing' : 'furnishing';
        $this->save();
    }
}