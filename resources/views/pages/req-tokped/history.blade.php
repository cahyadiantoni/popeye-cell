@extends('layouts.main')

@section('title', 'List Pengajuan Tokped')
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
                                <h4>List History Pengajuan Tokped</h4>
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
                                        href="#!">History Pengajuan Tokped</a>
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
                            <div class="card-block">
                                <hr>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="start-date">Dari Tanggal:</label>
                                        <input type="date" id="start-date" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end-date">Sampai Tanggal:</label>
                                        <input type="date" id="end-date" class="form-control">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button id="apply-filter" class="btn btn-success">Filter</button>
                                        <button id="clear-filter" class="btn btn-secondary ms-2">Clear</button>
                                    </div>
                                </div>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Kode Lok</th>
                                            <th>Nama Barang</th>
                                            <th>Lain Lain</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>PJ</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($histories as $history)
                                        <tr data-date="{{ $history->tgl }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $history->tgl }}</td>
                                            <td>{{ $history->kode_lok }}</td>
                                            <td>{{ $history->nama_barang }}</td>
                                            <td>{{ $history->lain_lain }}</td>
                                            <td>{{ $history->quantity }}</td>
                                            <td>
                                            @if($history->status == 0)
                                                <span class="badge bg-secondary">Draft</span>
                                            @elseif($history->status == 1)
                                                <span class="badge bg-primary">Terkirim</span>
                                            @elseif($history->status == 2)
                                                <span class="badge bg-warning text-dark">Revisi</span>
                                            @elseif($history->status == 3)
                                                <span class="badge bg-danger">Ditolak</span>
                                            @elseif($history->status == 4)
                                                <span class="badge bg-info text-dark">Proses Tokped</span>
                                            @elseif($history->status == 5)
                                                <span class="badge bg-success">Sudah Diterima</span>
                                            @endif
                                            </td>
                                            <td>{{ $history->pj }}</td>    
                                        </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>Kode Lok</th>
                                                <th>Nama Barang</th>
                                                <th>Lain Lain</th>
                                                <th>Quantity</th>
                                                <th>Status</th>
                                                <th>PJ</th>
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
            $("#apply-filter").on("click", function () {
                let startDate = $("#start-date").val();
                let endDate = $("#end-date").val();

                $("#simpletable tbody tr").each(function () {
                    let rowDate = $(this).data("date");
                    let showRow = true;

                    if (startDate) {
                        if (new Date(rowDate) < new Date(startDate)) {
                            showRow = false;
                        }
                    }

                    if (endDate) {
                        if (new Date(rowDate) > new Date(endDate)) {
                            showRow = false;
                        }
                    }

                    $(this).toggle(showRow);
                });
            });

            $("#clear-filter").on("click", function () {
                $("#start-date").val("");
                $("#end-date").val("");
                $("#simpletable tbody tr").show();
            });
        });
    </script>

@endsection()