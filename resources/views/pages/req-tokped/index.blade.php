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
                                <h4>List Pengajuan Tokped</h4>
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
                                        href="#!">Pengajuan Tokped</a>
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
                                @if($isActive)
                                <a href="{{ route('req-tokped.create') }}" class="btn btn-primary mb-3">Tambah Pengajuan Tokped</a>
                                @else
                                <a id="tutup-transfer" class="btn btn-secondary mb-3">Tambah Pengajuan Tokped</a>
                                @endif
                                <hr>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="filter-status">Filter Status:</label>
                                        <select id="filter-status" class="form-select">
                                            <option value="">Semua</option>
                                            <option value="0">Draft</option>
                                            <option value="1">Terkirim</option>
                                            <option value="2">Revisi</option>
                                            <option value="3">Ditolak</option>
                                            <option value="4">Proses Tokped</option>
                                            <option value="5">Sudah Diterima</option>
                                        </select>
                                    </div>
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
                                        <a id="export-excel" class="btn btn-success ms-2">Export Excel</a>
                                    </div>
                                </div>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Nama AM</th>
                                            <th>Kode Lokasi</th>
                                            <th>Nama Toko</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($todos as $todo)
                                        <tr data-status="{{ $todo->status }}" data-date="{{ $todo->tgl }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $todo->tgl }}</td>
                                            <td>{{ $todo->user->name }}</td>
                                            <td>{{ $todo->kode_lok }}</td>
                                            <td>{{ $todo->nama_toko }}</td>
                                            <td>
                                            @if($todo->status == 0)
                                                <span class="badge bg-secondary">Draft</span>
                                            @elseif($todo->status == 1)
                                                <span class="badge bg-primary">Terkirim</span>
                                            @elseif($todo->status == 2)
                                                <span class="badge bg-warning text-dark">Revisi</span>
                                            @elseif($todo->status == 3)
                                                <span class="badge bg-danger">Ditolak</span>
                                            @elseif($todo->status == 4)
                                                <span class="badge bg-info text-dark">Proses Tokped</span>
                                            @elseif($todo->status == 5)
                                                <span class="badge bg-success">Sudah Diterima</span>
                                            @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('req-tokped.show', $todo->id) }}" class="btn btn-primary">Detail</a>

                                                @if($roleUser=='admin')
                                                    @if(!in_array($todo->status, [3, 5])) 
                                                        @if($todo->status == 1)
                                                            <!-- Tombol Revisi, Tolak, Proses Tokped, dan Sudah Ditransfer -->
                                                            <form action="{{ route('req-tokped.updateStatus', ['id' => $todo->id, 'status' => 2]) }}" method="POST" class="d-inline confirm-form">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-warning">Revisi</button>
                                                            </form>

                                                            <form action="{{ route('req-tokped.updateStatus', ['id' => $todo->id, 'status' => 3]) }}" method="POST" class="d-inline confirm-form">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-danger">Tolak</button>
                                                            </form>

                                                            <form action="{{ route('req-tokped.updateStatus', ['id' => $todo->id, 'status' => 4]) }}" method="POST" class="d-inline confirm-form">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-info">Proses Tokped</button>
                                                            </form>

                                                        @elseif($todo->status == 4)
                                                            <form action="{{ route('req-tokped.updateStatus', ['id' => $todo->id, 'status' => 5]) }}" method="POST" class="d-inline confirm-form">
                                                                @csrf
                                                                @method('PUT')
                                                                <button type="submit" class="btn btn-success">Sudah Diterima</button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>Nama AM</th>
                                                <th>Kode Lokasi</th>
                                                <th>Nama Toko</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
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
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("filter-status").addEventListener("change", function () {
                let selectedStatus = this.value;
                let rows = document.querySelectorAll("#simpletable tbody tr");
                rows.forEach(row => {
                    let status = row.getAttribute("data-status");
                    if (selectedStatus === "" || status === selectedStatus) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            });
        });
    </script>
        
    <script>
        $(document).ready(function () {
            $("#apply-filter").on("click", function () {
                let selectedStatus = $("#filter-status").val();
                let startDate = $("#start-date").val();
                let endDate = $("#end-date").val();

                $("#simpletable tbody tr").each(function () {
                    let status = $(this).data("status").toString();
                    let rowDate = $(this).data("date");
                    let showRow = true;

                    if (selectedStatus !== "" && status !== selectedStatus) {
                        showRow = false;
                    }

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
                $("#filter-status").val("");
                $("#start-date").val("");
                $("#end-date").val("");
                $("#simpletable tbody tr").show();
            });
        });
    </script>

    <script>
        $(document).ready(function () {
            $("#export-excel").on("click", function (e) {
                e.preventDefault();

                let status = $("#filter-status").val();
                let startDate = $("#start-date").val();
                let endDate = $("#end-date").val();

                let query = $.param({ status, start_date: startDate, end_date: endDate });

                window.location.href = "{{ route('req-tokped.export') }}?" + query;
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const confirmForms = document.querySelectorAll('.confirm-form');
            confirmForms.forEach(form => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    let button = form.querySelector('button');
                    let actionText = button.innerText;
                    if (confirm(`Apakah Anda yakin ingin mengubah status menjadi "${actionText}"?`)) {
                        form.submit();
                    }
                });
            });
        });
    </script>
    
    <script>
        $(document).ready(function () {
            $("#tutup-transfer").on("click", function (e) {
                alert("Pengajuan Tokped sedang ditutup.");
            });
        });
    </script>

@endsection()