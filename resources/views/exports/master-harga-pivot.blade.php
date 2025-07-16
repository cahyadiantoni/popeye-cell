<table>
    <thead>
        <tr>
            <th>Tipe</th>
            <th>Grade</th>
            @foreach($tanggalHeaders as $tanggal)
                <th>{{ $tanggal->format('d-M-y') }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($dataPivot as $row)
            <tr>
                <td>{{ $row->tipe }}</td>
                <td>{{ $row->grade }}</td>
                @foreach($tanggalHeaders as $tanggal)
                    <td>
                        @php
                            $tanggalKey = $tanggal->format('Y-m-d');
                            $harga = $row->harga_per_tanggal[$tanggalKey] ?? null;
                        @endphp
                        @if(isset($harga))
                            {{ $harga }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>