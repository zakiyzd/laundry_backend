<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense; // Pastikan model Expense sudah dibuat
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenseController extends Controller
{
public function index(Request $request)
{
    // Ambil bulan dan tahun dari request, default-nya bulan & tahun sekarang
    $bulan = $request->query('bulan', date('m'));
    $tahun = $request->query('tahun', date('Y'));

    $expenses = \App\Models\Expense::whereMonth('created_at', $bulan)
        ->whereYear('created_at', $tahun)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($expenses);
}

  public function store(Request $request)
{
    try {
        $request->validate([
            'nama_barang' => 'required',
            'total_harga' => 'required|numeric',
        ]);

        // HAPUS kolom 'jumlah' dan 'harga_satuan' dari sini
        $expense = \App\Models\Expense::create([
            'nama_barang' => $request->nama_barang,
            'total_harga' => $request->total_harga,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran berhasil dicatat!',
            'data' => $expense
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            // Ini akan memunculkan pesan error asli di log kalau gagal
            'message' => $e->getMessage() 
        ], 500);
    }
}

public function downloadPDF(Request $request)
{
    $bulan = $request->query('bulan', date('m'));
    $tahun = $request->query('tahun', date('Y'));

    $expenses = \App\Models\Expense::whereMonth('created_at', $bulan)
        ->whereYear('created_at', $tahun)
        ->get();

    $total = $expenses->sum('total_harga');
    $namaBulan = date('F', mktime(0, 0, 0, $bulan, 10));

    $pdf = Pdf::loadView('pdf.expenses', compact('expenses', 'total', 'namaBulan', 'tahun'));
    return $pdf->download("Laporan_Pengeluaran_{$namaBulan}_{$tahun}.pdf");
}

public function destroy($id)
{
    $expense = \App\Models\Expense::find($id);
    if (!$expense) {
        return response()->json(['message' => 'Data tidak ditemukan'], 404);
    }
    $expense->delete();
    return response()->json(['success' => true]);
}
}