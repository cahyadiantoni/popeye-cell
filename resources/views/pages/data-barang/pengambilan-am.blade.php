@extends('layouts.main')

@section('title', 'Pengambilan AM')

@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
  <div class="page-wrapper">
    <div class="page-header">
      <div class="row align-items-end">
        <div class="col-lg-8">
          <div class="page-header-title">
            <div class="d-inline">
              <h4>Pengambilan Barang oleh AM</h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="page-body">
      {{-- Flash --}}
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- Riwayat + Tombol Tambah --}}
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Riwayat Pengambilan AM</h5>
          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalPengambilanAm">
            + Tambah Pengambilan
          </button>
        </div>
        <div class="card-block">
          <div class="dt-responsive table-responsive">
            <table id="table-pengambilan" class="table table-striped table-bordered nowrap" style="width:100%;">
              <thead>
                <tr>
                  <th>Tgl Ambil</th>
                  <th>LOK SPK</th>
                  <th>Tipe Barang</th>
                  <th>Nama AM</th>
                  <th>Nama Toko</th>
                  <th>User Input</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- MODAL Input --}}
<div class="modal fade" id="modalPengambilanAm" tabindex="-1" role="dialog" aria-labelledby="modalPengambilanAmLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form action="{{ route('pengambilan-am.store') }}" method="POST" id="formPengambilanAm">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalPengambilanAmLabel">Input Pengambilan AM</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div class="row">
            {{-- Tanggal Ambil --}}
            <div class="col-md-4">
              <div class="form-group">
                <label for="tgl_ambil">Tanggal Ambil</label>
                <input type="date" name="tgl_ambil" id="tgl_ambil" class="form-control" value="{{ date('Y-m-d') }}" required>
              </div>
            </div>

            {{-- LOK SPK --}}
            <div class="col-md-8">
              <div class="form-group">
                <label for="lok_spk">LOK SPK</label>
                <input type="text" name="lok_spk" id="lok_spk" class="form-control" placeholder="Contoh: LOK-ABC-001" required>
                <small class="text-muted">Masukkan LOK SPK status=1. Non-admin hanya bisa dari gudang sendiri.</small>
              </div>
            </div>

            {{-- Nama AM --}}
            <div class="col-md-4">
              <div class="form-group">
                <label for="nama_am">Nama AM</label>
                <input type="text" name="nama_am" id="nama_am" class="form-control" placeholder="Masukkan Nama AM" required>
              </div>
            </div>

            {{-- Kode Toko --}}
            <div class="col-md-4">
              <div class="form-group">
                <label for="kode_toko">Kode Toko (Opsional)</label>
                <input type="text" name="kode_toko" id="kode_toko" class="form-control" placeholder="Contoh: TKBGS">
              </div>
            </div>

            {{-- Nama Toko --}}
            <div class="col-md-4">
              <div class="form-group">
                <label for="nama_toko">Nama Toko (Opsional)</label>
                <input type="text" name="nama_toko" id="nama_toko" class="form-control" placeholder="Contoh: Toko Bagus">
              </div>
            </div>

            {{-- Keterangan --}}
            <div class="col-md-12">
              <div class="form-group">
                <label for="keterangan">Keterangan (Opsional)</label>
                <textarea name="keterangan" id="keterangan" class="form-control" rows="3"></textarea>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- JS Kustom (digabung) --}}
<script>
  $(document).ready(function () {
    $('#table-pengambilan').DataTable({
      processing: true,
      serverSide: true,
      ajax: "{{ route('pengambilan-am.index') }}",
      columns: [
        { data: 'tgl_ambil',   name: 'tgl_ambil' },
        { data: 'lok_spk',     name: 'lok_spk' },
        { data: 'barang.tipe', name: 'barang.tipe' }, // nested relasi
        { data: 'nama_am',     name: 'nama_am' },
        { data: 'nama_toko',   name: 'nama_toko' },
        { data: 'user.name',   name: 'user.name' },   // nested relasi
        { data: 'action',      name: 'action', orderable: false, searchable: false },
      ],
      order: [[0, 'desc']]
    });
  });

  $('#modalPengambilanAm').on('hidden.bs.modal', function(){
      const f = this.querySelector('form');
      if (f) f.reset();
    });

    // SweetAlert2 konfirmasi hapus (fallback confirm jika Swal tidak ada)
    $(document).on('submit', '.delete-form', function(e){
      e.preventDefault();
      const form = this;

      if (typeof Swal === 'undefined') {
        if (confirm('Hapus data ini? Status barang akan dikembalikan (tersedia).')) form.submit();
        return;
      }

      Swal.fire({
        title: 'Hapus data?',
        text: 'Status barang akan dikembalikan (tersedia).',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
      }).then((res) => {
        if (res.isConfirmed) form.submit();
      });
    });

    // Optional: auto reload kalau ada flash (tidak wajib jika redirect)
    @if(session('success') || session('error'))
      try { dt.ajax.reload(null, false); } catch(e) {}
    @endif
</script>

@endsection
