<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    // Izinkan kolom-kolom ini diisi secara massal
    protected $fillable = ['nama_barang', 'total_harga'];
}