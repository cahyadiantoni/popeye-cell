@extends('layouts.main')

@section('title', 'Riwayat Perubahan Faktur Online')
@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Riwayat Perubahan Faktur Online</h5>
                            <span>Melihat semua catatan perubahan yang terjadi pada data faktur online.</span>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="tableRiwayatFakturOnline" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Tanggal & Waktu</th>
                                            <th>Nomor Faktur</th>
                                            <th>Detail Perubahan</th>
                                            <th>User</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Tanggal & Waktu</th>
                                            <th>Nomor Faktur</th>
                                            <th>Detail Perubahan</th>
                                            <th>User</th>
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

<script>
$(document).ready(function () {
    $('#tableRiwayatFakturOnline').DataTable({ 
        processing: true,
        serverSide: true,
        ajax: "{{ route('history-edit-faktur-online.index') }}", 
        columns: [
            { data: 'created_at', name: 'created_at' },
            { data: 'faktur.title', name: 'faktur.title' },
            { data: 'update', name: 'update' },
            { data: 'user.name', name: 'user.name' },
        ],
        // Menambahkan JQuery agar bisa di load
        initComplete: function(settings, json) {
            // Pastikan jQuery sudah di-load sepenuhnya sebelum DataTables dijalankan
        }
    });
});
</script>

@endsection