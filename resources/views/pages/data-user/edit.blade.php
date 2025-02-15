@extends('layouts.main')

@section('title', 'Edit Data User')
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
                                <h3>Form Edit Data User</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Data User</h4>
                                <form method="POST" action="{{ route('data-user.update', $user->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Name</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Email</label>
                                        <div class="col-sm-10">
                                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Role</label>
                                        <div class="col-sm-10">
                                            <select name="role" class="form-control">
                                                <option value="">Pilih Role</option>
                                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Select Gudang</label>
                                        <div class="col-sm-10">
                                        <select name="gudang_id" class="form-select form-control" required>
                                            <option value="0">-- Pilih Gudang --</option>
                                            @foreach($gudangs as $gudang)
                                                <option value="{{ $gudang->id }}" {{ $gudang->id == $user->gudang_id ? 'selected' : '' }}>
                                                    {{ $gudang->nama_gudang }}
                                                </option>
                                            @endforeach
                                        </select>
                                        </div>
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('data-user.index') }}" class="btn btn-secondary btn-round">Kembali</a>
                                        <button type="submit" class="btn btn-primary btn-round">Update User</button>
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