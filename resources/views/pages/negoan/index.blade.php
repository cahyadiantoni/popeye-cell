@extends('layouts.main')

@section('title', 'Negoan Harga')
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
                                <h4>List Negoan Harga</h4>
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
                                        href="#!">Negoan Harga</a>
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
                                <a href="{{ route('negoan.create') }}" class="btn btn-primary btn-round">Tambahkan Negoan</a>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tipe</th>
                                                <th>Hrg Awal</th>
                                                <th>Note</th>
                                                <th>Hrg Nego</th>
                                                <th>Hrg ACC</th>
                                                <th>Note</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($negoans as $nego)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <a href="{{ route('negoan.show', $nego->id) }}">
                                                        {{ $nego->tipe }}
                                                    </a>
                                                </td>
                                                <td>{{ 'Rp. ' . number_format($nego->harga_awal, 0, ',', '.') }}</td>
                                                <td>{{ $nego->note_nego }}</td>
                                                <td>{{ 'Rp. ' . number_format($nego->harga_nego, 0, ',', '.') }}</td>
                                                <td>{{ 'Rp. ' . number_format($nego->harga_acc, 0, ',', '.') }}</td>
                                                <td>{{ $nego->note_acc }}</td>
                                                <td>
                                                @if ($nego->status == 0)
                                                    <span class="badge bg-warning">Proses</span>
                                                @elseif ($nego->status == 1)
                                                    <span class="badge bg-success">Disetujui</span>
                                                @elseif ($nego->status == 2)
                                                    <span class="badge bg-danger">Ditolak</span>
                                                @endif
                                                </td>
                                                <td>
                                                    <!-- Tombol View -->
                                                    <a href="{{ route('negoan.show', $nego->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @if($nego->status == 0)
                                                    <form action="{{ route('negoan.destroy', $nego->id) }}" method="POST" class="d-inline delete-form">
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
                                                <th>No</th>
                                                <th>Tipe</th>
                                                <th>Hrg Awal</th>
                                                <th>Note</th>
                                                <th>Hrg Nego</th>
                                                <th>Hrg ACC</th>
                                                <th>Note</th>
                                                <th>Status</th>
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