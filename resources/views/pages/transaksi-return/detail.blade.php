@extends('layouts.main')

@section('title', 'Detail Return')
@section('content')
<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail Return</h4>
                            <span>Nomor Return: {{ $return->nomor_return }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <a href="{{ route('transaksi-return.index') }}" class="btn btn-secondary">Kembali</a>
                    <button class="btn btn-success" id="addBarangBtn">Add Barang</button>
                </div>
            </div>
        </div>

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

            <!-- Informasi Return -->
            <div class="card">
                <div class="card-header">
                    <h5>Informasi Return</h5>
                </div>
                <div class="card-block">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th width="30%">No Return</th>
                            <td>{{ $return->nomor_return }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Return</th>
                            <td>{{ \Carbon\Carbon::parse($return->tgl_return)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <th>Petugas</th>
                            <td>{{ $return->user->name }}</td>
                        </tr>
                        <tr>
                            <th>Total Barang</th>
                            <td>{{ $return->total_barang }}</td>
                        </tr>
                        <tr>
                            <th>Total Harga</th>
                            <td>Rp. {{ number_format($return->total_harga, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Keterangan</th>
                            <td>{{ $return->keterangan }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Tabel Barang -->
            <div class="card">
                <div class="card-header">
                    <h5>Daftar Barang</h5>
                </div>
                <div class="card-block table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lok SPK</th>
                                <th>Tipe Barang</th>
                                <th>Harga</th>
                                <th>Pedagang</th>
                                <th>Alasan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($returnBarangs as  $return)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $return->lok_spk }}</td>
                                <td>{{ $return->barang->tipe ?? '-' }}</td>
                                <td>Rp. {{ number_format($return->harga, 0, ',', '.') }}</td>
                                <td>{{ $return->pedagang ?? '-' }}</td>
                                <td>{{ $return->alasan ?? '-' }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn" data-id="{{ $return->id }}" data-lok_spk="{{ $return->lok_spk }}" data-harga="{{ $return->harga }}" data-alasan="{{ $return->alasan }}">Edit</button>
                                    <form action="{{ route('transaksi-return-barang.delete', $return->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Barang -->
<div class="modal fade" id="addBarangModal" tabindex="-1" aria-labelledby="addBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('transaksi-return-barang.addbarang') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addBarangModalLabel">Add Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <a href="{{ asset('files/template return.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                    </div>
                    <div class="mb-3">
                        <label for="fileExcel" class="form-label">Upload File Excel</label>
                        <input type="file" class="form-control" id="filedata" name="filedata" required>
                        <input type="hidden" class="form-control" id="t_return_id" name="t_return_id" value="<?= $return->t_return_id ?>" required>
                        <input type="hidden" class="form-control" id="petugas" name="petugas" value="<?= $return->return->user->name ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Barang -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Harga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editTransaksiId" class="form-label">LOK SPK</label>
                        <input type="hidden" class="form-control" id="editTransaksiId" name="id" required readonly>
                        <input type="text" class="form-control" id="editTransaksiLokSpk" name="lok_spk" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editHarga" class="form-label">Harga</label>
                        <input type="number" class="form-control" id="editHarga" name="harga" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPedagang" class="form-label">Pedagang</label>
                        <input type="text" class="form-control" id="editPedagang" name="pedagang" required>
                    </div>
                    <div class="mb-3">
                        <label for="editAlasan" class="form-label">Alasan</label>
                        <input type="text" class="form-control" id="editAlasan" name="alasan" required>
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
        const editTransaksiId = document.getElementById('editTransaksiId');
        const editTransaksiLokSpk = document.getElementById('editTransaksiLokSpk');
        const editHarga = document.getElementById('editHarga');
        const editPedagang = document.getElementById('editPedagang');
        const editAlasan = document.getElementById('editAlasan');

        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const returnId = button.dataset.id;
                const returnLokSpk = button.dataset.lok_spk;
                const harga = button.dataset.harga;
                const pedagang = button.dataset.pedagang;
                const alasan = button.dataset.alasan;

                editTransaksiId.value = returnId;
                editTransaksiLokSpk.value = returnLokSpk;
                editHarga.value = harga;
                editPedagang.value = pedagang;
                editAlasan.value = alasan;

                editForm.action = '{{ route("transaksi-return-barang.update") }}';
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

        const addBarangBtn = document.getElementById('addBarangBtn');
        const addBarangModal = new bootstrap.Modal(document.getElementById('addBarangModal'));
        addBarangBtn.addEventListener('click', () => {
            addBarangModal.show();
        });
    });
</script>

@endsection
