<?php

use App\Http\Controllers\web\auth\AuthController;
use App\Http\Controllers\web\Product\ProductController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin/supplier'], function () {
    Route::get('all', [SupplierController::class, 'index'])->name('admin.supplier.index');
    Route::get('create', [SupplierController::class, 'create'])->name('admin.supplier.create');
    Route::post('store', [SupplierController::class, 'store'])->name('admin.supplier.store');
    Route::get('edit/{id}', [SupplierController::class, 'edit'])->name('admin.supplier.edit');
    Route::put('update/{id}', [SupplierController::class, 'update'])->name('admin.supplier.update');
    Route::get('delete/{id}', [SupplierController::class, 'delete'])->name('admin.supplier.delete');
    Route::get('search', [SupplierController::class, 'search'])->name('admin.supplier.search');
});
