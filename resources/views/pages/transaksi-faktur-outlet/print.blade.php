<!DOCTYPE html>
<html>
<head>
    <title>Struk Penjualan</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }
        body {
            width: 58mm;
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            margin: 0;
            padding: 5px;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        .row { display: flex; justify-content: space-between; }
        .total { font-weight: bold; text-align: right; margin-top: 5px; }
        .footer { text-align: center; font-style: italic; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="center bold">PT INDO GADAI PRIMA</div>
    <div class="center">Jl. KH Hasyim Ashari 112BB</div>
    <div class="center">Jakarta Pusat</div>
    <div class="center">Telp: 0856-9312-4547</div>
    <div class="line"></div>
    <div class="center bold">STRUK PENJUALAN</div>
    <div class="line"></div>

    <div>No: {{ $faktur->nomor_faktur }}</div>
    <div>Pembeli: {{ $faktur->pembeli }}</div>
    <div>Tgl: {{ \Carbon\Carbon::parse($faktur->tgl_jual)->format('d/m/Y') }}</div>
    <div>Petugas: {{ $faktur->petugas }}</div>
    @if($faktur->keterangan)
    <div>Keterangan: {{ $faktur->keterangan }}</div>
    @endif
    <div class="line"></div>

    @foreach($transaksiJuals as $index => $transaksi)
        <div class="bold">{{ $transaksi->barang->tipe ?? '-' }}</div>
        <div class="row">
            <div>{{ $transaksi->lok_spk }}</div>
            <div>Rp {{ number_format($transaksi->harga, 0, ',', '.') }}</div>
        </div>
    @endforeach

    <div class="line"></div>
    <div class="total">Total: Rp {{ number_format($totalHarga, 0, ',', '.') }}</div>
    <div class="line"></div>

    <div class="footer">Terima kasih telah berbelanja!</div>
</body>
</html>
