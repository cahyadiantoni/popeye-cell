@extends('layouts.main')

@section('title', 'Cek SO Barang')
@section('content')

    <!-- Main-body start -->
    <div class="main-body">
        <div class="page-wrapper">
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>Cek SO Barang</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="page-header-breadcrumb">
                            <ul class="breadcrumb-title">
                                <li class="breadcrumb-item" style="float: left;">
                                    <a href="<?= url('/') ?>"> <i class="feather icon-home"></i> </a>
                                </li>
                                <li class="breadcrumb-item" style="float: left;"><a
                                        href="#!">Cek SO Barang</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
                @if(session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                @if(session('error'))<div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card">
                            <div class="card-block">
                                <button class="btn btn-success" id="addCekSOBtn">Cek SO Barang</button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Kode</th>
                                                <th>Gudang</th>
                                                <th>Petugas</th>
                                                <th>Scan Sistem</th>
                                                <th>Input Manual</th>
                                                <th>Upload Excel</th>
                                                <th>Total/Stok</th>
                                                <th>Tgl Mulai</th>
                                                <th>Hasil</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($cekSOs as $index => $cekso)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-secondary btn-sm copy-link-btn" 
                                                            data-url="{{ route('cek-so.show-guest', $cekso->id) }}"
                                                            title="Salin Link Guest">
                                                        <i class="fas fa-link"></i>
                                                    </button>
                                                    {{ $cekso->kode }}
                                                </td>
                                                <td>{{ $cekso->nama_gudang ?? 'N/A' }}</td>
                                                <td>{{ $cekso->petugas }}</td>
                                                <td>{{ $cekso->jumlah_scan_sistem ?? 0 }}</td>
                                                <td>{{ $cekso->jumlah_input_manual ?? 0 }}</td>
                                                <td>{{ $cekso->jumlah_upload_excel ?? 0 }}</td>
                                                <td>{{ $cekso->jumlah_scan_sistem + $cekso->jumlah_input_manual + $cekso->jumlah_upload_excel }}/{{ $cekso->jumlah_stok }}</td>
                                                <td>{{ \Carbon\Carbon::parse($cekso->waktu_mulai)->format('d M y H:i') }}</td>
                                                <td>
                                                    @switch($cekso->hasil)
                                                        @case(0) <span class="badge bg-danger">Belum Sesuai</span> @break
                                                        @case(1) <span class="badge bg-success">Sesuai</span> @break
                                                        @case(2) <span class="badge bg-warning text-dark">Lok_SPK Belum sesuai</span> @break
                                                    @endswitch
                                                </td>
                                                <td>
                                                    @if($cekso->is_finished) <span class="badge bg-success">Selesai</span>
                                                    @else <span class="badge bg-warning text-dark">Belum Selesai</span> @endif
                                                </td>
                                                <td>
                                                    @if ($cekso->is_finished)
                                                        <a href="{{ route('cekso.showFinish', $cekso->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @else
                                                        {{-- PROSES pindah ke halaman guest --}}
                                                        <a href="{{ route('cek-so.show-guest', $cekso->id) }}" class="btn btn-primary btn-sm">Proses</a>
                                                
                                                        {{-- Tombol Akhiri Scan SO --}}
                                                        <button type="button"
                                                                class="btn btn-danger btn-sm btn-end-scan"
                                                                data-id="{{ $cekso->id }}"
                                                                data-kode="{{ $cekso->kode }}">
                                                            Akhiri Scan
                                                        </button>
                                                    @endif
                                                
                                                    {{-- Copy link guest --}}
                                                    <button type="button" class="btn btn-secondary btn-sm copy-link-btn" 
                                                            data-url="{{ route('cek-so.show-guest', $cekso->id) }}"
                                                            title="Salin Link Guest">
                                                        <i class="fas fa-link"></i>
                                                    </button>
                                                
                                                    {{-- Export Excel --}}
                                                    <a href="{{ route('cek-so.export', $cekso->id) }}" class="btn btn-success btn-sm" title="Export ke Excel">
                                                        <i class="fas fa-file-excel"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
    </div>
    <!-- Main-body end -->

    <!-- Modal Add Cek SO -->
    <div class="modal fade" id="addCekSOModal" tabindex="-1" aria-labelledby="addCekSOModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('cek-so.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCekSOModalLabel">Buat Cek SO Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="kode" class="form-label">Kode SO</label>
                            <input type="text" class="form-control" id="kode" name="kode" readonly required>
                        </div>
                        <div class="mb-3">
                            <label for="petugas" class="form-label">Nama Petugas</label>
                            <input type="text" class="form-control" id="petugas" name="petugas" placeholder="Tambahkan nama petugas" required>
                        </div>
                        <div class="mb-3">
                            <label for="waktu_mulai" class="form-label">Waktu Mulai</label>
                            <input type="datetime-local" class="form-control" id="waktu_mulai" name="waktu_mulai" required value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label col-sm-2 col-form-label">Select Gudang</label>
                            <div class="col-sm-10">
                                <select name="penerima_gudang_id" class="form-select form-control" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach($allgudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Buat Cek SO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const addCekSOBtn = document.getElementById('addCekSOBtn');
            if (addCekSOBtn) {
                addCekSOBtn.addEventListener("click", function () {
                    let addCekSOModal = new bootstrap.Modal(document.getElementById("addCekSOModal"));
                    addCekSOModal.show();
                });
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
        let gudangKode = {
            1: "SOBWH",
            2: "SOZLF",
            3: "SOTKP",
            5: "SOVR"
        };

        document.querySelector("select[name='penerima_gudang_id']").addEventListener("change", function () {
            let gudangId = this.value;
            let kodeAwal = gudangKode[gudangId] || "SOXXX"; // Default jika gudang_id tidak ditemukan
            
            if (gudangId) {
                fetch(`/get-last-kode/${gudangId}`)
                    .then(response => response.json())
                    .then(data => {
                        let bulan = new Date().getMonth() + 1; // 1-12
                        let tahun = new Date().getFullYear().toString().substr(-2); // 2 digit terakhir tahun

                        let bulanStr = bulan.toString().padStart(2, '0'); // Format 2 digit
                        let nextNumber = data.next_number.toString().padStart(3, '0'); // Format 3 digit

                        let kodeBaru = `${kodeAwal}${bulanStr}${tahun}${nextNumber}`;
                        document.getElementById("kode").value = kodeBaru;
                    })
                    .catch(error => console.error("Error fetching last kode:", error));
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const copyButtons = document.querySelectorAll('.copy-link-btn');
        
        copyButtons.forEach(button => {
            button.addEventListener('click', function () {
                const urlToCopy = this.dataset.url;

                // Menggunakan Clipboard API modern
                navigator.clipboard.writeText(urlToCopy).then(() => {
                    // Berhasil disalin
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Link Guest berhasil disalin ke clipboard.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }, () => {
                    // Gagal menyalin
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Tidak dapat menyalin link secara otomatis.'
                    });
                });
            });
        });
    });
    </script>
    
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Handler tombol "Akhiri Scan"
        document.querySelectorAll('.btn-end-scan').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const ceksoId  = this.dataset.id;
                const kodeSO   = this.dataset.kode;
    
                Swal.fire({
                    title: `Akhiri Scan SO ${kodeSO}?`,
                    html: `
                        <div class="text-start">
                            <label class="form-label">Catatan (opsional):</label>
                            <textarea id="swal-catatan" class="form-control" rows="3" placeholder="Tambahkan catatan..."></textarea>
                            <div class="form-text mt-2">Proses ini akan mengunci hasil scan dan memindahkan data ke hasil akhir.</div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Akhiri',
                    cancelButtonText: 'Batal',
                    focusConfirm: false,
                    preConfirm: () => {
                        const catatan = document.getElementById('swal-catatan').value || '';
                        return { catatan };
                    }
                }).then((result) => {
                    if (!result.isConfirmed) return;
    
                    // Kirim ke route finish
                    $.ajax({
                        url: "{{ route('cekso.finish') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            t_cek_so_id: ceksoId,
                            catatan: result.value.catatan
                        },
                        beforeSend: () => {
                            Swal.fire({
                                title: "Memproses...",
                                allowOutsideClick: false,
                                didOpen: () => Swal.showLoading()
                            });
                        },
                        success: function(res) {
                            // Sukses → arahkan ke halaman hasil (showFinish) sesuai response
                            Swal.fire({ icon: 'success', title: res.message || 'Berhasil', timer: 1200, showConfirmButton: false })
                                .then(() => {
                                    if (res.redirect_url) {
                                        window.location.href = res.redirect_url;
                                    } else {
                                        // fallback reload index
                                        window.location.reload();
                                    }
                                });
                        },
                        error: function(xhr) {
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan!';
                            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
                        }
                    });
                });
            });
        });
    });
    </script>

@endsection()