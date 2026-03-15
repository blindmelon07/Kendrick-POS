<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'is_active'];

    public function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<Product, $this> */
    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }
}
