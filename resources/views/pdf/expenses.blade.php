<h2 style="text-align: center;">Laporan Pengeluaran Laundry</h2>
<p>Periode: {{ $namaBulan }} {{ $tahun }}</p>
<table border="1" width="100%" style="border-collapse: collapse;">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Keterangan</th>
            <th>Total Harga</th>
        </tr>
    </thead>
    <tbody>
        @foreach($expenses as $item)
        <tr>
            <td>{{ $item->created_at->format('d/m/Y') }}</td>
            <td>{{ $item->nama_barang }}</td>
            <td>Rp {{ number_format($item->total_harga) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="2">TOTAL</th>
            <th>Rp {{ number_format($total) }}</th>
        </tr>
    </tfoot>
</table>