<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http; // WAJIB UNTUK NOTIFIKASI WA VIA BAILEYS

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nama_pelanggan' => 'required',
            'nomor_hp' => 'required',
            'kategori_pesanan' => 'required',
            'layanan' => 'required',
            'total_harga' => 'required|numeric',
        ]);

        $order = Order::create([
            'nama_pelanggan' => $request->nama_pelanggan,
            'nomor_hp' => $request->nomor_hp,
            'alamat' => $request->alamat,
            'kategori_pesanan' => $request->kategori_pesanan,
            'jenis_satuan' => $request->jenis_satuan,
            'tipe_paket' => $request->tipe_paket,
            'berat' => $request->berat ?? 0,
            'layanan' => $request->layanan,
            'total_harga' => $request->total_harga,
            'status' => 'Antre',
        ]);

        // --- 1. PESAN NOTIFIKASI WA: ORDER BARU ---
        $pesanOrderBaru = "Halo *" . $order->nama_pelanggan . "*, 👋😊\n" .
                          "Terima kasih sudah laundry di sini. Pesanan Anda telah diterima:\n\n" .
                          "📄 *ID:* #00" . $order->id . "\n" .
                          "🧺 *Layanan:* " . $order->kategori_pesanan . " - " . $order->layanan . "\n" .
                          "⚖️ *Berat:* " . $order->berat . " Kg\n" .
                          "💰 *Harga:* Rp " . number_format($order->total_harga, 0, ',', '.') . "\n" .
                          "📌 *Status:* " . $order->status . "\n\n" .
                          "Mohon tunggu notifikasi selanjutnya saat cucian diproses. Terima kasih! 🙏";

        $this->kirimNotifikasiWA($order->nomor_hp, $pesanOrderBaru);
        // --- END NOTIFIKASI ORDER BARU ---

        return response()->json(['message' => 'Pesanan berhasil disimpan!', 'data' => $order]);
    }

    public function index()
    {
        // Mengambil semua data untuk ditampilkan di list
        $orders = Order::orderBy('created_at', 'desc')->get();
        
        // MENGHITUNG HANYA YANG AKTIF (Antre & Proses)
        $totalAktif = Order::whereIn('status', ['Antre', 'Proses', 'Diproses'])->count();
        
        return response()->json([
            'total' => $totalAktif,
            'data' => $orders
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $order = \App\Models\Order::find($id);

            if (!$order) {
                return response()->json(['message' => 'ID ' . $id . ' tidak ada di DB'], 404);
            }

            $order->status = $request->status;
            $order->save();

            // --- LOGIKA NOTIFIKASI WA OTOMATIS BERDASARKAN STATUS VIA PORT 3000 ---
            
            // 2. STATUS: PROSES / DIPROSES
            if ($order->status === 'Proses' || $order->status === 'Diproses') {
                $pesanProses = "Halo *" . $order->nama_pelanggan . "*, 👋\n" .
                               "Menginfokan bahwa cucian Anda dengan *ID Pesanan #00" . $order->id . "* **sedang dalam proses** pengerjaan. 🧺🧼";
                
                $this->kirimNotifikasiWA($order->nomor_hp, $pesanProses);
            } 
            
            // 3. STATUS: SELESAI
            else if ($order->status === 'Selesai') {
                $pesanSelesai = "Halo *" . $order->nama_pelanggan . "*, 🧺✨\n" .
                                "Cucian Anda dengan *ID Pesanan #00" . $order->id . "* **telah selesai dan siap diambil**.\n\n" .
                                "💵 *Total Tagihan:* Rp " . number_format($order->total_harga, 0, ',', '.') . "\n\n" .
                                "Silakan melakukan pengambilan ke toko ya. Terima kasih! 😊";
                
                $this->kirimNotifikasiWA($order->nomor_hp, $pesanSelesai);
            } 
            
            // 4. STATUS: DIAMBIL
            else if ($order->status === 'Diambil') {
                $pesanDiambil = "Halo *" . $order->nama_pelanggan . "*, 🙏😊\n" .
                                "Pesanan *ID #00" . $order->id . "* **telah sukses diambil dan dinyatakan lunas**. Terima kasih banyak atas kepercayaan Anda pada laundry kami! 👕🌸";
                
                $this->kirimNotifikasiWA($order->nomor_hp, $pesanDiambil);
            }
            // --- END LOGIKA NOTIFIKASI WA ---

            return response()->json(['message' => 'Berhasil!', 'data' => $order]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $order = \App\Models\Order::find($id);

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

        $query = Order::where('status', 'Diambil')
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
        $bulan = $request->query('bulan', date('m'));
        $tahun = $request->query('tahun', date('Y'));

        $orders = Order::where('status', 'Selesai')
                      ->whereMonth('created_at', $bulan)
                      ->whereYear('created_at', $tahun)
                      ->get();

        $omzet = $orders->sum('total_harga');
        
        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $namaBulan = $bulanIndo[(int)$bulan];

        $pdf = Pdf::loadView('pdf.pemasukan', compact('orders', 'omzet', 'namaBulan', 'tahun'));
        return $pdf->download("Laporan_Pemasukan_{$namaBulan}_{$tahun}.pdf");
    }

    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        $order->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil diperbarui',
            'data' => $order
        ]);
    }

    public function show($id) 
    {
        $order = Order::find($id);
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