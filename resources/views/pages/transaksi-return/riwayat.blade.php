@extends('layouts.main')

@section('title', 'Riwayat Barang Return')
@section('content')

    <div class="main-body">
        <div class="page-wrapper">
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>Riwayat Barang Return</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="page-header-breadcrumb">
                            <ul class="breadcrumb-title">
                                <li class="breadcrumb-item" style="float: left;">
                                    <a href="<?= url('/') ?>"> <i class="feather icon-home"></i> </a>
                                </li>
                                <li class="breadcrumb-item" style="float: left;"><a href="{{ route('transaksi-return.index') }}">Transaksi Return</a></li>
                                <li class="breadcrumb-item" style="float: left;"><a href="#!">Riwayat</a></li>
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
                                <h5>Daftar Semua Barang yang Pernah Diretur</h5>
                            </div>
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Lok SPK</th>
                                                <th>Tipe</th>
                                                <th>Status Barang</th>
                                                <th>Nomor Return</th>
                                                <th>Nomor Faktur</th>
                                                <th>Tgl Return</th>
                                                <th>Harga Return</th>
                                                <th>Pedagang</th>
                                                <th>Alasan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($riwayat_barang as $item)
                                            <tr>
                                                <td>{{ $item->lok_spk }}</td>
                                                {{-- Menggunakan optional() untuk keamanan jika relasi barang tidak ditemukan --}}
                                                <td>{{ optional($item->barang)->tipe }}</td>
                                                <td>
                                                    @switch(optional($item->barang)->status_barang)
                                                        @case(1)
                                                            <span class="badge badge-success">Tersedia</span>
                                                            @break
                                                        @case(2)
                                                            <span class="badge badge-danger">Terjual</span>
                                                            @break
                                                        @case(5)
                                                            <span class="badge badge-warning">Proses Terjual</span>
                                                            @break
                                                        @case(0)
                                                            <span class="badge badge-info">Proses Kirim</span>
                                                            @break
                                                        @default
                                                            <span class="badge badge-default">N/A</span>
                                                    @endswitch
                                                </td>
                                                {{-- Menggunakan optional() untuk keamanan jika relasi returnModel tidak ditemukan --}}
                                                <td>
                                                    <a href="{{ route('transaksi-return.show', optional($item->returnModel)->id) }}">
                                                        {{ optional($item->returnModel)->nomor_return }}
                                                    </a>
                                                </td>
                                                <td>{{ optional($item->barang)->no_faktur }}</td>
                                                <td>{{ optional($item->returnModel)->tgl_return }}</td>
                                                <td>{{ 'Rp. ' . number_format($item->harga, 0, ',', '.') }}</td>
                                                <td>{{ $item->pedagang }}</td>
                                                <td>{{ $item->alasan }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
    </div>
    @endsection