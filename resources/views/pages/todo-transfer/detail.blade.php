@extends('layouts.main')
@section('title', 'Detail To Do Transfer')
@section('content')
<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-4">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail To Do Transfer</h4>
                            <span>ID Transfer: {{ $todoTransfer->id }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 text-end">
                @if(!in_array($todoTransfer->status, [3, 5])) 
                    @if($todoTransfer->status == 0 || $todoTransfer->status == 2)
                        <!-- Tombol Kirim -->
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
                        <form action="{{ route('todo-transfer.updateStatus', ['id' => $todoTransfer->id, 'status' => 1]) }}" method="POST" class="d-inline confirm-form">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-success">Kirim</button>
                        </form>
                    @elseif($todoTransfer->status == 1)
                        @if($roleUser=='admin')
                            <!-- Tombol Revisi, Tolak, Proses Transfer, dan Sudah Ditransfer -->
                            <form action="{{ route('todo-transfer.updateStatus', ['id' => $todoTransfer->id, 'status' => 2]) }}" method="POST" class="d-inline confirm-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-warning">Revisi</button>
                            </form>

                            <form action="{{ route('todo-transfer.updateStatus', ['id' => $todoTransfer->id, 'status' => 3]) }}" method="POST" class="d-inline confirm-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-danger">Tolak</button>
                            </form>

                            <form action="{{ route('todo-transfer.updateStatus', ['id' => $todoTransfer->id, 'status' => 4]) }}" method="POST" class="d-inline confirm-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-info">Proses Transfer</button>
                            </form>
                        @endif
                    @elseif($todoTransfer->status == 4)
                        @if($roleUser=='admin')
                            <form action="{{ route('todo-transfer.updateStatus', ['id' => $todoTransfer->id, 'status' => 5]) }}" method="POST" class="d-inline confirm-form">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success">Sudah Ditransfer</button>
                            </form>
                        @endif
                    @endif
                @endif
                    <a href="{{ route('todo-transfer.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="card">
                <div class="card-header">
                    <h5>Informasi Transfer</h5>
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
                            <tr><th>Bank</th><td>{{ $todoTransfer->bank }}</td></tr>
                            <tr><th>No Rekening</th><td>{{ $todoTransfer->no_rek }}</td></tr>
                            <tr><th>Nama Rekening</th><td>{{ $todoTransfer->nama_rek }}</td></tr>
                            <tr><th>Nominal</th><td>Rp{{ number_format($todoTransfer->nominal, 0, ',', '.') }}</td></tr>
                            <tr><th>Keterangan</th><td>{{ $todoTransfer->keterangan }}</td></tr>
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
                                    <span class="badge bg-info text-dark">Proses Transfer</span>
                                @elseif($todoTransfer->status == 5)
                                    <span class="badge bg-success">Sudah Ditransfer</span>
                                @endif
                                </td>
                            </tr>
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
                                        <img src="{{ asset('storage/' . $bukti->foto) }}" alt="Bukti Transfer" class="img-thumbnail" style="width: 150px; height: auto;">
                                    </a>
                                </td>
                                @if($todoTransfer->status == 0 || $todoTransfer->status == 2)
                                <td>
                                    <form action="{{ route('todo-transfer.bukti.delete', $bukti->id) }}" method="POST">
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
            <form action="{{ route('todo-transfer.update', $todoTransfer->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit To Do Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="tgl">Tanggal</label>
                    <input type="date" name="tgl" class="form-control" value="{{ $todoTransfer->tgl }}">
                    <label for="kode_lok">Kode Lokasi</label>
                    <input type="text" name="kode_lok" class="form-control" value="{{ $todoTransfer->kode_lok }}">
                    <label for="nama_toko">Nama Toko</label>
                    <input type="text" name="nama_toko" class="form-control" value="{{ $todoTransfer->nama_toko }}">
                    <label for="bank">Bank</label>
                    <input type="text" name="bank" class="form-control" value="{{ $todoTransfer->bank }}">
                    <label for="no_rek">No Rekening</label>
                    <input type="text" name="no_rek" class="form-control" value="{{ $todoTransfer->no_rek }}">
                    <label for="nama_rek">Nama Rekening</label>
                    <input type="text" name="nama_rek" class="form-control" value="{{ $todoTransfer->nama_rek }}">
                    <label for="nominal">Nominal</label>
                    <input type="number" name="nominal" class="form-control" value="{{ $todoTransfer->nominal }}">
                    <label for="keterangan">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" value="{{ $todoTransfer->keterangan }}">
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
            <form action="{{ route('todo-transfer.bukti.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Bukti Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="adm_todo_tf_id" value="{{ $todoTransfer->id }}">
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

