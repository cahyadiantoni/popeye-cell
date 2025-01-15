<!DOCTYPE html>
<html>
<head>
    <title>Faktur Penjualan Online</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; margin-bottom: 10px; }
        .line { border-bottom: 1px solid black; margin-bottom: 15px; }
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
    <div class="title">Faktur Penjualan Online</div>
    <div class="line"></div>

    <div class="info">
        <table class="info-table">
            <tr>
                <td><strong>Judul</strong><br>{{ $faktur->title }}</td>
                <td class="center-align"><strong>Toko</strong><br>{{ $faktur->toko }}</td>
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
                <th>Invoice</th>
                <th>SPK</th>
                <th>Merk</th>
                <th>PJ</th>
                <th>Harga</th>
                <th>Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaksiJuals as $index => $transaksi)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $transaksi->invoice }}</td>
                <td>{{ $transaksi->lok_spk }}</td>
                <td>{{ $transaksi->barang->tipe ?? '-' }}</td>
                <td>Rp. {{ number_format($transaksi->pj, 0, ',', '.') }}</td>
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
