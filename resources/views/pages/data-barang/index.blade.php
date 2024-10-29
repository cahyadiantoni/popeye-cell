@extends('layouts.main')

@section('title', 'Data Barang')
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
                                <h4>List Data Barang</h4>
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
                                        href="#!">Data Barang</a>
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
                                <a href="{{ route('data-barang.create') }}" class="btn btn-primary btn-round">Upload Excel Barang</a>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>LOK_SPK</th>
                                                <th>Jenis</th>
                                                <th>Tipe</th>
                                                <th>Grade</th>
                                                <th>Gudang</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($barangs as $barang)
                                            <tr>
                                                <td>{{ $barang->lok_spk }}</td>
                                                <td>{{ $barang->jenis }}</td>
                                                <td>{{ $barang->tipe }}</td>
                                                <td>{{ $barang->grade }}</td>
                                                <td>{{ $barang->gudang_id }}</td>
                                                <td>
                                                    <a href="{{ route('data-barang.edit', urlencode($barang->lok_spk)) }}" class="btn btn-warning btn-round">Edit</a>
                                                    <!-- Tombol Delete -->
                                                    <form action="{{ route('data-barang.destroy', urlencode($barang->lok_spk)) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-round"
                                                                onclick="return confirm('Are you sure you want to delete this barang?')">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>LOK_SPK</th>
                                                <th>Jenis</th>
                                                <th>Tipe</th>
                                                <th>Grade</th>
                                                <th>Gudang</th>
                                                <th>Action</th>
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