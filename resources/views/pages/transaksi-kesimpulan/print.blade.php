<!DOCTYPE html>
<html>
<head>
    <title>Kesimpulan Penjualan</title>
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
    <div class="title">Kesimpulan Penjualan</div>
    <div class="line"></div>

    <div class="info">
        <table class="info-table">
            <tr>
                <td><strong>Nomor Kesimpulan</strong><br>{{ $kesimpulan->nomor_kesimpulan }}</td>
                <td class="center-align"><strong>Tanggal Penjualan</strong><br>{{ \Carbon\Carbon::parse($kesimpulan->tgl_jual)->translatedFormat('d F Y') }}</td>
                <td class="right-align"><strong>Potongan Kondisi</strong><br>Rp. {{ number_format($kesimpulan->potongan_kondisi, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Diskon</strong><br>{{ $kesimpulan->diskon }}%</td>
                <td class="center-align"><strong>Potongan (Diskon)</strong><br>Rp. {{ number_format($kesimpulan->total_diskon = ($kesimpulan->total - $kesimpulan->potongan_kondisi) * ($kesimpulan->diskon/100), 0, ',', '.') }}</td>
                <td class="right-align"><strong>Total Potongan</strong><br>Rp. {{ number_format($kesimpulan->total_diskon + $kesimpulan->potongan_kondisi, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Total Harga</strong><br>Rp. {{ number_format($kesimpulan->total, 0, ',', '.') }}</td>
                <td class="center-align"><strong>Sudah Dibayar</strong><br>Rp. {{ number_format($kesimpulan->total_nominal, 0, ',', '.') }}</td>
                <td class="right-align"><strong>Sisa Hutang</strong><br>Rp. {{ number_format($kesimpulan->grand_total - $kesimpulan->total_nominal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Status Pembayaran</strong><br>
                @if ($kesimpulan->is_lunas == 0)
                    <span class="badge bg-warning">Hutang</span>
                @else
                    <span class="badge bg-success">Lunas</span>
                @endif
                </td>
                @if ($kesimpulan->keterangan)
                    <td class="center-align"><strong>Keterangan</strong><br>{{ $kesimpulan->keterangan }}</td>
                @else
                    <td class="center-align"></td>
                @endif
                <td class="right-align">
                </td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>No Faktur</th>
                <th>Pembeli</th>
                <th>Tgl Faktur</th>
                <th>jumlah Barang</th>
                <th>Total Harga</th>
                <th>Petugas</th>
                <th>Grade</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fakturs as $index => $faktur)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $faktur->faktur->nomor_faktur }}</td>
                <td>{{ $faktur->faktur->pembeli }}</td>
                <td>{{ $faktur->faktur->tgl_jual }}</td>
                <td>{{ $faktur->faktur->barangs->count() }}</td>
                <td>{{ 'Rp. ' . number_format($faktur->faktur->total, 0, ',', '.') }}</td>
                <td>{{ $faktur->faktur->petugas }}</td>
                <td>{{ $faktur->faktur->grade }}</td>
                <td>{{ $faktur->faktur->keterangan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">Total Barang: {{ $kesimpulan->total_barang }}</div>
    <div class="total">Grand Total Harga: Rp. {{ number_format($kesimpulan->grand_total, 0, ',', '.') }}</div>

    <div class="footer">Terima Kasih telah Berbelanja dengan Kami!</div>
</body>
</html>
