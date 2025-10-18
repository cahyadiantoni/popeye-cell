{{-- resources/views/pages/history-todo-transfer/index.blade.php --}}
@extends('layouts.main')
@section('title','History Todo Transfer')

@section('content')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<div class="main-body">
  <div class="page-wrapper">
    <div class="page-header">
      <div class="row align-items-end">
        <div class="col-lg-8">
          <div class="page-header-title">
            <div class="d-inline"><h4>History Todo Transfer</h4></div>
          </div>
        </div>
      </div>
    </div>

    <div class="page-body">
      <div class="row">
        <div class="col-sm-12">

          {{-- Notif --}}
          @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
              {{ session('success') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif
          @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
              <strong>Error!</strong>
              <ul class="mb-0">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
              </ul>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif
          @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
              {!! session('error') !!}
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif

          {{-- Filter --}}
          <div class="card">
            <div class="card-header"><h5>Filter</h5></div>
            <div class="card-block">
              <form action="{{ route('history-todo-transfer.index') }}" method="GET">
                <div class="row g-3">
                  <div class="col-md-2">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Kode Toko</label>
                    <select name="kode_toko" class="form-control">
                      <option value="">-- Semua --</option>
                      @foreach($kodeTokos as $kt)
                        <option value="{{ $kt }}" {{ request('kode_toko')==$kt?'selected':'' }}>{{ $kt }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Nama Bank</label>
                    <select name="nama_bank" class="form-control">
                      <option value="">-- Semua --</option>
                      @foreach($namaBanks as $nb)
                        <option value="{{ $nb }}" {{ request('nama_bank')==$nb?'selected':'' }}>{{ $nb }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Min Nominal</label>
                    <input type="number" step="0.01" name="min_nominal" class="form-control" value="{{ request('min_nominal') }}">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Max Nominal</label>
                    <input type="number" step="0.01" name="max_nominal" class="form-control" value="{{ request('max_nominal') }}">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Cari (toko/AM/keterangan)</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ketik kata kunci...">
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                  <button class="btn btn-primary">Filter</button>
                  <a href="{{ route('history-todo-transfer.index') }}" class="btn btn-secondary ms-2">Reset</a>
                  <a href="{{ route('history-todo-transfer.exportTemplate') }}" class="btn btn-success ms-2">
                    <i class="feather icon-download"></i> Download Template Excel
                  </a>
                </div>
              </form>
            </div>
          </div>

          {{-- Tabel + Actions --}}
          <div class="card">
            <div class="card-block">
              <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addModal">
                Tambah Data
              </button>
              <button class="btn btn-info btn-round" data-bs-toggle="modal" data-bs-target="#batchModal">
                Upload (Copy-Paste)
              </button>
              <hr>
              <div class="dt-responsive table-responsive">
                <table class="table table-striped table-bordered nowrap" style="width:100%">
                  <thead>
                    <tr>
                      <th>Tgl Transfer</th>
                      <th>Kode Toko</th>
                      <th>Nama Toko</th>
                      <th>Nama AM</th>
                      <th>Bank</th>
                      <th>No Rek</th>
                      <th>Nama Rek</th>
                      <th class="text-end">Nominal</th>
                      <th>Keterangan</th>
                      <th>Bukti</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($items as $it)
                      <tr>
                        <td>{{ \Carbon\Carbon::parse($it->tgl_transfer)->format('d M Y') }}</td>
                        <td>{{ $it->kode_toko }}</td>
                        <td>{{ $it->nama_toko }}</td>
                        <td>{{ $it->nama_am }}</td>
                        <td>{{ $it->nama_bank }}</td>
                        <td>{{ $it->norek_bank }}</td>
                        <td>{{ $it->nama_norek }}</td>
                        <td class="text-end">{{ number_format($it->nominal, 0, ',', '.') }}</td>
                        <td>{{ $it->keterangan }}</td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary btn-upload-bukti"
                                    data-id="{{ $it->id }}"
                                    data-nama="{{ $it->nama_toko ?? $it->kode_toko }}">
                              Upload
                            </button>

                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-lihat-bukti"
                                    data-id="{{ $it->id }}">
                              Lihat
                              @if($it->proofs_count > 0)
                                <span class="badge bg-secondary">{{ $it->proofs_count }}</span>
                              @endif
                            </button>
                          </div>
                        </td>
                        <td>
                          <button class="btn btn-warning btn-sm btn-round btn-edit"
                            data-id="{{ $it->id }}"
                            data-tgl="{{ $it->tgl_transfer->format('Y-m-d') }}"
                            data-kode_toko="{{ $it->kode_toko }}"
                            data-nama_toko="{{ $it->nama_toko }}"
                            data-nama_am="{{ $it->nama_am }}"
                            data-keterangan="{{ $it->keterangan }}"
                            data-nama_bank="{{ $it->nama_bank }}"
                            data-norek_bank="{{ $it->norek_bank }}"
                            data-nama_norek="{{ $it->nama_norek }}"
                            data-nominal="{{ $it->nominal }}"
                            data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>

                          <button class="btn btn-danger btn-sm btn-round btn-delete" data-id="{{ $it->id }}">Hapus</button>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="10" class="text-center">Tidak ada data.</td></tr>
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

{{--  MODAL: ADD --}}
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('history-todo-transfer.store') }}" method="POST" id="addForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Tambah Data</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @error('form') <div class="alert alert-danger">{{ $message }}</div> @enderror

          <div class="mb-2">
            <label class="form-label">Tanggal Transfer</label>
            <input type="date" name="tgl_transfer" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Kode Toko (angka saja)</label>
            <input type="text" name="kode_toko" class="form-control" pattern="[0-9]+" title="Hanya angka" required>
          </div>
          <div class="mb-2"><label class="form-label">Nama Toko</label><input type="text" name="nama_toko" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Nama AM</label><input type="text" name="nama_am" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Nama Bank</label><input type="text" name="nama_bank" class="form-control"></div>
          <div class="mb-2">
            <label class="form-label">No Rekening (angka saja)</label>
            <input type="text" name="norek_bank" class="form-control" pattern="[0-9]+" title="Hanya angka" required>
          </div>
          <div class="mb-2"><label class="form-label">Nama Rekening</label><input type="text" name="nama_norek" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Nominal</label><input type="number" step="0.01" min="0" name="nominal" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Keterangan</label><textarea name="keterangan" class="form-control" rows="2"></textarea></div>

          <small class="text-muted">* Kombinasi (tgl_transfer, kode_toko, norek_bank, nominal) harus unik.</small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{--  MODAL: EDIT --}}
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editForm" method="POST">
        @csrf @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Edit Data</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Tanggal Transfer</label><input type="date" id="e_tgl" name="tgl_transfer" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Kode Toko</label><input type="text" id="e_kode_toko" name="kode_toko" class="form-control" pattern="[0-9]+" required></div>
          <div class="mb-2"><label class="form-label">Nama Toko</label><input type="text" id="e_nama_toko" name="nama_toko" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Nama AM</label><input type="text" id="e_nama_am" name="nama_am" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Nama Bank</label><input type="text" id="e_nama_bank" name="nama_bank" class="form-control"></div>
          <div class="mb-2"><label class="form-label">No Rekening</label><input type="text" id="e_norek_bank" name="norek_bank" class="form-control" pattern="[0-9]+" required></div>
          <div class="mb-2"><label class="form-label">Nama Rekening</label><input type="text" id="e_nama_norek" name="nama_norek" class="form-control"></div>
          <div class="mb-2"><label class="form-label">Nominal</label><input type="number" step="0.01" min="0" id="e_nominal" name="nominal" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Keterangan</label><textarea id="e_keterangan" name="keterangan" class="form-control" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{--  MODAL: BATCH PASTE --}}
