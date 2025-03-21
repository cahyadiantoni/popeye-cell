@extends('layouts.main')

@section('title', 'Jual Barang')
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
                                <h3>Form Jual Barang</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Upload Jual Barang</h4>
                                <form method="POST" action="{{ route('transaksi-jual.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <a href="{{ asset('files/template jual barang.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                                    <hr>
                                    <div class="mb-3 row">
                                        <div class="sub-title">Masukan file excel di bawah!</div>
                                        <input type="file" name="filedata">
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tanggal Jual</label>
                                        <div class="col-sm-10">
                                            <input type="date" name="tgl_jual" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Pilih kode Faktur</label>
                                        <div class="col-sm-10">
                                            <select name="kode_faktur" class="form-select form-control">
                                                <option value="">-- Pilih Kode Faktur --</option>
                                                <option value="BW" {{ $gudangId == 1 ? 'selected' : '' }}>BW - Gudang Bawah</option>
                                                <option value="AT" {{ $gudangId == 2 ? 'selected' : '' }}>AT - Gudang Zilfa</option>
                                                <option value="TKP" {{ $gudangId == 3 ? 'selected' : '' }}>TKP - Gudang Tokopedia</option>
                                                <option value="VR" {{ $gudangId == 5 ? 'selected' : '' }}>VR - Gudang Vira</option>
                                                <option value="LN">LN - Lain Lain</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Nomor Faktur</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="nomor_faktur" class="form-control" placeholder="Ketik Nomor Faktur" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Pembeli</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="pembeli" class="form-control" placeholder="Ketik Pembeli" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Petugas</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="petugas" class="form-control" placeholder="Ketik Nama Petugas" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Grade</label>
                                        <div class="col-sm-10">
                                            <select name="grade" class="form-control" required>
                                                <option value="">Pilih Grade</option>
                                                <option value="Barang JB">Barang JB</option>
                                                <option value="Barang 2nd">Barang 2nd</option>
                                                <option value="Grade B">Grade B</option>
                                                <option value="Grade C">Grade C</option>
                                                <option value="Batangan">Batangan</option>
                                                <option value="Lain Lain">Lain Lain</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Keterangan</label>
                                        <div class="col-sm-10">
                                            <textarea name="keterangan" class="form-control" placeholder="Tambahkan keterangan jika diperlukan" rows="4"></textarea>
                                        </div>
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('transaksi-jual.index') }}" class="btn btn-secondary btn-round">List All Transaksi</a>
                                        <button type="submit" class="btn btn-primary btn-round">Submit Jual Barang</button>
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
    <script>
        $(document).ready(function() {
            function updateNomorFaktur() {
                var kodeFaktur = $('select[name="kode_faktur"]').val();
                var tglJual = $('input[name="tgl_jual"]').val(); // Ambil tanggal jual yang dipilih

                if (kodeFaktur && tglJual) {
                    $.ajax({
                        url: "{{ route('suggest.no.fak') }}",
                        type: "GET",
                        data: { kode_faktur: kodeFaktur, tgl_jual: tglJual }, // Kirim tanggal jual
                        dataType: "json",
                        success: function(response) {
                            console.log("Response:", response); // Debugging
                            $('input[name="nomor_faktur"]').val(response.suggested_no_fak);
                        },
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", error); // Debugging
                        }
                    });
                }
            }

            $('select[name="kode_faktur"]').on('change', updateNomorFaktur);
            $('input[name="tgl_jual"]').on('change', updateNomorFaktur);
        });
    </script>
@endsection()