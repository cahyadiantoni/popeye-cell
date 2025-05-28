<table>
    <tr>
        <th colspan="2" style="font-size:18px; font-weight:bold;">Informasi Faktur</th>
    </tr>
    <tr><td>Judul</td><td>{{ $faktur->title }}</td></tr>
    <tr><td>Toko</td><td>{{ $faktur->toko }}</td></tr>
    <tr><td>Tanggal Jual</td><td>{{ \Carbon\Carbon::parse($faktur->tgl_jual)->translatedFormat('d F Y') }}</td></tr>
    <tr><td>Petugas</td><td>{{ $faktur->petugas }}</td></tr>
    <tr><td>Total</td><td>{{ $faktur->total }}</td></tr>
    <tr><td>Keterangan</td><td>{{ $faktur->keterangan }}</td></tr>
</table>

<br>

<table>
    <thead style="background-color:#DDEBF7; font-weight:bold;">
        <tr>
            <th>No</th>
            <th>Invoice</th>
            <th>Lok SPK</th>
            <th>Tipe Barang</th>
            <th>Harga</th>
            <th>PJ</th>
            <th>Selisih</th>
            <th>Uang Masuk</th>
            <th>Tanggal Masuk</th>
            <th>Tanggal Return</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach($transaksiJuals->groupBy('invoice') as $invoice => $items) {{-- $invoice di sini adalah asli --}}
            @php
                $rowspan = count($items);
                // Normalisasi $invoice untuk lookup di $uangMasukPerInvoice
                $cleanedInvoiceKey = Illuminate\Support\Str::substr(preg_replace('/\D/', '', trim($invoice)), -7);
                $uangMasuk = $uangMasukPerInvoice[$cleanedInvoiceKey] ?? null;
            @endphp
            @foreach($items as $i => $transaksi)
                <tr>
                    <td>{{ $no++ }}</td>
                    @if($i == 0)
                        <td rowspan="{{ $rowspan }}">{{ $invoice }}</td> {{-- Tampilkan invoice asli --}}
                    @endif
                    <td>{{ $transaksi->lok_spk }}</td>
                    <td>{{ $transaksi->barang->tipe ?? '-' }}</td>
                    <td>{{ $transaksi->harga }}</td>
                    <td>{{ $transaksi->pj }}</td>
                    <td>
                        @php $selisih = $transaksi->harga - $transaksi->pj; @endphp
                        {{ $transaksi->pj == 0 ? '-' : $selisih }}
                    </td>
                    @if($i == 0)
                        <td rowspan="{{ $rowspan }}">
                            {{-- Gunakan variabel $uangMasuk yang diambil dengan kunci bersih --}}
                            {{ $uangMasuk ? number_format(optional($uangMasuk)->total_uang_masuk, 0, ',', '.') : 'Belum ada' }}
                        </td>
                        <td rowspan="{{ $rowspan }}">
                            {{ $uangMasuk && optional($uangMasuk)->tanggal_masuk ? \Carbon\Carbon::parse($uangMasuk->tanggal_masuk)->translatedFormat('j F Y') : '-' }}
                        </td>
                    @endif
                    <td>
                        @if($transaksi->tgl_return)
                            {{ \Carbon\Carbon::parse($transaksi->tgl_return)->translatedFormat('j F Y') }}
                        @else
                            - {{-- Tidak perlu span jika ini untuk Excel polos --}}
                        @endif
                    </td>
                </tr>
            @endforeach
        @endforeach
        {{-- Baris Total jika ada --}}
        @if($transaksiJuals->isNotEmpty())
        @php
            $totalHarga = $transaksiJuals->sum('harga');
            $totalPj = $transaksiJuals->sum('pj');
            $totalSelisih = $transaksiJuals->sum(function($item) {
                return $item->pj > 0 ? $item->harga - $item->pj : 0;
            });
            // Hitung total uang masuk dari $uangMasukPerInvoice yang sudah dinormalisasi dan unik per invoice bersih
            // Ini memerlukan sedikit perubahan cara $uangMasukPerInvoice di-pass atau di-sum jika $invoice (kunci groupBy) adalah asli
            // Untuk Excel, biasanya total uang masuk dihitung dari kolom yang sudah ditampilkan
            // Atau, jika Anda ingin sum dari $uangMasukPerInvoice:
            // $totalUangMasuk = $uangMasukPerInvoice->sum('total_uang_masuk'); // Ini akan menjumlahkan semua entri di $uangMasukPerInvoice.
                                                                            // Jika ada beberapa $invoice asli yang bersihnya sama,
                                                                            // pastikan $uangMasukPerInvoice sudah unik berdasarkan kunci bersihnya.
                                                                            // Cara di controller sudah benar (mapWithKeys).

            // Cara yang lebih aman untuk total di Excel adalah dengan SUM formula di AfterSheet,
            // atau jika ingin dari data, pastikan data $uangMasukPerInvoice yang di-sum adalah unik per invoice group.
            // Untuk sementara, kita akan membiarkan perhitungan total uang masuk di AfterSheet via formula.
        @endphp
        <tr>
            <td colspan="4"><strong>TOTAL</strong></td>
            <td><strong>{{ $totalHarga }}</strong></td>
            <td><strong>{{ $totalPj }}</strong></td>
            <td><strong>{{ $totalSelisih }}</strong></td>
            <td></td> {{-- Kolom Total Uang Masuk (akan diisi formula di AfterSheet atau bisa juga dihitung di sini jika datanya siap) --}}
            <td></td>
            <td></td>
        </tr>
        @endif
    </tbody>
</table>
