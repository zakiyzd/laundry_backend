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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('nama_pelanggan');
        $table->double('berat', 8, 2);
        $table->string('layanan');
        $table->integer('total_harga');
        $table->string('status')->default('Antre');
        $table->string('nomor_hp')->after('nama_pelanggan');
        $table->timestamps(); // INI SUDAH OTOMATIS BIKIN created_at & updated_at
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