<div class="modal fade" id="batchModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('history-todo-transfer.batchPaste') }}" method="POST" id="batchForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Upload (Copy-Paste dari Excel)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>
            1) <a href="{{ route('history-todo-transfer.exportTemplate') }}" class="btn btn-sm btn-success">
              <i class="feather icon-download"></i> Download Template
            </a>
            &nbsp; 2) Isi di Excel/Spreadsheet &nbsp; 3) Copy seluruh tabel (termasuk header) &nbsp; 4) Paste ke kolom di bawah:
          </p>
          <textarea name="pasted_table" id="pasted_table" class="form-control" rows="12" placeholder="Paste di sini..."></textarea>
          <div class="alert alert-info mt-2 mb-0">
            <strong>Catatan Validasi:</strong>
            <ul class="mb-0">
              <li><code>tgl_transfer</code> tidak boleh lebih dari hari ini.</li>
              <li><code>kode_toko</code> & <code>norek_bank</code> hanya angka (tanpa spasi/simbol/huruf).</li>
              <li>Duplikat (tgl_transfer, kode_toko, norek_bank, nominal) akan menggagalkan seluruh upload.</li>
            </ul>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="uploadBuktiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="uploadBuktiForm" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Upload Bukti</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label">Pilih File (bisa banyak)</label>
            <input type="file" class="form-control" name="files[]" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple required>
            <small class="text-muted">Maks 5MB per file. Format: JPG, PNG, WEBP, PDF.</small>
          </div>
          <input type="hidden" id="upload_bukti_todo_id" name="todo_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="lihatBuktiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Bukti Transfer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="buktiList" class="row g-3">
          {{-- akan diisi via JS --}}
        </div>
        <small class="text-muted d-block mt-2">Klik item untuk preview / download.</small>
      </div>
    </div>
  </div>
