@extends('layouts.main')

@section('title', 'Data Master Pulsa')
@section('content')

{{-- Pastikan jQuery sudah dimuat, bisa dari layout utama atau di sini --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Data Master Pulsa</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class="breadcrumb-title">
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                            </li>
                            <li class="breadcrumb-item" style="float: left;"><a href="#!">Data Master Pulsa</a>
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
                            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="card-block">
                            <button type="button" class="btn btn-primary btn-round mb-3" data-bs-toggle="modal" data-bs-target="#uploadModalPulsaMaster">
                                Upload Data Master
                            </button>
                            <a href="{{ route('pulsa-master.exportTemplate') }}" class="btn btn-info btn-round mb-3">
                                Download Template (isi data)
                            </a>


                            <div class="dt-responsive table-responsive">
                                <table id="tablePulsaMaster" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Toko</th>
                                            <th>Pasca Bayar 1</th>
                                            <th>Pasca Bayar 2</th>
                                            <th>Token 1</th>
                                            <th>Token 2</th>
                                            <th>PAM 1</th>
                                            <th>PAM 2</th>
                                            <th>Pulsa 1</th>
                                            <th>Pulsa 2</th>
                                            <th>Pulsa 3</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Toko</th>
                                            <th>Pasca Bayar 1</th>
                                            <th>Pasca Bayar 2</th>
                                            <th>Token 1</th>
                                            <th>Token 2</th>
                                            <th>PAM 1</th>
                                            <th>PAM 2</th>
                                            <th>Pulsa 1</th>
                                            <th>Pulsa 2</th>
                                            <th>Pulsa 3</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    </div>
            </div>
        </div>
        </div>
</div>
<div class="modal fade" id="uploadModalPulsaMaster" tabindex="-1" aria-labelledby="uploadModalPulsaMasterLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('pulsa-master.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalPulsaMasterLabel">Upload File Excel Master Pulsa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filedata" class="form-label">Pilih File Excel (.xlsx, .xls, .csv)</label>
                        <input type="file" name="filedata" id="filedataMaster" class="form-control" accept=".xlsx, .xls, .csv" required>
                        <small class="form-text text-muted">Pastikan baris pertama adalah header: kode, nama_toko, pasca_bayar1, pasca_bayar2, token1, token2, pam1, pam2, pulsa1, pulsa2, pulsa3.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Upload</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#tablePulsaMaster').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('pulsa-master.index') }}",
            columns: [
                { data: 'kode', name: 'kode' },
                { data: 'nama_toko', name: 'nama_toko' },
                { data: 'pasca_bayar1', name: 'pasca_bayar1' },
                { data: 'pasca_bayar2', name: 'pasca_bayar2' },
                { data: 'token1', name: 'token1' },
                { data: 'token2', name: 'token2' },
                { data: 'pam1', name: 'pam1' },
                { data: 'pam2', name: 'pam2' },
                { data: 'pulsa1', name: 'pulsa1' },
                { data: 'pulsa2', name: 'pulsa2' },
                { data: 'pulsa3', name: 'pulsa3' },
            ]
        });
    });
</script>

@endsection