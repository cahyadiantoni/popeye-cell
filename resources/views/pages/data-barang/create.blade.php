@extends('layouts.main')

@section('title', 'Tambah Data Barang')
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
                                <h3>Form Tambah Data Barang</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Upload Data Barang</h4>
                                <form method="POST" action="{{ route('data-barang.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <a href="{{ asset('files/template.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                                    <hr>
                                    <div class="mb-3 row">
                                        <div class="sub-title">Masukan file excel di bawah!</div>
                                        <input type="file" name="filedata">
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Select Gudang</label>
                                        <div class="col-sm-10">
                                            <select name="gudang_id" class="form-select form-control" required>
                                                <option value="">-- Pilih Gudang --</option>
                                                @foreach($gudangs as $gudang)
                                                    <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('data-barang.index') }}" class="btn btn-secondary btn-round">Kembali</a>
                                        <button type="submit" class="btn btn-primary btn-round">Upload Data Barang</button>
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