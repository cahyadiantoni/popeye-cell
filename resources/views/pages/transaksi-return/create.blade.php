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
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Basic Form Inputs card start -->
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
                                        <input type="file" name="filedata">
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tanggal Return</label>
                                        <div class="col-sm-10">
                                            <input type="date" name="tgl_return" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Nomor Return</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="nomor_return" class="form-control" placeholder="Ketik Nomor Return" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Petugas</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="petugas" class="form-control" placeholder="Ketik Nama Petugas" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Keterangan Return</label>
                                        <div class="col-sm-10">
                                            <textarea name="keterangan" class="form-control" placeholder="Tambahkan keterangan jika diperlukan" rows="4"></textarea>
                                        </div>
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('transaksi-return.index') }}" class="btn btn-secondary btn-round">List Return</a>
                                        <button type="submit" class="btn btn-primary btn-round">Submit Return Barang</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Basic Form Inputs card end -->
                    </div>
                </div>
            </div>
            <!-- Page body end -->
        </div>
    </div>
    <!-- Main-body end -->
    <!-- <script>
        $(document).ready(function() {
            function updateNomorReturn() {
                var tglReturn = $('input[name="tgl_return"]').val();

                if (tglReturn) {
                    $.ajax({
                        url: "{{ route('transaksi-return.suggest') }}",
                        type: "GET",
                        data: {tgl_return: tglReturn },
                        dataType: "json",
                        success: function(response) {
                            console.log("Response:", response); // Debugging
                            $('input[name="nomor_return"]').val(response.suggested_no_fak);
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", error); // Debugging
                        }
                    });
                }
            }

            $('input[name="tgl_return"]').on('change', updateNomorReturn);
        });
    </script> -->
@endsection()