<!DOCTYPE html>
<html>
<head>
    <title>Faktur Penjualan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; }
        .subtitle { text-align: center; font-size: 14px; margin-bottom: 10px}
        .line { border-bottom: 1px solid black; margin-bottom: 10px; margin-top: 10px; }
        .info-table { width: 100%; margin-bottom: 10px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table th, .table td { border: 1px solid black; padding: 5px; text-align: center; }
        .info-table .right-align { text-align: right; }
        .info-table .center-align { text-align: center; }
        .total { font-weight: bold; }
        .footer { font-style: italic; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="title">PT INDO GADAI PRIMA</div>
    <div class="subtitle">Jl. KH Hasyim Ashari 112BB, Jakarta Pusat | Telp : 0856-9312-4547</div>
    <div class="title">Faktur Penjualan</div>
    <div class="line"></div>

    <div class="info">
        <table class="info-table">
            <tr>
                <td><strong>Nomor Faktur</strong><br>{{ $faktur->nomor_faktur }}</td>
                <td class="center-align"><strong>Kepada</strong><br>{{ $faktur->pembeli }}</td>
                <td class="right-align"><strong>Tanggal Penjualan</strong><br>{{ \Carbon\Carbon::parse($faktur->tgl_jual)->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td><strong>Petugas</strong><br>{{ $faktur->petugas }}</td>
                <td class="center-align"><strong>Keterangan</strong><br>{{ $faktur->keterangan }}</td>
                <td></td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>ID</th>
                <th>Merk Tipe</th>
                <th>Harga</th>
                <th>Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaksiJuals as $index => $transaksi)
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

    <div class="total">Total Harga Keseluruhan: Rp. {{ number_format($totalHarga, 0, ',', '.') }}</div>

    <div class="footer">Terima Kasih telah Berbelanja dengan Kami!</div>
</body>
</html>
