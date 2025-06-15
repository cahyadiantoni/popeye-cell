@extends('layouts.main')

@section('title', 'Detail Kesimpulan')
@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail Kesimpulan</h4>
                            <span>Nomor Kesimpulan: {{ $kesimpulan->nomor_kesimpulan }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    @if($roleUser=='admin' && $kesimpulan->is_finish==0)
                        <form action="{{ route('transaksi-kesimpulan.tandai-sudah-dicek', $kesimpulan->id) }}" method="POST" class="d-inline finish-form">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-success finish-btn">Tandai Dicek</button>
                        </form>
                    @endif
                    <a href="{{ route('transaksi-kesimpulan.print', $kesimpulan->id) }}" class="btn btn-primary" target="_blank">Print PDF</a>
                    <a href="{{ route('transaksi-kesimpulan.print-all', $kesimpulan->id) }}" class="btn btn-primary" target="_blank">Print All PDF</a>
                    <a href="{{ route('transaksi-kesimpulan.index') }}" class="btn btn-secondary">Kembali</a>
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

            <!-- Informasi Kesimpulan -->
            <div class="card">
                <div class="card-header">
                    <h5>Informasi Kesimpulan</h5>
                </div>
                <div class="card-block">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th width="30%">No Kesimpulan</th>
                            <td>{{ $kesimpulan->nomor_kesimpulan }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Jual</th>
                            <td>{{ \Carbon\Carbon::parse($kesimpulan->tgl_jual)->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <th>Jumlah Barang</th>
                            <td>{{ $kesimpulan->total_barang }}</td>
                        </tr>
                        <tr>
                            <th>Potongan Kondisi</th>
                            <td>Rp. {{ number_format($kesimpulan->potongan_kondisi, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Diskon</th>
                            <td>
                                {{ $kesimpulan->diskon }}% ( 
                                Rp. {{ number_format($kesimpulan->total_diskon = ($kesimpulan->total - $kesimpulan->potongan_kondisi) * ($kesimpulan->diskon/100), 0, ',', '.') }})
                            </td>
                        </tr>
                        <tr>
                            <th>Total Harga</th>
                            <td>Rp. {{ number_format($kesimpulan->total, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Total Potongan</th>
                            <td>Rp. {{ number_format($kesimpulan->total_diskon + $kesimpulan->potongan_kondisi, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Grand Total</th>
                            <td>Rp. {{ number_format($kesimpulan->grand_total, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Sudah Dibayar</th>
                            <td>Rp. {{ number_format($kesimpulan->total_nominal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Sisa Hutang</th>
                            <td>Rp. {{ number_format($kesimpulan->grand_total - $kesimpulan->total_nominal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Pembayaran</th>
                            <td>
                                @if ($kesimpulan->is_lunas == 0)
                                    <span class="badge bg-warning">Hutang</span>
                                @else
                                    <span class="badge bg-success">Lunas</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Keterangan</th>
                            <td>{{ $kesimpulan->keterangan }}</td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Tabel Faktur -->
            <div class="card">
                <div class="card-header">
                    <h5>Daftar Faktur</h5>
                </div>
                <div class="card-block table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Cek</th>
                                <th>No Faktur</th>
                                <th>Pembeli</th>
                                <th>Tgl Faktur</th>
                                <th>jumlah Barang</th>
                                <th>Total Harga</th>
                                <th>Petugas</th>
                                <th>Grade</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fakturs as $index => $faktur)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if ($faktur->faktur->is_finish == 0)
                                        @if($roleUser == 'admin')
                                            <form action="{{ route('transaksi-faktur-bawah.tandai-sudah-dicek', $faktur->faktur->id) }}" method="POST" class="d-inline finish-form">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-primary btn-sm finish-btn">Tandai Dicek</button>
                                            </form>
                                        @else
                                        <span class="badge bg-warning">Belum Dicek</span>
                                        @endif
                                    @else
                                        <!-- Keterangan Sudah Dicek -->
                                        <span class="badge bg-success">Sudah Dicek</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('transaksi-faktur-bawah.show', $faktur->faktur->nomor_faktur) }}" target="_blank">
                                        {{ $faktur->faktur->nomor_faktur }}
                                    </a>
                                </td>
                                <td>{{ $faktur->faktur->pembeli }}</td>
                                <td>{{ $faktur->faktur->tgl_jual }}</td>
                                <td>{{ $faktur->faktur->barangs->count() }}</td>
                                <td>{{ 'Rp. ' . number_format($faktur->faktur->total, 0, ',', '.') }}</td>
                                <td>{{ $faktur->faktur->petugas }}</td>
                                <td>{{ $faktur->faktur->grade }}</td>
                                <td>{{ $faktur->faktur->keterangan }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>List Bukti Transfer</h5>
                    @if($kesimpulan->is_finish == 0 || $kesimpulan->is_lunas == 0)
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
                                @if($kesimpulan->is_finish == 0 || $kesimpulan->is_lunas == 0)
                                <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kesimpulan->bukti as $bukti)
                            <tr>
                                <td>{{ $bukti->keterangan }}</td>
                                <td>Rp. {{ number_format($bukti->nominal, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ asset('storage/' . $bukti->foto) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $bukti->foto) }}" alt="Bukti Transfer" class="img-thumbnail" style="width: 150px; height: auto;">
                                    </a>
                                </td>
                                @if($kesimpulan->is_finish == 0 || $kesimpulan->is_lunas == 0)
                                <td>
                                    <form action="{{ route('transaksi-kesimpulan.bukti.delete', $bukti->id) }}" method="POST">
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

<!-- Modal Tambah Bukti -->
<div class="modal fade" id="addBuktiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('transaksi-kesimpulan.bukti.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Bukti Transfer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="kesimpulan_id" value="{{ $kesimpulan->id }}">
                    <input type="text" class="form-control mb-2" name="keterangan" placeholder="Keterangan" value="Bukti TF Bawah" required>
                    <input type="number" class="form-control mb-2" id="nominal" name="nominal" placeholder="Nominal Transfer" required>
                    <small class="form-text text-muted" id="nominal_display">{{ 'Rp. 0' }}</small>
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

<script>
    $(document).ready(function() {
        // Function to format number as currency
        function formatCurrency(value) {
            return 'Rp. ' + new Intl.NumberFormat('id-ID').format(value);
        }

        // Update display for nominal
        $('#nominal').on('input', function() {
            const value = $(this).val();
            $('#nominal_display').text(formatCurrency(value));
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const finishForms = document.querySelectorAll('.finish-form');
        finishForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault(); // Mencegah submit form langsung
                if (confirm('Apakah Anda yakin ingin menandai sebagai sudah dicek?')) {
                    form.submit(); // Submit form jika konfirmasi "OK"
                }
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const finishForms = document.querySelectorAll('.finish-faktur-form');
        finishForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                event.preventDefault(); // Mencegah submit form langsung
                if (confirm('Apakah Anda yakin ingin menandai sebagai sudah dicek?')) {
                    form.submit(); // Submit form jika konfirmasi "OK"
                }
            });
        });
    });
</script>

@endsection
