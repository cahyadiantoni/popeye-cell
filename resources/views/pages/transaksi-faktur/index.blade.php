@extends('layouts.main')

@section('title', 'Transaksi Faktur')
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
                                <h4>List Transaksi Faktur</h4>
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
                                        href="#!">Transaksi Faktur</a>
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
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Faktur</th>
                                                <th>jumlah Barang</th>
                                                <th>Total Harga</th>
                                                <th>Petugas</th>
                                                <th>Keterangan</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($fakturs as $faktur)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('transaksi-faktur.show', $faktur->nomor_faktur) }}">
                                                        {{ $faktur->nomor_faktur }}
                                                    </a>
                                                </td>
                                                <td>{{ $faktur->pembeli }}</td>
                                                <td>{{ $faktur->tgl_jual }}</td>
                                                <td>{{ $faktur->total_barang }}</td>
                                                <td>{{ 'Rp. ' . number_format($faktur->total, 0, ',', '.') }}</td>
                                                <td>{{ $faktur->petugas }}</td>
                                                <td>{{ $faktur->keterangan }}</td>
                                                <td>
                                                    <!-- Tombol View -->
                                                    <a href="{{ route('transaksi-faktur.show', $faktur->nomor_faktur) }}" class="btn btn-info btn-sm">View</a>
                                                    <!-- Tombol Edit -->
                                                    <button class="btn btn-warning btn-sm edit-btn" data-id="{{ $faktur->nomor_faktur }}" data-pembeli="{{ $faktur->pembeli }}" data-tgl-jual="{{ $faktur->tgl_jual }}" data-petugas="{{ $faktur->petugas }}" data-keterangan="{{ $faktur->keterangan }}">Edit</button>
                                                    <form action="{{ route('transaksi-faktur.delete', $faktur->nomor_faktur) }}" method="POST" class="d-inline delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Faktur</th>
                                                <th>jumlah Barang</th>
                                                <th>Total Harga</th>
                                                <th>Petugas</th>
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

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Faktur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editNomorFaktur" name="nomor_faktur">
                        <div class="mb-3">
                            <label for="editPembeli" class="form-label">Pembeli</label>
                            <input type="text" class="form-control" id="editPembeli" name="pembeli" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTglJual" class="form-label">Tgl Faktur</label>
                            <input type="date" class="form-control" id="editTglJual" name="tgl_jual" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPetugas" class="form-label">Petugas</label>
                            <input type="text" class="form-control" id="editPetugas" name="petugas" required>
                        </div>
                        <div class="mb-3">
                            <label for="editKeterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="editKeterangan" name="keterangan" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editButtons = document.querySelectorAll('.edit-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const editForm = document.getElementById('editForm');
            const editNomorFaktur = document.getElementById('editNomorFaktur');
            const editPembeli = document.getElementById('editPembeli');
            const editTglJual = document.getElementById('editTglJual');
            const editPetugas = document.getElementById('editPetugas');
            const editKeterangan = document.getElementById('editKeterangan');

            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Ambil data dari button
                    const nomorFaktur = button.dataset.id;
                    const pembeli = button.dataset.pembeli;
                    const tglJual = button.dataset.tglJual;
                    const petugas = button.dataset.petugas;
                    const keterangan = button.dataset.keterangan;

                    // Isi form modal dengan data
                    editNomorFaktur.value = nomorFaktur;
                    editPembeli.value = pembeli;
                    editTglJual.value = tglJual;
                    editPetugas.value = petugas;
                    editKeterangan.value = keterangan;

                    // Update action form dan tampilkan modal
                    editForm.action = `/transaksi-faktur/update/${nomorFaktur}`;
                    editModal.show();
                });
            });

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