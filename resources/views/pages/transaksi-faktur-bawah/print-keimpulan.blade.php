<!DOCTYPE html>
<html>
<head>
    <title>Kesimpulan Faktur</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; }
        .line { border-bottom: 1px solid black; margin-bottom: 10px; margin-top: 10px; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 5px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .data-table th, .data-table td { border: 1px solid black; padding: 5px; text-align: left; }
        .data-table th { background-color: #e0e0e0; text-align: center; } /* Abu-abu untuk header */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="title">Kesimpulan Faktur</div>
    <div class="line"></div>

    <div class="info">
        <table class="info-table">
            <tr>
                <td><strong>Tgl Kesimpulan:</strong> {{ $rentangTanggal }}</td>
                <td><strong>Total Jumlah Barang:</strong> {{ number_format($totalJumlahBarang, 0, ',', '.') }}</td>
                <td class="text-right"><strong>Total Harga:</strong> Rp. {{ number_format($totalHargaKeseluruhan, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Jual</th>
                <th>Invoice</th>
                <th>Faktur Program</th>
                <th>Petugas</th>
                <th>Jml Barang</th>
                <th>Ttl Harga</th>
                <th>Sub Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $subtotal = 0; // Inisialisasi variabel subtotal
            @endphp
            @foreach($fakturs as $index => $faktur)
            @php
                $subtotal += $faktur->total; // Tambahkan total harga saat ini ke subtotal
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($faktur->tgl_jual)->format('d-m-Y') }}</td>
                <td>{{ $faktur->keterangan }}</td>
                <td>{{ $faktur->nomor_faktur }}</td>
                <td>{{ $faktur->petugas }}</td>
                <td class="text-center">{{ $faktur->total_barang }}</td>
                <td class="text-right">Rp. {{ number_format($faktur->total, 0, ',', '.') }}</td>
                <td class="text-right">Rp. {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>