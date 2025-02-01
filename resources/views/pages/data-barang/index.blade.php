@extends('layouts.main')

@section('title', 'Data Barang')
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
                                <h4>List Data Barang</h4>
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
                                        href="#!">Data Barang</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

            <!-- Page-body start -->
            <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Zero config.table start -->
                        <div class="card">
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
                            <div class="card-block">
                            <a href="{{ route('data-barang.create') }}" class="btn btn-primary btn-round mx-2">Upload Excel Barang</a>
                            <a href="{{ url('/mass-edit-barang') }}" class="btn btn-info btn-round mx-2">Mass Edit Barang</a>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                <table id="tablebarang" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>LOK_SPK</th>
                                            <th>Jenis</th>
                                            <th>Tipe</th>
                                            <th>Grade</th>
                                            <th>Kel</th>
                                            <th>Gudang</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>LOK_SPK</th>
                                            <th>Jenis</th>
                                            <th>Tipe</th>
                                            <th>Grade</th>
                                            <th>Kel</th>
                                            <th>Gudang</th>
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

    <!-- Modal Edit Barang -->
    <div class="modal fade" id="editBarangModal" tabindex="-1" aria-labelledby="editBarangLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editBarangForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editBarangLabel">Edit Data Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="lok_spk_original" name="lok_spk_original">
                        <div class="mb-3">
                            <label for="lok_spk" class="form-label">Lok SPK</label>
                            <input type="text" class="form-control" id="lok_spk" name="lok_spk" required>
                        </div>
                        <div class="mb-3">
                            <label for="jenis" class="form-label">Jenis</label>
                            <input type="text" class="form-control" id="jenis" name="jenis" required>
                        </div>
                        <div class="mb-3">
                            <label for="tipe" class="form-label">Tipe</label>
                            <input type="text" class="form-control" id="tipe" name="tipe" required>
                        </div>
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade</label>
                            <input type="text" class="form-control" id="grade" name="grade">
                        </div>
                        <div class="mb-3">
                            <label for="kelengkapan" class="form-label">Kelengkapan</label>
                            <input type="text" class="form-control" id="kelengkapan" name="kelengkapan">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


<script>
    $(document).ready(function () {
        $('#tablebarang').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('data-barang.index') }}", // Pastikan rute ini mengarah ke metode `index` di controller
            columns: [
                { data: 'lok_spk', name: 'lok_spk' },
                { data: 'jenis', name: 'jenis' },
                { data: 'tipe', name: 'tipe' },
                { data: 'grade', name: 'grade' },
                { data: 'kelengkapan', name: 'kelengkapan' },
                { data: 'gudang.nama_gudang', name: 'gudang.nama_gudang' },
                { 
                    data: 'action', 
                    name: 'action', 
                    orderable: false, 
                    searchable: false 
                },
            ]
        });
    });

    $(document).on('click', '.edit-barang-btn', function () {
        // Ambil data dari tombol
        const lok_spk = $(this).data('lok_spk');
        const jenis = $(this).data('jenis');
        const tipe = $(this).data('tipe');
        const grade = $(this).data('grade');
        const kelengkapan = $(this).data('kelengkapan');

        // Isi data ke dalam modal
        $('#lok_spk_original').val(lok_spk); // Lok SPK original
        $('#lok_spk').val(lok_spk);
        $('#jenis').val(jenis);
        $('#tipe').val(tipe);
        $('#grade').val(grade);
        $('#kelengkapan').val(kelengkapan);

        // Atur action form modal edit
        $('#editBarangForm').attr('action', `/update-data-barang/${lok_spk}`);

        // Tampilkan modal
        $('#editBarangModal').modal('show');
    });
</script>


@endsection()