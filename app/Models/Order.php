<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $fillable = ['id_pelanggan', 'id_services', 'jenis_satuan', 'berat', 'total_harga', 'status'];

    // Relasi: Orderan ini milik siapa?
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_pelanggan', 'id');
    }

    // Relasi: Orderan ini pakai layanan apa?
    public function service()
    {
        return $this->belongsTo(Service::class, 'id_services', 'id');
    }
}