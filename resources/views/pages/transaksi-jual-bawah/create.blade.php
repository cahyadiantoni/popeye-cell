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
                                <h4 class="sub-title">Copy-Paste Data Jual Barang</h4>
                                <form method="POST" action="{{ route('transaksi-jual-bawah.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3 row">
                                        <div class="col-sm-12">
                                            <p>Salin data dari Excel lalu tempelkan di area bawah ini. Pastikan urutan kolom sesuai:</p>
                                            <p><strong>tgl_jual | petugas | keterangan | lok_spk | harga_beli | harga_jual | merk_tipe | kelengkapan | grade | kerusakan | pj | imei | pembeli</strong></p>
                                            <h5 style="color:red">PASTIKAN AGAR TIDAK ADA HARGA YANG BERBEDA UNTUK TIPE DAN GRADE YANG SAMA.</h5>
                                            <hr>
                                            <textarea name="pasted_data" class="form-control" rows="15" placeholder="Tempelkan data dari Excel di sini..." required></textarea>
                                        </div>
                                    </div>

                                    <div class="mb-3 row">
                                        <label class="col-sm-2 col-form-label">Tindakan Lanjutan</label>
                                        <div class="col-sm-10">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="create_conclusion" id="conclusion_no" value="0" checked>
                                                <label class="form-check-label" for="conclusion_no">
                                                    Hanya Buat Faktur (Default)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="create_conclusion" id="conclusion_yes" value="1">
                                                <label class="form-check-label" for="conclusion_yes">
                                                    Buat Faktur & Langsung Jadikan Kesimpulan
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">Pilih opsi ini jika data yang ditempel hanya untuk satu faktur dan ingin langsung dibuatkan kesimpulannya.</small>
                                        </div>
                                    </div>

                                    <div id="conclusion-fields" style="display: none;">
                                        <hr>
                                        <div class="mb-3 row">
                                            <div class="sub-title mb-2">Masukan Bukti Transfer & Potongan Jika Ada (Opsional)</div>

                                            <!-- Potongan & Diskon -->
                                            <div class="mb-3 row mt-4">
                                                <div class="col-md-6">
                                                    <label class="form-label">Potongan Kondisi (Dalam Rp.)</label>
                                                    <input type="number" id="potongan_kondisi" name="potongan_kondisi" class="form-control" placeholder="Ketik Potongan Kondisi">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Diskon (Dalam %)</label>
                                                    <input type="number" id="diskon" name="diskon" class="form-control" placeholder="Ketik Persentase Diskon">
                                                </div>
                                            </div>
                                            <!-- Bukti Transfer -->
                                            <div id="bukti-transfer-container">
                                                <div class="row align-items-center bukti-entry mb-2">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Foto Bukti Transfer</label>
                                                        <input type="file" name="fotos[]" class="form-control">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label">Nominal Transfer</label>
                                                        <input type="number" name="nominals[]" class="form-control" placeholder="Ketik Nominal Transfer">
                                                    </div>
                                                    <div class="col-md-1 d-flex align-self-end">
                                                        {{-- Tombol hapus tidak ada untuk entri pertama --}}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-2">
                                                <button type="button" id="add-bukti-btn" class="btn btn-success btn-sm">Tambah Bukti Transfer</button>
                                            </div>
                                        </div>
                                        <hr>
                                    </div>


                                    <div class="d-flex justify-content-between mt-3">
                                        <a href="{{ route('transaksi-jual-bawah.index') }}" class="btn btn-secondary btn-round">List All Transaksi</a>
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
        // 1. Logika untuk menampilkan/menyembunyikan form upload
        $('input[name="create_conclusion"]').change(function() {
            if ($(this).val() == '1') {
                $('#conclusion-fields').slideDown(); // Tampilkan jika "Ya" dipilih
            } else {
                $('#conclusion-fields').slideUp(); // Sembunyikan jika "Tidak" dipilih
            }
        });

        // 2. Logika untuk menambah entri bukti transfer baru
        $('#add-bukti-btn').click(function() {
            const newEntry = `
            <div class="row align-items-center bukti-entry mb-2">
                <div class="col-md-6">
                    <input type="file" name="fotos[]" class="form-control">
                </div>
                <div class="col-md-5">
                    <input type="number" name="nominals[]" class="form-control" placeholder="Ketik Nominal Transfer">
                </div>
                <div class="col-md-1 d-flex align-self-end">
                    <button type="button" class="btn btn-danger btn-sm remove-bukti-btn">Hapus</button>
                </div>
            </div>`;
            $('#bukti-transfer-container').append(newEntry);
        });

        // 3. Logika untuk menghapus entri bukti transfer
        $('#bukti-transfer-container').on('click', '.remove-bukti-btn', function() {
            $(this).closest('.bukti-entry').remove();
        });
    });
    </script>
@endsection()