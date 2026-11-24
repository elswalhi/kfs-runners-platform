<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ShopOrder extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $hidden = ['receipt_path'];
    public function items() { return $this->hasMany(ShopOrderItem::class, 'order_id'); }
    public function payment() { return $this->hasOne(Payment::class, 'shop_order_id'); }
}
