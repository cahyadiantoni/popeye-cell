@extends('layouts.main')

@section('title', 'List Kirim Barang')
@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Main-body start -->
    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page-header start -->
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>List Kirim Barang</h4>
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
                                        href="#!">Kirim Barang</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

            <!-- Page-body start -->
            <div class="page-body">
                {{-- Pesan Berhasil --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Pesan Gagal --}}
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Zero config.table start -->
                        <div class="card">
                            <div class="card-block">
                                <button class="btn btn-success" id="addKirimBtn">Kirim Barang</button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Gudang Asal</th>
                                                <th>Gudang Tujuan</th>
                                                <th>Pengirim</th>
                                                <th>Penerima</th>
                                                <th>Jumlah Barang</th>
                                                <th>Status</th>
                                                <th>Tgl Kirim</th>
                                                <th>Tgl Terima</th>
                                                <th>Keterangan</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($kirims as $kirim)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('kirim-barang.show', $kirim->id) }}">
                                                        {{ $kirim->id }}
                                                    </a>
                                                </td>
                                                <td>{{ $kirim->pengirimGudang->nama_gudang ?? 'N/A' }}</td>
                                                <td>{{ $kirim->penerimaGudang->nama_gudang }}</td>
                                                <td>{{ $kirim->pengirimUser->name }}</td>
                                                <td>{{ $kirim->penerimaUser->name }}</td>
                                                <td>{{ $jumlahBarang[$kirim->id] ?? 0 }}</td>
                                                <td>
                                                    @switch($kirim->status)
                                                        @case(0)
                                                            <span class="badge bg-warning text-dark">Dalam Proses</span>
                                                            @break
                                                        @case(1)
                                                            <span class="badge bg-success">Diterima</span>
                                                            @break
                                                        @case(2)
                                                            <span class="badge bg-danger">Ditolak</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">Status Tidak Diketahui</span>
                                                    @endswitch
                                                </td>
                                                <td>{{ $kirim->dt_kirim }}</td>
                                                <td>{{ $kirim->dt_terima }}</td>
                                                <td>{{ $kirim->keterangan }}</td>
                                                <td>
                                                    <!-- Tombol View -->
                                                    <a href="{{ route('kirim-barang.show', $kirim->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @if($kirim->status == 0)
                                                        <!-- Tombol View -->
                                                        <form action="{{ route('kirim-barang.delete', $kirim->id) }}" method="POST" class="d-inline delete-form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-sm delete-btn">Delete</button>
                                                        </form>
                                                    @elseif($kirim->status == 1)
                                                        <!-- Tombol Upload Bukti Transfer -->
                                                        <button class="btn btn-success btn-sm upload-bukti-btn" 
                                                            data-id="{{ $kirim->id }}" 
                                                            data-bukti-tf="{{ $kirim->bukti_tf }}">
                                                            Upload Bukti
                                                        </button>
                                                        @if ($kirim->bukti_tf)
                                                            <a href="{{ asset($kirim->bukti_tf) }}" target="_blank" class="btn btn-primary btn-sm">Lihat Bukti</a>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>ID</th>
                                                <th>Gudang Asal</th>
                                                <th>Gudang Tujuan</th>
                                                <th>Pengirim</th>
                                                <th>Penerima</th>
                                                <th>Jumlah Barang</th>
                                                <th>Status</th>
                                                <th>Tgl Kirim</th>
                                                <th>Tgl Terima</th>
                                                <th>Keterangan</th>
                                                <th>Action</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Zero config.table end -->
                    </div>
                </div>
            </div>
            <!-- Page-body end -->
        </div>
    </div>
    <!-- Main-body end -->

    <!-- Modal Add Barang -->
    <div class="modal fade" id="addKirimModal" tabindex="-1" aria-labelledby="addKirimModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('kirim-barang.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addKirimModalLabel">Kirim Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <a href="{{ asset('files/template kirim barang.xlsx') }}" class="btn btn-primary btn-round" download>Download Template Excel</a>
                        </div>
                        <div class="mb-3">
                            <label for="fileExcel" class="form-label">Upload File Excel</label>
                            <input type="file" class="form-control" id="filedata" name="filedata" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Gudang Tujuan</label>
                            <div class="col-sm-10">
                                <select name="penerima_gudang_id" class="form-select form-control" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach($allgudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <input type="text" class="form-control" id="keterangan" name="keterangan" placeholder="Tambahkan keterangan (opsional)">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadBuktiModal" tabindex="-1" aria-labelledby="uploadBuktiModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="uploadBuktiForm" action="{{ route('kirim-barang.upload-bukti') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadBuktiModalLabel">Upload Bukti Kirim Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="buktiId" name="id">

                        <div class="mb-3">
                            <a href="{{ route('kirim-barang.print-bukti', $kirim->id) }}" class="btn btn-primary btn-round" download>Print Bukti Kirim Barang</a>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bukti_tf" class="form-label">Pilih Bukti Kirim Barang</label>
                            <input type="file" class="form-control" id="bukti_tf" name="bukti_tf" accept="image/*" required>
                        </div>

                        <div id="buktiPreview" class="text-center d-none">
                            <img id="previewImage" src="" class="img-fluid mt-2" style="max-height: 200px;">
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
        document.addEventListener('DOMContentLoaded', function () {

            const deleteForms = document.querySelectorAll('.delete-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault(); // Mencegah submit form langsung
                    if (confirm('Yakin ingin menghapus data ini?')) {
                        form.submit(); // Submit form jika konfirmasi "OK"
                    }
                });
            });

            const addKirimBtn = document.getElementById('addKirimBtn');
            const addKirimModal = new bootstrap.Modal(document.getElementById('addKirimModal'));
            addKirimBtn.addEventListener('click', () => {
                addKirimModal.show();
            });
        });

        $(document).ready(function () {
            // Tampilkan modal saat tombol "Upload Bukti" diklik
            $('.upload-bukti-btn').click(function () {
                let id = $(this).data('id');
                let buktiTf = $(this).data('bukti-tf');

                $('#buktiId').val(id); // Set ID ke dalam input hidden

                if (buktiTf) {
                    $('#previewImage').attr('src', buktiTf).removeClass('d-none');
                    $('#buktiPreview').removeClass('d-none');
                } else {
                    $('#buktiPreview').addClass('d-none');
                }

                $('#uploadBuktiModal').modal('show');
            });

            // Preview gambar sebelum diupload
            $('#bukti_tf').change(function (event) {
                let reader = new FileReader();
                reader.onload = function (e) {
                    $('#previewImage').attr('src', e.target.result).removeClass('d-none');
                    $('#buktiPreview').removeClass('d-none');
                };
                reader.readAsDataURL(event.target.files[0]);
            });
        });
    </script>
@endsection()