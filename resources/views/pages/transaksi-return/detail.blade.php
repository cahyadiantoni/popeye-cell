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
                                    <button class="btn btn-warning btn-sm edit-btn" 
                                        data-id="{{ $return->id }}" 
                                        data-lok_spk="{{ $return->lok_spk }}" 
                                        data-harga="{{ $return->harga }}" 
                                        data-pedagang="{{ $return->pedagang }}" 
                                        data-alasan="{{ $return->alasan }}"
                                        data-jenis="{{ $return->barang->jenis ?? '' }}"
                                        data-tipe="{{ $return->barang->tipe ?? '' }}"
                                        data-kelengkapan="{{ $return->barang->kelengkapan ?? '' }}"
                                        data-grade="{{ $return->barang->grade ?? '' }}">
                                        Edit
                                    </button>
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
            <form id="editForm" action="{{ route('transaksi-return-barang.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Barang Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editTransaksiId" name="id" required>
                    
                    <div class="mb-3">
                        <label for="editTransaksiLokSpk" class="form-label">LOK SPK</label>
                        <input type="text" class="form-control" id="editTransaksiLokSpk" name="lok_spk" required>
                    </div>

                    {{-- KUMPULAN FIELD UNTUK BARANG BARU, AWALNYA DISEMBUNYIKAN --}}
                    <div id="newBarangFields" style="display: none; border-left: 3px solid #007bff; padding-left: 15px; background-color: #f8f9fa;">
                        <p class="text-primary fw-bold mt-2">Data Barang Baru</p>
                        <div class="mb-3">
                            <label for="editJenis" class="form-label">Jenis</label>
                            <input type="text" class="form-control" id="editJenis" name="jenis">
                        </div>
                        <div class="mb-3">
                            <label for="editTipe" class="form-label">Tipe</label>
                            <input type="text" class="form-control" id="editTipe" name="tipe">
                        </div>
                         <div class="mb-3">
                            <label for="editKelengkapan" class="form-label">Kelengkapan</label>
                            <input type="text" class="form-control" id="editKelengkapan" name="kelengkapan">
                        </div>
                         <div class="mb-3">
                            <label for="editGrade" class="form-label">Grade</label>
                            <input type="text" class="form-control" id="editGrade" name="grade">
                        </div>
                    </div>
                    {{-- AKHIR FIELD BARANG BARU --}}

                    <p class="text-primary fw-bold mt-3">Data Return</p>
                    <div class="mb-3">
                        <label for="editHarga" class="form-label">Harga</label>
                        <input type="number" class="form-control" id="editHarga" name="harga" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPedagang" class="form-label">Pedagang</label>
                        <input type="text" class="form-control" id="editPedagang" name="pedagang">
                    </div>
                    <div class="mb-3">
                        <label for="editAlasan" class="form-label">Alasan</label>
                        <input type="text" class="form-control" id="editAlasan" name="alasan">
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
    // === Blok untuk Modal Edit ===
    const editModalEl = document.getElementById('editModal');
    const editModal = new bootstrap.Modal(editModalEl);
    const editForm = document.getElementById('editForm');

    // Ambil semua elemen form
    const editFields = {
        id: document.getElementById('editTransaksiId'),
        lok_spk: document.getElementById('editTransaksiLokSpk'),
        harga: document.getElementById('editHarga'),
        pedagang: document.getElementById('editPedagang'),
        alasan: document.getElementById('editAlasan'),
        jenis: document.getElementById('editJenis'),
        tipe: document.getElementById('editTipe'),
        kelengkapan: document.getElementById('editKelengkapan'),
        grade: document.getElementById('editGrade'),
        newBarangFields: document.getElementById('newBarangFields')
    };

    // Event listener untuk semua tombol edit
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            // Isi form dengan data dari tombol
            editFields.id.value = button.dataset.id;
            editFields.lok_spk.value = button.dataset.lok_spk;
            editFields.harga.value = button.dataset.harga;
            editFields.pedagang.value = button.dataset.pedagang;
            editFields.alasan.value = button.dataset.alasan;

            // Isi field barang baru tapi buat readonly
            editFields.jenis.value = button.dataset.jenis;
            editFields.tipe.value = button.dataset.tipe;
            editFields.kelengkapan.value = button.dataset.kelengkapan;
            editFields.grade.value = button.dataset.grade;

            // Sembunyikan dan disable field baru saat modal pertama kali dibuka
            editFields.newBarangFields.style.display = 'none';
            toggleNewBarangFields(false);

            editModal.show();
        });
    });

    // Fungsi untuk enable/disable & set required pada field barang baru
    function toggleNewBarangFields(enable) {
        const fields = [editFields.jenis, editFields.tipe, editFields.kelengkapan, editFields.grade];
        fields.forEach(field => {
            field.readOnly = !enable;
            if(field.name === 'jenis' || field.name === 'tipe'){
                 field.required = enable;
            }
        });
    }

    // Event listener saat pengguna mengetik di LOK SPK
    let checkTimeout;
    editFields.lok_spk.addEventListener('input', function() {
        clearTimeout(checkTimeout);
        const lokSpkValue = this.value.trim();

        if (lokSpkValue.length < 3) { // Jangan cek jika terlalu pendek
            editFields.newBarangFields.style.display = 'none';
            toggleNewBarangFields(false);
            return;
        }

        // Tunda pengecekan agar tidak terjadi di setiap ketikan
        checkTimeout = setTimeout(() => {
            fetch(`{{ url('/transaksi-return/check-barang') }}/${lokSpkValue}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        // Jika LOK SPK ada, sembunyikan field baru
                        editFields.newBarangFields.style.display = 'none';
                        toggleNewBarangFields(false);
                        // Optional: update field jenis/tipe readonly dengan data yg ada
                        editFields.jenis.value = data.data.jenis;
                        editFields.tipe.value = data.data.tipe;
                    } else {
                        // Jika LOK SPK TIDAK ADA, tampilkan field baru & aktifkan
                        editFields.newBarangFields.style.display = 'block';
                        toggleNewBarangFields(true);
                        // Kosongkan field agar user bisa isi
                        editFields.jenis.value = '';
                        editFields.tipe.value = '';
                        editFields.kelengkapan.value = '';
                        editFields.grade.value = '';
                    }
                });
        }, 500); // Jeda 500ms
    });


    // === Blok untuk Modal Add & Delete (tetap sama) ===
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (confirm('Yakin ingin menghapus data ini?')) {
                form.submit();
            }
        });
    });

    const addBarangBtn = document.getElementById('addBarangBtn');
    if (addBarangBtn) {
        const addBarangModal = new bootstrap.Modal(document.getElementById('addBarangModal'));
        addBarangBtn.addEventListener('click', () => {
            addBarangModal.show();
        });
    }
});
</script>

@endsection
