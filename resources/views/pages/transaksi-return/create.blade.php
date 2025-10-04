@extends('layouts.main')

@section('title', 'Create Return Barang')
@section('content')
    <!-- Main-body start -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page body start -->
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
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h3>Form Return Barang</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Upload Return Barang</h4>
                                <form method="POST" action="{{ route('transaksi-return.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <a href="{{ asset('files/template return.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                                    <hr>
                                    <div class="mb-3 row">
                                        <div class="sub-title">Masukan file excel di bawah!</div>
                                        <input type="file" name="filedata" required>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tanggal Return</label>
                                        <div class="col-sm-10">
                                            <input type="date" name="tgl_return" class="form-control" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                    </div>

                                    {{-- PERUBAHAN DI SINI: Input Nomor Return berdasarkan Role --}}
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Nomor Return</label>
                                        <div class="col-sm-10">
                                            @if($roleUser == 'admin')
                                                {{-- Untuk Admin, input manual --}}
                                                <input type="text" name="nomor_return" class="form-control" placeholder="Ketik Nomor Return (Contoh: RTO-JK-1025-001)" required>
                                            @else
                                                {{-- Untuk Non-Admin, otomatis dan readonly --}}
                                                <input type="text" name="nomor_return" class="form-control" placeholder="Pilih Tanggal Return untuk nomor otomatis" required readonly>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Petugas</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="petugas" class="form-control" value="{{ Auth::user()->name }}" required readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Keterangan Return</label>
                                        <div class="col-sm-10">
                                            <textarea name="keterangan" class="form-control" placeholder="Tambahkan keterangan jika diperlukan" rows="4"></textarea>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('transaksi-return.index') }}" class="btn btn-secondary btn-round">List Return</a>
                                        <button type="submit" class="btn btn-primary btn-round">Submit Return Barang</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PERUBAHAN DI SINI: Script AJAX hanya aktif untuk non-admin --}}
    @if($roleUser != 'admin')
    <script>
        $(document).ready(function() {
            function updateNomorReturn() {
                var tglReturn = $('input[name="tgl_return"]').val();

                if (tglReturn) {
                    $.ajax({
                        url: "{{ route('transaksi-return.suggest') }}",
                        type: "GET",
                        data: { tgl_return: tglReturn },
                        dataType: "json",
                        success: function(response) {
                            if(response.suggested_no_fak) {
                                $('input[name="nomor_return"]').val(response.suggested_no_fak);
                            } else {
                                alert(response.error || 'Gagal mendapatkan nomor return.');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", error);
                            alert('Terjadi kesalahan saat mengambil nomor return.');
                        }
                    });
                }
            }

            // Panggil fungsi saat halaman dimuat dan saat tanggal berubah
            updateNomorReturn(); 
            $('input[name="tgl_return"]').on('change', updateNomorReturn);
        });
    </script>
    @endif
@endsection()
