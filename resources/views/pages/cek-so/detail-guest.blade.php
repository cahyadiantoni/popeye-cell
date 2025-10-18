@extends('layouts.plain')

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
                        <button class="btn btn-primary" id="addManualBtn">Input Manual</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="page-body">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
            @if(session('error'))<div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
            @if(session('errors'))<div class="alert alert-danger alert-dismissible fade show" role="alert"><ul>@foreach (session('errors') as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
            
            <div class="card">
                <div class="card-block table-responsive">
                    <table class="table table-bordered text-center mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Kode SO</th>
                                <th>Gudang / Petugas</th>
                                <th>Scan Sistem</th>
                                <th>Input Manual</th>
                                <th>Upload Excel</th>
                                <th>Total / Stok</th>
                                <th>Waktu Mulai / Selesai</th>
                                <th>Durasi</th>
                                <th>Hasil</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $cekso->kode }}</td>
                                <td>{{ $cekso->nama_gudang }} / {{ $cekso->petugas }}</td>
                                <td>{{ $cekso->jumlah_scan_sistem ?? 0 }}</td>
                                <td>{{ $cekso->jumlah_input_manual ?? 0 }}</td>
                                <td>{{ $cekso->jumlah_upload_excel ?? 0 }}</td>
                                <td>{{ $cekso->jumlah_scan_sistem + $cekso->jumlah_input_manual + $cekso->jumlah_upload_excel }} / {{ $cekso->jumlah_stok }}</td>
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
                                    @if($cekso->is_finished)
                                        <span class="badge bg-success">Selesai</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Belum Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Tambahkan ringkasan jumlah BOX / BTG / OTHER di bawah tabel --}}
                    <div class="p-3 border-top bg-light text-center">
                        <span class="badge bg-dark fs-6 me-2 px-3 py-2">
                            BOX: <strong>{{ $jumlah_box }}</strong>
                        </span>
                        <span class="badge bg-secondary fs-6 me-2 px-3 py-2">
                            BTG: <strong>{{ $jumlah_btg }}</strong>
                        </span>
                        <span class="badge bg-info text-dark fs-6 px-3 py-2">
                            OTHER: <strong>{{ $jumlah_other }}</strong>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="petugas_scan_input" class="form-label fw-bold">Petugas Scan (Anda) <span class="text-danger">*</span></label>
                            <input type="text" id="petugas_scan_input" class="form-control form-control-lg" placeholder="Masukkan nama Anda" required>
                            <div class="form-text">Nama ini akan dicatat untuk setiap item yang Anda input/scan.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="lokasi_input" class="form-label fw-bold">Lokasi Pengecekan <span class="text-danger">*</span></label>
                            <input type="text" id="lokasi_input" class="form-control form-control-lg" placeholder="Contoh: Rak A-01">
                        </div>

                        <div class="col-md-4">
                            <label for="kelengkapan_update_input" class="form-label fw-bold">Kelengkapan Update <span class="text-danger">*</span></label>
                            <select id="kelengkapan_update_input" class="form-select form-select-lg" required>
                            <option value="">-- Pilih Kelengkapan Update --</option>
                            <option value="BOX">BOX</option>
                            <option value="BTG">BTG</option>
                            <option value="OTHER">OTHER</option>
                            </select>
                            <div class="form-text">Wajib pilih sebelum scan / input / upload.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center my-3">
                <input type="text" id="scanInput" class="form-control text-center p-3 fs-4 fw-bold" placeholder="Tekan untuk Scan barcode di sini" autofocus>
            </div>
            <div id="locationCountDisplay" class="text-center my-3 fs-2 fw-bold text-primary"></div>
            <div id="loading" class="text-center d-none"><div class="spinner-border text-primary" role="status"></div><p class="fw-bold mt-2">Memproses scan...</p></div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3"><h5>Daftar Barang (Master)</h5></div>
                        <div class="col-md-3">
                            <select id="filterScan" class="form-select">
                                <option value="">-- Filter Status --</option>
                                <option value="ditemukan">Semua Ditemukan</option>
                                <option value="1">Scan Sistem</option>
                                <option value="3">Input Manual</option>
                                <option value="4">Upload Excel</option>
                                <option value="belum_ditemukan">Belum Ditemukan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterPetugas" class="form-select">
                                <option value="">-- Filter Petugas --</option>
                                @foreach($petugasScans as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterLokasi" class="form-select">
                                <option value="">-- Filter Lokasi --</option>
                                @foreach($lokasis as $l)
                                <option value="{{ $l }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-block table-responsive">
                    <table id="barangTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lok SPK</th>
                                <th>Jenis</th>
                                <th>Tipe</th>
                                <th>Kelengkapan</th>
                                <th>Status Scan</th>
                                <th>Petugas Scan</th>
                                <th>Lokasi</th>
                                <th>Kelengkapan Update</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6"><h5>Daftar Barang Ditemukan (Tidak Ada di Master)</h5></div>
                        <div class="col-md-3">
                            <select id="filterPetugasNa" class="form-select">
                                <option value="">-- Filter Petugas --</option>
                                @foreach($petugasScans as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filterLokasiNa" class="form-select">
                                <option value="">-- Filter Lokasi --</option>
                                @foreach($lokasis as $l)
                                <option value="{{ $l }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-block table-responsive">
                    <table id="barangNaTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lok SPK</th>
                                <th>Status Input</th>
                                <th>Petugas Scan</th>
                                <th>Lokasi</th>
                                <th>Kelengkapan Update</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <!--<div class="my-3"><textarea id="catatan" class="form-control" placeholder="Tambahkan catatan di sini..."></textarea></div>-->
            <!--<div class="text-center my-3"><button id="postButton" class="btn btn-primary btn-lg w-100 p-3 fs-4 fw-bold">Akhiri Scan SO</button></div>-->
        </div>
    </div>
</div>

<div class="modal fade" id="addBarangModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cekso.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Upload Excel LokSPK</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="upload_petugas_scan" name="petugas_scan">
                    <input type="hidden" id="upload_lokasi" name="lokasi">
                    <input type="hidden" id="upload_kelengkapan_update" name="kelengkapan_update">
                    <div class="mb-3"><a href="{{ asset('files/template cek so.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a></div>
                    <div class="mb-3">
                        <label for="filedata" class="form-label">Upload File Excel</label>
                        <input type="file" class="form-control" id="filedata" name="filedata" required>
                        <input type="hidden" name="t_cek_so_id" value="{{ $cekso->id }}">
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Upload</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addManualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formManualLokSpk" method="POST">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Input Manual LOK_SPK</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="t_cek_so_id" value="{{ $cekso->id }}">
                    <input type="hidden" id="manual_petugas_scan" name="petugas_scan">
                    <input type="hidden" id="manual_lokasi" name="lokasi">
                    <input type="hidden" id="manual_kelengkapan_update" name="kelengkapan_update">
                    <div class="mb-3">
                        <label for="lok_spk" class="form-label">LOK_SPK</label>
                        <input type="text" class="form-control" id="lok_spk" name="lok_spk" placeholder="Masukkan LOK_SPK" required>
                    </div>
                    <div id="manualAlert"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>
<script>
    const IS_AUTH = {{ auth()->check() ? 'true' : 'false' }};
</script>

<script>
$(document).ready(function () {
    // Inisialisasi DataTable Utama
    let table = $('#barangTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: "{{ route('get-cekso.barangs', $cekso->id) }}", data: function (d) { d.scan_status = $('#filterScan').val(); d.petugas_scan = $('#filterPetugas').val(); d.lokasi = $('#filterLokasi').val(); } },
        columns: [
            { data: null, name: 'nomor', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + 1 },
            { data: 'lok_spk', name: 'lok_spk' }, { data: 'jenis', name: 'jenis', defaultContent: '-' },
            { data: 'tipe', name: 'tipe', defaultContent: '-' },
            { data: 'kelengkapan', name: 'kelengkapan', defaultContent: '-' },
            {
                data: 'scan_status_val', name: 'scan_status_val', orderable: false, searchable: false,
                render: function(data) {
                    if (data == 1) return '<span class="badge bg-success">Scan Sistem</span>';
                    if (data == 3) return '<span class="badge bg-info">Input Manual</span>';
                    if (data == 4) return '<span class="badge bg-primary">Upload Excel</span>';
                    return '<span class="badge bg-danger">Belum Discan</span>';
                }
            },
            { data: 'petugas_scan', name: 'petugas_scan', defaultContent: '-' },
            { data: 'lokasi', name: 'lokasi', defaultContent: '-' },
            { data: 'kelengkapan_update', name: 'kelengkapan_update', defaultContent: '-' }
        ]
    });

    // Inisialisasi DataTable Kedua ("Tidak Ada di DB")
    let tableNa = $('#barangNaTable').DataTable({
        processing: true, serverSide: true,
        ajax: { 
            url: "{{ route('get-cekso.not-in-master', $cekso->id) }}",
            data: function (d) {
                d.petugas_scan = $('#filterPetugasNa').val();
                d.lokasi = $('#filterLokasiNa').val();
            } 
        },
        columns: [
            { data: null, name: 'nomor', orderable: false, searchable: false,
            render: (data, type, row, meta) => meta.row + 1 },
            { data: 'lok_spk', name: 'lok_spk' },
            { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
            { data: 'petugas_scan', name: 'petugas_scan', defaultContent: '-' },
            { data: 'lokasi', name: 'lokasi', defaultContent: '-' },
            { data: 'kelengkapan_update', name: 'kelengkapan_update', defaultContent: '-' },
            // === Kolom Aksi (baru) ===
            { data: null, name: 'aksi', orderable: false, searchable: false,
            render: function (data, type, row) {
                if (!IS_AUTH) return ''; 
                return `
                    <button class="btn btn-sm btn-danger btn-del-na" data-id="${row.id}">
                        Hapus
                    </button>`;
            }
            }
        ]
    });

    // Event handler untuk semua filter
    $('#filterScan, #filterPetugas, #filterLokasi').change(() => table.ajax.reload());
    $('#filterPetugasNa, #filterLokasiNa').change(() => tableNa.ajax.reload());
    
    // Sinkronkan "Lokasi Pengecekan" -> filter lokasi tabel
    function applyLokasiToFilters(val) {
        const v = (val || '').trim();

        // helper: set value ke <select>, tambahkan option jika belum ada
        const syncSelect = ($sel, value) => {
            if (value && $sel.find(`option[value="${value}"]`).length === 0) {
                $sel.append(new Option(value, value)); // tambahkan option baru
            }
            $sel.val(value).trigger('change'); // memicu reload tabel
        };

        if (v === '') {
            // kosongkan filter jika input lokasi dikosongkan
            $('#filterLokasi').val('').trigger('change');
            $('#filterLokasiNa').val('').trigger('change');
        } else {
            syncSelect($('#filterLokasi'), v);
            syncSelect($('#filterLokasiNa'), v);
        }
    }

    // Debounce supaya tabel tidak reload terlalu sering saat user mengetik
    let lokasiDebounce;
    $('#lokasi_input').on('input change', function () {
        clearTimeout(lokasiDebounce);
        const val = this.value;
        lokasiDebounce = setTimeout(() => applyLokasiToFilters(val), 300);
    });

    // Inisialisasi awal bila sudah ada nilai lokasi saat halaman dibuka
    applyLokasiToFilters($('#lokasi_input').val());

    // --- LOGIKA SCAN INPUT DENGAN ANTI-MANUAL ---
    let lastInputTime = 0;
    const SCANNER_THRESHOLD = 100;

    $('#scanInput').focus();

    $('#scanInput').on('keydown', function (e) {
        let currentTime = new Date().getTime();
        if (currentTime - lastInputTime > SCANNER_THRESHOLD && $(this).val().length > 0) {
            $(this).val('');
        }
        lastInputTime = currentTime;
        if (e.key === 'Enter') {
            e.preventDefault();
            let scanValue = $(this).val().trim();
            if (scanValue && isPetugasLokasiValid()) {
                $('#loading').removeClass('d-none');
                submitScan(scanValue);
                $(this).val('');
            } else {
                $(this).val('');
            }
        }
    });

    $('#scanInput').on('paste', (e) => {
        e.preventDefault();
        Swal.fire('Aksi Ditolak', 'Paste tidak diizinkan. Silakan gunakan scanner.', 'warning');
    });

    function isPetugasLokasiValid() {
        if ($('#petugas_scan_input').val().trim() === '') {
            Swal.fire('Error', 'Nama "Petugas Scan (Anda)" wajib diisi terlebih dahulu!', 'error');
            $('#petugas_scan_input').focus();
            return false;
        }
        if (!$('#kelengkapan_update_input').val()) {
            Swal.fire('Error', 'Pilih "Kelengkapan Update" terlebih dahulu!', 'error');
            $('#kelengkapan_update_input').focus();
            return false;
        }
        if ($('#lokasi_input').val().trim() === '') {
          Swal.fire('Error', 'Isi "Lokasi Pengecekan" dulu, ya!', 'error');
          $('#lokasi_input').focus();
          return false;
        }

        return true;
    }

    // -- FUNGSI DIUBAH --
    function submitScan(lok_spk) {
        $.ajax({
            url: "{{ route('cekso.scan') }}", method: "POST",
            data: {                                  // ← perbaiki baris ini
                _token: "{{ csrf_token() }}",
                t_cek_so_id: "{{ $cekso->id }}",
                lok_spk: lok_spk,
                petugas_scan: $('#petugas_scan_input').val().trim(),
                lokasi: $('#lokasi_input').val().trim(),
                kelengkapan_update: $('#kelengkapan_update_input').val()
            },
            success: function (response) {
                $('#loading').addClass('d-none');
                $('#scanInput').focus();
                if (response.status === 'success') {
                    let icon = response.found_in_master ? 'success' : 'warning';
                    let title = response.found_in_master ? 'Ditemukan!' : 'Tidak Ada di Master!';
                    
                    // PERUBAHAN: Tampilkan jumlah di SweetAlert dan di bawah input
                    let alertMessage = `${response.message}<br><br><b>Total di lokasi ini: ${response.location_count}</b>`;
                    Swal.fire({ icon: icon, title: title, html: alertMessage, timer: 100, showConfirmButton: false });
                    $('#locationCountDisplay').text(`Total di Lokasi Ini: ${response.location_count}`);
                    // ---------------------------------------------------------------

                    table.ajax.reload(null, false);
                    tableNa.ajax.reload(null, false);
                } else if (response.status === 'duplicate') {
                    Swal.fire({ icon: 'warning', title: 'Barang sudah pernah discan!', timer: 1000, showConfirmButton: false });
                }
            },
            error: function () {
                $('#loading').addClass('d-none');
                $('#scanInput').focus();
                Swal.fire({ icon: 'error', title: 'Gagal scan, harap ulangi!', timer: 1000, showConfirmButton: false });
            }
        });
    }

    // --- Logika Modal & Tombol Finish ---
    $('#addBarangBtn').click(() => {
        if(isPetugasLokasiValid()){
            $('#upload_petugas_scan').val($('#petugas_scan_input').val().trim());
            $('#upload_lokasi').val($('#lokasi_input').val().trim());
            $('#upload_kelengkapan_update').val($('#kelengkapan_update_input').val());
            new bootstrap.Modal(document.getElementById('addBarangModal')).show();
        }
    });

    $('#addManualBtn').click(() => {
        if(isPetugasLokasiValid()){
            $('#manual_petugas_scan').val($('#petugas_scan_input').val().trim());
            $('#manual_lokasi').val($('#lokasi_input').val().trim());
            $('#manual_kelengkapan_update').val($('#kelengkapan_update_input').val());
            $('#formManualLokSpk').find("input[name='lok_spk']").val('');
            $('#manualAlert').html('');
            new bootstrap.Modal(document.getElementById('addManualModal')).show();
        }
    });
    
    // -- FUNGSI DIUBAH --
    $('#formManualLokSpk').submit(function (e) {
        e.preventDefault();

        const $form = $(this);
        const $btn  = $form.find('button[type="submit"]');

        // lock UI (anti double click)
        if ($btn.prop('disabled')) return; // sudah submit, abaikan
        $btn.data('orig-html', $btn.html());
        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyimpan...'
        );

        $.ajax({
            url: "{{ route('cekso.manual') }}",
            method: "POST",
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.status == 'success') {
                    $('#addManualModal').modal('hide');

                    let icon = res.found_in_master ? 'success' : 'info';
                    let title = res.found_in_master ? 'Ditemukan!' : 'Tidak Ada di Master!';
                    let alertMessage = `${res.message}<br><br><b>Total di lokasi ini: ${res.location_count}</b>`;

                    Swal.fire({ icon, title, html: alertMessage, timer: 1000, showConfirmButton: false });
                    $('#locationCountDisplay').text(`Total di Lokasi Ini: ${res.location_count}`);

                    table.ajax.reload(null, false);
                    tableNa.ajax.reload(null, false);
                } else {
                    $('#manualAlert').html('<div class="alert alert-warning">' + (res.message || 'Error.') + '</div>');
                }
            },
            error: function () {
                $('#manualAlert').html('<div class="alert alert-danger">Gagal simpan data.</div>');
            },
            complete: function () {
                // restore tombol (kalau modal masih terbuka karena error)
                $btn.prop('disabled', false).html($btn.data('orig-html'));
            }
        });
    });
    
    // Handler delete baris NA
    $('#barangNaTable').on('click', '.btn-del-na', function () {
        const $btn = $(this);
        const row  = tableNa.row($btn.closest('tr')).data();
        const id   = $btn.data('id');

        if (!id) {
            Swal.fire('Error', 'ID data tidak ditemukan.', 'error');
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Hapus item ini?',
            html: `LOK_SPK: <b>${row?.lok_spk || '-'}</b><br>Lokasi: <b>${row?.lokasi || '-'}</b>`,
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then((res) => {
            if (!res.isConfirmed) return;

            // lock tombol agar tidak dobel klik
            $btn.prop('disabled', true).text('Menghapus...');

            $.ajax({
                url: "{{ route('cekso.not-in-master.destroy', ['cekso' => $cekso->id, 'id' => '___ID___']) }}".replace('___ID___', id),
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                success: function (resp) {
                    Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1000, showConfirmButton: false });
                    tableNa.ajax.reload(null, false);
                    table.ajax.reload(null, false); // opsional, kalau mau segarkan tabel master juga
                },
                error: function (xhr) {
                    Swal.fire('Gagal', (xhr.responseJSON?.message || 'Gagal menghapus data.'), 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Hapus');
                }
            });
        });
    });
});
</script>

@endsection