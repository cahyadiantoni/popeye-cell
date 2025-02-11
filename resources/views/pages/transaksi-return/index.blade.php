@extends('layouts.main')

@section('title', 'Transaksi Return')
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
                                <h4>List Transaksi Return</h4>
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
                                        href="#!">Transaksi Return</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

            <!-- Page-body start -->
            <div class="page-body">
                <!-- Pesan Success atau Error -->
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
                        <!-- Zero config.table start -->
                        <div class="card">
                            <div class="card-block">
                                <button type="button" class="btn btn-primary btn-round" id="returnBarangBtn">Return Barang</button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>LOK_SPK</th>
                                                <th>Tipe</th>
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Jual</th>
                                                <th>Tgl Return</th>
                                                <th>Harga Jual</th>
                                                <th>Petugas</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($returns as $return)
                                            <tr>
                                                <td>{{ $return->lok_spk }}</td>
                                                <td>{{ $return->barang->tipe ?? '-' }}</td>
                                                <td>{{ $return->barang->no_faktur ?? '-' }}</td>
                                                <td>{{ $return->barang->faktur->pembel ?? '-'i }}</td>
                                                <td>{{ $return->barang->faktur->tgl_jual ?? '-' }}</td>
                                                <td>{{ $return->tgl_return }}</td>
                                                <td>{{ 'Rp. ' . number_format($return->barang->harga_jual ?? 0, 0, ',', '.') }}</td>
                                                <td>{{ $return->user->name }}</td>
                                                <td>
                                                    <form action="{{ route('transaksi-return.delete', $return->lok_spk) }}" method="POST" class="d-inline delete-form">
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
                                                <th>LOK_SPK</th>
                                                <th>Tipe</th>
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Jual</th>
                                                <th>Tgl Return</th>
                                                <th>Harga Jual</th>
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

    <!-- Modal Return Barang -->
    <div class="modal fade" id="returnBarangModal" tabindex="-1" aria-labelledby="returnBarangModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('transaksi-return.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="returnBarangModalLabel">Return Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    <div class="mb-3">
                        <a href="{{ asset('files/template return barang.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                    </div>
                        <div class="mb-3">
                            <label for="fileBarang" class="form-label">Upload File Return</label>
                            <input type="file" class="form-control" id="fileBarang" name="filedata" required>
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
            const returnBarangBtn = document.getElementById('returnBarangBtn');
            const returnBarangModal = new bootstrap.Modal(document.getElementById('returnBarangModal'));

            returnBarangBtn.addEventListener('click', () => {
                returnBarangModal.show();
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
@endsection
