@extends('layouts.main')

@section('title', 'Data Inventaris')
@section('content')
    <div class="main-body">
        <div class="page-wrapper">
            {{-- Header Halaman --}}
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>List Data Inventaris</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Body Halaman --}}
            <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">

                        {{-- FORM FILTER --}}
                        <div class="card">
                            <div class="card-header">
                                <h5>Filter Data</h5>
                            </div>
                            <div class="card-block">
                                <form action="{{ route('data-inventaris.index') }}" method="GET">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="start_date">Tanggal Mulai</label>
                                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="end_date">Tanggal Selesai</label>
                                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="kode_toko">Kode Toko</label>
                                            <select name="kode_toko" class="form-control">
                                                <option value="">-- Semua Kode Toko --</option>
                                                @foreach($filterKodeTokos as $kode)
                                                    <option value="{{ $kode }}" {{ request('kode_toko') == $kode ? 'selected' : '' }}>
                                                        {{ $kode }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="asal_barang">Asal Barang</label>
                                            <input type="text" name="asal_barang" class="form-control" placeholder="Cari asal barang..." value="{{ request('asal_barang') }}">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('data-inventaris.index') }}" class="btn btn-secondary mx-2">Reset</a>
                                        <a href="{{ route('data-inventaris.export', request()->query()) }}" class="btn btn-success">
                                            <i class="feather icon-download"></i> Export Excel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- TABEL DATA --}}
                        <div class="card">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {!! session('error') !!}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="card-block">
                                <button type="button" class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addModal">
                                    Add Inventaris
                                </button>
                                <button type="button" class="btn btn-success btn-round" data-bs-toggle="modal" data-bs-target="#batchUploadModal">
                                    Upload Excel
                                </button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Tgl</th>
                                                <th>Nama</th>
                                                <th>Toko</th>
                                                <th>Lok SPK</th>
                                                <th>Jenis</th>
                                                <th>Tipe</th>
                                                <th>Asal Barang</th>
                                                <th>Status</th>
                                                <th>Tgl Gantian</th>
                                                <th>Alasan Gantian</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($inventaris as $item)
                                            <tr>
                                                <td>{{ $item->tgl ? \Carbon\Carbon::parse($item->tgl)->format('d M Y') : '-' }}</td>
                                                <td>{{ $item->nama }}</td>
                                                <td>{{ $item->kode_toko }} - {{ $item->nama_toko }}</td>
                                                <td>{{ $item->lok_spk }}</td>
                                                <td>{{ $item->jenis }}</td>
                                                <td>{{ $item->tipe }}</td>
                                                <td>{{ $item->asal_barang ?? '-' }}</td>
                                                <td>
                                                    @switch($item->status)
                                                        @case(1) <span class="badge bg-success">Pengambilan</span> @break
                                                        @case(2) <span class="badge bg-info">Gantian</span> @break
                                                        @default <span class="badge bg-secondary">Lainnya</span>
                                                    @endswitch
                                                </td>
                                                <td>{{ $item->tgl_gantian ? \Carbon\Carbon::parse($item->tgl_gantian)->format('d M Y') : '-' }}</td>
                                                <td>
                                                    @if($item->status == 2 && !empty($item->alasan_gantian))
                                                        {{ $item->alasan_gantian }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->status != 2)
                                                        <button type="button" class="btn btn-info btn-round btn-sm gantian-btn" data-id="{{ $item->id }}" data-lok-spk="{{ $item->lok_spk }}">Gantian</button>
                                                        <button type="button" class="btn btn-warning btn-round btn-sm btn-edit"
                                                                data-bs-toggle="modal" data-bs-target="#editModal" data-id="{{ $item->id }}"
                                                                data-asal_barang="{{ $item->asal_barang }}"
                                                                data-nama="{{ $item->nama }}"
                                                                data-kode_toko="{{ $item->kode_toko }}" data-nama_toko="{{ $item->nama_toko }}"
                                                                data-lok_spk="{{ $item->lok_spk }}" data-jenis="{{ $item->jenis }}" data-tipe="{{ $item->tipe }}"
                                                                data-kelengkapan="{{ $item->kelengkapan }}" data-keterangan="{{ $item->keterangan }}">
                                                            Edit
                                                        </button>
                                                    @else
                                                        <button class="btn btn-light btn-round btn-sm" disabled>Gantian</button>
                                                        <button class="btn btn-light btn-round btn-sm" disabled>Edit</button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="11" class="text-center">Tidak ada data yang cocok dengan filter.</td>
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
    {{-- ADD --}}
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('data-inventaris.store') }}" method="POST" id="addForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Tambah Data Inventaris</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @error('form')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <div class="mb-3">
                            <label for="asal_barang" class="form-label">Asal Barang</label>
                            <input type="text" class="form-control add-input" name="asal_barang" placeholder="Ketik asal barang">
                        </div>
                        <div class="mb-3"><label class="form-label">Nama</label><input type="text" class="form-control add-input" name="nama"></div>
                        <div class="mb-3"><label class="form-label">Kode Toko</label><input type="text" class="form-control add-input" name="kode_toko"></div>
                        <div class="mb-3"><label class="form-label">Nama Toko</label><input type="text" class="form-control add-input" name="nama_toko"></div>
                        <div class="mb-3"><label class="form-label">Lok SPK</label><input type="text" class="form-control add-input" name="lok_spk"></div>
                        <div class="mb-3">
                            <label class="form-label">Jenis</label>
                            <select class="form-control add-input" name="jenis">
                                <option value="">Pilih Jenis</option>
                                <option value="HP">HP</option>
                                <option value="LP">LP</option>
                                <option value="TAB">TAB</option>
                                <option value="TV">TV</option>
                                <option value="LAIN LAIN">LAIN LAIN</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Tipe</label><input type="text" class="form-control add-input" name="tipe"></div>
                        <div class="mb-3">
                            <label class="form-label">Kelengkapan</label>
                            <select class="form-control add-input" name="kelengkapan">
                                <option value="">Pilih</option>
                                <option value="BOX">BOX</option>
                                <option value="BTG">BTG</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Keterangan</label><textarea class="form-control add-input" name="keterangan" rows="3"></textarea></div>
                        <small class="text-muted">* Tombol Simpan aktif jika ada minimal satu kolom terisi.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="addSubmitBtn" disabled>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- EDIT --}}
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Data Inventaris</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Asal Barang</label>
                            <input type="text" class="form-control" id="edit_asal_barang" name="asal_barang" placeholder="Ketik asal barang">
                        </div>
                        <div class="mb-3"><label class="form-label">Nama</label><input type="text" class="form-control" id="edit_nama" name="nama"></div>
                        <div class="mb-3"><label class="form-label">Kode Toko</label><input type="text" class="form-control" id="edit_kode_toko" name="kode_toko"></div>
                        <div class="mb-3"><label class="form-label">Nama Toko</label><input type="text" class="form-control" id="edit_nama_toko" name="nama_toko"></div>
                        <div class="mb-3"><label class="form-label">Lok SPK</label><input type="text" class="form-control" id="edit_lok_spk" name="lok_spk"></div>
                        <div class="mb-3">
                            <label class="form-label">Jenis</label>
                            <select class="form-control" id="edit_jenis" name="jenis">
                                <option value="">Pilih Jenis</option>
                                <option value="HP">HP</option>
                                <option value="LP">LP</option>
                                <option value="TAB">TAB</option>
                                <option value="TV">TV</option>
                                <option value="LAIN LAIN">LAIN LAIN</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Tipe</label><input type="text" class="form-control" id="edit_tipe" name="tipe"></div>
                        <div class="mb-3">
                            <label class="form-label">Kelengkapan</label>
                            <select class="form-control" id="edit_kelengkapan" name="kelengkapan">
                                <option value="">Pilih</option>
                                <option value="BOX">BOX</option>
                                <option value="BTG">BTG</option>
                            </select>
                        </div>
                        <div class="mb-3"><label class="form-label">Keterangan</label><textarea class="form-control" id="edit_keterangan" name="keterangan" rows="3"></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- GANTIAN --}}
    <div class="modal fade" id="gantianModal" tabindex="-1" aria-labelledby="gantianModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="gantianForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="gantianModalLabel">Konfirmasi Gantian Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda akan mengubah status barang <strong id="gantianLokSpk"></strong> menjadi "Gantian".</p>
                        <div class="mb-3">
                            <label class="form-label">Alasan Gantian <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="alasan_gantian" name="alasan_gantian" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Yakin & Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- BATCH UPLOAD --}}
    <div class="modal fade" id="batchUploadModal" tabindex="-1" aria-labelledby="batchUploadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('data-inventaris.batchUpload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="batchUploadModalLabel">Upload Data Inventaris (Batch)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <p>Silakan unduh template. <br>
                            <a href="{{ asset('files/template data inventaris.xlsx') }}" class="btn btn-info btn-round" download>
                                <i class="feather icon-download"></i> Download Template
                            </a>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label for="file" class="form-label">Pilih File Excel</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".xlsx, .xls" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload dan Proses</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ===== Add Modal: Enable submit only if any input has value =====
            const addForm = document.getElementById('addForm');
            const addInputs = addForm.querySelectorAll('.add-input');
            const addSubmitBtn = document.getElementById('addSubmitBtn');

            function toggleAddSubmit() {
                let anyFilled = false;
                addInputs.forEach(el => {
                    const v = (el.value || '').trim();
                    if (v.length > 0) anyFilled = true;
                });
                addSubmitBtn.disabled = !anyFilled;
            }
            addInputs.forEach(el => {
                el.addEventListener('input', toggleAddSubmit);
                el.addEventListener('change', toggleAddSubmit);
            });
            // reset on open
            const addModalEl = document.getElementById('addModal');
            addModalEl.addEventListener('shown.bs.modal', () => toggleAddSubmit());
            addModalEl.addEventListener('hidden.bs.modal', () => {
                addForm.reset();
                toggleAddSubmit();
            });

            // ===== Edit Modal: populate =====
            const editModal = document.getElementById('editModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const form = document.getElementById('editForm');

                const id            = button.getAttribute('data-id');
                const asalBarang    = button.getAttribute('data-asal_barang');
                const nama          = button.getAttribute('data-nama');
                const kode_toko     = button.getAttribute('data-kode_toko');
                const nama_toko     = button.getAttribute('data-nama_toko');
                const lok_spk       = button.getAttribute('data-lok_spk');
                const jenis         = button.getAttribute('data-jenis');
                const tipe          = button.getAttribute('data-tipe');
                const kelengkapan   = button.getAttribute('data-kelengkapan');
                const keterangan    = button.getAttribute('data-keterangan');

                let url = "{{ route('data-inventaris.update', ':id') }}";
                url = url.replace(':id', id);
                form.action = url;

                form.querySelector('#edit_asal_barang').value = asalBarang || '';
                form.querySelector('#edit_nama').value        = nama || '';
                form.querySelector('#edit_kode_toko').value   = kode_toko || '';
                form.querySelector('#edit_nama_toko').value   = nama_toko || '';
                form.querySelector('#edit_lok_spk').value     = lok_spk || '';
                form.querySelector('#edit_jenis').value       = jenis || '';
                form.querySelector('#edit_tipe').value        = tipe || '';
                form.querySelector('#edit_kelengkapan').value = kelengkapan || '';
                form.querySelector('#edit_keterangan').value  = keterangan || '';
            });

            // ===== Gantian Modal =====
            const gantianModalEl = document.getElementById('gantianModal');
            const gantianModal = new bootstrap.Modal(gantianModalEl);

            document.querySelectorAll('.gantian-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.id;
                    const itemLokSpk = this.dataset.lokSpk;
                    const form = document.getElementById('gantianForm');
                    let url = "{{ route('data-inventaris.gantian', ':id') }}";
                    url = url.replace(':id', itemId);
                    form.action = url;
                    document.getElementById('gantianLokSpk').textContent = itemLokSpk;
                    gantianModal.show();
                });
            });

            // Submit form gantian via fetch
            document.getElementById('gantianForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const url = form.action;
                const formData = new FormData(form);
                fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        gantianModal.hide();
                        Swal.fire({ icon: 'success', title: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                    } else {
                        throw new Error(data.message || 'Terjadi kesalahan.');
                    }
                })
                .catch(error => {
                    gantianModal.hide();
                    Swal.fire({ icon: 'error', title: 'Error!', text: error.message, showConfirmButton: true });
                });
            });
        });
    </script>
@endsection
