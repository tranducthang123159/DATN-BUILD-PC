<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\App\Http\Controllers\admin\BlogController;

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

Route::group([], function () {
    Route::get('admin/blog', [BlogController::class, 'index'])->name('blog');
    Route::get('admin/blog/add', [BlogController::class, 'add'])->name('add_blog');
    Route::post('admin/blog/add', [BlogController::class, 'add_blog'])->name('add_blog');
    Route::get('admin/blog/{id}', [BlogController::class, 'show'])->name('show_blog');
    Route::get('admin/blog/{id}/edit', [BlogController::class, 'edit'])->name('edit_blog');
    Route::put('admin/blog/{id}/edit', [BlogController::class, 'update_blog'])->name('update_blog');
    Route::delete('admin/blog/{id}', [BlogController::class, 'destroy'])->name('delete_blog');

});
