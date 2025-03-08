@extends('layouts.main')

@section('title', 'Rekap Transaksi Offline')
@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Rekap Transaksi Offline</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class="breadcrumb-title">
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                            </li>
                            <li class="breadcrumb-item" style="float: left;"><a href="#!">Rekap Transaksi Offline</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-block">

                            {{-- Form Filter --}}
                            <form method="GET" action="{{ route('transaksi-faktur.rekap') }}">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="gudang">Pilih Gudang:</label>
                                        <select class="form-control" name="gudang" id="gudang">
                                            <option value="">Semua Gudang</option>
                                            @foreach($daftarGudang as $key => $value)
                                                <option value="{{ $key }}" {{ request('gudang') == $key ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="bulan">Pilih Bulan:</label>
                                        <input type="month" class="form-control" name="bulan" id="bulan" value="{{ request('bulan') }}">
                                    </div>

                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('transaksi-faktur.rekap') }}" class="btn btn-secondary ml-2">Reset</a>
                                    </div>
                                </div>
                            </form>

                            {{-- Tabel Data --}}
                            <div class="dt-responsive table-responsive">
                                <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Nama Gudang</th>
                                            <th>Bulan</th>
                                            <th>Total Pendapatan</th>
                                            <th>Total Barang Terjual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rekaps as $rekap)
                                        <tr>
                                            <td>{{ $rekap->nama_gudang }}</td>
                                            <td>{{ $rekap->bulan }}</td>
                                            <td>{{ 'Rp. ' . number_format($rekap->total_pendapatan, 0, ',', '.') }}</td>
                                            <td>{{ $rekap->total_barang }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Nama Gudang</th>
                                            <th>Bulan</th>
                                            <th>Total Pendapatan</th>
                                            <th>Total Barang Terjual</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div> {{-- End Table --}}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
