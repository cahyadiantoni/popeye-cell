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

                        <div class="card-block">
                            <button type="button" class="btn btn-primary btn-round mb-3" data-bs-toggle="modal" data-bs-target="#uploadModalPulsaReport">
                                Impor Laporan CSV
                            </button>
                            
                            <hr> {{-- Pemisah antara tombol dan tabel --}}
                            
                            <h5>Data Laporan Pulsa</h5>
                            <div class="dt-responsive table-responsive mt-3">
                                <table id="tablePulsaReport" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
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
    $('#tablePulsaReport').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('pulsa-report.index') }}",
        columns: [
            { data: 'Tanggal', name: 'Tanggal' },
            { data: 'Keterangan', name: 'Keterangan' },
            { data: 'Cabang', name: 'Cabang' },
            { data: 'Jumlah', name: 'Jumlah', className: 'text-end' },
            { data: 'Jenis', name: 'Jenis' },
            { data: 'Saldo', name: 'Saldo', className: 'text-end' }
        ],
        order: [[0, 'desc']] // Urutkan berdasarkan kolom Tanggal descending secara default
    });
});
</script>
@endsection