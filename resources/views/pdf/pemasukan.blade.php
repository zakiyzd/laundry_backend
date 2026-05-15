<h2 style="text-align: center;">LAPORAN PEMASUKAN LAUNDRY</h2>
<p>Periode: {{ $namaBulan }} {{ $tahun }}</p>
<table border="1" width="100%" style="border-collapse: collapse;">
    <thead style="background-color: #673AB7; color: white;">
        <tr>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Layanan</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orders as $item)
        <tr>
            <td>{{ $item->created_at->format('d/m/Y') }}</td>
            <td>{{ $item->nama_pelanggan }}</td>
            <td>{{ $item->layanan }}</td>
            <td>Rp {{ number_format($item->total_harga) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot style="background-color: #f2f2f2; font-weight: bold;">
        <tr>
            <td colspan="3" align="right">TOTAL PEMASUKAN</td>
            <td>Rp {{ number_format($omzet) }}</td>
        </tr>
    </tfoot>
</table>