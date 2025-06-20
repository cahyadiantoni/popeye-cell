@extends('layouts.main')

@section('title', 'Data Order Tokped')
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
                            <h4>Data Order Tokped</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class="breadcrumb-title">
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                            </li>
                            <li class="breadcrumb-item" style="float: left;"><a href="#!">Data Order Tokped</a>
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
                    <!-- Table start -->
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

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="card-block">
                            <!-- Tombol Upload -->
                            <button type="button" class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                Upload Data Order
                            </button>

                            <hr>

                            <div class="dt-responsive table-responsive">
                                <table id="tablebarang" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Produk</th>
                                            <th>Payment</th>
                                            <th>Selesai</th>
                                            <th>Cancel</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Produk</th>
                                            <th>Payment</th>
                                            <th>Selesai</th>
                                            <th>Cancel</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Table end -->
                </div>
            </div>
        </div>
        <!-- Page-body end -->
    </div>
</div>
<!-- Main-body end -->

<!-- Modal Upload Excel -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('tokped-order.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload File Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Upload File Excel -->
                    <div class="mb-3">
                        <label for="filedata" class="form-label">Pilih File Excel</label>
                        <input type="file" name="filedata" id="filedata" class="form-control" accept=".xlsx, .xls, .csv" required>
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
        $('#tablebarang').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('tokped-order.index') }}",
            columns: [
                { data: 'invoice_number', name: 'invoice_number' },
                { data: 'latest_status', name: 'latest_status' },
                { data: 'product_name', name: 'product_name' },
                { data: 'payment_at', name: 'payment_at' },
                { data: 'completed_at', name: 'completed_at' },
                { data: 'cancelled_at', name: 'cancelled_at' },
            ]
        });
    });
</script>

@endsection
