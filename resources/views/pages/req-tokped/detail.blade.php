@extends('layouts.main')
@section('title', 'Detail Request Tokped')
@section('content')
<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-4">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail Request Tokped</h4>
                            <span>ID Request: {{ $todoTransfer->id }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 text-end">
                @if(!in_array($todoTransfer->status, [3, 5])) 
                    @if($todoTransfer->status == 0 || $todoTransfer->status == 2)
                        <!-- Tombol Kirim -->
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
                        <form action="{{ route('req-tokped.updateStatus', ['id' => $todoTransfer->id, 'status' => 1]) }}" method="POST" class="d-inline confirm-form">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-success">Kirim</button>
                        </form>
                    @elseif($todoTransfer->status == 1)
                        @if($roleUser=='admin')
                            <!-- Tombol Revisi, Tolak, Proses Transfer, dan Sudah Ditransfer -->
                            <form action="{{ route('req-tokped.updateStatus', ['id' => $todoTransfer->id, 'status' => 2]) }}" method="POST" class="d-inline confirm-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-warning">Revisi</button>
                            </form>

                            <form action="{{ route('req-tokped.updateStatus', ['id' => $todoTransfer->id, 'status' => 3]) }}" method="POST" class="d-inline confirm-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-danger">Tolak</button>
                            </form>

                            <form action="{{ route('req-tokped.updateStatus', ['id' => $todoTransfer->id, 'status' => 4]) }}" method="POST" class="d-inline confirm-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-info">Proses Tokped</button>
                            </form>
                        @endif
                    @elseif($todoTransfer->status == 4)
                        @if($roleUser=='admin')
                            <form action="{{ route('req-tokped.updateStatus', ['id' => $todoTransfer->id, 'status' => 5]) }}" method="POST" class="d-inline confirm-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success">Sudah Diterima</button>
                            </form>
                        @endif
                    @endif
                @endif
                    <a href="{{ route('req-tokped.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="card">
                <div class="card-header">
                    <h5>Informasi Request Tokped</h5>
                </div>
                <div class="card-block">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Tanggal</th>
                                <td>{{ \Carbon\Carbon::parse($todoTransfer->tgl)->translatedFormat('d F Y') }}</td>
                            </tr>
                            <tr><th>Kode Lokasi</th><td>{{ $todoTransfer->kode_lok }}</td></tr>
                            <tr><th>Nama Toko</th><td>{{ $todoTransfer->nama_toko }}</td></tr>
                            <tr><th>Alasan</th><td>{{ $todoTransfer->alasan }}</td></tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                @if($todoTransfer->status == 0)
                                    <span class="badge bg-secondary">Draft</span>
                                @elseif($todoTransfer->status == 1)
                                    <span class="badge bg-primary">Terkirim</span>
                                @elseif($todoTransfer->status == 2)
                                    <span class="badge bg-warning text-dark">Revisi</span>
                                @elseif($todoTransfer->status == 3)
                                    <span class="badge bg-danger">Ditolak</span>
                                @elseif($todoTransfer->status == 4)
                                    <span class="badge bg-info text-dark">Proses Tokped</span>
                                @elseif($todoTransfer->status == 5)
                                    <span class="badge bg-success">Sudah Diterima</span>
                                @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>List Barang</h5>
                    @if($todoTransfer->status == 0 || $todoTransfer->status == 2)
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">Tambah Barang</button>
                    @endif
                </div>
                <div class="card-block table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Lok</th>
                                <th>Barang</th>
                                <th>Lain2</th>
                                <th>Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todoTransfer->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->kode_lok }}</td>
                                <td>{{ $item->item->name }}</td>
                                <td>{{ $item->nama_barang ?? "-" }}</td>
                                <td>{{ $item->quantity }}</td>
                                @if($roleUser=='admin')
                                    @if($todoTransfer->status == 1 || $todoTransfer->status == 4)
                                        <td>
                                            <!-- Tombol Edit -->
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editItemModal{{ $item->id }}">Edit</button>

                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('req-tokped.item.delete', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    @else
                                    <td>  </td>
                                    @endif
                                @else
                                    @if($todoTransfer->status == 0 || $todoTransfer->status == 2)
                                    <td>
                                        <!-- Tombol Edit -->
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editItemModal{{ $item->id }}">Edit</button>

                                        <!-- Tombol Hapus -->
                                        <form action="{{ route('req-tokped.item.delete', $item->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                    @else
                                    <td>  </td>
                                    @endif
                                @endif
                            </tr>

                            <!-- Modal Edit Item -->
                            <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('req-tokped.item.update', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Item</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">

                                                <label for="nama_barang">Nama Barang</label>
                                                <input type="text" class="form-control" name="nama_barang" value="{{ $item->item->name ?? '-' }}" readonly>
                                                
                                                <label for="lain_lain">Lain Lain</label>
                                                <input type="text" class="form-control" name="lain_lain" value="{{ $item->nama_barang ?? '-' }}" readonly>

                                                <label for="quantity">Jumlah Barang</label>
                                                <input type="number" class="form-control" name="quantity" value="{{ $item->quantity }}" required>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>List Bukti Transfer</h5>
                    @if($todoTransfer->status == 0 || $todoTransfer->status == 2)
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBuktiModal">Tambah Bukti</button>
                    @endif
                </div>
                <div class="card-block table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Keterangan</th>
                                <th>Foto</th>
                                @if($todoTransfer->status == 0 || $todoTransfer->status == 2)
                                <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todoTransfer->bukti as $bukti)
                            <tr>
                                <td>{{ $bukti->keterangan }}</td>
                                <td>
                                    <a href="{{ asset('storage/' . $bukti->foto) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $bukti->foto) }}" alt="Bukti Request" class="img-thumbnail" style="width: 150px; height: auto;">
                                    </a>
                                </td>
                                @if($todoTransfer->status == 0 || $todoTransfer->status == 2)
                                <td>
                                    <form action="{{ route('req-tokped.bukti.delete', $bukti->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
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

<!-- Modal Edit Transfer -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('req-tokped.update', $todoTransfer->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Request Tokped</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="tgl">Tanggal</label>
                    <input type="date" name="tgl" class="form-control" value="{{ $todoTransfer->tgl }}">
                    <label for="kode_lok">Kode Lokasi</label>
                    <input type="text" name="kode_lok" class="form-control" value="{{ $todoTransfer->kode_lok }}">
                    <label for="nama_toko">Nama Toko</label>
                    <input type="text" name="nama_toko" class="form-control" value="{{ $todoTransfer->nama_toko }}">
                    <label for="alasan">Alasan</label>
                    <input type="text" name="alasan" class="form-control" value="{{ $todoTransfer->alasan }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Bukti -->
<div class="modal fade" id="addBuktiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('req-tokped.bukti.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Bukti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="adm_req_tokped_id" value="{{ $todoTransfer->id }}">
                    <input type="text" class="form-control mb-2" name="keterangan" placeholder="Keterangan" required>
                    <input type="file" class="form-control" name="foto" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah Bukti</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Item -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('req-tokped.item.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Item Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="adm_req_tokped_id" value="{{ $todoTransfer->id }}">

                    <div class="mb-3" id="kodeLok">
                        <label for="kode_lok" class="form-label">Kode Lokasi / Kode Toko (angka)</label>
                        <input type="number" class="form-control" name="kode_lok" id="kode_lok" placeholder="Masukkan Kode Lokasi / Kode Toko (Angka)">
                    </div>

                    <!-- Pilih Item -->
                    <div class="mb-3">
                        <label for="adm_item_tokped_id" class="form-label">Pilih Item</label>
                        <select name="adm_item_tokped_id" id="adm_item_tokped_id" class="form-select" required>
                            <option value="">-- Pilih Item --</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Input Nama Barang (Hidden by Default) -->
                    <div class="mb-3" id="namaBarangContainer" style="display: none;">
                        <label for="nama_barang" class="form-label">Nama Barang</label>
                        <input type="text" class="form-control" name="nama_barang" id="nama_barang" placeholder="Masukkan Nama Barang">
                    </div>

                    <!-- Jumlah Barang -->
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Jumlah Barang</label>
                        <input type="number" class="form-control" name="quantity" placeholder="Quantity" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const confirmForms = document.querySelectorAll('.confirm-form');
        confirmForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                let button = form.querySelector('button');
                let actionText = button.innerText;
                if (confirm(`Apakah Anda yakin ingin mengubah status menjadi "${actionText}"?`)) {
                    form.submit();
                }
            });
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        let selectItem = document.getElementById("adm_item_tokped_id");
        let namaBarangContainer = document.getElementById("namaBarangContainer");
        let namaBarangInput = document.getElementById("nama_barang");

        selectItem.addEventListener("change", function () {
            if (this.value == "1") {
                namaBarangContainer.style.display = "block";
                namaBarangInput.setAttribute("required", "required");
            } else {
                namaBarangContainer.style.display = "none";
                namaBarangInput.removeAttribute("required");
                namaBarangInput.value = null;
            }
        });
    });
</script>

