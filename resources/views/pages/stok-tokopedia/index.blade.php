{{-- resources/views/pages/stok-tokopedia/index.blade.php --}}
@extends('layouts.main')
@section('title','Stok Barang Tokopedia')

@section('content')
<div class="main-body">
  <div class="page-wrapper">
    <div class="page-header">
      <div class="row align-items-end">
        <div class="col-lg-8">
          <div class="page-header-title">
            <div class="d-inline"><h4>Stok Barang Tokopedia</h4></div>
          </div>
        </div>
      </div>
    </div>

    <div class="page-body">
      <div class="row">
        <div class="col-sm-12">

          {{-- Filter --}}
          <div class="card">
            <div class="card-header"><h5>Filter</h5></div>
            <div class="card-block">
              <form method="GET" action="{{ route('stok-tokopedia.index') }}">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $start }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $end }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Cari Nama Barang</label>
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Ketik nama barang...">
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                  <button class="btn btn-primary">Terapkan Filter</button>
                  <a href="{{ route('stok-tokopedia.index') }}" class="btn btn-secondary ms-2">Reset</a>
                </div>
              </form>
            </div>
          </div>

          {{-- Tabel Stok --}}
          <div class="card">
            <div class="card-block">
              <div class="dt-responsive table-responsive">
                <table class="table table-striped table-bordered nowrap" style="width: 100%;">
                  <thead>
                    <tr>
                      <th>Nama Barang</th>
                      <th>History</th>
                      <th class="text-end">Total Stok</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($rows as $row)
                      <tr>
                        <td class="align-top"><strong>{{ $row['nama_barang'] }}</strong></td>
                        <td class="align-top">
                          {{-- History: IN (hijau), OUT (merah) --}}
                          @if(count($row['history']) === 0)
                            <span class="text-muted">Belum ada pergerakan.</span>
                          @else
                            <ul class="mb-0 ps-3">
                              @foreach($row['history'] as $h)
                                @php
                                  $tgl = \Carbon\Carbon::parse($h->tanggal)->format('d M Y');
                                @endphp
                                @if($h->tipe === 'IN')
                                  <li class="text-success">
                                    <strong>{{ $tgl }}</strong> — IN: +{{ number_format($h->qty,0,',','.') }}
                                  </li>
                                @else
                                  <li class="text-danger">
                                    <strong>{{ $tgl }}</strong> — OUT: -{{ number_format($h->qty,0,',','.') }}
                                    @php
                                        $parts = [];
                                        if ($h->kode_toko) $parts[] = $h->kode_toko;
                                        if ($h->nama_toko) $parts[] = $h->nama_toko;
                                        if ($h->nama_am)   $parts[] = 'AM: '.$h->nama_am;
                                    @endphp
                                    @if(count($parts) > 0)
                                        — <span class="text-dark">({{ implode(', ', $parts) }})</span>
                                    @endif
                                  </li>
                                @endif
                              @endforeach
                            </ul>
                          @endif
                        </td>
                        <td class="text-end align-top">
                          @php $stok = (int)$row['stok']; @endphp
                          @if($stok < 0)
                            <span class="text-danger"><strong>{{ number_format($stok,0,',','.') }}</strong></span>
                          @elseif($stok === 0)
                            <span class="text-muted"><strong>0</strong></span>
                          @else
                            <span class="text-success"><strong>{{ number_format($stok,0,',','.') }}</strong></span>
                          @endif
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="3" class="text-center">Tidak ada data untuk filter yang dipilih.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              <small class="text-muted">
                * Stok = total masuk − total keluar. Nama barang digabung lintas tanggal.
              </small>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>
@endsection
