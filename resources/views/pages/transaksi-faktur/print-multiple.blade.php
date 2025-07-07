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
            
            <table class="info-table" style="margin-top: 10px;">
                <tr>
                    <td style="width: 50%;"></td>
                    <td style="width: 50%; text-align: right;">
                        <table style="width: 100%; border-collapse: collapse;">
                            @if($faktur->potongan_kondisi > 0 || $faktur->diskon > 0)
                                <tr>
                                    <td style="text-align: left; padding: 2px;">Subtotal</td>
                                    <td style="padding: 2px;">Rp. {{ number_format($faktur->totalHarga, 0, ',', '.') }}</td>
                                </tr>
                            @endif

                            @if($faktur->potongan_kondisi > 0)
                                <tr>
                                    <td style="text-align: left; padding: 2px;">Potongan Kondisi</td>
                                    <td style="padding: 2px;">- Rp. {{ number_format($faktur->potongan_kondisi, 0, ',', '.') }}</td>
                                </tr>
                            @endif

                            @if($faktur->diskon > 0)
                                @php
                                    $hargaSetelahPotongan = $faktur->totalHarga - $faktur->potongan_kondisi;
                                    $diskonAmount = ($hargaSetelahPotongan * $faktur->diskon) / 100;
                                @endphp
                                <tr>
                                    <td style="text-align: left; padding: 2px;">Diskon ({{ $faktur->diskon }}%)</td>
                                    <td style="padding: 2px;">- Rp. {{ number_format($diskonAmount, 0, ',', '.') }}</td>
                                </tr>
                            @endif

                            <tr class="total">
                                <td style="text-align: left; padding: 5px; border-top: 1px solid black; border-bottom: 1px solid black; font-weight:bold;">Total Akhir</td>
                                <td style="padding: 5px; border-top: 1px solid black; border-bottom: 1px solid black; font-weight:bold;">Rp. {{ number_format($faktur->total, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
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
