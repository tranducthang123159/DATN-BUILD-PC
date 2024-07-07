<?php

namespace Modules\Order\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Cart\Entities\CartItem;
use Modules\Order\Entities\Orders;
use Modules\Order\Entities\Order_items;

class OrderController1 extends Controller
{
    public function checkout()
    {
        $user = auth()->user();

        $cartItems = CartItem::where('user_id', $user->id)
            ->with('product')
            ->get();

        $total = $cartItems->sum(function ($cartItem) {
            return $cartItem->product->price * $cartItem->quantity;
        });

        return view('public.checkout', compact('cartItems', 'total'));
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'full-name' => 'required|string',
            'phone-number' => 'required|string',
            'email' => 'required|email',
            'address' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'payment-method' => 'required|in:cash,momo,vnpay',
        ]);

        $user = auth()->user();

        $cartItems = CartItem::where('user_id', $user->id)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        $order = Orders::create([
            'user_id' => $user->id,
            // 'order_number' => uniqid('ORD-'),
            'total_amount' => $cartItems->sum(function ($cartItem) {
                return $cartItem->product->price * $cartItem->quantity;
            }),
            // 'status' => 'pending',
            // 'full_name' => $request->input('full-name'),
            // 'phone_number' => $request->input('phone-number'),
            // 'email' => $request->input('email'),
            // 'address' => $request->input('address'),
            // 'city' => $request->input('city'),
        ]);

        foreach ($cartItems as $cartItem) {
            Order_items::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->product->price,
            ]);
        }

        // Xóa giỏ hàng sau khi đặt hàng thành công
        CartItem::where('user_id', $user->id)->delete();

        return redirect()->route('home')->with('success', 'Đặt hàng thành công.');
    }

}
