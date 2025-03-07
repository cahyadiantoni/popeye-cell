@extends('layouts.main')

@section('title', 'Transaksi Jual Online')
@section('content')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>List Transaksi Jual Online</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-block">
                            <a href="{{ route('transaksi-jual-online.create') }}" class="btn btn-primary btn-round">Jual Barang</a>
                            <hr>
                            <div class="dt-responsive table-responsive">
                                <table id="transaksiJualOnlineTable" class="table table-striped table-bordered nowrap">
                                    <thead>
                                        <tr>
                                            <th>LOK_SPK</th>
                                            <th>Tipe</th>
                                            <th>Title</th>
                                            <th>Toko</th>
                                            <th>Tgl Jual</th>
                                            <th>Harga Jual</th>
                                            <th>Petugas</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot>
                                        <tr>
                                            <th>LOK_SPK</th>
                                            <th>Tipe</th>
                                            <th>Title</th>
                                            <th>Toko</th>
                                            <th>Tgl Jual</th>
                                            <th>Harga Jual</th>
                                            <th>Petugas</th>
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
        $('#transaksiJualOnlineTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('transaksi-jual-online.data') }}',
            columns: [
                { data: 'lok_spk', name: 'lok_spk' },
                { data: 'tipe', name: 'tipe' },
                { data: 'title_faktur', name: 'title' },
                { data: 'toko_faktur', name: 'toko' },
                { data: 'tgl_jual', name: 'tgl_jual', orderable: false, searchable: false },
                { data: 'harga_jual', name: 'harga_jual', orderable: false, searchable: false },
                { data: 'petugas_faktur', name: 'petugas' }
            ]
        });
    });
</script>
@endsection