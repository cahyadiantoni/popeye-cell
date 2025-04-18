@extends('layouts.main')

@section('title', 'Kesimpulan Kesimpulan')
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
                                <h4>List Kesimpulan Kesimpulan</h4>
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
                                        href="#!">Kesimpulan Kesimpulan</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

            <!-- Page-body start -->
            <div class="page-body">
                {{-- Pesan Berhasil --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Pesan Gagal --}}
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Zero config.table start -->
                        <div class="card">
                            <div class="card-header">
                                <form action="{{ route('transaksi-kesimpulan.index') }}" method="GET">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="tanggal_mulai">Tanggal Mulai</label>
                                            <input type="date" name="tanggal_mulai" class="form-control" value="{{ request('tanggal_mulai') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="tanggal_selesai">Tanggal Selesai</label>
                                            <input type="date" name="tanggal_selesai" class="form-control" value="{{ request('tanggal_selesai') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="status">Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">-- Semua status --</option>
                                                <option value="Lunas" {{ request('status') == 'Lunas' ? 'selected' : '' }}>Lunas</option>
                                                <option value="Hutang" {{ request('status') == 'Hutang' ? 'selected' : '' }}>Hutang</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('transaksi-kesimpulan.index') }}" class="btn btn-secondary mx-2">Reset</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-block">
                                <a href="{{ route('transaksi-kesimpulan.create') }}" class="btn btn-primary btn-round">Buat Kesimpulan</a>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Cek</th>
                                                <th>No Kesimpulan</th>
                                                <th>Tanggal</th>
                                                <th>Jumlah Barang</th>
                                                <th>Total Harga</th>
                                                <th>Potongan</th>
                                                <th>Diskon</th>
                                                <th>Grand Total</th>
                                                <th>Sudah Dibayar</th>
                                                <th>Pembayaran</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($kesimpulans as $kesimpulan)
                                            <tr>
                                                <td>
                                                    @if ($kesimpulan->is_finish == 0)
                                                        @if($roleUser == 'admin')
                                                            <form action="{{ route('transaksi-kesimpulan.tandai-sudah-dicek', $kesimpulan->id) }}" method="POST" class="d-inline finish-form">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-primary btn-sm finish-btn">Tandai Dicek</button>
                                                            </form>
                                                        @else
                                                        <span class="badge bg-warning">Belum Dicek</span>
                                                        @endif
                                                    @else
                                                        <!-- Keterangan Sudah Dicek -->
                                                        <span class="badge bg-success">Sudah Dicek</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('transaksi-kesimpulan.show', $kesimpulan->nomor_kesimpulan) }}">
                                                        {{ $kesimpulan->nomor_kesimpulan }}
                                                    </a>
                                                </td>
                                                <td>{{ $kesimpulan->tgl_jual }}</td>
                                                <td>{{ $kesimpulan->total_barang }}</td>
                                                <td>{{ 'Rp. ' . number_format($kesimpulan->total, 0, ',', '.') }}</td>
                                                <td>{{ 'Rp. ' . number_format($kesimpulan->potongan_kondisi, 0, ',', '.') }}</td>
                                                <td>{{ $kesimpulan->diskon }}%</td>
                                                <td>{{ 'Rp. ' . number_format($kesimpulan->grand_total, 0, ',', '.') }}</td>
                                                <td>{{ 'Rp. ' . number_format($kesimpulan->total_nominal, 0, ',', '.') }}</td>
                                                <td>
                                                    @if ($kesimpulan->is_lunas == 0)
                                                        <span class="badge bg-warning">Hutang</span>
                                                    @else
                                                        <span class="badge bg-success">Lunas</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <!-- Tombol View -->
                                                    <a href="{{ route('transaksi-kesimpulan.show', $kesimpulan->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @if ($kesimpulan->is_finish==0)
                                                    <form action="{{ route('transaksi-kesimpulan.delete', $kesimpulan->id) }}" method="POST" class="d-inline delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                                    </form>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Cek</th>
                                                <th>No Kesimpulan</th>
                                                <th>Tanggal</th>
                                                <th>Jumlah Barang</th>
                                                <th>Total Harga</th>
                                                <th>Potongan</th>
                                                <th>Diskon</th>
                                                <th>Grand Total</th>
                                                <th>Sudah Dibayar</th>
                                                <th>Pembayaran</th>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault(); // Mencegah submit form langsung
                    if (confirm('Yakin ingin menghapus data ini?')) {
                        form.submit(); // Submit form jika konfirmasi "OK"
                    }
                });
            });

            const finishForms = document.querySelectorAll('.finish-form');
            finishForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault(); // Mencegah submit form langsung
                    if (confirm('Apakah Anda yakin ingin menandai transaksi ini sebagai sudah dicek?')) {
                        form.submit(); // Submit form jika konfirmasi "OK"
                    }
                });
            });
        });
    </script>
@endsection()