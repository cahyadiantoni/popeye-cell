@extends('layouts.main')

@section('title', 'Detail Stok Opname')
@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Detail Stok Opname</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end">
                    @if($cekso->is_finished == 0)
                        <button class="btn btn-success" id="addBarangBtn">Upload Excel</button>
                    @endif
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
            <!-- Informasi Cek SO Barang -->
            <div class="card">
                <div class="card-block">
                    <table class="table table-bordered text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Kode SO</th>
                                <th>Gudang / Petugas</th>
                                <th>Scan</th>
                                <th>Manual</th>
                                <th>Jumlah Barang / Stok</th>
                                <th>Waktu Mulai / Waktu Berakhir</th>
                                <th>Durasi</th>
                                <th>Hasil SO</th>
                                <th>Status SO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $cekso->kode }}</td>
                                <td>{{ $cekso->nama_gudang }} / {{ $cekso->petugas }}</td>
                                <td>{{ $cekso->jumlah_scan ?? 0 }}</td>
                                <td>{{ $cekso->jumlah_manual ?? 0 }}</td>
                                <td>{{ $cekso->jumlah_scan + $cekso->jumlah_manual }} / {{ $cekso->jumlah_stok }}</td>
                                <td>{{ $cekso->waktu_mulai }} / {{ $cekso->waktu_selesai }}</td>
                                <td>{{ $cekso->durasi }}</td>
                                <td>
                                    @switch($cekso->hasil)
                                        @case(0) <span class="badge bg-danger">Belum Sesuai</span> @break
                                        @case(1) <span class="badge bg-success">Sesuai</span> @break
                                        @case(2) <span class="badge bg-warning text-dark">Lok_SPK Belum Sesuai</span> @break
                                        @default <span class="badge bg-secondary">Tidak Diketahui</span>
                                    @endswitch
                                </td>
                                <td>
                                    @switch($cekso->is_finished)
                                        @case(0) <span class="badge bg-warning text-dark">Belum Selesai</span> @break
                                        @case(1) <span class="badge bg-success">Selesai</span> @break
                                        @default <span class="badge bg-secondary">Tidak Diketahui</span>
                                    @endswitch
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Input besar untuk scan barcode -->
            <div class="text-center my-3">
                <input type="text" id="scanInput" class="form-control text-center p-3 fs-4 fw-bold" placeholder="Tekan untuk Scan barcode di sini" autofocus>
            </div>

            <!-- Animasi loading -->
            <div id="loading" class="text-center d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="fw-bold mt-2">Memproses scan...</p>
            </div>

            <!-- Tabel Barang -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Daftar Barang</h5>
                    <select id="filterScan" class="form-select w-auto">
                        <option value="">Semua</option>
                        <option value="1">Sudah Discan</option>
                        <option value="0">Belum Discan</option>
                    </select>
                </div>
                <div class="card-block table-responsive">
                    <table id="barangTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lok SPK</th>
                                <th>Jenis</th>
                                <th>Tipe</th>
                                <th>Kelengkapan</th>
                                <th>Scan</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!-- Tabel Barang Tidak ada di Database -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Daftar Barang Tidak ada di Database</h5>
                </div>
                <div class="card-block table-responsive">
                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lok SPK</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ceksoBarangnas as $index => $barangna)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $barangna->lok_spk }}</td>
                                <td>
                                    @if ($barangna->status == 1)
                                        <span class="badge bg-success">Sudah Discan</span>  
                                    @else
                                        <span class="badge bg-info">Upload Manual</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-center my-3">
                <textarea id="catatan" class="form-control text-center p-3 fs-4 fw-bold" placeholder="Tambahkan catatan di sini"></textarea>
            </div>
            <div class="text-center my-3">
                <button id="postButton" class="btn btn-primary btn-lg w-100 p-3 fs-4 fw-bold">
                    Akhiri Scan SO
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modal Add Barang -->
<div class="modal fade" id="addBarangModal" tabindex="-1" aria-labelledby="addBarangModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cekso.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addBarangModalLabel">Add LokSPK</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <a href="{{ asset('files/template cek so.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                    </div>
                    <div class="mb-3">
                        <label for="fileExcel" class="form-label">Upload File Excel</label>
                        <input type="file" class="form-control" id="filedata" name="filedata" required>
                        <input type="hidden" class="form-control" id="t_cek_so_id" name="t_cek_so_id" value="{{ $cekso->id }}" required>
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

