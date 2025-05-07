<!DOCTYPE html>
<html>
<head>
    <title>Faktur Penjualan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; }
        .subtitle { text-align: center; font-size: 14px; margin-bottom: 10px}
        .line { border-bottom: 1px solid black; margin: 10px 0; }
        .info-table { width: 100%; margin-bottom: 10px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table th, .table td { border: 1px solid black; padding: 5px; text-align: center; }
        .total { font-weight: bold; text-align: right; margin-top: 10px; }
        .footer { font-style: italic; text-align: center; margin-top: 20px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach($fakturs as $faktur)
        <div>
            <div class="title">PT INDO GADAI PRIMA</div>
            <div class="subtitle">Jl. KH Hasyim Ashari 112BB, Jakarta Pusat | Telp : 0856-9312-4547</div>
            <div class="title">Faktur Penjualan</div>
            <div class="line"></div>

            <table class="info-table">
                <tr>
                    <td><strong>Nomor Faktur</strong><br>{{ $faktur->nomor_faktur }}</td>
                    <td align="center"><strong>Kepada</strong><br>{{ $faktur->pembeli }}</td>
                    <td align="right"><strong>Tanggal Penjualan</strong><br>{{ \Carbon\Carbon::parse($faktur->tgl_jual)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Petugas</strong><br>{{ $faktur->petugas }}</td>
                    <td align="center"><strong>Grade</strong><br>{{ $faktur->grade }}</td>
                    <td align="right"><strong>Keterangan</strong><br>{{ $faktur->keterangan }}</td>
                </tr>
            </table>

            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Lok Spk</th>
                        <th>Merk Tipe</th>
                        <th>Harga</th>
                        <th>Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($faktur->transaksiJuals as $index => $transaksi)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $transaksi->lok_spk }}</td>
                        <td>{{ $transaksi->barang->tipe ?? '-' }}</td>
                        <td>Rp. {{ number_format($transaksi->harga, 0, ',', '.') }}</td>
                        <td>Rp. {{ number_format($transaksi->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total">Total Harga Keseluruhan: Rp. {{ number_format($faktur->totalHarga, 0, ',', '.') }}</div>
            <div class="footer">Terima Kasih telah Berbelanja dengan Kami!</div>
        </div>

        @if(!$faktur->bukti->isEmpty())
            @foreach($faktur->bukti as $index => $bukti)
                <div class="page-break"></div> {{-- Pisahkan sebelum bukti --}}
                <div>
                    <div class="title">Bukti Transfer</div>
                    <p><strong>Keterangan:</strong> {{ $bukti->keterangan ?? '-' }}</p>
                    <p><strong>Nominal:</strong> Rp. {{ number_format($bukti->nominal ?? 0, 0, ',', '.') }}</p>
                    <div style="text-align: center; margin-top: 10px;">
                        <img src="{{ storage_path('app/public/' . $bukti->foto) }}" alt="Bukti Transfer {{ $index + 1 }}" style="max-width: 100%; max-height: 800px; object-fit: contain;">
                    </div>
                </div>
            @endforeach
        @endif

        @if(!$loop->last)
            <div class="page-break"></div> {{-- Pisahkan antar faktur --}}
        @endif
    @endforeach
</body>
</html>
