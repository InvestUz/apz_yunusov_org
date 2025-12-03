<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'inn',
        'pinfl',
        'passport',
        'company_name',
        'district',
        'status',
        'contract_date',
        'completion_date',
        'contract_amount',
        'initial_payment',
        'remaining_amount',
        'quarterly_payment',
        'payment_terms',
        'payment_period',
        'advance_percent',
        'notes',
        'needs_manual_resolve',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'completion_date' => 'date',
        'contract_amount' => 'decimal:2',
        'initial_payment' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'quarterly_payment' => 'decimal:2',
        'advance_percent' => 'decimal:2',
        'needs_manual_resolve' => 'boolean',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function schedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    // Alias for paymentSchedules
    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount_debit');
    }

    public function getTotalDebtAttribute()
    {
        return $this->contract_amount - $this->getTotalPaidAttribute();
    }

    public function getIdentifier()
    {
        return $this->inn ?? $this->pinfl ?? $this->passport;
    }

    public function scopeActive($query)
    {
        return $query->where('status', config('dashboard.statuses.active'));
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotIn('status', [
            config('dashboard.statuses.active'),
            config('dashboard.statuses.completed'),
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', config('dashboard.statuses.completed'));
    }
}
