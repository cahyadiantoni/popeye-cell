@extends('layouts.main')

@section('title', 'Riwayat Perubahan Barang')
@section('content')

{{-- Pastikan jQuery sudah di-load, bisa dari layout utama atau di sini --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Riwayat Perubahan Data Barang</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class="breadcrumb-title">
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                            </li>
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="{{ route('data-barang.index') }}">Data Barang</a>
                            </li>
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="#!">Riwayat Perubahan</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daftar Semua Perubahan</h5>
                            <span>Berikut adalah catatan semua perubahan yang dilakukan pada data barang.</span>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="tableRiwayat" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Tanggal & Waktu</th>
                                            <th>LOK SPK</th>
                                            <th>Detail Perubahan</th>
                                            <th>User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Isi akan dimuat oleh DataTables --}}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Tanggal & Waktu</th>
                                            <th>LOK SPK</th>
                                            <th>Detail Perubahan</th>
                                            <th>User</th>
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
<script>
    $(document).ready(function () {
        $('#tableRiwayat').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('riwayat-barang.index') }}",
            columns: [
                { data: 'created_at', name: 'created_at' },
                { data: 'lok_spk', name: 'lok_spk' },
                { data: 'update', name: 'update' },
                { data: 'user.name', name: 'user.name', orderable: false, searchable: false }
            ]
        });
    });
</script>

@endsection