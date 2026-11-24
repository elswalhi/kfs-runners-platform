<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ShopProductImage extends Model
{
    protected $guarded = [];
    public function product() { return $this->belongsTo(ShopProduct::class, 'product_id'); }
    public function url(): string { return str_starts_with($this->path, 'storage/shop-products/') ? route('shop.image', $this) : asset($this->path); }
}
