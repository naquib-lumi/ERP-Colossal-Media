<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $table = 'machines';

    protected $fillable = [
        'user_id',
        'machine_name',
        'machine_type',
    ];
}
