@extends('layouts.main')

@section('title', 'List Kirim Barang')
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
                                <h4>List Kirim Barang</h4>
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
                                        href="#!">Kirim Barang</a>
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
                                <button class="btn btn-success" id="addKirimBtn">Kirim Barang</button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Gudang Asal</th>
                                                <th>Gudang Tujuan</th>
                                                <th>Pengirim</th>
                                                <th>Penerima</th>
                                                <th>Status</th>
                                                <th>Tgl Kirim</th>
                                                <th>Tgl Terima</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($kirims as $kirim)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('kirim-barang.show', $kirim->id) }}">
                                                        {{ $kirim->id }}
                                                    </a>
                                                </td>
                                                <td>{{ $kirim->pengirimGudang->nama_gudang ?? 'N/A' }}</td>
                                                <td>{{ $kirim->penerimaGudang->nama_gudang }}</td>
                                                <td>{{ $kirim->pengirimUser->name }}</td>
                                                <td>{{ $kirim->penerimaUser->name }}</td>
                                                <td>
                                                    @switch($kirim->status)
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
                                                <td>{{ $kirim->dt_kirim }}</td>
                                                <td>{{ $kirim->dt_terima }}</td>
                                                <td>
                                                    <!-- Tombol View -->
                                                    <a href="{{ route('kirim-barang.show', $kirim->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @if($kirim->status == 0)
                                                        <!-- Tombol View -->
                                                        <form action="{{ route('kirim-barang.delete', $kirim->id) }}" method="POST" class="d-inline delete-form">
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

    <!-- Modal Add Barang -->
    <div class="modal fade" id="addKirimModal" tabindex="-1" aria-labelledby="addKirimModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('kirim-barang.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addKirimModalLabel">Kirim Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <a href="{{ asset('files/template kirim barang.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                        </div>
                        <div class="mb-3">
                            <label for="fileExcel" class="form-label">Upload File Excel</label>
                            <input type="file" class="form-control" id="filedata" name="filedata" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label col-sm-2 col-form-label">Select Gudang</label>
                            <div class="col-sm-10">
                                <select name="penerima_gudang_id" class="form-select form-control" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach($allgudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Kirim</button>
                    </div>
                </form>
            </div>
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

            const addKirimBtn = document.getElementById('addKirimBtn');
            const addKirimModal = new bootstrap.Modal(document.getElementById('addKirimModal'));
            addKirimBtn.addEventListener('click', () => {
                addKirimModal.show();
            });
        });
    </script>
@endsection()