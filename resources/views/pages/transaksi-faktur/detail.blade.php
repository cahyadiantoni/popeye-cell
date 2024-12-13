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
                    <a href="{{ route('transaksi-faktur.index') }}" class="btn btn-secondary">Kembali</a>
                </div>
            </div>
        </div>

        <div class="page-body">
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
                    <p><strong>Total:</strong> Rp. {{ number_format($faktur->total*1000, 0, ',', '.') }}</p>
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
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaksiJuals as $index => $transaksi)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $transaksi->lok_spk }}</td>
                                <td>{{ $transaksi->barang->tipe ?? '-' }}</td>
                                <td>Rp. {{ number_format($transaksi->harga*1000, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <h4><strong>Total:</strong> Rp. {{ number_format($faktur->total*1000, 0, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
