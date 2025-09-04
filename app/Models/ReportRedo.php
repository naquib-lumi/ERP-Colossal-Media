<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportRedo extends Model
{
    protected $table = 'report_redo';
    protected $primaryKey = 'ReportID';
    protected $fillable = ['OrderID', 'reason'];
    public $timestamps = true;

    public function order()
    {
        return $this->belongsTo(Order::class, 'OrderID', 'id');
    }
}