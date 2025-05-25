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
        @foreach($transaksiJuals->groupBy('invoice') as $invoice => $items)
            @php
                $rowspan = count($items);
                $uangMasuk = $uangMasukPerInvoice[$invoice] ?? null;
            @endphp
            @foreach($items as $i => $transaksi)
                <tr>
                    <td>{{ $no++ }}</td>
                    @if($i == 0)
                        <td rowspan="{{ $rowspan }}">{{ $invoice }}</td>
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
                            {{ $uangMasuk ? number_format($uangMasuk->total_uang_masuk, 0, ',', '.') : 'Belum ada' }}
                        </td>
                        <td rowspan="{{ $rowspan }}">
                            {{ $uangMasuk ? \Carbon\Carbon::parse($uangMasuk->tanggal_masuk)->translatedFormat('j F Y') : '-' }}
                        </td>
                    @endif
                    <td>
                        @if($transaksi->tgl_return)
                            {{ \Carbon\Carbon::parse($transaksi->tgl_return)->translatedFormat('j F Y') }}
                        @else
                            <span style="color: gray;">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
