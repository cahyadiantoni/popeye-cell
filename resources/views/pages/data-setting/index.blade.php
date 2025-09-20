@extends('layouts.main')

@section('title', 'Data Settingan')
@section('content')
    <div class="main-body">
        <div class="page-wrapper">
            {{-- Header Halaman --}}
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>Data Settingan</h4>
                                <span>Kelola settingan aplikasi.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Body Halaman --}}
            <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">

                        {{-- TABEL DATA --}}
                        <div class="card">
                            {{-- Tampilkan Notifikasi Sukses --}}
                            @if (session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            {{-- Tampilkan Notifikasi Error Validasi --}}
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="card-block">
                                <button type="button" class="btn btn-primary btn-round" data-bs-toggle="modal"
                                    data-bs-target="#addSettingModal">
                                    <i class="feather icon-plus"></i> Tambah Settingan
                                </button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap"
                                        style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Value</th>
                                                <th>Keterangan</th>
                                                <th>Status</th>
                                                <th style="width: 200px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($settings as $setting)
                                                <tr>
                                                    <td>{{ $setting->name }}</td>
                                                    <td>{{ Str::limit($setting->value, 70, '...') }}</td>
                                                    <td>{{ $setting->keterangan ?? '-' }}</td>
                                                    <td>
                                                        @if ($setting->is_active)
                                                            <span class="badge bg-success">Aktif</span>
                                                        @else
                                                            <span class="badge bg-danger">Tidak Aktif</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{-- Tombol Edit --}}
                                                        <button type="button" class="btn btn-warning btn-round btn-sm btn-edit"
                                                            data-bs-toggle="modal" data-bs-target="#editSettingModal"
                                                            data-id="{{ $setting->id }}"
                                                            data-name="{{ $setting->name }}"
                                                            data-value="{{ $setting->value }}"
                                                            data-keterangan="{{ $setting->keterangan }}"
                                                            data-is_active="{{ $setting->is_active ? 1 : 0 }}">
                                                            Edit
                                                        </button>

                                                        {{-- Tombol Toggle Aktif/Nonaktif --}}
                                                        @if ($setting->is_active)
                                                            <button type="button"
                                                                class="btn btn-danger btn-round btn-sm btn-toggle-active"
                                                                data-id="{{ $setting->id }}"
                                                                data-name="{{ $setting->name }}">
                                                                Nonaktifkan
                                                            </button>
                                                        @else
                                                            <button type="button"
                                                                class="btn btn-success btn-round btn-sm btn-toggle-active"
                                                                data-id="{{ $setting->id }}"
                                                                data-name="{{ $setting->name }}">
                                                                Aktifkan
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">Belum ada data settingan.
                                                    </td>
                                                </tr>
                                            @endforelse
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

    {{-- ================================ MODALS ================================ --}}

    {{-- ADD MODAL --}}
    <div class="modal fade" id="addSettingModal" tabindex="-1" aria-labelledby="addSettingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('data-setting.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSettingModalLabel">Tambah Settingan Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name') }}"
                                placeholder="Contoh: WAKTU_TUTUP_PUSAT" required>
                            <small class="text-muted">Nama unik untuk pengenal (tanpa spasi disarankan).</small>
                        </div>
                        <div class="mb-3">
                            <label for="value" class="form-label">Value <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="value" rows="3" required>{{ old('value') }}</textarea>
                            <small class="text-muted">Isi dari settingan.</small>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="2">{{ old('keterangan') }}</textarea>
                            <small class="text-muted">Penjelasan singkat untuk settingan ini.</small>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active_add" checked>
                            <label class="form-check-label" for="is_active_add">
                                Aktifkan settingan ini
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div class="modal fade" id="editSettingModal" tabindex="-1" aria-labelledby="editSettingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST"> {{-- Action di-set via JS --}}
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editSettingModalLabel">Edit Settingan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                            <small class="text-muted">Nama unik untuk pengenal (tanpa spasi disarankan).</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_value" class="form-label">Value <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_value" name="value" rows="3" required></textarea>
                            <small class="text-muted">Isi dari settingan.</small>
                        </div>
                        <div class="mb-3">
                            <label for="edit_keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="edit_keterangan" name="keterangan" rows="2"></textarea>
                            <small class="text-muted">Penjelasan singkat untuk settingan ini.</small>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                            <label class="form-check-label" for="edit_is_active">
                                Aktifkan settingan ini
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Scripts (PENTING!) --}}
    {{-- Kita butuh SweetAlert, pastikan Anda sudah memilikinya di layout utama, jika tidak, tambahkan: --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ===== 1. Script untuk mengisi Modal Edit =====
            const editModal = document.getElementById('editSettingModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget; // Tombol yang diklik
                const form = document.getElementById('editForm');

                // Ambil data dari atribut data-*
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const value = button.getAttribute('data-value');
                const keterangan = button.getAttribute('data-keterangan');
                const isActive = button.getAttribute('data-is_active'); // akan jadi "1" atau "0"

                // Set URL action form
                let url = "{{ route('data-setting.update', ':id') }}";
                url = url.replace(':id', id);
                form.action = url;

                // Isi field-field di modal
                form.querySelector('#edit_name').value = name;
                form.querySelector('#edit_value').value = value;
                form.querySelector('#edit_keterangan').value = keterangan;
                
                // Set checkbox status
                form.querySelector('#edit_is_active').checked = (isActive == 1);
            });

            // ===== 2. Script untuk Toggle Aktif/Nonaktif (seperti 'gantian') =====
            document.querySelectorAll('.btn-toggle-active').forEach(button => {
                button.addEventListener('click', function() {
                    const settingId = this.dataset.id;
                    const settingName = this.dataset.name;
                    const currentStatusText = this.innerText.trim(); // "Aktifkan" atau "Nonaktifkan"
                    const actionText = currentStatusText.toLowerCase();

                    // Konfirmasi dengan SweetAlert
                    Swal.fire({
                        title: `Anda yakin?`,
                        text: `Anda akan ${actionText} settingan '${settingName}'.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: `Ya, ${actionText}!`,
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Buat URL action
                            let url = "{{ route('data-setting.toggleActive', ':id') }}";
                            url = url.replace(':id', settingId);

                            // Kirim request via Fetch
                            fetch(url, {
                                method: 'POST',
                                headers: {
                                    // Ambil CSRF token dari meta tag (pastikan ada di layout Anda)
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if(data.status === 'success') {
                                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.message, timer: 1500, showConfirmButton: false })
                                    .then(() => location.reload()); // Muat ulang halaman
                                } else {
                                    throw new Error(data.message || 'Terjadi kesalahan.');
                                }
                            })
                            .catch(error => {
                                Swal.fire({ icon: 'error', title: 'Error!', text: error.message, showConfirmButton: true });
                            });
                        }
                    });
                });
            });

            // ===== 3. Penanganan Error Validasi (agar modal terbuka jika error) =====
            // Jika ada error pada form '#addSettingModal' (dari validasi server)
            @if ($errors->hasBag('default') && old('form_type') === 'add')
                var addModal = new bootstrap.Modal(document.getElementById('addSettingModal'));
                addModal.show();
            // Jika ada error pada form '#editSettingModal'
            @elseif ($errors->hasBag('default') && old('form_type') === 'edit')
                // Ini sedikit tricky karena kita perlu tahu ID mana yang error
                // Untuk kesederhanaan, kita bisa buka modal Add jika edit gagal
                // atau Anda bisa menyimpan ID di session flash
                var addModal = new bootstrap.Modal(document.getElementById('addSettingModal'));
                addModal.show(); 
                // Solusi lebih baik: Saat validasi update gagal di controller,
                // redirect()->back()->withInput()->with('error_modal_id', $id)
                
                // !! INI YANG DIPERBAIKI !!
                // Lalu cek di JS: @@if(session('error_modal_id')) ... buka modal edit yg benar 
            @endif

            // Menambahkan input hidden untuk melacak form mana yang disubmit
            // (jika Anda ingin penanganan error modal yang lebih canggih)
            document.getElementById('addSettingModal').querySelector('form').addEventListener('submit', function() {
                this.insertAdjacentHTML('beforeend', '<input type="hidden" name="form_type" value="add">');
            });
            document.getElementById('editSettingModal').querySelector('form').addEventListener('submit', function() {
                this.insertAdjacentHTML('beforeend', '<input type="hidden" name="form_type" value="edit">');
            });

            // Pastikan Anda punya meta tag CSRF di <head> layout Anda:
            // <meta name="csrf-token" content="{{ csrf_token() }}">

        });
    </script>
@endsection