@extends('layouts.main')

@section('title', 'Laporan Pulsa')
@section('content')

{{-- Pastikan jQuery sudah dimuat, bisa dari layout utama atau di sini jika belum --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Laporan Pulsa</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class="breadcrumb-title">
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                            </li>
                            <li class="breadcrumb-item" style="float: left;"><a href="#!">Laporan Pulsa</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        {{-- Menampilkan Pesan Sukses/Error --}}
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                                <strong>Error!</strong>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        <div class="card-header">
                            <form id="filterPulsaReportForm" class="row g-3">
                                <div class="col-md-3">
                                    <label for="filter_kode_toko" class="form-label">Pilih Toko</label>
                                    <select id="filter_kode_toko" name="filter_kode_toko" class="form-control form-select">
                                        <option value="">-- Semua Toko --</option>
                                        @foreach($storeOptions as $option)
                                            <option value="{{ $option->kode }}">{{ $option->nama_toko }} ({{ $option->kode }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="tanggal_mulai_filter" class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="tanggal_mulai_filter" name="tanggal_mulai">
                                </div>
                                <div class="col-md-3">
                                    <label for="tanggal_selesai_filter" class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" id="tanggal_selesai_filter" name="tanggal_selesai">
                                </div>
                                <div class="col-md-3">
                                    <label for="filter_cek" class="form-label">Status Kode Toko</label>
                                    <select id="filter_cek" name="filter_cek" class="form-control form-select">
                                        <option value="">-- Semua Status --</option>
                                        <option value="ada_kode">Ada Kode Toko</option>
                                        <option value="tidak_ada_kode">Tidak Ada Kode Toko</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mt-3 text-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <button type="button" class="btn btn-secondary" id="resetFilterPulsaReport">Reset</button>
                                    <button href="#" id="exportExcelPulsaReport" class="btn btn-success"> Export Excel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-block">
                            <button type="button" class="btn btn-primary btn-round mb-3" data-bs-toggle="modal" data-bs-target="#uploadModalPulsaReport">
                                Impor Laporan CSV
                            </button>
                            <div class="dt-responsive table-responsive mt-3">
                                <table id="tablePulsaReport" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kode</th>
                                            <th>Nama Toko</th>
                                            <th>Transaksi</th>
                                            <th>Keterangan</th>
                                            <th>Cabang</th>
                                            <th>Jumlah</th>
                                            <th>Jenis</th>
                                            <th>Saldo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- DataTables akan mengisi ini --}}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kode</th>
                                            <th>Nama Toko</th>
                                            <th>Transaksi</th>
                                            <th>Keterangan</th>
                                            <th>Cabang</th>
                                            <th>Jumlah</th>
                                            <th>Jenis</th>
                                            <th>Saldo</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
</div>
<div class="modal fade" id="uploadModalPulsaReport" tabindex="-1" aria-labelledby="uploadModalPulsaReportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('pulsa-report.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalPulsaReportLabel">Impor Laporan Pulsa dari CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filecsv" class="form-label">Pilih File CSV (.csv, .txt)</label>
                        <input type="file" name="filecsv" id="filecsvModal" class="form-control" accept=".csv,.txt" required>
                        <small class="form-text text-muted">
                            Pastikan file CSV Anda memiliki header di baris ke-5 dan data transaksi dimulai dari baris ke-6.
                            Kolom yang diharapkan: Tanggal, Keterangan, Cabang, Jumlah, Jenis, Saldo.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Impor Data</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    var tablePulsaReport = $('#tablePulsaReport').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('pulsa-report.index') }}",
            data: function (d) {
                // Mengambil nilai dari form filter dan menambahkannya ke request DataTables
                d.filter_kode_toko = $('#filter_kode_toko').val();
                d.tanggal_mulai = $('#tanggal_mulai_filter').val();
                d.tanggal_selesai = $('#tanggal_selesai_filter').val();
                d.filter_cek = $('#filter_cek').val();
            }
        },
        columns: [
            { data: 'Tanggal', name: 'Tanggal' },
            { data: 'kode_master', name: 'kode_master'}, 
            { data: 'nama_toko_master', name: 'nama_toko_master'}, 
            { data: 'tipe_transaksi', name: 'tipe_transaksi'},
            { data: 'Keterangan', name: 'Keterangan', width: '30%' },
            { data: 'Cabang', name: 'Cabang' },
            { data: 'Jumlah', name: 'Jumlah', className: 'text-end' },
            { data: 'Jenis', name: 'Jenis' },
            { data: 'Saldo', name: 'Saldo', className: 'text-end' }
        ],
        order: [[1, 'asc'], [5, 'asc']]
    });

    // Event handler untuk form filter
    $('#filterPulsaReportForm').on('submit', function(e) {
        e.preventDefault();
        tablePulsaReport.ajax.reload(); // Muat ulang DataTables dengan parameter filter baru
    });

    // Event handler untuk tombol reset filter
    $('#resetFilterPulsaReport').on('click', function() {
        $('#filterPulsaReportForm')[0].reset(); // Reset nilai form
        tablePulsaReport.ajax.reload(); // Muat ulang DataTables
    });

    $('#exportExcelPulsaReport').on('click', function(e) {
        e.preventDefault();
        // Ambil parameter filter saat ini
        var kodeToko = $('#filter_kode_toko').val();
        var tanggalMulai = $('#tanggal_mulai_filter').val();
        var tanggalSelesai = $('#tanggal_selesai_filter').val();
        var filterCek = $('#filter_cek').val();

        // Buat URL untuk ekspor dengan parameter filter
        var exportUrl = "{{ route('pulsa-report.exportExcel') }}?";
        exportUrl += "filter_kode_toko=" + encodeURIComponent(kodeToko);
        exportUrl += "&tanggal_mulai=" + encodeURIComponent(tanggalMulai);
        exportUrl += "&tanggal_selesai=" + encodeURIComponent(tanggalSelesai);
        exportUrl += "&filter_cek=" + encodeURIComponent(filterCek);
        
        // Redirect ke URL ekspor
        window.location.href = exportUrl;
    });
});
</script>
@endsection