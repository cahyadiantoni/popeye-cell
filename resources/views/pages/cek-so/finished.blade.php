@extends('layouts.main')

@section('title', 'Hasil Stok Opname')
@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Hasil Stok Opname</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="card">
                <div class="card-block table-responsive">
                    {{-- Tabel summary --}}
                    <table class="table table-bordered text-center mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Kode SO</th>
                                <th>Gudang / Petugas</th>
                                <th>Scan Sistem</th>
                                <th>Input Manual</th>
                                <th>Upload Excel</th>
                                <th>Total / Stok</th>
                                <th>Waktu Mulai / Selesai</th>
                                <th>Durasi</th>
                                <th>Hasil</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $cekso->kode }}</td>
                                <td>{{ $cekso->nama_gudang }} / {{ $cekso->petugas }}</td>
                                <td>{{ $cekso->jumlah_scan_sistem ?? 0 }}</td>
                                <td>{{ $cekso->jumlah_input_manual ?? 0 }}</td>
                                <td>{{ $cekso->jumlah_upload_excel ?? 0 }}</td>
                                <td>{{ ($cekso->jumlah_scan_sistem + $cekso->jumlah_input_manual + $cekso->jumlah_upload_excel) }} / {{ $cekso->jumlah_stok }}</td>
                                <td>{{ $cekso->waktu_mulai }} / {{ $cekso->waktu_selesai }}</td>
                                <td>{{ $cekso->durasi }}</td>
                                <td>
                                    @switch($cekso->hasil)
                                        @case(0) <span class="badge bg-danger">Belum Sesuai</span> @break
                                        @case(1) <span class="badge bg-success">Sesuai</span> @break
                                        @case(2) <span class="badge bg-warning text-dark">Lok_SPK Belum Sesuai</span> @break
                                        @default <span class="badge bg-secondary">Tidak Diketahui</span>
                                    @endswitch
                                </td>
                                <td><span class="badge bg-success">Selesai</span></td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Ringkasan BOX/BTG/OTHER ditampilkan di bawah tabel --}}
                    <div class="p-3 border-top bg-light text-center">
                        <span class="badge bg-dark fs-6 me-2 px-3 py-2">
                            BOX: <strong>{{ $jumlah_box }}</strong>
                        </span>
                        <span class="badge bg-secondary fs-6 me-2 px-3 py-2">
                            BTG: <strong>{{ $jumlah_btg }}</strong>
                        </span>
                        <span class="badge bg-info text-dark fs-6 px-3 py-2">
                            OTHER: <strong>{{ $jumlah_other }}</strong>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3"><h5>Detail Hasil Barang</h5></div>
                        <div class="col-md-3">
                            <select id="filterScan" class="form-select">
                                <option value="">-- Filter Status --</option>
                                <option value="1">Scan Sistem</option>
                                <option value="3">Input Manual</option>
                                <option value="4">Upload Excel</option>
                                <option value="0">Belum Discan</option>
                                <option value="2">Tidak Ada di DB</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterPetugas" class="form-select">
                                <option value="">-- Filter Petugas Scan --</option>
                                @foreach($petugasScans as $petugas)
                                    <option value="{{ $petugas }}">{{ $petugas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterLokasi" class="form-select">
                                <option value="">-- Filter Lokasi --</option>
                                @foreach($lokasis as $lokasi)
                                    <option value="{{ $lokasi }}">{{ $lokasi }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-block table-responsive">
                    <table id="barangTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lok SPK</th>
                                <th>Jenis</th>
                                <th>Tipe</th>
                                <th>Kelengkapan</th>
                                <th>Kelengkapan Update</th>
                                <th>Status</th>
                                <th>Petugas Scan</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    let table = $('#barangTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('get-cekso.finish', $cekso->id) }}",
            data: function (d) {
                d.scan_status  = $('#filterScan').val();
                d.petugas_scan = $('#filterPetugas').val();
                d.lokasi       = $('#filterLokasi').val();
            }
        },
        columns: [
            { data: null, name: 'nomor', orderable: false, searchable: false,
              render: (data, type, row, meta) => meta.row + 1
            },
            { data: 'lok_spk', name: 'lok_spk' },
            { data: 'jenis', name: 'jenis', defaultContent: '-' },
            { data: 'tipe', name: 'tipe', defaultContent: '-' },
            { data: 'kelengkapan', name: 'kelengkapan', defaultContent: '-' },

            { data: 'kelengkapan_update', name: 'kelengkapan_update', defaultContent: '-' },

            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'petugas_scan', name: 'petugas_scan', defaultContent: '-' },
            { data: 'lokasi', name: 'lokasi', defaultContent: '-' }
        ]
    });

    $('#filterScan, #filterPetugas, #filterLokasi').change(function () {
        table.ajax.reload();
    });
});
</script>

@endsection
