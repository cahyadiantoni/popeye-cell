<!DOCTYPE html>
<html>
<head>
    <title>Laporan Gabungan: Kesimpulan, Faktur, Bukti</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
        .container { padding: 10mm; box-sizing: border-box; } /* Tambahkan box-sizing di sini untuk konsistensi */

        /* --- Common Styles --- */
        .title { text-align: center; font-weight: bold; font-size: 16px; }
        .subtitle { text-align: center; font-size: 14px; margin-bottom: 10px}
        .line { border-bottom: 1px solid black; margin-bottom: 10px; margin-top: 10px; }
        .info-table { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: top; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table th, .table td { border: 1px solid black; padding: 5px; text-align: center; }
        .info-table .right-align { text-align: right; }
        .info-table .center-align { text-align: center; }
        .total { font-weight: bold; margin-top: 5px; }
        .footer { font-style: italic; text-align: center; margin-top: 20px; }
        .badge { display: inline-block; padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; color: #fff; }
        .bg-warning { background-color: #ffc107; color: #000; }
        .bg-success { background-color: #28a745; }


        /* --- STYLES FOR BUKTI SECTION (TABLE LAYOUT) --- */

        /* Wrapper utama untuk tabel, jika diperlukan */
        .bukti-container {
            width: 100%;
        }
        
        /* Tabel utama yang akan menjadi grid */
        .bukti-table {
            width: 100%;
            border-collapse: collapse; /* Menghilangkan spasi antar sel */
            page-break-inside: avoid;  /* Mencegah tabel terpotong jika memungkinkan */
        }
        
        /* Sel tabel (TD) yang akan menjadi kolom kita */
        .bukti-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 5px 15px 5px; /* Padding: atas kanan bawah kiri */
            page-break-inside: avoid; /* SANGAT PENTING: Mencegah isi sel terpotong antar halaman */
        }
        
        /* Ini adalah blok konten di dalam setiap sel */
        .bukti-item {
            border: 1px solid #ccc;
            padding: 8px;
            height: 400px; /* agar 2 baris muat per halaman A4 (90vh total dengan margin) */
            box-sizing: border-box;
            width: 90%;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        
        .bukti-item p {
            margin: 2px 0;
            font-size: 11px;
        }
        
        .bukti-item .bukti-image-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .bukti-item .bukti-image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

    </style>
</head>
<body>

    {{-- ==================== SECTION 1: KESIMPULAN ==================== --}}
    <div class="container">
        <div class="title">PT INDO GADAI PRIMA</div>
        <div class="subtitle">Jl. KH Hasyim Ashari 112BB, Jakarta Pusat | Telp : 0856-9312-4547</div>
        <div class="title">Kesimpulan Penjualan</div>
        <div class="line"></div>
        {{-- ... Konten Kesimpulan lainnya tetap sama ... --}}
        <div class="info">
            <table class="info-table">
                <tr>
                    <td><strong>Nomor Kesimpulan</strong><br>{{ $kesimpulan->nomor_kesimpulan }}</td>
                    <td class="center-align"><strong>Tanggal Penjualan</strong><br>{{ \Carbon\Carbon::parse($kesimpulan->tgl_jual)->translatedFormat('d F Y') }}</td>
                    <td class="right-align"><strong>Potongan Kondisi</strong><br>Rp. {{ number_format($kesimpulan->potongan_kondisi, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td><strong>Diskon</strong><br>{{ $kesimpulan->diskon }}%</td>
                    <td class="center-align"><strong>Potongan (Diskon)</strong><br>Rp. {{ number_format(($kesimpulan->total - $kesimpulan->potongan_kondisi) * ($kesimpulan->diskon/100), 0, ',', '.') }}</td>
                    <td class="right-align"><strong>Total Potongan</strong><br>Rp. {{ number_format(($kesimpulan->total - $kesimpulan->potongan_kondisi) * ($kesimpulan->diskon/100) + $kesimpulan->potongan_kondisi, 0, ',', '.') }}</td>
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
                        <td colspan="2" class="right-align"><strong>Keterangan</strong><br>{{ $kesimpulan->keterangan }}</td>
                    @else
                         <td colspan="2" class="right-align"></td>
                    @endif
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
                    <th>Jumlah Barang</th>
                    <th>Total Harga</th>
                    <th>Petugas</th>
                    <th>Grade</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kesimpulan->fakturKesimpulans as $index => $fakturKesimpulan)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $fakturKesimpulan->faktur->nomor_faktur ?? '-' }}</td>
                    <td>{{ $fakturKesimpulan->faktur->pembeli ?? '-' }}</td>
                    <td>{{ $fakturKesimpulan->faktur->tgl_jual ?? '-' }}</td>
                    <td>{{ $fakturKesimpulan->faktur->barangs->count() ?? '?' }}</td>
                    <td>{{ 'Rp. ' . number_format($fakturKesimpulan->faktur->total ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $fakturKesimpulan->faktur->petugas ?? '-' }}</td>
                    <td>{{ $fakturKesimpulan->faktur->grade ?? '-' }}</td>
                    <td>{{ $fakturKesimpulan->faktur->keterangan ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">Total Barang: {{ $kesimpulan->total_barang ?? '0' }}</div>
        <div class="total">Grand Total Harga: Rp. {{ number_format($kesimpulan->grand_total ?? 0, 0, ',', '.') }}</div>
    </div>

    @if (!empty($faktursDataForView) || !$kesimpulan->bukti->isEmpty())
        <div class="page-break"></div>
    @endif


    {{-- ==================== SECTION 2: FAKTUR(S) ==================== --}}
    @foreach($faktursDataForView as $index => $fakturData)
        <div class="container">
            <div class="title">PT INDO GADAI PRIMA</div>
            <div class="subtitle">Jl. KH Hasyim Ashari 112BB, Jakarta Pusat | Telp : 0856-9312-4547</div>
            <div class="title">Faktur Penjualan</div>
            <div class="line"></div>
             {{-- ... Konten Faktur lainnya tetap sama ... --}}
            <div class="info">
                <table class="info-table">
                    <tr>
                        <td><strong>Nomor Faktur</strong><br>{{ $fakturData['faktur']->nomor_faktur ?? '-' }}</td>
                        <td class="center-align"><strong>Kepada</strong><br>{{ $fakturData['faktur']->pembeli ?? '-' }}</td>
                        <td class="right-align"><strong>Tanggal Penjualan</strong><br>{{ \Carbon\Carbon::parse($fakturData['faktur']->tgl_jual)->translatedFormat('d F Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Petugas</strong><br>{{ $fakturData['faktur']->petugas ?? '-' }}</td>
                        <td class="center-align"><strong>Grade</strong><br>{{ $fakturData['faktur']->grade ?? '-' }}</td>
                        <td class="right-align"><strong>Keterangan</strong><br>{{ $fakturData['faktur']->keterangan ?? '-' }}</td>
                    </tr>
                </table>
            </div>

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
                    @foreach($fakturData['transaksiJuals'] as $transaksiIndex => $transaksi)
                    <tr>
                        <td>{{ $transaksiIndex + 1 }}</td>
                        <td>{{ $transaksi->lok_spk ?? '-' }}</td>
                        <td>{{ $transaksi->barang->tipe ?? '-' }}</td>
                        <td>Rp. {{ number_format($transaksi->harga ?? 0, 0, ',', '.') }}</td>
                        <td>Rp. {{ number_format($transaksi->subtotal ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="total">Total Harga Keseluruhan: Rp. {{ number_format($fakturData['totalHarga'] ?? 0, 0, ',', '.') }}</div>
        </div>

        @if (!$loop->last || !$kesimpulan->bukti->isEmpty())
             <div class="page-break"></div>
        @endif
    @endforeach


    {{-- ==================== SECTION 3: BUKTI(S) (REVISED WITH TABLE LAYOUT) ==================== --}}
    @if(!$kesimpulan->bukti->isEmpty())
        <div class="container">
            <div class="title">Bukti Transfer</div>
        </div>
        <div class="bukti-container">
            <table class="bukti-table">
                {{-- Loop melalui bukti, ambil 2 item per baris (chunk) --}}
                @foreach($kesimpulan->bukti->chunk(2) as $rowChunk)
                    <tr>
                        {{-- Loop untuk setiap bukti dalam satu baris --}}
                        @foreach($rowChunk as $bukti)
                            <td>
                                <div class="bukti-item">
                                    <p><strong>Keterangan:</strong> {{ $bukti->keterangan ?? '-' }}</p>
                                    <p><strong>Nominal:</strong> Rp. {{ number_format($bukti->nominal ?? 0, 0, ',', '.') }}</p>
                                    <div class="bukti-image-container">
                                        <img src="{{ storage_path('app/public/' . $bukti->foto) }}" alt="Bukti Transfer">
                                    </div>
                                </div>
                            </td>
                        @endforeach
    
                        {{-- Jika dalam satu baris hanya ada 1 bukti, tambahkan sel kosong agar tabel tidak rusak --}}
                        @if (count($rowChunk) < 2)
                            <td></td>
                        @endif
                    </tr>
    
                    {{-- KONDISI PAGE BREAK YANG DIPERBAIKI --}}
                    {{-- Lakukan page break setelah setiap 2 baris (kelipatan 2), dan pastikan bukan baris terakhir --}}
                    @if ($loop->iteration % 2 == 0 && !$loop->last)
                        {{-- Tutup tabel saat ini, buat halaman baru, lalu buka tabel baru --}}
                        </table>
                        <div class="page-break"></div>
                        <table class="bukti-table">
                    @endif
    
                @endforeach
            </table>
        </div>
    @endif


</body>
</html>