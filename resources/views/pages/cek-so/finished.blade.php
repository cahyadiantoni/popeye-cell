@extends('layouts.main')

@section('title', 'Detail Stok Opname')
@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail Stok Opname</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <!-- Informasi Cek SO Barang -->
            <div class="card">
                <div class="card-block">
                    <table class="table table-bordered text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Kode SO</th>
                                <th>Gudang / Petugas</th>
                                <th>Jumlah Scan / Stok</th>
                                <th>Waktu Mulai / Waktu Berakhir</th>
                                <th>Durasi</th>
                                <th>Hasil SO</th>
                                <th>Status SO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $cekso->kode }}</td>
                                <td>{{ $cekso->nama_gudang }} / {{ $cekso->petugas }}</td>
                                <td>{{ $cekso->jumlah_scan }} / {{ $cekso->jumlah_stok }}</td>
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
                                <td>
                                    @switch($cekso->is_finished)
                                        @case(0) <span class="badge bg-warning text-dark">Belum Selesai</span> @break
                                        @case(1) <span class="badge bg-success">Selesai</span> @break
                                        @default <span class="badge bg-secondary">Tidak Diketahui</span>
                                    @endswitch
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabel Barang -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Daftar Barang</h5>
                    <select id="filterScan" class="form-select w-auto">
                        <option value="">Semua</option>
                        <option value="1">Sudah Discan</option>
                        <option value="0">Belum Discan</option>
                        <option value="2">Tidak Ada di Database</option>
                    </select>
                </div>
                <div class="card-block table-responsive">
                    <table id="barangTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lok SPK</th>
                                <th>Jenis</th>
                                <th>Tipe</th>
                                <th>Kelengkapan</th>
                                <th>Status</th>
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
                    let filterValue = $('#filterScan').val();
                    d.scan_status = filterValue !== "" ? filterValue : null; // Kirim `null` jika semua
                }
            },
            columns: [
                { 
                    data: null, 
                    name: 'nomor', 
                    render: function (data, type, row, meta) {
                        return meta.row + 1; // Menampilkan nomor urut
                    },
                    orderable: false, 
                    searchable: false 
                },
                { data: 'lok_spk', name: 'lok_spk' },
                { data: 'jenis', name: 'jenis', defaultContent: '-' },
                { data: 'tipe', name: 'tipe', defaultContent: '-' },
                { data: 'kelengkapan', name: 'kelengkapan', defaultContent: '-' },
                {
                    data: 'status', 
                    name: 'status', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        return data;
                    }
                }
            ]
        });

        // Event ketika dropdown filter diubah
        $('#filterScan').change(function () {
            table.ajax.reload(); // Refresh tabel dengan filter baru
        });
    });
</script>

@endsection
