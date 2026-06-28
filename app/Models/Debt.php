<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_name',
        'total_amount',
        'remaining_amount',
        'status',
        'due_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    /**
     * Relationship with sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relationship with payments.
     */
    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    /**
     * Check if debt is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === 'paid' || !$this->due_date) {
            return false;
        }
        return $this->due_date->isPast() && $this->remaining_amount > 0;
    }
}
