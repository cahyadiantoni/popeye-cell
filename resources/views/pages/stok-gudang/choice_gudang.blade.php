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
                                <h4>Pilih Gudang</h4>
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
                                        href="#!">Pilih Gudang</a>
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
                            <div class="card-block">
                                <div class="dt-responsive table-responsive">
                                <form id="request-form" method="GET" action="{{ route('stokOpname') }}">
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Select Gudang</label>
                                        <div class="col-sm-10">
                                            <select name="gudang_id" class="form-select form-control" required>
                                                <option value="all">-- Semua Gudang --</option>
                                                @foreach($allgudangs as $gudang)
                                                    <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success">Lihat Stok Opname</button>
                                </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()