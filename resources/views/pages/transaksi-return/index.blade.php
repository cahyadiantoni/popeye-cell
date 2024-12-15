@extends('layouts.main')

@section('title', 'Transaksi Return')
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
                                <h4>List Transaksi Return</h4>
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
                                        href="#!">Transaksi Return</a>
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
                                                <th>LOK_SPK</th>
                                                <th>Tipe</th>
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Jual</th>
                                                <th>Tgl Return</th>
                                                <th>Harga Jual</th>
                                                <th>Petugas</th>
                                                <!-- <th>Action</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($returns as $return)
                                            <tr>
                                                <td>{{ $return->lok_spk }}</td>
                                                <td>{{ $return->barang->tipe }}</td>
                                                <td>{{ $return->barang->no_faktur }}</td>
                                                <td>{{ $return->barang->faktur->pembeli }}</td>
                                                <td>{{ $return->barang->faktur->tgl_jual }}</td>
                                                <td>{{ $return->tgl_return }}</td>
                                                <td>{{ 'Rp. ' . number_format($return->barang->harga_jual, 0, ',', '.') }}</td>
                                                <td>{{ $return->user->name }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>LOK_SPK</th>
                                                <th>Tipe</th>
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Jual</th>
                                                <th>Tgl Return</th>
                                                <th>Harga Jual</th>
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