@extends('layouts.main')

@section('title', 'Detail Faktur Online')
@section('content')
@php use Carbon\Carbon; @endphp
<style>
    table td, table th {
        vertical-align: middle !important;
    }

    table.custom-bordered th,
    table.custom-bordered td {
        border: 2px solid #000 !important; /* hitam dan tebal */
    }
</style>


<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail Faktur Online</h4>
                            <span>Title: {{ $faktur->title }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    @if($roleUser=='admin' && $faktur->is_finish==0)
                        <form action="{{ route('transaksi-faktur-online.tandai-sudah-dicek', $faktur->id) }}" method="POST" class="d-inline finish-form">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-primary finish-btn">Tandai Dicek</button>
                        </form>
                    @endif
                    <a href="{{ route('transaksi-faktur-online.export', $faktur->id) }}" class="btn btn-primary">
                        Export Excel
                    </a>
                    <a href="{{ route('transaksi-faktur-online.print', $faktur->id) }}" class="btn btn-primary" target="_blank">Print PDF</a>
                    <a href="{{ route('transaksi-faktur-online.index') }}" class="btn btn-secondary">Kembali</a>
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
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th width="30%">Judul</th>
                            <td>{{ $faktur->title }}</td>
                        </tr>
                        <tr>
                            <th>Toko</th>
                            <td>{{ $faktur->toko }}</td>
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
                            <th>Total</th>
                            <td>Rp. {{ number_format($faktur->total, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Keterangan</th>
                            <td>{{ $faktur->keterangan }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Tabel Barang -->
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>Daftar Barang</h5>
                    @if($roleUser=='admin' && $faktur->is_finish==0)
                        <button class="btn btn-success" id="addBarangBtn">Add Barang</button>
                    @endif
                </div>
                <div class="card-block table-responsive">
                    <table class="table table-bordered custom-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Invoice</th>
                                <th>Lok SPK</th>
                                <th>Tipe Barang</th>
                                <th>Harga</th>
                                <th>PJ</th>
                                <th>Selisih</th>
                                <th>Uang Masuk</th>
                                <th>Tanggal Masuk</th>
                                <th>Tgl Return</th>
                                @if($roleUser=='admin' && $faktur->is_finish==0)
                                    <th>Aksi</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $invoicesGrouped = $transaksiJuals->groupBy('invoice');
                                $no = 1;
                            @endphp

                            @foreach($invoicesGrouped as $invoice => $items)
                                @php
                                    $rowspan = count($items);
                                    $uangMasuk = $uangMasukPerInvoice[$invoice] ?? null;
                                @endphp

                                @foreach($items as $i => $transaksi)
                                    <tr>
                                        <td>{{ $no++ }}</td>

                                        {{-- Invoice hanya ditampilkan di baris pertama --}}
                                        @if($i === 0)
                                            <td rowspan="{{ $rowspan }}">{{ $invoice }}</td>
                                        @endif

                                        <td>{{ $transaksi->lok_spk }}</td>
                                        <td>{{ $transaksi->barang->tipe ?? '-' }}</td>
                                        <td>Rp. {{ number_format($transaksi->harga, 0, ',', '.') }}</td>
                                        <td>Rp. {{ number_format($transaksi->pj, 0, ',', '.') }}</td>

                                        {{-- Selisih --}}
                                        <td>
                                            @if($transaksi->pj == 0)
                                                <span style="color:black">-</span>
                                            @else
                                                @php $selisih = $transaksi->harga - $transaksi->pj; @endphp
                                                <span style="color:{{ $selisih < 0 ? 'red' : 'green' }}">
                                                    Rp. {{ number_format($selisih, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Uang Masuk & Tanggal hanya di baris pertama invoice --}}
                                        @if($i === 0)
                                            <td rowspan="{{ $rowspan }}">
                                                @if(isset($uangMasukPerInvoice[$invoice]))
                                                    Rp. {{ number_format($uangMasukPerInvoice[$invoice]->total_uang_masuk, 0, ',', '.') }}
                                                @else
                                                    <span style="color: red;">-</span>
                                                @endif
                                            </td>

                                            <td rowspan="{{ $rowspan }}">
                                                @if(isset($uangMasukPerInvoice[$invoice]))
                                                    @php
                                                        $tanggal = \Carbon\Carbon::parse($uangMasukPerInvoice[$invoice]->tanggal_masuk);
                                                        $formattedTanggal = $tanggal->translatedFormat('j F Y');
                                                    @endphp
                                                    {{ $formattedTanggal }}
                                                @else
                                                    <span style="color: red;">Belum ada Invoice</span>
                                                @endif
                                            </td>
                                        @endif

                                        <td>
                                            @if($transaksi->tgl_return)
                                                {{ \Carbon\Carbon::parse($transaksi->tgl_return)->translatedFormat('j F Y') }}
                                            @else
                                                <span style="color: gray;">-</span>
                                            @endif
                                        </td>


                                        {{-- Aksi --}}
                                        @if($roleUser=='admin' && $faktur->is_finish==0)
                                            <td>
                                                <button class="btn btn-warning btn-sm edit-btn"
                                                        data-id="{{ $transaksi->id }}"
                                                        data-lok_spk="{{ $transaksi->lok_spk }}"
                                                        data-invoice="{{ $transaksi->invoice }}"
                                                        data-harga="{{ $transaksi->harga }}"
                                                        data-pj="{{ $transaksi->pj }}">Edit</button>

                                                <form action="{{ route('transaksi-jual-online.delete', $transaksi->id) }}"
                                                    method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @endforeach
                                @php
                                    $totalHarga = $transaksiJuals->sum('harga');
                                    $totalPj = $transaksiJuals->sum('pj');
                                    $totalSelisih = $transaksiJuals->sum(function($item) {
                                        return $item->pj > 0 ? $item->harga - $item->pj : 0;
                                    });

                                    $totalUangMasuk = $uangMasukPerInvoice->sum('total_uang_masuk');
                                @endphp

                                <tr style="background-color: #e0e0e0;">
                                    <td colspan="4" style="text-align: center; font-weight: bold;">TOTAL</td>
                                    <td style="font-weight: bold;">Rp. {{ number_format($totalHarga, 0, ',', '.') }}</td>
                                    <td style="font-weight: bold;">Rp. {{ number_format($totalPj, 0, ',', '.') }}</td>
                                    <td style="font-weight: bold;">Rp. {{ number_format($totalSelisih, 0, ',', '.') }}</td>
                                    <td style="font-weight: bold;">Rp. {{ number_format($totalUangMasuk, 0, ',', '.') }}</td>
                                </tr>
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
            <form action="{{ route('transaksi-jual-online.addbarang') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addBarangModalLabel">Add Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <a href="{{ asset('files/template jual online.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                    </div>
                    <div class="mb-3">
                        <label for="fileExcel" class="form-label">Upload File Excel</label>
                        <input type="file" class="form-control" id="filedata" name="filedata" required>
                        <input type="hidden" class="form-control" id="faktur_online_id" name="faktur_online_id" value="<?= $faktur->id ?>" required>
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
                    <h5 class="modal-title" id="editModalLabel">Edit Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editTransaksiId" class="form-label">LOK SPK</label>
                        <input type="text" class="form-control" id="editTransaksiLokSpk" name="lok_spk" required readonly>
                        <input type="hidden" class="form-control" id="editTransaksiId" name="id" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editInvoice" class="form-label">Invoice</label>
                        <input type="text" class="form-control" id="editInvoice" name="invoice" required>
                    </div>
                    <div class="mb-3">
                        <label for="editHarga" class="form-label">Harga</label>
                        <input type="number" class="form-control" id="editHarga" name="harga" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPJ" class="form-label">Harga</label>
                        <input type="number" class="form-control" id="editPJ" name="pj" required>
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
        const editInvoice = document.getElementById('editInvoice');
        const editHarga = document.getElementById('editHarga');
        const editPJ = document.getElementById('editPJ');

        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const transaksiId = button.dataset.id;
                const transaksiLokSpk = button.dataset.lok_spk;
                const invoice = button.dataset.invoice;
                const harga = button.dataset.harga;
                const pj = button.dataset.pj;

                editTransaksiId.value = transaksiId;
                editTransaksiLokSpk.value = transaksiLokSpk;
                editInvoice.value = invoice;
                editHarga.value = harga;
                editPJ.value = pj;

                editForm.action = '{{ route("transaksi-jual-online.update") }}';
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
