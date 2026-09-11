<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'period',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'amount' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Calculate spent amount for this budget in its date range.
     */
    public function getSpentAttribute()
    {
        return (float) Transaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$this->start_date, $this->end_date])
            ->sum('amount');
    }

    /**
     * Calculate percentage spent.
     */
    public function getPercentageAttribute()
    {
        if ($this->amount <= 0) {
            return 0;
        }

        return min(100, round(($this->spent / $this->amount) * 100));
    }
}

