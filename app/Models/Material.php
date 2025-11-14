<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Material extends Model
{
    use HasFactory;
    protected $table = 'materials';
    protected $primaryKey = 'MaterialID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    protected $fillable = [
        'UserID', 'materialName', 'materialDescription', 'material_type_id', 'unitCost', 'pastUsageReference', 'active'
    ];
    protected $casts = [
        'unitCost' => 'decimal:6',
        'active' => 'boolean'
    ];
    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'UserID', 'id');
    }
    public function materialType()
    {
        return $this->belongsTo(MaterialType::class, 'material_type_id');
    }
    public function item()
    {
        return $this->belongsTo(ProductItem::class, 'ItemID', 'ItemID');
    }
    public function primaryOfItems()
    {
        return $this->hasMany(ProductItem::class, 'MaterialID', 'MaterialID');
    }
}