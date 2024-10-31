@extends('layouts.main')

@section('title', 'History Kirim Barang')
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
                                <h4>History Kirim Barang</h4>
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
                                        href="#!">Data History</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

            <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                        <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Lok SPK</th>
                                                    <th>Tipe</th>
                                                    <th>Gudang Asal</th>
                                                    <th>Gudang Tujuan</th>
                                                    <th>Pengirim</th>
                                                    <th>Penerima</th>
                                                    <th>Status</th>
                                                    <th>Tgl Kirim</th>
                                                    <th>Tgl Terima</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($requests as $request)
                                                <tr>
                                                    <td>{{ $request->lok_spk }}</td>
                                                    <td>{{ $request->barang->tipe }}</td>
                                                    <td>{{ $request->pengirimGudang->nama_gudang ?? 'N/A' }}</td>
                                                    <td>{{ $request->penerimaGudang->nama_gudang }}</td>
                                                    <td>{{ $request->pengirimUser->name }}</td>
                                                    <td>{{ $request->penerimaUser->name }}</td>
                                                    <td>
                                                    @switch($request->status)
                                                        @case(0)
                                                            Dalam Proses
                                                            @break
                                                        @case(1)
                                                            Diterima
                                                            @break
                                                        @case(2)
                                                            Ditolak
                                                            @break
                                                        @default
                                                            Status Tidak Diketahui
                                                    @endswitch
                                                    </td>
                                                    <td>{{ $request->dt_kirim }}</td>
                                                    <td>{{ $request->dt_terima }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Lok SPK</th>
                                                    <th>Tipe</th>
                                                    <th>Gudang Asal</th>
                                                    <th>Gudang Tujuan</th>
                                                    <th>Pengirim</th>
                                                    <th>Penerima</th>
                                                    <th>Status</th>
                                                    <th>Tgl Kirim</th>
                                                    <th>Tgl Terima</th>
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
    <!-- Main-body end -->
@endsection()