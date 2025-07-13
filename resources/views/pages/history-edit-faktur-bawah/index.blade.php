@extends('layouts.main')

@section('title', 'Riwayat Perubahan Faktur Bawah') {{-- DIUBAH --}}
@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Riwayat Perubahan Faktur Bawah</h5> {{-- DIUBAH --}}
                            <span>Melihat semua catatan perubahan yang terjadi pada data faktur bawah.</span> {{-- DIUBAH --}}
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="tableRiwayatFaktur" class="table table-striped table-bordered nowrap" style="width: 100%;">
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
// Pastikan jQuery & DataTable sudah di-load dari layout utama
$(document).ready(function () {
    // Inisialisasi DataTable
    $('#tableRiwayatFaktur').DataTable({ 
        processing: true,
        serverSide: true,
        // DIUBAH: Route AJAX menunjuk ke controller Faktur Bawah
        ajax: "{{ route('history-edit-faktur-bawah.index') }}", 
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