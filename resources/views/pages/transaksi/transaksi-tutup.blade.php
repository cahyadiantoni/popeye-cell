@extends('layouts.main')

@section('title', 'Penjualan Ditutup')

{{-- 
  Kita tambahkan CSS khusus untuk animasi icon.
  @push('styles') akan menambahkannya di <head> layout Anda.
--}}
@push('styles')
<style>
    /* Animasi denyut (pulse) untuk icon */
    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.7;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Style untuk icon-nya */
    .icon-pulse {
        animation: pulse 2.5s infinite; /* Terapkan animasi */
        font-size: 80px;            /* Buat ikon jadi besar */
        color: #dc3545;             /* Beri warna merah (danger) */
        margin-bottom: 20px;
    }
</style>
@endpush


@section('content')
<div class="main-body">
    <div class="page-wrapper">
        
        {{-- Header Halaman --}}
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Akses Ditutup</h4>
                            <span>Waktu Transaksi Telah Berakhir</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Body Halaman --}}
        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-block text-center" style="padding: 60px 20px;">
                            
                            {{-- 1. Ikon dengan animasi "gerakan" (pulse) --}}
                            <i class="feather icon-clock icon-pulse"></i>
                            
                            {{-- 2. Teks Judul --}}
                            <h2 class="h2 text-danger" style="font-weight: 600;">
                                Maaf, Waktu Penjualan Telah Ditutup
                            </h2>
                            
                            {{-- 3. Teks Keterangan --}}
                            <p class="h5" style="margin-top: 15px; font-weight: 400; color: #555;">
                                Sistem penjualan ditutup secara otomatis pada pukul 
                                <strong>{{ $waktuTutup ?? 'N/A' }} WIB</strong>.
                            </p>
                            
                            <p style="margin-top: 25px; color: #777;">
                                Anda dapat kembali mengakses halaman ini pada jam operasional berikutnya.
                            </p>
                            
                            {{-- 4. Tombol Kembali (Opsional, tapi bagus) --}}
                            <a href="{{ url('/') }}" class="btn btn-primary btn-round mt-4">
                                <i class="feather icon-home"></i> Kembali ke Dashboard
                            </a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection