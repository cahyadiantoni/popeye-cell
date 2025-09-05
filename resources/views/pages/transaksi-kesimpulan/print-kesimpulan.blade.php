<!DOCTYPE html>
<html>
<head>
    <title>Kesimpulan Faktur Bawah</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .title { text-align: center; font-weight: bold; font-size: 16px; }
        .line { border-bottom: 1px solid #000; margin: 10px 0; }
        .info-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .info-table td { padding: 4px 6px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }
        table.data th {
            background: #e0e0e0;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="title">Kesimpulan Bawah</div>
    <div class="line"></div>

    <table class="info-table">
        <tr>
            <td><strong>Rentang Tanggal:</strong> {{ $rentangTanggal }}</td>
            <td class="text-right">
                <strong>Total Barang:</strong> {{ number_format($totalBarang, 0, ',', '.') }}
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <strong>Total Transfer:</strong> Rp {{ number_format($totalTransfer, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:30px">No</th>
                <th style="width:80px">Tgl</th>
                <th>Invoice</th>
                <th>Petugas</th>
                <th style="width:80px">Jml Barang</th>
                <th style="width:100px">Total Harga</th>
                <th style="width:100px">Pot. Kondisi</th>
                <th style="width:80px">Diskon</th>
                <th style="width:120px">Transfer</th>
                <th>Note Manual</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $r)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td class="text-center nowrap">
                        {{ \Carbon\Carbon::parse($r['tgl'])->format('d-m-Y') }}
                    </td>
                    <td>{{ $r['invoice'] }}</td>
                    <td>{{ $r['petugas'] }}</td>
                    <td class="text-center">{{ number_format($r['jumlah_barang'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($r['total'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($r['potongan'], 0, ',', '.') }}</td>
                    <td class="text-center">
                        {{ rtrim(rtrim(number_format($r['diskon'], 2, ',', '.'), '0'), ',') }}%
                    </td>
                    <td class="text-right">Rp {{ number_format($r['transfer'], 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
