@extends('layouts.main')

@section('title', 'Detail Faktur')
@section('content')
<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail Faktur</h4>
                            <span>Nomor Faktur: {{ $faktur->nomor_faktur }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <a href="{{ route('transaksi-faktur.print', $faktur->nomor_faktur) }}" class="btn btn-primary" target="_blank">Print PDF</a>
                    <a href="{{ route('transaksi-faktur.index') }}" class="btn btn-secondary">Kembali</a>
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

            <!-- Informasi Faktur -->
            <div class="card">
                <div class="card-header">
                    <h5>Informasi Faktur</h5>
                </div>
                <div class="card-block">
                    <p><strong>No Faktur:</strong> {{ $faktur->nomor_faktur }}</p>
                    <p><strong>Pembeli:</strong> {{ $faktur->pembeli }}</p>
                    <p><strong>Tanggal Jual:</strong> {{ \Carbon\Carbon::parse($faktur->tgl_jual)->translatedFormat('d F Y') }}</p>
                    <p><strong>Petugas:</strong> {{ $faktur->petugas }}</p>
                    <p><strong>Total:</strong> Rp. {{ number_format($faktur->total, 0, ',', '.') }}</p>
                    <p><strong>Keterangan:</strong> {{ $faktur->keterangan }}</p>
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
                                <th>Lokasi SPK</th>
                                <th>Tipe Barang</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaksiJuals as $index => $transaksi)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $transaksi->lok_spk }}</td>
                                <td>{{ $transaksi->barang->tipe ?? '-' }}</td>
                                <td>Rp. {{ number_format($transaksi->harga, 0, ',', '.') }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn" data-id="{{ $transaksi->lok_spk }}" data-harga="{{ $transaksi->harga }}">Edit</button>
                                    <form action="{{ route('transaksi-jual.delete', $transaksi->lok_spk) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <h4><strong>Total:</strong> Rp. {{ number_format($faktur->total, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Barang -->
<div class="modal fade" id="addBarangModal" tabindex="-1" aria-labelledby="addBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('transaksi-jual.addbarang') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addBarangModalLabel">Add Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <a href="{{ asset('files/templateJual.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                    </div>
                    <div class="mb-3">
                        <label for="fileExcel" class="form-label">Upload File Excel</label>
                        <input type="file" class="form-control" id="filedata" name="filedata" required>
                        <input type="hidden" class="form-control" id="nomor_faktur" name="nomor_faktur" value="<?= $faktur->nomor_faktur ?>" required>
                        <input type="hidden" class="form-control" id="total" name="total" value="<?= $faktur->total ?>" required>
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
                        <input type="text" class="form-control" id="editTransaksiId" name="lok_spk" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editHarga" class="form-label">Harga</label>
                        <input type="number" class="form-control" id="editHarga" name="harga" required>
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
        const editHarga = document.getElementById('editHarga');

        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const transaksiId = button.dataset.id;
                const harga = button.dataset.harga;

                editTransaksiId.value = transaksiId;
                editHarga.value = harga;

                editForm.action = '{{ route("transaksi-jual.update") }}';
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
