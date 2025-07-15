@extends('layouts.main')

@section('title', 'Detail Faktur Outlet')
@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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
                <div class="col-lg-4 text-end">
                    @if($roleUser=='admin' && $faktur->is_finish==0)
                        <form action="{{ route('transaksi-faktur-outlet.tandai-sudah-dicek', $faktur->id) }}" method="POST" class="d-inline finish-form">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-primary finish-btn">Tandai Dicek</button>
                        </form>
                    @endif
                    <a href="{{ route('transaksi-faktur-outlet.print', $faktur->nomor_faktur) }}" class="btn btn-primary" target="_blank">Print PDF</a>
                    <a href="{{ route('transaksi-faktur-outlet.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>

        <div class="page-body">
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
            @if(session('errors') && session('errors')->any())
                 <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul>
                        @foreach (session('errors')->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h5>Informasi Faktur</h5>
                </div>
                <div class="card-block">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <th width="30%">No Faktur</th>
                                <td>{{ $faktur->nomor_faktur }}</td>
                            </tr>
                            <tr>
                                <th>Pembeli</th>
                                <td>{{ $faktur->pembeli }}</td>
                            </tr>
                            <tr>
                                <th>Tanggal Jual</th>
                                <td>{{ \Carbon\Carbon::parse($faktur->tgl_jual)->translatedFormat('d F Y') }}</td>
                            </tr>
                            <tr>
                                <th>Petugas</th>
                                <td>{{ $faktur->petugas }}</td>
                            </tr>
                            <tr>
                                <th>Total Harga</th>
                                <td>Rp. {{ number_format($faktur->total, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Total Bayar</th>
                                <td>Rp. {{ number_format($totalNominal, 0, ',', '.') }}</td>
                            </tr>
                            @php
                                $sisa = $faktur->total - $totalNominal;
                            @endphp
                            <tr>
                                <th>
                                    @if ($sisa < 0)
                                        Lebih Bayar
                                    @else
                                        Sisa Hutang
                                    @endif
                                </th>
                                <td>
                                    @if ($sisa < 0)
                                        <span style="color: green; font-weight: bold;">
                                            Rp. {{ number_format(abs($sisa), 0, ',', '.') }}
                                        </span>
                                    @elseif ($sisa > 0)
                                        <span style="color: red; font-weight: bold;">
                                            Rp. {{ number_format($sisa, 0, ',', '.') }}
                                        </span>
                                    @else
                                        Rp. {{ number_format($sisa, 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Pembayaran</th>
                                <td>
                                    @if ($faktur->is_lunas == 0)
                                        <span class="badge bg-warning">Hutang</span>
                                    @else
                                        <span class="badge bg-success">Lunas</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Keterangan</th>
                                <td>{{ $faktur->keterangan }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>List Bukti Transfer</h5>
                    @if($faktur->is_finish == 0 || $faktur->is_lunas == 0)
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBuktiModal">Tambah Bukti</button>
                    @endif
                </div>
                <div class="card-block table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Keterangan</th>
                                <th>Nominal</th>
                                <th>Foto</th>
                                @if($faktur->is_finish == 0 || $faktur->is_lunas == 0)
                                <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($faktur->bukti as $bukti)
                            <tr>
                                <td>{{ $bukti->keterangan }}</td>
                                <td>Rp. {{ number_format($bukti->nominal, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ asset('storage/' . $bukti->foto) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $bukti->foto) }}" alt="Bukti Transfer" class="img-thumbnail" style="width: 150px; height: auto;">
                                    </a>
                                </td>
                                @if($faktur->is_finish == 0 || $faktur->is_lunas == 0)
                                <td>
                                    <form action="{{ route('transaksi-faktur-outlet.bukti.delete', $bukti->id) }}" method="POST" class="delete-form">
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

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>Daftar Barang</h5>
                    @if($roleUser=='admin')
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBarangModal">Tambah Barang</button>
                    @endif
                </div>
                <div class="card-block table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lokasi SPK</th>
                                <th>Tipe Barang</th>
                                <th>Grade</th>
                                <th>Harga</th>
                                @if($roleUser=='admin')
                                    <th>Harga Acc Negoan</th>
                                    <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaksiJuals as $index => $transaksi)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $transaksi->lok_spk }}</td>
                                <td>{{ $transaksi->barang->tipe ?? '-' }}</td>
                                <td>{{ $faktur->grade ?? '-' }}</td>
                                <td>Rp. {{ number_format($transaksi->harga, 0, ',', '.') }}</td>
                                @if($roleUser=='admin')
                                <td>Rp. {{ number_format($transaksi->harga_acc, 0, ',', '.') }}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-btn" data-id="{{ $transaksi->id }}" data-lok_spk="{{ $transaksi->lok_spk }}" data-harga="{{ $transaksi->harga / 1000 }}">Edit</button>
                                    <form action="{{ route('transaksi-jual-outlet.destroy', $transaksi->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                                @endif
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

<div class="modal fade" id="addBarangModal" tabindex="-1" aria-labelledby="addBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('transaksi-jual-outlet.addbarang') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addBarangModalLabel">Tambah Barang Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="nomor_faktur" value="{{ $faktur->nomor_faktur }}">
                    <div class="mb-3">
                        <label for="addLokSpk" class="form-label">LOK SPK</label>
                        <input type="text" class="form-control" id="addLokSpk" name="lok_spk" required>
                    </div>
                    <div class="mb-3">
                        <label for="addHarga" class="form-label">Harga (input dalam ribuan, cth: 50 untuk 50.000)</label>
                        <input type="number" class="form-control" id="addHarga" name="harga" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Barang</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Harga</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editTransaksiId" name="id">
                    <div class="mb-3">
                        <label for="editTransaksiLokSpk" class="form-label">LOK SPK</label>
                        <input type="text" class="form-control" id="editTransaksiLokSpk" name="lok_spk" required>
                    </div>
                    <div class="mb-3">
                        <label for="editHarga" class="form-label">Harga (input dalam ribuan, cth: 50 untuk 50.000)</label>
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

<div class="modal fade" id="addBuktiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('transaksi-faktur-outlet.bukti.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Bukti Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="t_faktur_id" value="{{ $faktur->id }}">
                    <input type="text" class="form-control mb-2" name="keterangan" placeholder="Keterangan" required>
                    <input type="number" class="form-control mb-2" id="nominal" name="nominal" placeholder="Nominal Transfer" required>
                    <small class="form-text text-muted" id="nominal_display">Rp. 0</small>
                    <input type="file" class="form-control mt-2" name="foto" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Tambah Bukti</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script untuk format nominal di modal bukti
    $(document).ready(function() {
        $('#nominal').on('input', function() {
            const value = $(this).val();
            const formattedValue = 'Rp. ' + new Intl.NumberFormat('id-ID').format(value || 0);
            $('#nominal_display').text(formattedValue);
        });
    });

    // Script untuk modal dan konfirmasi delete
    document.addEventListener('DOMContentLoaded', function () {
        // Edit Modal Logic
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('editTransaksiId').value = button.dataset.id;
                document.getElementById('editTransaksiLokSpk').value = button.dataset.lok_spk;
                document.getElementById('editHarga').value = button.dataset.harga;
                document.getElementById('editForm').action = `/transaksi-jual-outlet/${button.dataset.id}`;
                editModal.show();
            });
        });

        // Delete Confirmation Logic
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!confirm('Yakin ingin menghapus data ini?')) {
                    e.preventDefault();
                }
            });
        });

        // Add Barang Modal Trigger
        const addBarangBtn = document.getElementById('addBarangBtn');
        if (addBarangBtn) {
            const addBarangModal = new bootstrap.Modal(document.getElementById('addBarangModal'));
            addBarangBtn.addEventListener('click', () => addBarangModal.show());
        }

        // Finish Confirmation
        const finishForm = document.querySelector('.finish-form');
        if(finishForm) {
            finishForm.addEventListener('submit', function(event) {
                if (!confirm('Apakah Anda yakin ingin menandai transaksi ini sebagai sudah dicek?')) {
                    event.preventDefault();
                }
            });
        }
    });
</script>

@endsection