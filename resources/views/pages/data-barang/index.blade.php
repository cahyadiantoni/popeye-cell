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
                            <div class="card-header">
                                <form id="formFilterDataBarang" action="{{ route('data-barang.index') }}" method="GET">
                                <div class="row">
                                    @if($roleUser == 'admin')
                                    <div class="col-md-3">
                                    <label for="gudang_nama">Gudang</label>
                                    <select name="gudang_nama" id="gudang_nama" class="form-control">
                                        <option value="">-- Semua gudang --</option>
                                        @foreach($gudangs as $g)
                                        <option value="{{ $g->nama_gudang }}">{{ $g->nama_gudang }}</option>
                                        @endforeach
                                    </select>
                                    </div>
                                    @endif

                                    <div class="col-md-3">
                                    <label for="start_date">Tanggal Mulai Upload</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                                    </div>

                                    <div class="col-md-3">
                                    <label for="end_date">Tanggal Selesai Upload</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                                    </div>

                                    <div class="col-md-3">
                                    <label for="status_barang_filter">Status Barang</label>
                                    <select name="status_barang_filter" id="status_barang_filter" class="form-control">
                                        <option value="">-- Semua status --</option>
                                        <option value="terjual" {{ request('status_barang_filter')=='terjual'?'selected':'' }}>Terjual</option>
                                        <option value="belum"   {{ request('status_barang_filter')=='belum'?'selected':'' }}>Belum Terjual</option>
                                    </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary" id="btnFilter">Filter</button>

                                    <a href="{{ route('data-barang.index') }}" class="btn btn-secondary mx-2" id="btnResetLink">
                                    Reset
                                    </a>

                                    <a href="#" class="btn btn-success" id="btnExport">
                                    Export Excel
                                    </a>
                                </div>
                                </form>
                            </div>
                        </div>
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
                                            <th>Tgl Upload</th>
                                            <th>Jenis</th>
                                            <th>Tipe</th>
                                            <th>Imei</th>
                                            <th>Grade</th>
                                            <th>Kel</th>
                                            <th>Gudang</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>LOK_SPK</th>
                                            <th>Tgl Upload</th>
                                            <th>Jenis</th>
                                            <th>Tipe</th>
                                            <th>Imei</th>
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
    let tableBarang;

    function getFilterParams() {
        // Ambil langsung dari field form
        return {
        start_date: $('#start_date').val(),
        end_date: $('#end_date').val(),
        status_barang_filter: $('#status_barang_filter').val(),
        gudang_nama: $('#gudang_nama').val()
        };
    }

    $(document).ready(function () {
        tableBarang = $('#tablebarang').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('data-barang.index') }}",
            data: function (d) {
            const f = getFilterParams();
            d.start_date = f.start_date;
            d.end_date   = f.end_date;
            d.status_barang_filter = f.status_barang_filter;
            d.gudang_nama = f.gudang_nama;
            }
        },
        columns: [
            { data: 'lok_spk', name: 'lok_spk' },
            { data: 'created_at', name: 'created_at' },
            { data: 'jenis', name: 'jenis' },
            { data: 'tipe', name: 'tipe' },
            { data: 'imei', name: 'imei' },
            { data: 'grade', name: 'grade' },
            { data: 'kelengkapan', name: 'kelengkapan' },
            { data: 'gudang.nama_gudang', name: 'gudang.nama_gudang' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
        });

        // Tombol Filter (gunakan AJAX reload, cegah submit form)
        $('#btnFilter').on('click', function (e) {
        e.preventDefault();
        tableBarang.ajax.reload();
        });

        // Tombol Reset: kosongkan field + reload tabel (tanpa refresh halaman)
        $('#btnResetLink').on('click', function (e) {
        e.preventDefault();
        $('#start_date').val('');
        $('#end_date').val('');
        $('#status_barang_filter').val('');
        $('#gudang_nama').val('');
        tableBarang.ajax.reload();
        // kalau tetap ingin benar2 reload page, hapus e.preventDefault() di atas
        // window.location = "{{ route('data-barang.index') }}";
        });

        // Tombol Export: pakai filter yang aktif
        $('#btnExport').on('click', function (e) {
        e.preventDefault();
        const f = getFilterParams();
        const qs = new URLSearchParams(f).toString();
        window.location.href = "{{ route('data-barang.export') }}" + "?" + qs;
        });
    });

    // (Tetap) handler modal edit
    $(document).on('click', '.edit-barang-btn', function () {
        const lok_spk = $(this).data('lok_spk');
        const jenis = $(this).data('jenis');
        const tipe = $(this).data('tipe');
        const grade = $(this).data('grade');
        const kelengkapan = $(this).data('kelengkapan');

        $('#lok_spk_original').val(lok_spk);
        $('#lok_spk').val(lok_spk);
        $('#jenis').val(jenis);
        $('#tipe').val(tipe);
        $('#grade').val(grade);
        $('#kelengkapan').val(kelengkapan);

        $('#editBarangForm').attr('action', `/update-data-barang/${lok_spk}`);
        $('#editBarangModal').modal('show');
    });
</script>


@endsection()