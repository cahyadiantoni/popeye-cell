{{-- resources/views/pages/tokopedia-barang-masuk/index.blade.php --}}
@extends('layouts.main')
@section('title','Tokopedia Barang Masuk')

@section('content')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<div class="main-body">
  <div class="page-wrapper">
    <div class="page-header">
      <div class="row align-items-end">
        <div class="col-lg-8">
          <div class="page-header-title">
            <div class="d-inline"><h4>Tokopedia Barang Masuk</h4></div>
          </div>
        </div>
      </div>
    </div>

    <div class="page-body">
      <div class="row">
        <div class="col-sm-12">

          {{-- Alerts --}}
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
              <form action="{{ route('tokopedia-barang-masuk.index') }}" method="GET">
                <div class="row g-3">
                  <div class="col-md-2">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Nama Barang</label>
                    <select name="nama_barang" class="form-control">
                      <option value="">-- Semua --</option>
                      @foreach($namaBarangOpts as $nb)
                        <option value="{{ $nb }}" {{ request('nama_barang')==$nb?'selected':'' }}>{{ $nb }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Min Total</label>
                    <input type="number" step="0.01" name="min_total" class="form-control" value="{{ request('min_total') }}">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Max Total</label>
                    <input type="number" step="0.01" name="max_total" class="form-control" value="{{ request('max_total') }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Cari Nama Barang</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ketik nama barang...">
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                  <button class="btn btn-primary">Filter</button>
                  <a href="{{ route('tokopedia-barang-masuk.index') }}" class="btn btn-secondary ms-2">Reset</a>
                  <a href="{{ route('tokopedia-barang-masuk.exportTemplate') }}" class="btn btn-success ms-2">
                    <i class="feather icon-download"></i> Download Template Excel
                  </a>
                </div>
              </form>
            </div>
          </div>

          {{-- Table & Actions --}}
          <div class="card">
            <div class="card-block">
              <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Data</button>
              <button class="btn btn-info btn-round" data-bs-toggle="modal" data-bs-target="#batchModal">Upload (Copy-Paste)</button>
              <hr>
              <div class="dt-responsive table-responsive">
                <table id="bmTable" class="table table-striped table-bordered nowrap" style="width:100%">
                  <thead>
                    <tr>
                      <th>Tgl Beli</th>
                      <th>Nama Barang</th>
                      <th class="text-end">Qty</th>
                      <th class="text-end">Harga Satuan</th>
                      <th class="text-end">Ongkir</th>
                      <th class="text-end">Potongan</th>
                      <th class="text-end">Total Harga</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($items as $it)
                      <tr>
                        <td>{{ \Carbon\Carbon::parse($it->tgl_beli)->format('d M Y') }}</td>
                        <td>{{ $it->nama_barang }}</td>
                        <td class="text-end">{{ number_format($it->quantity,0,',','.') }}</td>
                        <td class="text-end">{{ number_format($it->harga_satuan,0,',','.') }}</td>
                        <td class="text-end">{{ number_format($it->harga_ongkir,0,',','.') }}</td>
                        <td class="text-end">{{ number_format($it->harga_potongan,0,',','.') }}</td>
                        <td class="text-end">{{ number_format($it->total_harga,0,',','.') }}</td>
                        <td>
                          <button class="btn btn-warning btn-sm btn-round btn-edit"
                            data-id="{{ $it->id }}"
                            data-tgl="{{ $it->tgl_beli->format('Y-m-d') }}"
                            data-nama_barang="{{ $it->nama_barang }}"
                            data-quantity="{{ $it->quantity }}"
                            data-harga_satuan="{{ $it->harga_satuan }}"
                            data-harga_ongkir="{{ $it->harga_ongkir }}"
                            data-harga_potongan="{{ $it->harga_potongan }}"
                            data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>

                          <button class="btn btn-danger btn-sm btn-round btn-delete" data-id="{{ $it->id }}">Hapus</button>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="8" class="text-center">Tidak ada data.</td></tr>
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

{{-- MODAL: ADD --}}
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('tokopedia-barang-masuk.store') }}" method="POST" id="addForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Tambah Data</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          @error('form') <div class="alert alert-danger">{{ $message }}</div> @enderror

          <div class="mb-2">
            <label class="form-label">Tanggal Beli</label>
            <input type="date" name="tgl_beli" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Nama Barang (UPPERCASE)</label>
            <input type="text" name="nama_barang" class="form-control" required>
            <small class="text-muted">Akan diubah otomatis ke huruf besar & tanpa spasi di awal/akhir.</small>
          </div>
          <div class="mb-2">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control" min="1" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Harga Satuan</label>
            <input type="number" step="0.01" min="0" name="harga_satuan" class="form-control" required>
          </div>
          <div class="mb-2">
            <label class="form-label">Harga Ongkir</label>
            <input type="number" step="0.01" min="0" name="harga_ongkir" class="form-control" value="0">
          </div>
          <div class="mb-2">
            <label class="form-label">Harga Potongan</label>
            <input type="number" step="0.01" min="0" name="harga_potongan" class="form-control" value="0">
          </div>
          <small class="text-muted">* total_harga dihitung otomatis saat simpan.</small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL: EDIT --}}
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
          <div class="mb-2"><label class="form-label">Tanggal Beli</label><input type="date" id="e_tgl" name="tgl_beli" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Nama Barang (UPPERCASE)</label><input type="text" id="e_nama_barang" name="nama_barang" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Quantity</label><input type="number" id="e_quantity" name="quantity" class="form-control" min="1" required></div>
          <div class="mb-2"><label class="form-label">Harga Satuan</label><input type="number" step="0.01" min="0" id="e_harga_satuan" name="harga_satuan" class="form-control" required></div>
          <div class="mb-2"><label class="form-label">Harga Ongkir</label><input type="number" step="0.01" min="0" id="e_harga_ongkir" name="harga_ongkir" class="form-control" value="0"></div>
          <div class="mb-2"><label class="form-label">Harga Potongan</label><input type="number" step="0.01" min="0" id="e_harga_potongan" name="harga_potongan" class="form-control" value="0"></div>
          <small class="text-muted">* total_harga dihitung otomatis saat update.</small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL: BATCH PASTE --}}
