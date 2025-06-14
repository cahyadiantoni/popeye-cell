@extends('layouts.main')

@section('title', 'Jual Barang')
@section('content')
    <!-- Main-body start -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page body start -->
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
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Basic Form Inputs card start -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Form Jual Barang</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Copy-Paste Data Jual Barang</h4>
                                <form method="POST" action="{{ route('transaksi-jual-bawah.store') }}">
                                    @csrf
                                    <div class="mb-3 row">
                                        <div class="col-sm-12">
                                            <p>Salin data dari Excel lalu tempelkan di area bawah ini. Pastikan urutan kolom sesuai:</p>
                                            <p><strong>tgl_jual | petugas | keterangan | lok_spk | harga_beli | harga_jual | merk_tipe | kelengkapan | grade | kerusakan | pj | imei | pembeli</strong></p>
                                            <h5 style="color:red">PASTIKAN AGAR TIDAK ADA HARGA YANG BERBEDA UNTUK TIPE DAN GRADE YANG SAMA.</h5>
                                            <hr>
                                            <textarea name="pasted_data" class="form-control" rows="15" placeholder="Tempelkan data dari Excel di sini..." required></textarea>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between mt-3">
                                        <a href="{{ route('transaksi-jual-bawah.index') }}" class="btn btn-secondary btn-round">List All Transaksi</a>
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