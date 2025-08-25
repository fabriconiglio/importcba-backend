<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;

class CheckCartStatus extends Command
{
    protected $signature = 'cart:status';
    protected $description = 'Check cart status for debugging';

    public function handle()
    {
        $this->info('=== CART STATUS DEBUG ===');
        
        // Usuarios
        $users = User::all();
        $this->info("Total users: " . $users->count());
        
        // Carritos
        $carts = Cart::with(['items.product', 'user'])->get();
        $this->info("Total carts: " . $carts->count());
        
        foreach ($carts as $cart) {
            $this->info("\n--- Cart ID: {$cart->id} ---");
            $this->info("User: {$cart->user->name} ({$cart->user->email})");
            $this->info("Items count: " . $cart->items->count());
            $this->info("Total: $" . $cart->getTotal());
            
            foreach ($cart->items as $item) {
                $this->info("  - {$item->product->name} x{$item->quantity} = $" . ($item->quantity * $item->price));
            }
        }
        
        // Items totales
        $totalItems = CartItem::count();
        $this->info("\nTotal cart items: {$totalItems}");
    }
}