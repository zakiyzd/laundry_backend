<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Order;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // Buat Token (Kunci akses)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role, // Mengirimkan role ke React Native
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Berhasil keluar']);
    }

   public function registerCustomer(Request $request) {
    try {
        $customer = new \App\Models\Customer();
        $customer->username = $request->username;
        $customer->nomor_hp = $request->nomor_hp;
        $customer->save(); // baris yang memasukkan data ke DB

        return response()->json(['message' => 'Berhasil', 'data' => $customer], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 500);
    }
}

public function loginCustomer(Request $request)
    {
        // 1. Validasi input nomor HP
        $request->validate([
            'nomor_hp' => 'required'
        ]);

        // 2. Cari di tabel orders (bukan customers)
        // Kita ambil pesanan terbaru agar namanya update sesuai inputan terakhir admin
        $dataOrder = Order::where('nomor_hp', $request->nomor_hp)
                        ->orderBy('created_at', 'desc')
                        ->first();

        // 3. Jika nomor HP ditemukan
        if ($dataOrder) {
            return response()->json([
                'success' => true,
                'message' => 'Login Berhasil',
                'username' => $dataOrder->nama_pelanggan, // Mengambil nama yang diinput Admin
                'nomor_hp' => $dataOrder->nomor_hp
            ], 200);
        }

        // 4. Jika nomor HP tidak ada di tabel orders
        return response()->json([
            'success' => false,
            'message' => 'Nomor HP belum terdaftar dalam pesanan kami.'
        ], 404);
    }

    // Tambahkan ini di dalam class AuthController (tambah akun admin/owner)
    public function register(Request $request)
    {
        try {
            // 1. Validasi Input
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|in:admin,owner', 
            ]);

            // 2. Simpan User baru ke tabel users
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dibuat! Silakan login.',
                'user' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal daftar: ' . $e->getMessage()
            ], 500);
        }
    }
}

