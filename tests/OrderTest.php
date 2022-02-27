<?php

namespace Tests;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Services\FlashSalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function testHappy()
    {
        $stock = Stock::factory()->create();
        $currentStock = $stock->stock;
        $sale = Sale::factory()->create();
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $stock->product->id
        ]);

        $service = app(FlashSalesService::class);
        $this->assertTrue($service->order($sale->id, $stock->product->id, 1));
        $this->assertDatabaseHas('order_items', [
            'product_id' => $stock->product->id,
            'total' => 1
        ]);

        $this->assertEquals($currentStock - 1, $stock->refresh()->stock);
    }

    public function testUnhappy()
    {
        $stock = Stock::factory()->create([
            'stock' => 1
        ]);

        $sale = Sale::factory()->create();
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $stock->product->id
        ]);

        $service = app(FlashSalesService::class);
        // Can not make purchase over 1 unit
        $this->assertFalse($service->order($sale->id, $stock->product->id, 2));

        $this->assertTrue($service->order($sale->id, $stock->product->id, 1));
        $this->assertDatabaseHas('order_items', [
            'product_id' => $stock->product->id,
            'total' => 1
        ]);

        $this->assertEquals(0, $stock->refresh()->stock);

        // Out of stock
        $this->assertFalse($service->order($sale->id, $stock->product->id, 1));
    }
}
