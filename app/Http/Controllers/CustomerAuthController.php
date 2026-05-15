<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    // FUNGSI UNTUK DAFTAR AKUN
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'nomor_hp' => 'required|unique:customers'
        ]);

        $customer = Customer::create([
            'username' => $request->username,
            'nomor_hp' => $request->nomor_hp,
        ]);

        return response()->json(['message' => 'Berhasil daftar!', 'data' => $customer]);
    }

    // FUNGSI UNTUK LOGIN
    public function login(Request $request)
    {
        $request->validate(['nomor_hp' => 'required']);

        // Cari di tabel customers, apakah nomor HP ini sudah daftar akun?
        $customer = Customer::where('nomor_hp', $request->nomor_hp)->first();

        if ($customer) {
            return response()->json([
                'message' => 'Login Berhasil',
                'data' => $customer
            ]);
        }

        return response()->json(['message' => 'Nomor HP tidak terdaftar!'], 404);
    }
}