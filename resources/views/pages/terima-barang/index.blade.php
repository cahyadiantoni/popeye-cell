@extends('layouts.main')

@section('title', 'List Terima Barang')
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
                                <h4>List Terima Barang</h4>
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
                                        href="#!">Terima Barang</a>
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
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Gudang Asal</th>
                                                <th>Gudang Tujuan</th>
                                                <th>Pengirim</th>
                                                <th>Penerima</th>
                                                <th>Jumlah Barang</th>
                                                <th>Status</th>
                                                <th>Tgl Kirim</th>
                                                <th>Tgl Terima</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($requests as $request)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('terima-barang.show', $request->id) }}">
                                                        {{ $request->id }}
                                                    </a>
                                                </td>
                                                <td>{{ $request->pengirimGudang->nama_gudang ?? 'N/A' }}</td>
                                                <td>{{ $request->penerimaGudang->nama_gudang }}</td>
                                                <td>{{ $request->pengirimUser->name }}</td>
                                                <td>{{ $request->penerimaUser->name }}</td>
                                                <td>{{ $jumlahBarang[$request->id] ?? 0 }}</td>
                                                <td>
                                                    @switch($request->status)
                                                        @case(0)
                                                            <span class="badge bg-warning text-dark">Dalam Proses</span>
                                                            @break
                                                        @case(1)
                                                            <span class="badge bg-success">Diterima</span>
                                                            @break
                                                        @case(2)
                                                            <span class="badge bg-danger">Ditolak</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">Status Tidak Diketahui</span>
                                                    @endswitch
                                                </td>
                                                <td>{{ $request->dt_kirim }}</td>
                                                <td>{{ $request->dt_terima }}</td>
                                                <td>
                                                    <!-- Tombol View -->
                                                    <a href="{{ route('terima-barang.show', $request->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @if($request->status == 0)
                                                        <!-- Tombol View -->
                                                        <form action="{{ route('kirim-barang.delete', $request->id) }}" method="POST" class="d-inline delete-form">
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
                                                <th>ID</th>
                                                <th>Gudang Asal</th>
                                                <th>Gudang Tujuan</th>
                                                <th>Pengirim</th>
                                                <th>Penerima</th>
                                                <th>Jumlah Barang</th>
                                                <th>Status</th>
                                                <th>Tgl Kirim</th>
                                                <th>Tgl Terima</th>
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
        });
    </script>
@endsection()