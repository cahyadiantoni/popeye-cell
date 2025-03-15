@extends('layouts.main')

@section('title', 'Buku Stok')
@section('content')
<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-12">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Buku Stok {{ $namaGudang ?? 'Semua Gudang' }}</h4>
                        </div>
                    </div>
                    <!-- Moved the form here -->
                    <form action="{{ route('buku-stok.index') }}" method="GET" class="mt-3">
                        <div class="form-group">
                            <label for="gudang_id" class="form-label">Pilih Gudang :</label>
                            <select name="gudang_id" id="gudang_id" class="form-select" onchange="this.form.submit()">
                                @foreach($gudangs as $gudang)
                                    <option value="{{ $gudang->id }}" {{ $selectedGudangId == $gudang->id ? 'selected' : '' }}>
                                        {{ $gudang->nama_gudang }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="page-body">
            <!-- Tabel Ringkasan Stok Barang -->
            <div class="card">
                <div class="card-header">
                    <h5>Ringkasan Stok Barang</h5>
                </div>
                <div class="card-block table-responsive">
                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jumlah Masuk</th>
                                <th>Jumlah Keluar</th>
                                <th>Total Saat Ini</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($datas as $index => $data)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $data->tanggal }}</td>
                                <td>
                                    @if(isset($data->jumlahMasuk))
                                        <span class="text-success">
                                            <i class="fas fa-plus-circle"></i> {{ $data->jumlahMasuk }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($data->jumlahKeluar))
                                        <span class="text-danger">
                                            <i class="fas fa-minus-circle"></i> {{ $data->jumlahKeluar }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $data->totalSaatIni }}</td>
                                <td>{!! $data->keterangan ?? '-' !!}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <h4><strong>Total Barang Saat ini :</strong> {{ $stokGudang->total ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
