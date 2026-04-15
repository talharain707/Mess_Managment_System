<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'served_on',
        'meal_slot',
        'dish_name',
        'estimated_cost',
        'notes',
    ];

    protected $casts = [
        'served_on' => 'date',
        'estimated_cost' => 'decimal:2',
    ];
}