<div class="modal fade" id="batchModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('tokopedia-barang-masuk.batchPaste') }}" method="POST" id="batchForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Upload (Copy-Paste dari Excel)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>
            1) <a href="{{ route('tokopedia-barang-masuk.exportTemplate') }}" class="btn btn-sm btn-success">
              <i class="feather icon-download"></i> Download Template
            </a>
            &nbsp; 2) Isi di Excel (header wajib) &nbsp; 3) Copy seluruh tabel &nbsp; 4) Paste di bawah:
          </p>
          <textarea name="pasted_table" class="form-control" rows="12" placeholder="Header: tgl_beli, nama_barang, quantity, harga_satuan, harga_ongkir, harga_potongan"></textarea>
          <div class="alert alert-info mt-2 mb-0">
            <strong>Catatan:</strong>
            <ul class="mb-0">
              <li>nama_barang otomatis UPPERCASE & tanpa spasi awal/akhir.</li>
              <li>tgl_beli tidak boleh lebih dari hari ini.</li>
              <li>Duplikat (tgl_beli, nama_barang, quantity, total_harga) menggagalkan seluruh upload.</li>
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

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
  $('#bmTable').DataTable({
    pageLength: 25,
    order: [[0, 'desc']], // urut Tgl Beli terbaru
    columnDefs: [
      { targets: -1, orderable: false, searchable: false }, // kolom aksi
      { targets: [2,3,4,5,6], className: 'text-end' }       // kolom angka rata kanan
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
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
  // EDIT populate
  document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      const f = document.getElementById('editForm');
      f.action = "{{ route('tokopedia-barang-masuk.update', ':id') }}".replace(':id', id);

      document.getElementById('e_tgl').value = this.dataset.tgl || '';
      document.getElementById('e_nama_barang').value = this.dataset.nama_barang || '';
      document.getElementById('e_quantity').value = this.dataset.quantity || 1;
      document.getElementById('e_harga_satuan').value = this.dataset.harga_satuan || 0;
      document.getElementById('e_harga_ongkir').value = this.dataset.harga_ongkir || 0;
      document.getElementById('e_harga_potongan').value = this.dataset.harga_potongan || 0;
    });
  });

  // DELETE with SweetAlert
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      Swal.fire({
        title: 'Hapus data ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then((res)=>{
        if(res.isConfirmed){
          fetch("{{ url('/tokopedia-barang-masuk') }}/"+id, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            },
            body: new URLSearchParams({ _method: 'DELETE' })
          }).then(r=>r.json())
           .then(resp=>{
             if(resp.status==='success'){
               Swal.fire({icon:'success',title:'Terhapus',timer:1200,showConfirmButton:false})
                 .then(()=>location.reload());
             } else {
               Swal.fire({icon:'error',title:'Gagal',text:resp.message||'Terjadi kesalahan'});
             }
           }).catch(()=> Swal.fire({icon:'error',title:'Gagal',text:'Terjadi kesalahan.'}));
        }
      });
    });
  });
});
</script>
@endsection
