<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('alamat')->nullable()->after('nomor_hp');
        $table->string('kategori_pesanan')->after('alamat'); // Kiloan atau Satuan
        $table->string('jenis_satuan')->nullable()->after('kategori_pesanan'); // Bed cover, dll
        $table->string('tipe_paket')->nullable()->after('jenis_satuan'); // Express atau Biasa
    });
}

public function down(): void
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn(['alamat', 'kategori_pesanan', 'jenis_satuan', 'tipe_paket']);
    });
}
};
