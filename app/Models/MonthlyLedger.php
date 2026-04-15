<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'month',
        'opening_balance',
        'monthly_fee',
        'paid_amount',
        'closing_liability',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
