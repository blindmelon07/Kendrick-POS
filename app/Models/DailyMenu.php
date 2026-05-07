<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class DailyMenu extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'featured_date', 'sort_order'];

    protected $casts = ['featured_date' => 'date'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function todaysFeatured(): Collection
    {
        return static::where('featured_date', today())
            ->with(['product.category'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($entry) => $entry->product)
            ->filter(fn ($p) => $p && $p->is_active && $p->stock_quantity > 0)
            ->values();
    }

    public static function forDate(string $date): Collection
    {
        return static::whereDate('featured_date', $date)
            ->with(['product.category'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
