<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Service;
use App\Models\KategoriService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http; // WAJIB UNTUK NOTIFIKASI WA VIA BAILEYS
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * FUNGSI BARU: Melacak status cucian terakhir berdasarkan Nomor HP Customer
     */
public function checkStatusCustomer(Request $request)
{
    // 1. Validasi input nomor HP dari React Native
    $request->validate([
        'nomor_hp' => 'required|string'
    ]);

    // 2. Cari data customer berdasarkan nomor HP
    $customer = Customer::where('nomor_hp', $request->nomor_hp)->first();

    if (!$customer) {
        return response()->json([
            'success' => false,
            'message' => 'Nomor HP Anda belum terdaftar di sistem kami.'
        ], 404);
    }

    // 3. PERBAIKAN UTAMA: Mengubah ->first() menjadi ->get() agar mengambil SEMUA orderan
    $orders = Order::where('id_pelanggan', $customer->id)
                  ->with(['service.kategori', 'customer']) 
                  ->orderBy('created_at', 'desc')
                  ->get(); // <--- Mengambil seluruh riwayat dalam bentuk Array/List

    // 4. Return data lengkap dalam bentuk JSON yang baru
    return response()->json([
        'success'  => true,
        'customer' => $customer,
        'orders'   => $orders // <--- Nama key diubah jadi jamak 'orders' biar rapi
    ]);
}

    public function store(Request $request)
    {
        // 1. Validasi Inputan Kasir (Menyesuaikan input relasi yang dibutuhkan)
        $request->validate([
            'nama_pelanggan' => 'required',
            'nomor_hp' => 'required',
            'alamat' => 'required',
            'id_services' => 'required', // Menerima ID Layanan (angka) dari Picker React Native
            'total_harga' => 'required|numeric',
        ]);

        // 2. LOGIKA BARU: Ambil atau Buat Customer otomatis agar tidak redundan
        $customer = Customer::firstOrCreate(
            ['nomor_hp' => $request->nomor_hp],
            [
                'username' => $request->nama_pelanggan,
                'alamat' => $request->alamat
            ]
        );

        // Ambil data layanan untuk keperluan teks notifikasi WA
        $serviceInfo = Service::with('kategori')->find($request->id_services);
        $namaLayananTxt = $serviceInfo ? $serviceInfo->nama_layanan : 'Layanan';

        // 3. Simpan ke tabel orders berdasarkan struktur database baru
        $order = Order::create([
            'id_pelanggan' => $customer->id,
            'id_services' => $request->id_services,
            'jenis_satuan' => $request->kategori === 'Satuan' ? $request->jenis_satuan : null,
            'berat' => $request->berat ?? 0,
            'total_harga' => $request->total_harga,
            'status' => 'antre', // Sesuai ENUM baru yang telah kita normalisasi
        ]);

        // Muat ulang relasi agar objek customer dan service ikut masuk ke dalam variabel $order
        $order->load(['customer', 'service.kategori']);

        // --- 1. PESAN NOTIFIKASI WA: ORDER BARU ---
      $pesanOrderBaru = "Halo *" . $customer->username . "*, 👋😊\n" .
                  "Terima kasih sudah laundry di sini. Pesanan Anda telah diterima:\n\n" .
                  "📄 *ID:* #" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . "\n" .
                  "🧺 *Layanan:* " . $namaLayananTxt . "\n" .
                  "⚖️ *Berat:* " . ($order->berat > 0 ? $order->berat . " Kg" : "Satuan (" . ($order->jenis_satuan ?? '-') . ")") . "\n" .
                  "💰 *Harga:* Rp " . number_format($order->total_harga, 0, ',', '.') . "\n" .
                  "📌 *Status:* " . ucfirst($order->status) . "\n\n" .
                  "Mohon tunggu notifikasi selanjutnya saat cucian diproses. Terima kasih! 🙏\n\n" .
                  "----------------------------------------------\n" .
                  "*Pantau status cucian Anda melalui Aplikasi Mobile. Unduh sekarang di sini:*\n" .
                  "👉 https://bit.ly/DownloadAppLaundryZaki"; // Silakan ganti dengan link Google Drive / MediaFire kamu

        $this->kirimNotifikasiWA($customer->nomor_hp, $pesanOrderBaru);
        // --- END NOTIFIKASI ORDER BARU ---

        return response()->json(['message' => 'Pesanan berhasil disimpan!', 'data' => $order]);
    }

    public function index()
    {
        // Mengambil semua data order beserta data customer dan layanan terkait
        $orders = Order::with(['customer', 'service.kategori'])->orderBy('created_at', 'desc')->get();
        
        // MENGHITUNG HANYA YANG AKTIF (antre & diproses)
        $totalAktif = Order::whereIn('status', ['antre'])->count();
        
        return response()->json([
            'total' => $totalAktif,
            'data' => $orders
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            // Tarik data dengan relasi customernya
            $order = Order::with('customer')->find($id);

            if (!$order) {
                return response()->json(['message' => 'ID ' . $id . ' tidak ada di DB'], 404);
            }

            // Simpan status baru (pastikan yang dikirim dari React Native berhuruf kecil sesuai ENUM baru: antre, diproses, selesai, diambil)
            $order->status = strtolower($request->status);
            $order->save();

            // Sediakan variabel nama pelanggan dan nomor hp demi kelancaran notifikasi WA
            $namaPelanggan = $order->customer ? $order->customer->username : 'Pelanggan';
            $nomorHpPelanggan = $order->customer ? $order->customer->nomor_hp : null;

            if ($nomorHpPelanggan) {
                // --- LOGIKA NOTIFIKASI WA OTOMATIS BERDASARKAN STATUS VIA PORT 3000 ---
                
                // 2. STATUS: DIPROSES
                if ($order->status === 'diproses' || $order->status === 'proses') {
                    $pesanProses = "Halo *" . $namaPelanggan . "*,\n" .
                                   "Cucian Anda dengan *ID Pesanan #00" . $order->id . "* **Sedang Dalam Proses** pengerjaan.";
                    
                    $this->kirimNotifikasiWA($nomorHpPelanggan, $pesanProses);
                } 
                
                // 3. STATUS: SELESAI
                else if ($order->status === 'selesai') {
                    $pesanSelesai = "Halo *" . $namaPelanggan . "*,\n" .
                                    "Cucian Anda dengan *ID Pesanan #00" . $order->id . "* **Telah Selesai dan Siap Diambil**.\n\n" .
                                    "💵 *Total Tagihan:* Rp " . number_format($order->total_harga, 0, ',', '.') . "\n\n" .
                                    "Silakan melakukan pengambilan ke toko ya. Terima kasih! 😊";
                    
                    $this->kirimNotifikasiWA($nomorHpPelanggan, $pesanSelesai);
                } 
                
                // 4. STATUS: DIAMBIL
               else if ($order->status === 'diambil') {
                     $pesanDiambil = "Halo *" . $namaPelanggan . "*, \n" .
                    "Pesanan *ID #" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . "* **Telah Sukses Diambil dan Dinyatakan Lunas**.\n\n" .
                    "Terima kasih atas kepercayaan Anda pada MM Laundry😊";

                    
                    $this->kirimNotifikasiWA($nomorHpPelanggan, $pesanDiambil);
                }
                // --- END LOGIKA NOTIFIKASI WA ---
            }

            return response()->json(['message' => 'Berhasil!', 'data' => $order]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $order->delete();

        return response()->json(['message' => 'Pesanan berhasil dihapus!']);
    }

    public function laporan(Request $request)
    {
        $bulan = $request->query('bulan', date('m'));
        $tahun = $request->query('tahun', date('Y'));

        // Query menyesuaikan ENUM huruf kecil 'diambil' dan memuat data customer
        $query = Order::with(['customer', 'service.kategori'])
                      ->where('status', 'diambil')
                      ->whereMonth('created_at', $bulan)
                      ->whereYear('created_at', $tahun);

        $orders = $query->orderBy('created_at', 'desc')->get();
        $omzet = $query->sum('total_harga');

        return response()->json([
            'omzet' => $omzet,
            'jumlah_transaksi' => $orders->count(),
            'data' => $orders
        ]);
    }

  public function downloadLaporanPDF(Request $request)
{
    // 1. Ambil data orderan yang KHUSUS berstatus 'diambil' saja beserta relasinya
    $orders = Order::where('status', 'diambil')
                    ->with(['customer', 'service'])
                    ->orderBy('created_at', 'asc')
                    ->get();

    // 2. Hitung total pemasukan (omzet) secara dinamis
    $omzet = $orders->sum('total_harga');

    // 3. Pecah bulan dan tahun secara dinamis berdasarkan waktu saat ini (Juni 2026)
    // Menggunakan library Carbon bawaan Laravel agar otomatis mengikuti bulan berjalan
    $namaBulan = Carbon::now()->translatedFormat('F'); // Menghasilkan: Juni
    $tahun = Carbon::now()->format('Y');               // Menghasilkan: 2026

    // 4. Render ke view 'pemasukan.blade.php' dengan variabel yang SINKRON
    $pdf = PDF::loadView('pdf.pemasukan', [
    'orders'    => $orders,
    'omzet'     => $omzet,
    'namaBulan' => $namaBulan,
    'tahun'     => $tahun
    ]);

    // 5. Download file PDF dengan nama file dinamis sesuai bulan berjalan
    return $pdf->download('laporan-pemasukan-'.strtolower($namaBulan).'-'.$tahun.'.pdf');
}

    public function update(Request $request, $id)
{
    // 1. Cari data orderan berdasarkan ID
    $order = Order::find($id);
    if (!$order) {
        return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
    }

    // 2. Validasi input dari React Native
    $request->validate([
        'nama_pelanggan' => 'required',
        'nomor_hp' => 'required',
        'alamat' => 'required',
        'berat' => 'required|numeric',
        'total_harga' => 'required|numeric',
    ]);

    // 3. LOGIKA BARU: Cari data customer yang terikat dengan orderan ini, lalu perbarui datanya
    $customer = Customer::find($order->id_pelanggan);
    if ($customer) {
        $customer->update([
            'username' => $request->nama_pelanggan,
            'nomor_hp' => $request->nomor_hp,
            'alamat' => $request->alamat,
        ]);
    }

    // 4. Perbarui data di tabel orders (berat dan total harga)
    $order->update([
        'berat' => $request->berat,
        'total_berat' => $request->berat,
        'total_harga' => $request->total_harga,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Pesanan dan data pelanggan berhasil diperbarui',
        'data' => $order->load('customer') // Sertakan data terbaru
    ]);
}

    public function show($id) 
    {
        // Memuat single data lengkap beserta customer dan paket layanannya
        $order = Order::with(['customer', 'service.kategori'])->find($id);
        return response()->json(['data' => $order]);
    }

    /**
     * Fungsi Helper Privat untuk Menembak Gateway Node.js (Baileys)
     */
    private function kirimNotifikasiWA($nomorHp, $pesan)
    {
        try {
            $response = Http::post('http://localhost:3000/api/kirim-wa', [
                'nomor_hp' => $nomorHp,
                'pesan' => $pesan
            ]);

            if ($response->successful()) {
                \Log::info("WA Terkirim ke nomor: " . $nomorHp);
            } else {
                \Log::error("Gagal kirim WA ke: " . $nomorHp . " | Respon: " . $response->body());
            }
        } catch (\Exception $e) {
            \Log::error("Error koneksi ke WA Gateway: " . $e->getMessage());
        }
    }
}