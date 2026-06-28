<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_spend',
        'max_discount',
        'stock',
        'is_active',
        'expiry_date',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
        'expiry_date' => 'date',
    ];

    /**
     * Determine if the voucher is valid for a given order amount.
     * Returns an array with ['valid' => bool, 'message' => string]
     */
    public function checkValidity($totalAmount)
    {
        if (!$this->is_active) {
            return [
                'valid' => false,
                'message' => 'Voucher ini sudah tidak aktif.'
            ];
        }

        if ($this->stock <= 0) {
            return [
                'valid' => false,
                'message' => 'Kuota penggunaan voucher ini sudah habis.'
            ];
        }

        if ($this->expiry_date && $this->expiry_date->isPast() && !$this->expiry_date->isToday()) {
            return [
                'valid' => false,
                'message' => 'Voucher ini sudah kedaluwarsa.'
            ];
        }

        if ($totalAmount < $this->min_spend) {
            return [
                'valid' => false,
                'message' => 'Total belanja Anda kurang dari syarat minimal belanja (' . number_format($this->min_spend, 0, ',', '.') . ').'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Voucher berhasil dipasang.'
        ];
    }

    /**
     * Calculate the discount amount for a given order amount.
     */
    public function getDiscountValue($totalAmount)
    {
        $discount = 0;

        if ($this->type === 'fixed') {
            $discount = (float) $this->value;
        } elseif ($this->type === 'percent') {
            $discount = $totalAmount * ((float) $this->value / 100);
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = (float) $this->max_discount;
            }
        }

        // Discount cannot exceed the total amount
        return min($discount, $totalAmount);
    }

    /**
     * Relationship with sales.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
