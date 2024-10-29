@extends('layouts.main')

@section('title', 'Tambah Data Gudang')
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
                                <h3>Form Tambah Data Gudang</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Data Gudang</h4>
                                <form method="POST" action="{{ route('data-gudang.store') }}">
                                    @csrf
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Name</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="nama_gudang" class="form-control" placeholder="Ketik Nama Gudang" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Pilih Penanggung Jawab</label>
                                        <div class="col-sm-10">
                                            <select name="pj_gudang" class="form-select form-control" required>
                                                <option value="" readonly>-- Pilih Pengguna --</option>
                                                @foreach($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('data-gudang.index') }}" class="btn btn-secondary btn-round">Kembali</a>
                                        <button type="submit" class="btn btn-primary btn-round">Simpan Data Gudang</button>
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