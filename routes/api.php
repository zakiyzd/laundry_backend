<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ExpenseController;

// --- AUTH ADMIN ---
Route::post('/login', [AuthController::class, 'login']);
// --- REGISTER ADMIN/OWNER (TAMBAH AKUN BARU) ---
Route::post('/register', [AuthController::class, 'register']);

// --- ORDERS (ADMIN & CUSTOMER) ---
Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']); 
Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

// --- LAPORAN ADMIN ---
Route::get('/laporan-admin', [OrderController::class, 'laporan']);
Route::get('/laporan-admin/pdf', [OrderController::class, 'downloadLaporanPDF']);

// rute untuk pengeluaran
Route::get('/expenses', [ExpenseController::class, 'index']);
Route::post('/expenses', [ExpenseController::class, 'store']);
// Tambahkan ini tepat di bawah rute expenses yang sudah ada
Route::get('/expenses/pdf', [ExpenseController::class, 'downloadPDF']);
// Rute untuk hapus pengeluaran 
Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);

// tombol update
Route::put('/orders/{id}', [OrderController::class, 'update']);

Route::get('/orders/{id}', [OrderController::class, 'show']);

// --- AUTH CUSTOMER (LOGIKA BARU TANPA REGISTER) ---
// Kita satukan di AuthController agar tidak tabrakan
Route::post('/login-customer', [AuthController::class, 'loginCustomer']);

// --- LOGOUT ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});