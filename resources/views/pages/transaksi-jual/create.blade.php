@extends('layouts.main')

@section('title', 'Jual Barang')
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
                                <h3>Form Jual Barang</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Upload Jual Barang</h4>
                                <form method="POST" action="{{ route('transaksi-jual.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <a href="{{ asset('files/templateJual.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                                    <hr>
                                    <div class="mb-3 row">
                                        <div class="sub-title">Masukan file excel di bawah!</div>
                                        <input type="file" name="filedata">
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tanggal Jual</label>
                                        <div class="col-sm-10">
                                            <input type="date" name="tgl_jual" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Nomor Faktur</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="nomor_faktur" class="form-control" placeholder="Ketik Nomor Faktur" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Pembeli</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="pembeli" class="form-control" placeholder="Ketik Pembeli" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Petugas</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="petugas" class="form-control" placeholder="Ketik Nama Petugas" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Keterangan</label>
                                        <div class="col-sm-10">
                                            <textarea name="keterangan" class="form-control" placeholder="Tambahkan keterangan jika diperlukan" rows="4"></textarea>
                                        </div>
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('transaksi-jual.index') }}" class="btn btn-secondary btn-round">List All Transaksi</a>
                                        <button type="submit" class="btn btn-primary btn-round">Submit Jual Barang</button>
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