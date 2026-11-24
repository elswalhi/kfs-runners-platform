<?php

namespace App\Http\Controllers\Web;

use App\Models\ShopOrder;

class AdminShopOrderDetailController
{
    public function __invoke(int $order)
    {
        $order = ShopOrder::withTrashed()->with('items')->findOrFail($order);

        return response()->view('admin.shop-order-show', compact('order'))
            ->header('Cache-Control', 'private, no-store');
    }
}
