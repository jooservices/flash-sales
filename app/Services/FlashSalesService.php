<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Sale;
use App\Models\Stock;
use Carbon\Carbon;

class FlashSalesService
{
    public function order(int $saleId, int $productId, int $total)
    {
        $now = Carbon::now();

        // Invalid sale
        if (!Sale::where('start_time', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->where('end_time', '>=', $now)
                    ->orWhereNull('end_time');
            })->exists()) {
            return false;
        }

        // Invalid total
        /**
         * @TODO Can setup total default value in sale
         */
        if ($total > 1) {
            return false;
        }

        /**
         * Check stock
         * @var Stock $stock
         */
        $stock = Stock::where('product_id', $productId)->first();

        if ($stock->stock < $total) {
            return false;
        }


        $order = Order::create();
        $order->items()->create([
            'product_id' => $productId,
            'total' => $total
        ]);

        $stock->update([
            'stock' => $stock->stock - 1
        ]);

        return true;
    }
}
