@extends('layouts.main')

@section('title', 'Data MAC Address')
@section('content')
<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>List Data MAc Address</h4>
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
                                    href="#!">Data Mac Address</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page-body start -->
        <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">
                    <div class="card">
                        @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <div class="card-block">
                            <button class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#addModal">+ Tambah</button>
                            <hr>
                            <div class="dt-responsive table-responsive">
                                <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>MAC Address</th>
                                            <th>Status</th>
                                            <th>Keterangan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($macs as $mac)
                                        <tr>
                                            <td>{{ $mac->mac }}</td>
                                            <td>
                                                @if($mac->status == 0)
                                                    <span class="badge bg-secondary">Belum diverifikasi</span>
                                                @elseif($mac->status == 1)
                                                <span class="badge bg-success">Disetujui</span>
                                                @else
                                                <span class="badge bg-danger">Ditolak</span>
                                                @endif
                                            </td>
                                            <td>{{ $mac->keterangan }}</td>
                                            <td>
                                                <!-- Edit -->
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal{{ $mac->id }}">Edit</button>
    
                                                <!-- Delete -->
                                                <form action="{{ route('mac-address.destroy', $mac->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button onclick="return confirm('Hapus MAC ini?')" class="btn btn-danger btn-sm">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
    
                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal{{ $mac->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <form method="POST" action="{{ route('mac-address.update', $mac->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit MAC</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label>MAC</label>
                                                                <input type="text" name="mac" class="form-control" value="{{ $mac->mac }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Status</label>
                                                                <select name="status" class="form-control">
                                                                    <option value="0" {{ $mac->status == 0 ? 'selected' : '' }}>Belum diverifikasi</option>
                                                                    <option value="1" {{ $mac->status == 1 ? 'selected' : '' }}>Disetujui</option>
                                                                    <option value="2" {{ $mac->status == 2 ? 'selected' : '' }}>Ditolak</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label>Keterangan</label>
                                                                <textarea name="keterangan" class="form-control">{{ old('keterangan', $mac->keterangan ?? '') }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('mac-address.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah MAC Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>MAC</label>
                        <input type="text" name="mac" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="0">Belum diverifikasi</option>
                            <option value="1">Disetujui</option>
                            <option value="2">Ditolak</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
