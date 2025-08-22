<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class GenerateOrderNumbers extends Command
{
    protected $signature = 'orders:generate-numbers';
    protected $description = 'Generate order numbers for existing orders';

    public function handle()
    {
        $orders = Order::whereNull('order_number')->get();
        foreach ($orders as $order) {
            $order->order_number = '#ORD-' . $order->orderDate->format('Y') . '-' . str_pad($order->id, 3, '0', STR_PAD_LEFT);
            $order->save();
        }
        $this->info('Order numbers generated for ' . count($orders) . ' orders.');
    }
}