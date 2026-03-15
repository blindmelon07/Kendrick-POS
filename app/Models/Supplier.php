<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $fillable = ['name', 'contact_name', 'email', 'phone', 'address', 'notes', 'is_active'];

    public function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<PurchaseOrder, $this> */
    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<DeliveryOrder, $this> */
    public function deliveryOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }
}
