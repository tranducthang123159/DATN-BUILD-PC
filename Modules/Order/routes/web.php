<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\App\Http\Controllers\admin\OrderController;
use Modules\Order\App\Http\Controllers\OrderController1;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => 'admin'], function () {
    Route::get('admin/order', [OrderController::class, 'index'])->name('order');
    Route::get('admin/order/add', [OrderController::class, 'add'])->name('add_order');
    Route::post('admin/order/add', [OrderController::class, 'add_product'])->name('add_order');
    Route::get('admin/order/{id}', [OrderController::class, 'show'])->name('show_order');
    Route::get('admin/order/{id}/edit', [OrderController::class, 'edit'])->name('edit_order');
    Route::put('admin/order/{id}/edit', [OrderController::class, 'update_product'])->name('update_order');
    Route::delete('admin/order/{id}', [OrderController::class, 'destroy'])->name('delete_order');
    Route::delete('admin/order/{id}', [OrderController::class, 'OrderController'])->name('delete_order');
    Route::put('/admin/order/{order}/status',  [OrderController::class, 'OrderController'])->name('admin.orders.update_status');
});

Route::middleware('auth')->group(function () {
    // routes/web.php

Route::get('orders/checkout', [OrderController1::class, 'checkout'])->name('orders.checkout');
Route::post('orders/place', [OrderController1::class, 'placeOrder'])->name('orders.placeOrder');
Route::get('orders/p', [OrderController1::class, 'paymentsuccess'])->name('orders.paymentsuccess');
});
