<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'room_no',
        'bed_no',
        'monthly_fee',
        'opening_balance',
        'joined_on',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'joined_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
