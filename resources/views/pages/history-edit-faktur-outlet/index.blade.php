@extends('layouts.main')
@section('title', 'Riwayat Perubahan Faktur Outlet')
@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-body">
            <div class="card">
                <div class="card-header"><h5>Riwayat Perubahan Faktur Outlet</h5></div>
                <div class="card-block">
                    <div class="dt-responsive table-responsive">
                        <table id="table-riwayat" class="table table-striped table-bordered nowrap" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Tanggal & Waktu</th>
                                    <th>Nomor Faktur</th>
                                    <th>Detail Perubahan</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('#table-riwayat').DataTable({ 
        processing: true,
        serverSide: true,
        ajax: "{{ route('history-edit-faktur-outlet.index') }}", 
        columns: [
            { data: 'created_at', name: 'created_at' },
            { data: 'faktur.nomor_faktur', name: 'faktur.nomor_faktur' },
            { data: 'update', name: 'update' },
            { data: 'user.name', name: 'user.name' },
        ]
    });
});
</script>
@endsection