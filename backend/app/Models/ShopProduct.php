<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ShopProduct extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['price' => 'decimal:2', 'is_published' => 'boolean']; }
    public function images() { return $this->hasMany(ShopProductImage::class, 'product_id')->orderBy('sort_order'); }
    public function variants() { return $this->hasMany(ShopProductVariant::class, 'product_id'); }
}
