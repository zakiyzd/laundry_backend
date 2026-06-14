<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';
    protected $fillable = ['id_kategori', 'nama_layanan'];

    // Relasi ke tabel kategori_services
    public function kategori()
    {
        return $this->belongsTo(KategoriService::class, 'id_kategori', 'id');
    }
}