@extends('layouts.main')

@section('title', 'Stok Opname Gudang')
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
                                <h4>Stok Opname Gudang: {{ $gudangs->first()->nama_gudang }}</h4>
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
                                        href="#!">Data Barang Gudang {{ $gudangs->first()->nama_gudang }}</a>
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
                                    <form id="request-form" method="POST" action="{{ route('kirimBarang') }}">
                                        @csrf
                                        <table id="checkbox-select" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" id="select-all"></th>
                                                    <th>LOK_SPK</th>
                                                    <th>Jenis</th>
                                                    <th>Tipe</th>
                                                    <th>Grade</th>
                                                    <th>Gudang</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($barangs as $barang)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="select-checkbox" name="lok_spk[]" value="{{ $barang->lok_spk }}">
                                                    </td>
                                                    <td>{{ $barang->lok_spk }}</td>
                                                    <td>{{ $barang->jenis }}</td>
                                                    <td>{{ $barang->tipe }}</td>
                                                    <td>{{ $barang->grade }}</td>
                                                    <td>{{ $barang->gudang->nama_gudang ?? 'N/A' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th></th>
                                                    <th>LOK_SPK</th>
                                                    <th>Jenis</th>
                                                    <th>Tipe</th>
                                                    <th>Grade</th>
                                                    <th>Gudang</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        <div class="mb-3 row">
                                            <label class="form-label col-sm-2 col-form-label">Select Gudang</label>
                                            <div class="col-sm-10">
                                                <select name="gudang_id" class="form-select form-control" required>
                                                    <option value="">-- Pilih Gudang --</option>
                                                    @foreach($allgudangs as $gudang)
                                                        <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <!-- Tombol aksi -->
                                        <button type="submit" name="action" value="kirim" class="btn btn-success">Kirim</button>
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