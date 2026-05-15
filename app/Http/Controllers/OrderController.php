<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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

    return response()->json(['message' => 'Pesanan berhasil disimpan!', 'data' => $order]);
}

 public function index()
{
    // Mengambil semua data untuk ditampilkan di list
    $orders = Order::orderBy('created_at', 'desc')->get();
    
    // MENGHITUNG HANYA YANG AKTIF (Antre & Proses)
    $totalAktif = Order::whereIn('status', ['Antre', 'Proses'])->count();
    
    return response()->json([
        'total' => $totalAktif, // Kirim angka yang sudah difilter
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

// app/Http/Controllers/OrderController.php

// Update fungsi laporan yang sudah ada
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

// Tambah fungsi baru untuk PDF
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

    // Update semua field yang dikirim dari React Native
    $order->update($request->all());

    return response()->json([
        'success' => true,
        'message' => 'Pesanan berhasil diperbarui',
        'data' => $order
    ]);
}

public function show($id) {
    $order = Order::find($id);
    return response()->json(['data' => $order]);
}

}
