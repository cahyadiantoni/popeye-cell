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
                            <div class="card-header">
                            <form method="GET" action="{{ route('negoan.index') }}" class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label>Grade</label>
                                    <select name="grade" class="form-control">
                                        <option value="">Semua Grade</option>
                                        <option value="Barang JB" {{ request('grade') == 'Barang JB' ? 'selected' : '' }}>Barang JB</option>
                                        <option value="Barang 2nd" {{ request('grade') == 'Barang 2nd' ? 'selected' : '' }}>Barang 2nd</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label>Tanggal Awal</label>
                                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                                </div>

                                <div class="col-md-3">
                                    <label>Tanggal Akhir</label>
                                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                                </div>

                                <div class="col-md-3">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">Semua</option>
                                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Proses</option>
                                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Disetujui</option>
                                        <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Ditolak</option>
                                    </select>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('negoan.index') }}" class="btn btn-secondary mx-2">Reset</a>
                                </div>
                            </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-block">
                                <a href="{{ route('negoan.create') }}" class="btn btn-primary btn-round">Tambahkan Negoan</a>
                                <button type="button" class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                    Tambahkan Negoan (Upload)
                                </button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>Tipe</th>
                                                <th>Grade</th>
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
                                                <td>{{ \Carbon\Carbon::parse($nego->updated_at)->translatedFormat('d F Y') }}</td>
                                                <td>
                                                    <a href="{{ route('negoan.show', $nego->id) }}">
                                                        {{ $nego->tipe }}
                                                    </a>
                                                </td>
                                                <td>{{ $nego->grade }}</td>
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

    <!-- Modal Upload Excel -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('negoan.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload File Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <a href="{{ asset('files/template upload negoan.xlsx') }}" class="text-primary" target="_blank">Download Template Excel</a>
                        </div>

                        <!-- Upload File Excel -->
                        <div class="mb-3">
                            <label for="file" class="form-label">Pilih File Excel</label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx, .xls, .csv" required>
                            <small class="text-muted">Format: tipe, harga_awal, harga_nego, note_nego</small>
                        </div>

                        <!-- Grade manual input -->
                        <div class="mb-3 row">
                            <label class="form-label col-sm-3 col-form-label">Grade</label>
                            <div class="col-sm-9">
                                <select name="grade" id="grade" class="form-control" required>
                                    <option value="">Pilih Grade</option>
                                    <option value="Barang JB">Barang JB</option>
                                    <option value="Barang 2nd">Barang 2nd</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Upload</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

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