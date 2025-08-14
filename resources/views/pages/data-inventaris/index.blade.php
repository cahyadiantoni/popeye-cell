@extends('layouts.main')

@section('title', 'Data Inventaris')
@section('content')
    <div class="main-body">
        <div class="page-wrapper">
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>List Data Inventaris</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="page-header-breadcrumb">
                            <ul class="breadcrumb-title">
                                <li class="breadcrumb-item" style="float: left;">
                                    <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                                </li>
                                <li class="breadcrumb-item" style="float: left;"><a href="#!">Data Inventaris</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">
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
                            <div class="card-block">
                                <button type="button" class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addModal">
                                    Add Inventaris
                                </button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Tgl</th>
                                                <th>Nama</th>
                                                <th>Kode Toko</th>
                                                <th>Nama Toko</th>
                                                <th>Lok SPK</th>
                                                <th>Jenis</th>
                                                <th>Tipe</th>
                                                <th>Kelengkapan</th>
                                                <th>Keterangan</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inventaris as $item)
                                            <tr>
                                                <td>{{ $item->tgl }}</td>
                                                <td>{{ $item->nama }}</td>
                                                <td>{{ $item->kode_toko }}</td>
                                                <td>{{ $item->nama_toko }}</td>
                                                <td>{{ $item->lok_spk }}</td>
                                                <td>{{ $item->jenis }}</td>
                                                <td>{{ $item->tipe }}</td>
                                                <td>{{ $item->kelengkapan }}</td>
                                                <td>{{ $item->keterangan }}</td>
                                                <td>
                                                    <button type="button" class="btn btn-warning btn-round btn-edit"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editModal"
                                                        data-id="{{ $item->id }}"
                                                        data-tgl="{{ $item->tgl }}"
                                                        data-nama="{{ $item->nama }}"
                                                        data-kode_toko="{{ $item->kode_toko }}"
                                                        data-nama_toko="{{ $item->nama_toko }}"
                                                        data-lok_spk="{{ $item->lok_spk }}"
                                                        data-jenis="{{ $item->jenis }}"
                                                        data-tipe="{{ $item->tipe }}"
                                                        data-kelengkapan="{{ $item->kelengkapan }}"
                                                        data-keterangan="{{ $item->keterangan }}">
                                                        Edit
                                                    </button>

                                                    <form action="{{ route('data-inventaris.destroy', $item->id) }}" method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-round"
                                                                onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Delete</button>
                                                    </form>
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
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Tambah Data Inventaris</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('data-inventaris.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tgl" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="tgl" name="tgl">
                        </div>
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama</label>
                            <input type="text" class="form-control" id="nama" name="nama">
                        </div>
                        <div class="mb-3">
                            <label for="kode_toko" class="form-label">Kode Toko</label>
                            <input type="text" class="form-control" id="kode_toko" name="kode_toko">
                        </div>
                         <div class="mb-3">
                            <label for="nama_toko" class="form-label">Nama Toko</label>
                            <input type="text" class="form-control" id="nama_toko" name="nama_toko">
                        </div>
                        <div class="mb-3">
                            <label for="lok_spk" class="form-label">Lok SPK</label>
                            <input type="text" class="form-control" id="lok_spk" name="lok_spk">
                        </div>
                        <div class="mb-3">
                            <label for="jenis" class="form-label">Jenis</label>
                            <select class="form-control" id="jenis" name="jenis">
                                <option value="">Pilih Jenis</option>
                                <option value="TAB">TAB</option>
                                <option value="HP">HP</option>
                                <option value="LP">LP</option>
                                <option value="LAIN LAIN">LAIN LAIN</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tipe" class="form-label">Tipe</label>
                            <input type="text" class="form-control" id="tipe" name="tipe" placeholder="cth: Samsung A55 8/256">
                        </div>
                         <div class="mb-3">
                            <label for="kelengkapan" class="form-label">Kelengkapan</label>
                            <select class="form-control" id="kelengkapan" name="kelengkapan">
                                <option value="">Pilih Kelengkapan</option>
                                <option value="BOX">BOX</option>
                                <option value="BTG">BTG</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Data Inventaris</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                         <div class="mb-3">
                            <label for="edit_tgl" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="edit_tgl" name="tgl">
                        </div>
                        <div class="mb-3">
                            <label for="edit_nama" class="form-label">Nama</label>
                            <input type="text" class="form-control" id="edit_nama" name="nama">
                        </div>
                        <div class="mb-3">
                            <label for="edit_kode_toko" class="form-label">Kode Toko</label>
                            <input type="text" class="form-control" id="edit_kode_toko" name="kode_toko">
                        </div>
                         <div class="mb-3">
                            <label for="edit_nama_toko" class="form-label">Nama Toko</label>
                            <input type="text" class="form-control" id="edit_nama_toko" name="nama_toko">
                        </div>
                        <div class="mb-3">
                            <label for="edit_lok_spk" class="form-label">Lok SPK</label>
                            <input type="text" class="form-control" id="edit_lok_spk" name="lok_spk">
                        </div>
                        <div class="mb-3">
                            <label for="edit_jenis" class="form-label">Jenis</label>
                            <select class="form-control" id="edit_jenis" name="jenis">
                                <option value="">Pilih Jenis</option>
                                <option value="TAB">TAB</option>
                                <option value="HP">HP</option>
                                <option value="LP">LP</option>
                                <option value="LAIN LAIN">LAIN LAIN</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_tipe" class="form-label">Tipe</label>
                            <input type="text" class="form-control" id="edit_tipe" name="tipe" placeholder="cth: Samsung A55 8/256">
                        </div>
                         <div class="mb-3">
                            <label for="edit_kelengkapan" class="form-label">Kelengkapan</label>
                            <select class="form-control" id="edit_kelengkapan" name="kelengkapan">
                                <option value="">Pilih Kelengkapan</option>
                                <option value="BOX">BOX</option>
                                <option value="BTG">BTG</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="edit_keterangan" name="keterangan" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

<script>
    // Script untuk mengisi data ke modal edit
    document.addEventListener('DOMContentLoaded', function () {
        var editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang membuka modal
            
            // Ambil data dari atribut data-*
            var id = button.getAttribute('data-id');
            var tgl = button.getAttribute('data-tgl');
            var nama = button.getAttribute('data-nama');
            var kode_toko = button.getAttribute('data-kode_toko');
            var nama_toko = button.getAttribute('data-nama_toko');
            var lok_spk = button.getAttribute('data-lok_spk');
            var jenis = button.getAttribute('data-jenis');
            var tipe = button.getAttribute('data-tipe');
            var kelengkapan = button.getAttribute('data-kelengkapan');
            var keterangan = button.getAttribute('data-keterangan');

            // Atur action form
            var form = document.getElementById('editForm');
            var url = "{{ route('data-inventaris.update', ':id') }}";
            url = url.replace(':id', id);
            form.action = url;

            // Isi nilai ke dalam form di modal
            form.querySelector('#edit_tgl').value = tgl;
            form.querySelector('#edit_nama').value = nama;
            form.querySelector('#edit_kode_toko').value = kode_toko;
            form.querySelector('#edit_nama_toko').value = nama_toko;
            form.querySelector('#edit_lok_spk').value = lok_spk;
            form.querySelector('#edit_jenis').value = jenis;
            form.querySelector('#edit_tipe').value = tipe;
            form.querySelector('#edit_kelengkapan').value = kelengkapan;
            form.querySelector('#edit_keterangan').value = keterangan;
        });
    });
</script>