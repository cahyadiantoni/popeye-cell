@extends('layouts.main')

@section('title', 'Transaksi Faktur')
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
                                <h4>List Transaksi Faktur</h4>
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
                                        href="#!">Transaksi Faktur</a>
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
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Faktur</th>
                                                <th>jumlah Barang</th>
                                                <th>Total Harga</th>
                                                <th>Petugas</th>
                                                <th>Keterangan</th>
                                                <!-- <th>Action</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($fakturs as $faktur)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('transaksi-faktur.show', $faktur->nomor_faktur) }}">
                                                        {{ $faktur->nomor_faktur }}
                                                    </a>
                                                </td>
                                                <td>{{ $faktur->pembeli }}</td>
                                                <td>{{ $faktur->tgl_jual }}</td>
                                                <td>{{ $faktur->total_barang }}</td>
                                                <td>{{ 'Rp. ' . number_format($faktur->total*1000, 0, ',', '.') }}</td>
                                                <td>{{ $faktur->petugas }}</td>
                                                <td>{{ $faktur->keterangan }}</td>
                                                <!-- <td> -->
                                                    <!-- <a href="{{ route('transaksi-jual.edit', urlencode($faktur->lok_spk)) }}" class="btn btn-warning btn-round">Edit</a> -->
                                                <!-- </td> -->
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Faktur</th>
                                                <th>jumlah Barang</th>
                                                <th>Total Harga</th>
                                                <th>Petugas</th>
                                                <!-- <th>Action</th> -->
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
@endsection()