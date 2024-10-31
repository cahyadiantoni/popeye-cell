@extends('layouts.main')

@section('title', 'Request Barang Masuk')
@section('content')
    <!-- Main-body start -->
    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page-header start -->
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>Request Barang Masuk</h4>
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
                                        href="#!">Data Request</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

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
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                    <form id="request-form" method="POST" action="{{ route('handleRequest') }}">
                                        @csrf
                                        <table id="checkbox-select" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" id="select-all"></th>
                                                    <th>Lok SPK</th>
                                                    <th>Tipe</th>
                                                    <th>Gudang Asal</th>
                                                    <th>Gudang Tujuan</th>
                                                    <th>Pengirim</th>
                                                    <th>Penerima</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($requests as $request)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="select-checkbox" name="id[]" value="{{ $request->id }}">
                                                        <!-- Tambahkan hidden input untuk pengirim_gudang_id dan penerima_gudang_id -->
                                                        <input type="hidden" name="lok_spk[]" value="{{ $request->lok_spk }}">
                                                        <input type="hidden" name="pengirim_gudang_id[]" value="{{ $request->pengirim_gudang_id }}">
                                                        <input type="hidden" name="penerima_gudang_id[]" value="{{ $request->penerima_gudang_id }}">
                                                    </td>
                                                    <td>{{ $request->lok_spk }}</td>
                                                    <td>{{ $request->barang->tipe }}</td>
                                                    <td>{{ $request->pengirimGudang->nama_gudang ?? 'N/A' }}</td>
                                                    <td>{{ $request->penerimaGudang->nama_gudang }}</td>
                                                    <td>{{ $request->pengirimUser->name }}</td>
                                                    <td>{{ $request->penerimaUser->name }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th></th>
                                                    <th>Lok SPK</th>
                                                    <th>Tipe</th>
                                                    <th>Gudang Asal</th>
                                                    <th>Gudang Tujuan</th>
                                                    <th>Pengirim</th>
                                                    <th>Penerima</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <!-- Tombol aksi -->
                                        <button type="submit" name="action" value="terima" class="btn btn-success">Terima</button>
                                        <button type="submit" name="action" value="tolak" class="btn btn-danger">Tolak</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Script untuk memilih semua checkbox
    document.getElementById('select-all').addEventListener('change', function() {
        let checkboxes = document.querySelectorAll('.select-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });
    </script>
    <!-- Main-body end -->
@endsection()