<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    
   protected $fillable = [
    'nama_pelanggan', 
    'nomor_hp', 
    'alamat', 
    'kategori_pesanan', 
    'jenis_satuan', 
    'tipe_paket', 
    'berat', 
    'layanan', 
    'total_harga', 
    'status'
];
}