<script>
    $(document).ready(function () {
        let table = $('#barangTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('get-cekso.barangs', $cekso->id) }}",
                data: function (d) {
                    d.scan_status = $('#filterScan').val(); // Kirim filter status scan
                }
            },
            columns: [
                { 
                    data: null, 
                    name: 'nomor', 
                    render: function (data, type, row, meta) {
                        return meta.row + 1; // Menampilkan nomor urut
                    },
                    orderable: false, 
                    searchable: false 
                },
                { data: 'lok_spk', name: 'lok_spk' },
                { data: 'jenis', name: 'jenis', defaultContent: '-' },
                { data: 'tipe', name: 'tipe', defaultContent: '-' },
                { data: 'kelengkapan', name: 'kelengkapan', defaultContent: '-' },
                {
                    data: 'is_scanned', 
                    name: 'is_scanned', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        if (data == 3) {
                            return '<span class="badge bg-info">Upload Manual</span>'; // Ganti dengan badge yang Anda inginkan
                        } else if (data == 1) {
                            return '<span class="badge bg-success">Sudah Discan</span>';
                        } else {
                            return '<span class="badge bg-danger">Belum Discan</span>';
                        }
                    }
                }
            ]
        });

        // Event ketika dropdown filter diubah
        $('#filterScan').change(function () {
            table.ajax.reload(); // Refresh tabel dengan filter baru
        });
    });

    $(document).ready(function () {
        let lastInputTime = 0;
        // Threshold untuk Enter dari scanner (biasanya sangat cepat setelah karakter terakhir)
        const SCANNER_ENTER_THRESHOLD = 50;
        const SCANNER_INPUT_THRESHOLD = 75;

        $('#scanInput').focus();

        $('#scanInput').on('keydown', function (e) {
            let currentTime = Date.now();
            let timeDiff = currentTime - lastInputTime;
            let currentValue = $(this).val();

            // 1. Handle Tombol Enter
            if (e.key === 'Enter') {
                e.preventDefault(); // Selalu cegah default action Enter
                let scanValue = currentValue.trim();

                // Hanya proses jika ada nilai DAN jeda dari input terakhir sangat singkat
                if (scanValue !== '' && timeDiff < SCANNER_ENTER_THRESHOLD) {
                    $('#loading').removeClass('d-none');
                    submitScan(scanValue); // Panggil fungsi submit
                    $(this).val(''); // Kosongkan setelah submit
                    lastInputTime = 0; // Reset timer
                } else {
                    // Jika ada teks hasil ketikan lambat, mungkin kita mau hapus?
                    if (scanValue !== '') $(this).val(''); // Opsional: Hapus jika enter manual
                    lastInputTime = 0; // Reset timer
                }
                return; // Selesai untuk tombol Enter
            }

            // 2. Handle Tombol Lain (Cegah Pengetikan Manual)
            // Kita hanya peduli pada tombol yang menghasilkan karakter atau mengubah nilai (seperti backspace)
            let isPotentiallyManualInputKey = e.key.length === 1 || e.key === 'Backspace' || e.key === 'Delete';

            if (isPotentiallyManualInputKey) {
                // Cek apakah ini input pertama (lastInputTime == 0) ATAU jeda terlalu lama
                if (lastInputTime !== 0 && timeDiff > SCANNER_INPUT_THRESHOLD) {
                    e.preventDefault(); // BLOKIR input manual (terlalu lambat)
                    $(this).val('');
                    lastInputTime = 0; // Reset timer

                    // Jangan update lastInputTime karena input diblokir
                    return; // Hentikan proses lebih lanjut untuk key ini
                }
                // Jika lolos (input pertama atau cukup cepat), Lanjutkan ke langkah 3
            } else {
                return; // Tidak perlu update waktu untuk kunci ini
            }


            // 3. Update Waktu Input Terakhir (HANYA jika input diizinkan)
            // Jika kode sampai di sini, berarti input tidak diblokir oleh logika di atas
            lastInputTime = currentTime;

        });

        // 4. Cegah Paste
        $('#scanInput').on('paste', function (e) {
            e.preventDefault();
            lastInputTime = 0;
            // Opsional: beri tahu pengguna
            alert("Paste tidak diizinkan. Silakan gunakan scanner.");
        });
    });

    function submitScan(lok_spk) {
        $.ajax({
            url: "{{ route('cekso.scan') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                t_cek_so_id: "{{ $cekso->id }}",
                lok_spk: lok_spk
            },
            success: function (response) {
                $('#loading').addClass('d-none'); // Sembunyikan animasi loading

                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Barang berhasil discan!',
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload(); // Refresh halaman setelah berhasil scan
                    });
                } else if (response.status === 'duplicate') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan!',
                        text: 'Barang sudah pernah discan!',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function () {
                $('#loading').addClass('d-none'); // Sembunyikan animasi loading
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal scan, harap ulangi!',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }

    $(document).ready(function () {
        let inactivityTime = 5000; // 10 detik
        let inactivityTimer;

        // Fungsi untuk mengembalikan fokus ke input scan
        function resetFocus() {
            $('#scanInput').focus();
        }

        // Fungsi untuk reset timer
        function resetTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(resetFocus, inactivityTime);
        }

        // Set fokus awal ke input scan
        resetFocus();

        // Deteksi aktivitas di layar
        $(document).on('mousemove keydown scroll click touchstart', function () {
            resetTimer();
        });

        // Saat input kehilangan fokus, kembalikan fokus
        $('#scanInput').on('blur', function () {
            resetTimer();
        });
    });

    $(document).ready(function () {
        $("#postButton").click(function () {
            let catatan = $("#catatan").val().trim();

            Swal.fire({
                title: "Konfirmasi",
                text: "Apakah Anda yakin ingin mengakhiri cek SO?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Kirim",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('cekso.finish') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            t_cek_so_id: "{{ $cekso->id }}",
                            catatan: catatan
                        },
                        beforeSend: function () {
                            Swal.fire({
                                title: "Mengirim...",
                                text: "Harap tunggu",
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function (response) {
                            Swal.fire({
                                icon: response.status === 'success' ? 'success' : 'error',
                                title: response.message,
                                timer: 2000, // Timer yang lebih singkat karena akan redirect
                                showConfirmButton: false
                            }).then(() => {
                                if (response.status === 'success' && response.redirect_url) {
                                    window.location.href = response.redirect_url; // Lakukan redirect
                                } else {
                                    // Jika ada error atau tidak ada redirect_url
                                    // Mungkin refresh halaman atau lakukan tindakan lain sesuai kebutuhan
                                    location.reload();
                                }
                            });
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: 'Terjadi kesalahan, coba lagi!',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    });
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
