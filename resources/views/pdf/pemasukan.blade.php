<h2 style="text-align: center;">LAPORAN PENDAPATAN LAUNDRY</h2>
<p>Periode: {{ $namaBulan }} {{ $tahun }}</p>
<table border="1" width="100%" style="border-collapse: collapse; font-family: sans-serif;">
    <thead style="background-color: #673AB7; color: white;">
        <tr>
            <th style="padding: 8px;">Tanggal</th>
            <th style="padding: 8px;">Pelanggan</th>
            <th style="padding: 8px;">Layanan</th>
            <th style="padding: 8px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orders as $item)
        <tr>
            <!-- 1. Menampilkan Tanggal -->
            <td style="padding: 8px;" align="center">
                {{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}
            </td>
            
            <!-- 2. PERBAIKAN: Mengambil nama dari relasi tabel customers -->
            <td style="padding: 8px;">
                {{ $item->customer->username ?? 'Pelanggan Anonim' }}
            </td>
            
            <!-- 3. PERBAIKAN: Mengambil nama paket laundry + jenis item satuan jika ada -->
            <td style="padding: 8px;">
                {{ $item->service->nama_layanan ?? 'Layanan' }}
                @if($item->jenis_satuan)
                    <br><small style="color: #555;">(Item: {{ $item->jenis_satuan }})</small>
                @endif
            </td>
            
            <!-- 4. Menampilkan Nominal Tagihan -->
            <td style="padding: 8px;" align="right">
                Rp {{ number_format($item->total_harga, 0, ',', '.') }}
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot style="background-color: #f2f2f2; font-weight: bold;">
        <tr>
            <td colspan="3" align="right" style="padding: 8px;">TOTAL PENDAPATAN</td>
            <td align="right" style="padding: 8px; color: #673AB7;">
                Rp {{ number_format($omzet, 0, ',', '.') }}
            </td>
        </tr>
    </tfoot>
</table>