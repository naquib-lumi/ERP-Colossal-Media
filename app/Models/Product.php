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

    public static function fulfillmentBreakdown(): array
    {
        $tasks    = ['printing','furnishing','installation'];   
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
}