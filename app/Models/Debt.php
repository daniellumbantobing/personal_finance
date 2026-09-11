<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'type',
        'person_name',
        'total_amount',
        'paid_amount',
        'due_date',
        'status',
        'description',
    ];

    protected $casts = [
        'due_date' => 'date',
        'total_amount' => 'float',
        'paid_amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class)->orderBy('payment_date', 'desc');
    }

    public function getRemainingAmountAttribute()
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    public function getPercentageAttribute()
    {
        if ($this->total_amount <= 0) return 0;
        return min(100, round(($this->paid_amount / $this->total_amount) * 100));
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'paid';
    }

    public function getDueDaysLeftAttribute()
    {
        if (!$this->due_date) return null;
        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
    }
}

