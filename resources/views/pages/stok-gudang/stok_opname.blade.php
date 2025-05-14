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
                                <h4>Stok Opname Gudang: {{ $selectedGudang->nama_gudang }}</h4>
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
                                        href="#!">Data Barang Gudang {{ $selectedGudang->nama_gudang }}</a>
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
                            <div class="card-header">
                                <form action="{{ route('stokOpname') }}" method="GET">
                                    <input type="hidden" name="gudang_id" value="{{ $selectedGudang->id }}">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="jenis">Jenis</label>
                                            <select name="jenis" class="form-control">
                                                <option value="">-- Semua jenis --</option>
                                                <option value="HP" {{ request('jenis') == 'HP' ? 'selected' : '' }}>HP</option>
                                                <option value="LAPTOP" {{ request('jenis') == 'LAPTOP' ? 'selected' : '' }}>LAPTOP</option>
                                                <option value="DSLR" {{ request('jenis') == 'DSLR' ? 'selected' : '' }}>DSLR</option>
                                                <option value="TV" {{ request('jenis') == 'TV' ? 'selected' : '' }}>TV</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                        <a href="{{ route('stokOpname', ['gudang_id' => $selectedGudang->id]) }}" class="btn btn-secondary mx-2">Reset</a>

                                        <a href="{{ route('export.barang', ['id' => $selectedGudang->id, 'jenis' => request('jenis')]) }}" class="btn btn-success mx-2">
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
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                        <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <!-- <th><input type="checkbox" id="select-all"></th> -->
                                                    <th>LOK_SPK</th>
                                                    <th>Jenis</th>
                                                    <th>Tipe</th>
                                                    <th>Kel</th>
                                                    <th>Grade</th>
                                                    <th>Gudang</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($barangs as $barang)
                                                <tr>
                                                    <!-- <td>
                                                        <input type="checkbox" class="select-checkbox" name="lok_spk[]" value="{{ $barang->lok_spk }}">
                                                    </td> -->
                                                    <td>{{ $barang->lok_spk }}</td>
                                                    <td>{{ $barang->jenis }}</td>
                                                    <td>{{ $barang->tipe }}</td>
                                                    <td>{{ $barang->kelengkapan }}</td>
                                                    <td>{{ $barang->grade }}</td>
                                                    <td>{{ $barang->gudang->nama_gudang ?? 'N/A' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <!-- <th></th> -->
                                                    <th>LOK_SPK</th>
                                                    <th>Jenis</th>
                                                    <th>Tipe</th>
                                                    <th>Kel</th>
                                                    <th>Grade</th>
                                                    <th>Gudang</th>
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
@endsection()