<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaterialType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
}