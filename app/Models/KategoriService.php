<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriService extends Model
{
    protected $table = 'kategori_services';
    protected $fillable = ['nama', 'harga_per_kg', 'keterangan'];
}