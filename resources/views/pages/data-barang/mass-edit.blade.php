@extends('layouts.main')

@section('title', 'Mass Edit Data Barang')
@section('content')
    <!-- Main-body start -->
    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page body start -->
            <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Basic Form Inputs card start -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Form Mass Edit Data Barang</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Upload Data Barang</h4>
                                <form method="POST" action="{{ route('mass-update.dataBarang') }}" enctype="multipart/form-data">
                                    @csrf
                                    <a href="{{ asset('files/template mass edit barang.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                                    <hr>
                                    <div class="mb-3 row">
                                        <div class="sub-title">Masukan file excel di bawah!</div>
                                        <input type="file" name="filedata">
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('data-barang.index') }}" class="btn btn-secondary btn-round">Kembali</a>
                                        <button type="submit" class="btn btn-primary btn-round">Edit Barang</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Basic Form Inputs card end -->
                    </div>
                </div>
            </div>
            <!-- Page body end -->
        </div>
    </div>
    <!-- Main-body end -->
@endsection()