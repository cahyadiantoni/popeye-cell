@extends('layouts.main')

@section('title', 'Detail Kirim')
@section('content')
<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail Kirim</h4>
                            <span>ID Kirim: {{ $kirim->id }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    <a href="{{ route('kirim-barang.print', $kirim->id) }}" class="btn btn-primary" target="_blank">Print PDF</a>
                    <a href="{{ route('kirim-barang.index') }}" class="btn btn-secondary">Kembali</a>
                    @if($kirim->status == 0)
                        <button class="btn btn-success" id="addBarangBtn">Add Barang</button>
                    @endif
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

            <!-- Informasi Kirim Barang -->
            <div class="card">
                <div class="card-header">
                    <h5>Informasi Kirim Barang</h5>
                </div>
                <div class="card-block">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th width="30%">ID Kirim</th>
                            <td>{{ $kirim->id }}</td>
                        </tr>
                        <tr>
                            <th>User Pengirim</th>
                            <td>{{ $kirim->pengirimUser->name }}</td>
                        </tr>
                        <tr>
                            <th>Gudang Pengirim</th>
                            <td>{{ $kirim->pengirimGudang->nama_gudang ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>User Penerima</th>
                            <td>{{ $kirim->penerimaUser->name }}</td>
                        </tr>
                        <tr>
                            <th>Gudang Penerima</th>
                            <td>{{ $kirim->penerimaGudang->nama_gudang }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Kirim</th>
                            <td>{{ $kirim->dt_kirim }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Terima</th>
                            <td>{{ $kirim->dt_terima }}</td>
                        </tr>
                        <tr>
                            <th>Jumlah Barang</th>
                            <td>{{ $jumlahBarang }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
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
                        </tr>
                        <tr>
                            <th>Keterangan</th>
                            <td>{{ $kirim->keterangan }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Tabel Barang -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Daftar Barang</h5>
                    <!-- Tombol Download Excel -->
                    <a href="{{ route('terima-barang.export', ['id' => $kirim->id]) }}" class="btn btn-success btn-sm">Download Excel</a>
                </div>
                <div class="card-block table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lok SPK</th>
                                <th>Tipe Barang</th>
                                <th>Kelengkapan</th>
                                @if($kirim->status == 0)
                                <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kirimBarangs as $index => $barang)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $barang->lok_spk }}</td>
                                <td>{{ $barang->barang->tipe ?? '-' }}</td>
                                <td>{{ $barang->barang->kelengkapan ?? '-' }}</td>
                                @if($kirim->status == 0)
                                <td>
                                    <form action="{{ route('kirim-barang.deletebarang', $barang->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                    </form>
                                </td>
                                @endif
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
            <form action="{{ route('kirim-barang.addbarang') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addBarangModalLabel">Add Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <a href="{{ asset('files/template kirim barang.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                    </div>
                    <div class="mb-3">
                        <label for="fileExcel" class="form-label">Upload File Excel</label>
                        <input type="file" class="form-control" id="filedata" name="filedata" required>
                        <input type="hidden" class="form-control" id="kirim_id" name="kirim_id" value="<?= $kirim->id ?>" required>
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

        const addBarangBtn = document.getElementById('addBarangBtn');
        const addBarangModal = new bootstrap.Modal(document.getElementById('addBarangModal'));
        addBarangBtn.addEventListener('click', () => {
            addBarangModal.show();
        });
    });
</script>

@endsection
