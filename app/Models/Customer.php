<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    
    // Kolom yang boleh diisi massal
    protected $fillable = ['username', 'nomor_hp', 'alamat'];

    // Relasi: Satu customer bisa punya banyak orderan
    public function orders()
    {
        return $this->hasMany(Order::class, 'id_pelanggan', 'id');
    }
}