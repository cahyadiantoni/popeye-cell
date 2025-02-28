@extends('layouts.main')

@section('title', 'Form Pengajuan Transfer')
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
                                <h3>Form Pengajuan Tokped</h3>
                            </div>
                            <div class="card-block">
                            <form action="{{ route('req-tokped.store') }}" method="POST">
                                @csrf
                                <div class="mb-3 row">
                                    <label for="tgl" class="form-label col-sm-2 col-form-label">Tanggal</label>
                                    <input type="date" class="form-control" name="tgl" required>
                                </div>
                                <div class="mb-3 row">
                                    <label for="kode_lok" class="form-label col-sm-2 col-form-label">Kode Lokasi</label>
                                    <input type="text" class="form-control" name="kode_lok" required>
                                </div>
                                <div class="mb-3 row">
                                    <label for="nama_toko" class="form-label col-sm-2 col-form-label">Nama Toko</label>
                                    <input type="text" class="form-control" name="nama_toko" required>
                                </div>
                                <div class="mb-3 row">
                                    <label for="alasan" class="form-label col-sm-2 col-form-label">Alasan</label>
                                    <textarea class="form-control" name="alasan"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="{{ route('req-tokped.index') }}" class="btn btn-secondary">Batal</a>
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