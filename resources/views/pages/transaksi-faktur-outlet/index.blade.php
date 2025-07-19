@extends('layouts.main')

@section('title', 'Transaksi Faktur')
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
                            <div class="card-header">
                                <form action="{{ route('transaksi-faktur-outlet.index') }}" method="GET">
                                    <div class="row">
                                        @if($roleUser == 'admin')
                                        <div class="col-md-3">
                                            <label for="kode_faktur">Gudang</label>
                                            <select name="kode_faktur" class="form-control">
                                                <option value="">-- Semua gudang --</option>
                                                <option value="O-JK" {{ request('kode_faktur') == 'O-JK' ? 'selected' : '' }}>Joko Cell</option>
                                                <option value="O-AD" {{ request('kode_faktur') == 'O-AD' ? 'selected' : '' }}>Adit Cell</option>
                                                <option value="O-PY" {{ request('kode_faktur') == 'O-PY' ? 'selected' : '' }}>Popeye Cell</option>
                                            </select>
                                        </div>
                                        @endif
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
                                        <div class="col-md-3">
                                            <label for="cek">Cek</label>
                                            <select name="cek" class="form-control">
                                                <option value="">-- Semua cek --</option>
                                                <option value="Sudah_Dicek" {{ request('cek') == 'Sudah_Dicek' ? 'selected' : '' }}>Sudah Dicek</option>
                                                <option value="Belum_Dicek" {{ request('cek') == 'Belum_Dicek' ? 'selected' : '' }}>Belum Dicek</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('transaksi-faktur-outlet.index') }}" class="btn btn-secondary mx-2">Reset</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Cek</th>
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Faktur</th>
                                                <th>jumlah Barang</th>
                                                <th>Total Harga</th>
                                                <th>Sudah Dibayar</th>
                                                <th>Pembayaran</th>
                                                <th>Petugas</th>
                                                <th>Grade</th>
                                                <th>Keterangan</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($fakturs as $faktur)
                                            <tr>
                                                <td>
                                                    @if ($faktur->is_finish == 0)
                                                        @if($roleUser == 'admin')
                                                            <form action="{{ route('transaksi-faktur-outlet.tandai-sudah-dicek', $faktur->id) }}" method="POST" class="d-inline finish-form">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-primary btn-sm finish-btn">Tandai Dicek</button>
                                                            </form>
                                                        @else
                                                        <span class="badge bg-warning">Belum Dicek</span>
                                                        @endif
                                                    @else
                                                        @if($roleUser == 'admin')
                                                            <form action="{{ route('transaksi-faktur-outlet.tandai-belum-dicek', $faktur->id) }}" method="POST" class="d-inline back-form">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-warning btn-sm back-btn">Batalkan Dicek</button>
                                                            </form>
                                                        @else
                                                            <span class="badge bg-success">Sudah Dicek</span>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('transaksi-faktur-outlet.show', $faktur->nomor_faktur) }}">
                                                        {{ $faktur->nomor_faktur }}
                                                    </a>
                                                </td>
                                                <td>{{ $faktur->pembeli }}</td>
                                                <td>{{ $faktur->tgl_jual }}</td>
                                                <td>{{ $faktur->total_barang }}</td>
                                                <td>{{ 'Rp. ' . number_format($faktur->total, 0, ',', '.') }}</td>
                                                <td>{{ 'Rp. ' . number_format($faktur->total_nominal, 0, ',', '.') }}</td>
                                                <td>
                                                    @if ($faktur->is_lunas == 0)
                                                        <span class="badge bg-warning">Hutang</span>
                                                    @else
                                                        <span class="badge bg-success">Lunas</span>
                                                    @endif
                                                </td>
                                                <td>{{ $faktur->petugas }}</td>
                                                <td>{{ $faktur->grade }}</td>
                                                <td>{{ $faktur->keterangan }}</td>
                                                <td>
                                                    <!-- Tombol View -->
                                                    <a href="{{ route('transaksi-faktur-outlet.show', $faktur->nomor_faktur) }}" class="btn btn-info btn-sm">View</a>
                                                    @if ($faktur->is_finish==0 || $roleUser == 'admin')
                                                    <!-- Tombol Edit -->
                                                    <button class="btn btn-warning btn-sm edit-btn" data-id="{{ $faktur->id }}" data-nomor_faktur="{{ $faktur->nomor_faktur }}" data-pembeli="{{ $faktur->pembeli }}" data-tgl-jual="{{ $faktur->tgl_jual }}" data-petugas="{{ $faktur->petugas }}" data-keterangan="{{ $faktur->keterangan }}" data-grade="{{ $faktur->grade }}">Edit</button>
                                                    <form action="{{ route('transaksi-faktur-outlet.delete', $faktur->nomor_faktur) }}" method="POST" class="d-inline delete-form">
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
                                                <th>No Faktur</th>
                                                <th>Pembeli</th>
                                                <th>Tgl Faktur</th>
                                                <th>jumlah Barang</th>
                                                <th>Total Harga</th>
                                                <th>Total Bayar</th>
                                                <th>Pembayaran</th>
                                                <th>Petugas</th>
                                                <th>Grade</th>
                                                <th>Keterangan</th>
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
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-3">
                            <label for="editNomorFaktur" class="form-label">Nomor Faktur</label>
                            <input type="text" class="form-control" id="editNomorFaktur" name="nomor_faktur" required>
                        </div>
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
                            <label for="editGrade" class="form-label">Grade</label>
                            <select class="form-control" id="editGrade" name="grade" required>
                                <option value="">Pilih Grade</option>
                                <option value="Barang JB">Barang JB</option>
                                <option value="Barang 2nd">Barang 2nd</option>
                                <option value="Grade B">Grade B</option>
                                <option value="Grade C">Grade C</option>
                                <option value="Batangan">Batangan</option>
                                <option value="Lain Lain">Lain Lain</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editKeterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="editKeterangan" name="keterangan"></textarea>
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
            const editId = document.getElementById('editId');
            const editPembeli = document.getElementById('editPembeli');
            const editTglJual = document.getElementById('editTglJual');
            const editPetugas = document.getElementById('editPetugas');
            const editGrade = document.getElementById('editGrade');
            const editKeterangan = document.getElementById('editKeterangan');

            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Ambil data dari button
                    const id = button.dataset.id;
                    const nomorFaktur = button.dataset.nomor_faktur;
                    const pembeli = button.dataset.pembeli;
                    const tglJual = button.dataset.tglJual;
                    const petugas = button.dataset.petugas;
                    const grade = button.dataset.grade;
                    const keterangan = button.dataset.keterangan;

                    // Isi form modal dengan data
                    editId.value = id;
                    editNomorFaktur.value = nomorFaktur;
                    editPembeli.value = pembeli;
                    editTglJual.value = tglJual;
                    editPetugas.value = petugas;
                    editGrade.value = grade;
                    editKeterangan.value = keterangan;

                    // Update action form dan tampilkan modal
                    editForm.action = `/transaksi-faktur-outlet/update/${id}`;
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

            const finishForms = document.querySelectorAll('.finish-form');
            finishForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault(); // Mencegah submit form langsung
                    if (confirm('Apakah Anda yakin ingin menandai transaksi ini sebagai sudah dicek?')) {
                        form.submit(); // Submit form jika konfirmasi "OK"
                    }
                });
            });

            const backForms = document.querySelectorAll('.back-form');
            backForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault(); // Mencegah submit form langsung
                    if (confirm('Apakah Anda yakin ingin batalkan cek?')) {
                        form.submit(); // Submit form jika konfirmasi "OK"
                    }
                });
            });
        });
    </script>
@endsection()