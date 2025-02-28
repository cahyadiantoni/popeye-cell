@extends('layouts.main')
@section('title', 'Item Tokped')
@section('content')
<div class="main-body">
    <div class="page-wrapper">
        <!-- Page-header start -->
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>List Data Item Tokped</h4>
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
                                    href="#!">Data Item Tokped</a>
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
                        <div class="card-block">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Add Item</button>
                            <hr>
                            <div class="dt-responsive table-responsive">
                            <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                <thead>
                                    <tr><th>No</th><th>Name</th><th>Keterangan</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($settings as $setting)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $setting->name }}</td>
                                        <td>{{ $setting->keterangan }}</td>
                                        <td>
                                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $setting->id }}">Edit</button>
                                            <form action="{{ route('item-tokped.destroy', $setting->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('item-tokped.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h5>Add Setting</h5></div>
                <div class="modal-body">
                    <label>Name:</label>
                    <input type="text" name="name" class="form-control" required>
                    <label>Keterangan:</label>
                    <textarea name="keterangan" class="form-control"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modals -->
@foreach($settings as $setting)
<div class="modal fade" id="editModal{{ $setting->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('item-tokped.update', $setting->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5>Edit Item Tokped</h5></div>
                <div class="modal-body">
                    <label>Name:</label>
                    <input type="text" name="name" class="form-control" value="{{ $setting->name }}" required>
                    <label>Keterangan:</label>
                    <textarea name="keterangan" class="form-control">{{ $setting->keterangan }}</textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection