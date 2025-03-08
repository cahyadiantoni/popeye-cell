@extends('layouts.main')

@section('title', 'Transaksi Jual')
@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>List Transaksi Jual</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class="breadcrumb-title">
                            <li class="breadcrumb-item">
                                <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                            </li>
                            <li class="breadcrumb-item"><a href="#!">Transaksi Jual</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(session('errors'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul>
                                    @foreach (session('errors') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="card-block">
                            <a href="{{ route('transaksi-jual.create') }}" class="btn btn-primary btn-round">Jual Barang</a>
                            <hr>
                            <div class="dt-responsive table-responsive">
                                <table id="transaksiJualTable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>LOK_SPK</th>
                                            <th>Tipe</th>
                                            <th>No Faktur</th>
                                            <th>Pembeli</th>
                                            <th>Tgl Jual</th>
                                            <th>Harga Jual</th>
                                            <th>Petugas</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                            <th>LOK_SPK</th>
                                            <th>Tipe</th>
                                            <th>No Faktur</th>
                                            <th>Pembeli</th>
                                            <th>Tgl Jual</th>
                                            <th>Harga Jual</th>
                                            <th>Petugas</th>
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
        $('#transaksiJualTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('transaksi-jual.data') }}',
            columns: [
                { data: 'lok_spk', name: 'lok_spk' },
                { data: 'tipe', name: 'tipe' },
                { data: 'nomor_faktur', name: 'nomor_faktur' },
                { data: 'pembeli_faktur', name: 'pembeli' },
                { data: 'tgl_jual', name: 'tgl_jual', orderable: false, searchable: false },
                { data: 'harga_jual', name: 'harga_jual', orderable: false, searchable: false },
                { data: 'petugas_faktur', name: 'petugas' }
            ]
        });
    });
</script>
@endsection
