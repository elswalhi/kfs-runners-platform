<?php
namespace App\Mail;

use App\Models\ShopOrder;
use Illuminate\Mail\Mailable;

class ShopWalletOrderNotification extends Mailable
{
    public function __construct(public ShopOrder $order) {}
    public function build(): self
    {
        return $this->subject('طلب متجر جديد يحتاج مراجعة التحويل')->view('emails.shop-wallet-order-notification');
    }
}
