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
                                <a href="{{ route('data-barang.create') }}" class="btn btn-primary btn-round">Upload Excel Barang</a>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                <table id="tablebarang" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>LOK_SPK</th>
                                            <th>Jenis</th>
                                            <th>Tipe</th>
                                            <th>Grade</th>
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
</script>


@endsection()