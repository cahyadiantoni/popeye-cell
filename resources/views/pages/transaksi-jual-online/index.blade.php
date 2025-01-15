@extends('layouts.main')

@section('title', 'Transaksi Jual Online')
@section('content')
    <!-- Main-body start -->
    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page-header start -->
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>List Transaksi Jual Online</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="page-header-breadcrumb">
                            <ul class="breadcrumb-title">
                                <li class="breadcrumb-item" style="float: left;">
                                    <a href="<?= url('/') ?>"> <i class="feather icon-home"></i> </a>
                                </li>
                                <li class="breadcrumb-item" style="float: left;"><a
                                        href="#!">Transaksi Jual Online</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

            <!-- Page-body start -->
            <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Zero config.table start -->
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
                                <a href="{{ route('transaksi-jual-online.create') }}" class="btn btn-primary btn-round">Jual Barang</a>
                                <hr>
                                <!-- <form id="return-form" method="POST" action="{{ route('returnBarang') }}">
                                    @csrf -->
                                    <div class="dt-responsive table-responsive">
                                        <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <!-- <th><input type="checkbox" id="select-all"></th> -->
                                                    <th>LOK_SPK</th>
                                                    <th>Tipe</th>
                                                    <th>Title</th>
                                                    <th>Toko</th>
                                                    <th>Tgl Jual</th>
                                                    <th>Harga Jual</th>
                                                    <th>Petugas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($barangs as $barang)
                                                <tr>
                                                    <!-- <td>
                                                        <input type="checkbox" class="select-checkbox" name="lok_spk[]" value="{{ $barang->lok_spk }}">
                                                    </td> -->
                                                    <td>{{ $barang->lok_spk }}</td>
                                                    <td>{{ $barang->tipe }}</td>
                                                    <td>{{ $barang->title }}</td>
                                                    <td>{{ $barang->toko }}</td>
                                                    <td>{{ $barang->tgl_jual }}</td>
                                                    <td>{{ 'Rp. ' . number_format($barang->harga_jual, 0, ',', '.') }}</td>
                                                    <td>{{ $barang->petugas }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <!-- <th></th> -->
                                                    <th>LOK_SPK</th>
                                                    <th>Tipe</th>
                                                    <th>Title</th>
                                                    <th>Toko</th>
                                                    <th>Tgl Jual</th>
                                                    <th>Harga Jual</th>
                                                    <th>Petugas</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                            </div>
                        </div>
                        <!-- Zero config.table end -->
                    </div>
                </div>
            </div>
            <!-- Page-body end -->
        </div>
    </div>
    <!-- Main-body end -->

@endsection
