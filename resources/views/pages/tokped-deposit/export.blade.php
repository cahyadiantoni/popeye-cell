<table>
    <thead>
        <tr></tr>
        <tr>
            <th>Nomor Faktur</th>
            <th>Tanggal</th>
            <th>Total Unit Faktur</th>
            <th>Total Nominal Faktur</th>
            <th>Total Unit Invoice</th>
            <th>Uang Masuk</th>
            <th>Selisih</th>
            <th>Keterangan</th>
            <th>Bonusan</th>
            <th>Return</th>
        </tr>
    </thead>
    <tbody>
        @php
            $total_unit_faktur = 0;
            $total_nominal_faktur = 0;
            $total_unit_invoice = 0;
            $total_uang_masuk = 0;
            $total_selisih = 0;
        @endphp
        @foreach ($data as $item)
            @php
                $total_unit_faktur += $item['total_unit_faktur'];
                $total_nominal_faktur += $item['total_nominal_faktur'];
                $total_unit_invoice += $item['total_unit_invoice'];
                $total_uang_masuk += $item['total_uang_masuk'];
                $total_selisih += $item['selisih'];
            @endphp
            <tr>
                <td>
                    {{ strip_tags($item['title']) }}
                </td>
                <td>{{ $item['tgl'] }}</td>
                <td>{{ $item['total_unit_faktur'] }}</td>
                <td>{{ $item['total_nominal_faktur'] }}</td>
                <td>{{ $item['total_unit_invoice'] }}</td>
                <td>{{ $item['total_uang_masuk'] }}</td>
                <td>{{ $item['selisih'] }}</td>
                <td>{{ $item['keterangan'] }}</td>
                <td>{{ $item['bonusan'] }}</td>
                <td>{{ $item['return_count'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="2">TOTAL</td>
            <td>{{ $total_unit_faktur }}</td>
            <td>{{ $total_nominal_faktur }}</td>
            <td>{{ $total_unit_invoice }}</td>
            <td>{{ $total_uang_masuk }}</td>
            <td>{{ $total_selisih }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