</div>


{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
  $('table.table').DataTable({
    pageLength: 25,
    order: [[0, 'desc']], // urut berdasarkan kolom Tgl Transfer desc
    columnDefs: [
      { targets: -1, orderable: false, searchable: false }, // kolom aksi
      { targets: -2, orderable: false, searchable: false }  // kolom bukti
    ],
    language: {
      search: "Cari:",
      lengthMenu: "Tampilkan _MENU_ data per halaman",
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      paginate: {
        previous: "Sebelumnya",
        next: "Berikutnya"
      },
      zeroRecords: "Tidak ada data yang cocok",
    }
  });
});

document.addEventListener('DOMContentLoaded', function() {
  // EDIT modal populate
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      let url = "{{ route('history-todo-transfer.update', ':id') }}".replace(':id', id);
      const f = document.getElementById('editForm');
      f.action = url;

      document.getElementById('e_tgl').value        = this.dataset.tgl || '';
      document.getElementById('e_kode_toko').value  = this.dataset.kode_toko || '';
      document.getElementById('e_nama_toko').value  = this.dataset.nama_toko || '';
      document.getElementById('e_nama_am').value    = this.dataset.nama_am || '';
      document.getElementById('e_nama_bank').value  = this.dataset.nama_bank || '';
      document.getElementById('e_norek_bank').value = this.dataset.norek_bank || '';
      document.getElementById('e_nama_norek').value = this.dataset.nama_norek || '';
      document.getElementById('e_nominal').value    = this.dataset.nominal || '';
      document.getElementById('e_keterangan').value = this.dataset.keterangan || '';
    });
  });

  // DELETE with SweetAlert
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      Swal.fire({
        title: 'Hapus data ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch("{{ url('/history-todo-transfer') }}/" + id, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            },
            body: new URLSearchParams({ _method: 'DELETE' })
          }).then(r => r.json())
           .then(resp => {
              if(resp.status === 'success'){
                Swal.fire({icon:'success',title:'Terhapus',timer:1200,showConfirmButton:false})
                  .then(()=>location.reload());
              } else {
                Swal.fire({icon:'error',title:'Gagal',text:resp.message || 'Terjadi kesalahan'});
              }
           }).catch(() => {
              Swal.fire({icon:'error',title:'Gagal',text:'Terjadi kesalahan.'});
           });
        }
      });
    });
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const uploadModalEl = document.getElementById('uploadBuktiModal');
  const lihatModalEl  = document.getElementById('lihatBuktiModal');
  const buktiListEl   = document.getElementById('buktiList');

  const uploadModal = uploadModalEl ? new bootstrap.Modal(uploadModalEl) : null;
  const lihatModal  = lihatModalEl  ? new bootstrap.Modal(lihatModalEl)  : null;

    // OPEN UPLOAD MODAL
  document.querySelectorAll('.btn-upload-bukti').forEach(btn => {
    btn.addEventListener('click', function(){
      if (!uploadModal) return;
      const id = this.dataset.id;
      document.getElementById('upload_bukti_todo_id').value = id;
      document.getElementById('uploadBuktiForm').reset();
      uploadModal.show();
    });
  });

  // SUBMIT UPLOAD
  document.getElementById('uploadBuktiForm').addEventListener('submit', function(e){
    e.preventDefault();
    const id = document.getElementById('upload_bukti_todo_id').value;
    const fd = new FormData(this);
    // hapus field "todo_id" dari payload, url sudah bawa id
    fd.delete('todo_id');

    fetch("{{ url('/history-todo-transfer') }}/"+id+"/bukti", {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
      body: fd
    }).then(r => r.json())
      .then(resp => {
        if(resp.status === 'success'){
          uploadModal.hide();
          Swal.fire({icon:'success',title:resp.message,timer:1300,showConfirmButton:false})
            .then(()=>location.reload());
        } else {
          throw new Error(resp.message || 'Gagal upload.');
        }
      }).catch(err=>{
        Swal.fire({icon:'error',title:'Error',text:err.message});
      });
  });

    // OPEN LIHAT MODAL
  document.querySelectorAll('.btn-lihat-bukti').forEach(btn => {
    btn.addEventListener('click', function(){
      if (!lihatModal || !buktiListEl) return;
      const id = this.dataset.id;
      buktiListEl.innerHTML = '<div class="col-12 text-center text-muted">Memuat...</div>';
      fetch("{{ url('/history-todo-transfer') }}/"+id+"/bukti", { headers: {'Accept':'application/json'} })
        .then(r=>r.json())
        .then(resp=>{
          if(resp.status !== 'success') throw new Error('Gagal memuat.');
          const items = resp.data || [];
          if(items.length === 0){
            buktiListEl.innerHTML = '<div class="col-12 text-center text-muted">Belum ada bukti.</div>';
          } else {
            buktiListEl.innerHTML = items.map(it => {
              const isImage = it.mime.startsWith('image/');
              const thumb = isImage
                ? `<img src="${it.url}" class="img-fluid rounded" style="max-height:120px;object-fit:cover;">`
                : `<div class="p-3 border rounded text-center">PDF<br><small>${it.name}</small></div>`;
              return `
                <div class="col-6 col-md-4">
                  <a href="${it.download_url}" target="_blank" class="text-decoration-none text-dark">
                    ${thumb}
                  </a>
                  <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-truncate" title="${it.name}">${it.name}</small>
                    <button class="btn btn-sm btn-outline-danger btn-del-proof" data-id="${it.id}">Hapus</button>
                  </div>
                </div>
              `;
            }).join('');
            // bind delete...
            buktiListEl.querySelectorAll('.btn-del-proof').forEach((dbtn) => {
              dbtn.addEventListener('click', function (e) {
                e.preventDefault();
            
                const pid  = this.dataset.id;
                const card = this.closest('.col-6, .col-md-4');            // kartu bukti
                const lihatModalEl = document.getElementById('lihatBuktiModal');
                const lihatModal   = bootstrap.Modal.getInstance(lihatModalEl) || new bootstrap.Modal(lihatModalEl);
            
                // Tutup modal lihat dulu
                lihatModal.hide();
            
                // Setelah modal benar-benar tertutup, baru munculkan konfirmasi hapus
                const onHidden = () => {
                  lihatModalEl.removeEventListener('hidden.bs.modal', onHidden);
            
                  Swal.fire({
                    title: 'Hapus bukti ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                  }).then((res) => {
                    if (!res.isConfirmed) {
                      // Kalau batal, boleh tampilkan lagi modal lihatnya (opsional)
                      lihatModal.show();
                      return;
                    }
            
                    fetch("{{ url('/history-todo-transfer/bukti') }}/" + pid, {
                      method: 'POST',
                      headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                      },
                      body: new URLSearchParams({ _method: 'DELETE' })
                    })
                    .then((r) => r.json())
                    .then((res2) => {
                      if (res2.status === 'success') {
                        // Hapus kartu di DOM (kalau ingin buka lagi modalnya dengan list yang sudah berkurang)
                        if (card) card.remove();
                        Swal.fire({ icon: 'success', title: 'Terhapus', timer: 1200, showConfirmButton: false })
                          .then(() => {
                            // Opsi 1: buka lagi modal tanpa reload (list sudah ter-update karena card dihapus)
                            // lihatModal.show();
            
                            // Opsi 2: refresh halaman biar pasti sinkron semua badge/jumlah
                            location.reload();
                          });
                      } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res2.message || 'Terjadi kesalahan' })
                          .then(() => lihatModal.show());
                      }
                    })
                    .catch(() => {
                      Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan' })
                        .then(() => lihatModal.show());
                    });
                  });
                };
            
                // Pastikan konfirmasi muncul setelah animasi close modal selesai
                lihatModalEl.addEventListener('hidden.bs.modal', onHidden, { once: true });
              });
            });
          }
          lihatModal.show();
        })
        .catch(()=> {
          buktiListEl.innerHTML = '<div class="col-12 text-center text-danger">Gagal memuat bukti.</div>';
          lihatModal.show();
        });
    });
  });
});
</script>
@endsection
