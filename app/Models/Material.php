<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
class Material extends Model
{
    use HasFactory;
    protected $table = 'materials';
    protected $primaryKey = 'MaterialID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    // stock_quantity is left out on purpose: it changes only through
    // App\Services\MaterialStockService, which logs every movement.
    protected $fillable = [
        'UserID', 'materialName', 'materialDescription', 'material_type_id', 'unitCost', 'pastUsageReference', 'active',
        'quantity_unit', 'low_stock_quantity',
    ];
    protected $casts = [
        'unitCost' => 'decimal:6',
        'active' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_quantity' => 'integer',
    ];

    protected static function booted()
    {
        // Order items store material names; keep them pointing at this material after a rename.
        static::updated(function (Material $material) {
            if ($material->wasChanged('materialName')) {
                self::renameOnItems((string) $material->getOriginal('materialName'), (string) $material->materialName);
            }
        });
    }

    /** Replace $old with $new (ignoring case and spaces) in product_items.material lists. */
    public static function renameOnItems(string $old, string $new): int
    {
        $oldKey = mb_strtolower(trim($old));
        $new    = trim($new);
        if ($oldKey === '' || $new === '') {
            return 0;
        }

        $changed = 0;
        DB::table('product_items')->whereNotNull('material')->orderBy('ItemID')
            ->chunkById(500, function ($items) use ($oldKey, $new, &$changed) {
                foreach ($items as $item) {
                    $names = json_decode((string) $item->material, true);
                    if (! is_array($names)) {
                        continue;
                    }

                    $hit = false;
                    foreach ($names as $i => $name) {
                        if (is_string($name) && mb_strtolower(trim($name)) === $oldKey) {
                            $names[$i] = $new;
                            $hit = true;
                        }
                    }

                    if ($hit) {
                        DB::table('product_items')->where('ItemID', $item->ItemID)
                            ->update(['material' => json_encode(array_values(array_unique($names)))]);
                        $changed++;
                    }
                }
            }, 'ItemID');

        return $changed;
    }

    public function stockMovements()
    {
        return $this->hasMany(MaterialStockMovement::class, 'material_id', 'MaterialID');
    }

    /** At or below the low-stock alert level, when one is set. */
    public function isLowStock(): bool
    {
        return $this->low_stock_quantity !== null && $this->stock_quantity <= $this->low_stock_quantity;
    }
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