<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'image',
        'purchase_price',
        'selling_price',
        'stock',
        'expiry_date',
        'unit',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock' => 'integer',
        'expiry_date' => 'date',
    ];

    protected $appends = [
        'image_url',
    ];

    /**
     * Get image URL, or default placeholder.
     */
    public function getImageUrlAttribute()
    {
        if ($this->image && file_exists(public_path('storage/products/' . $this->image))) {
            return asset('storage/products/' . $this->image);
        }
        return asset('images/default-product.png');
    }

    /**
     * Check if product is expired.
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    /**
     * Check if product is near expiry (within 30 days and not yet expired).
     */
    public function isNearExpiry(): bool
    {
        if (!$this->expiry_date || $this->isExpired()) {
            return false;
        }
        return $this->expiry_date->diffInDays(Carbon::now(), true) <= 30;
    }

    /**
     * Check if product is low stock (less than 10).
     */
    public function isLowStock(): bool
    {
        return $this->stock < 10;
    }

    /**
     * Relationship with sale details.
     */
    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }
}
