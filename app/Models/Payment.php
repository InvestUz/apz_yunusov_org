<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'payment_date',
        'inn',
        'pinfl',
        'passport',
        'amount_credit',
        'amount_debit',
        'district',
        'description',
        'payment_type',
        'year',
        'month',
        'is_matched',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount_credit' => 'decimal:2',
        'amount_debit' => 'decimal:2',
        'is_matched' => 'boolean',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function getIdentifier()
    {
        return $this->inn ?? $this->pinfl ?? $this->passport;
    }

    public function getNetAmountAttribute()
    {
        return $this->amount_debit - abs($this->amount_credit);
    }
}
