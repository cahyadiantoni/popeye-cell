@extends('layouts.main')

@section('title', 'Transaksi Return')
@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
                                    <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                                </li>
                                <li class="breadcrumb-item" style="float: left;"><a href="#!">Transaksi Return</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

            <!-- Page-body start -->
            <div class="page-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('errors') && session('errors')->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul>
                            @foreach (session('errors')->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            {{-- PERUBAHAN DI SINI: Menambahkan Form Filter --}}
                            <div class="card-header">
                                <form action="{{ route('transaksi-return.index') }}" method="GET">
                                    <div class="row">
                                        @if($roleUser == 'admin')
                                        <div class="col-md-3">
                                            <label for="kode_faktur">Gudang</label>
                                            <select name="kode_faktur" class="form-control">
                                                <option value="">-- Semua Gudang Outlet --</option>
                                                <option value="RTO-JK" {{ request('kode_faktur') == 'RTO-JK' ? 'selected' : '' }}>Outlet JK</option>
                                                <option value="RTO-AD" {{ request('kode_faktur') == 'RTO-AD' ? 'selected' : '' }}>Outlet AD</option>
                                                <option value="RTO-PY" {{ request('kode_faktur') == 'RTO-PY' ? 'selected' : '' }}>Outlet PY</option>
                                            </select>
                                        </div>
                                        @endif
                                        <div class="col-md-3">
                                            <label for="tanggal_mulai">Tanggal Mulai</label>
                                            <input type="date" name="tanggal_mulai" class="form-control" value="{{ request('tanggal_mulai') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="tanggal_selesai">Tanggal Selesai</label>
                                            <input type="date" name="tanggal_selesai" class="form-control" value="{{ request('tanggal_selesai') }}">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('transaksi-return.index') }}" class="btn btn-secondary mx-2">Reset</a>
                                    </div>
                                </form>
                            </div>
                            <div class="card-block">
                                <a href="{{ route('transaksi-return.create') }}" class="btn btn-primary btn-round">Return Barang</a>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>No Return</th>
                                                <th>Tgl Return</th>
                                                <th>Total Barang</th>
                                                <th>Total Harga</th>
                                                <th>Keterangan</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($returns as $return)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <a href="{{ route('transaksi-return.show', $return->id) }}">
                                                        {{ $return->nomor_return }}
                                                    </a>
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($return->tgl_return)->format('d-m-Y') }}</td>
                                                <td>{{ $return->total_barang }}</td>
                                                <td>{{ 'Rp. ' . number_format($return->total_harga, 0, ',', '.') }}</td>
                                                <td>{{ $return->keterangan }}</td>
                                                <td>
                                                    <a href="{{ route('transaksi-return.show', $return->id) }}" class="btn btn-info btn-sm">View</a>
                                                    <form action="{{ route('transaksi-return.destroy', $return->id) }}" method="POST" class="d-inline delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                                    </form>
                                                </td>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    if (confirm('Yakin ingin menghapus data ini? Semua barang yang terkait akan dikembalikan statusnya.')) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection()