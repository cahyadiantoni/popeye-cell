@extends('layouts.main')

@section('title', 'Rekap Invoice Tokped')
@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Rekap Invoice Tokped</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class="breadcrumb-title">
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                            </li>
                            <li class="breadcrumb-item" style="float: left;"><a href="#!">Rekap Invoice</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <form action="{{ route('tokped-deposit.rekap') }}" method="GET">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="kode_faktur">Pilih Toko</label>
                                        <select name="kode_faktur" id="filterKodeFaktur" class="form-control">
                                            <option value="">-- Semua Toko --</option>
                                            <option value="POD">Toko Podomoro</option>
                                            <option value="PPY">Toko Popeye</option>
                                            <option value="JJ">Toko JJ</option>
                                            <option value="NAR">Toko Naruto</option>
                                            <option value="Lain">Lain Lain</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="tanggal_mulai">Tanggal Mulai</label>
                                        <input type="date" name="tanggal_mulai" id="filterTanggalMulai" class="form-control" >
                                    </div>

                                    <div class="col-md-3">
                                        <label for="tanggal_selesai">Tanggal Selesai</label>
                                        <input type="date" name="tanggal_selesai" id="filterTanggalSelesai" class="form-control">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="cek">Status</label>
                                        <select name="cek" id="filterCek" class="form-control">
                                            <option value="">-- Semua Status --</option>
                                            <option value="Sudah_Dicek">Lunas</option>
                                            <option value="Belum_Dicek">Belum Lunas</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('tokped-deposit.rekap') }}" class="btn btn-secondary mx-2">Reset</a>
                                    <a href="{{ route('tokped-deposit.export') }}" id="btnExportExcel" class="btn btn-success">Export Excel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="rekapTable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Tanggal</th>
                                            <th>Total Unit Faktur</th>
                                            <th>Total Faktur</th>
                                            <th>Total Unit Invoice</th>
                                            <th>Total Unit Dibatalkan</th> <th>Uang Masuk</th>
                                            <th>Selisih</th>
                                            <th>Keterangan</th>
                                            <th>Bonusan</th>
                                            <th>Return</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Title</th>
                                            <th>Tanggal</th>
                                            <th>Total Unit Faktur</th>
                                            <th>Total Faktur</th>
                                            <th>Total Unit Invoice</th>
                                            <th>Total Unit Dibatalkan</th> <th>Uang Masuk</th>
                                            <th>Selisih</th>
                                            <th>Keterangan</th>
                                            <th>Bonusan</th>
                                            <th>Return</th>
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
        var table = $('#rekapTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('tokped-deposit.rekap') }}",
                data: function(d) {
                    d.kode_faktur = $('select[name=kode_faktur]').val();
                    d.tanggal_mulai = $('input[name=tanggal_mulai]').val();
                    d.tanggal_selesai = $('input[name=tanggal_selesai]').val();
                    d.cek = $('select[name=cek]').val();
                }
            },
            order: [[1, 'desc']], // Urutkan berdasarkan Tanggal (kolom kedua) descending
            columns: [
                { data: 'title', name: 'title' },
                { data: 'tgl', name: 'tgl' },
                { data: 'total_unit_faktur', name: 'total_unit_faktur' },
                { data: 'total_nominal_faktur', name: 'total_nominal_faktur' },
                { data: 'total_unit_invoice', name: 'total_unit_invoice' },
                { data: 'total_unit_dibatalkan', name: 'total_unit_dibatalkan' }, // Data baru
                { data: 'total_uang_masuk', name: 'total_uang_masuk' },
                { data: 'selisih', name: 'selisih' },
                { data: 'keterangan', name: 'keterangan' },
                { data: 'bonusan', name: 'bonusan' },
                { data: 'return_count', name: 'return_count' },
            ]
        });

        $('form').on('submit', function(e) {
            e.preventDefault();

            // Update URL export dengan filter terbaru
            const urlExport = '{{ route("tokped-deposit.export") }}?' + new URLSearchParams({ // Pastikan route() digunakan untuk base URL
                kode_faktur: $('#filterKodeFaktur').val(),
                tanggal_mulai: $('#filterTanggalMulai').val(),
                tanggal_selesai: $('#filterTanggalSelesai').val(),
                cek: $('#filterCek').val(),
            }).toString();

            $('#btnExportExcel').attr('href', urlExport);

            // Reload datatable
            table.ajax.reload();
        });

        // Inisialisasi URL Export saat halaman dimuat (opsional, jika ingin filter default diterapkan)
        // $('#btnExportExcel').attr('href', '{{ route("tokped-deposit.export") }}?' + new URLSearchParams({
        //     kode_faktur: $('#filterKodeFaktur').val(),
        //     tanggal_mulai: $('#filterTanggalMulai').val(),
        //     tanggal_selesai: $('#filterTanggalSelesai').val(),
        //     cek: $('#filterCek').val(),
        // }).toString());

    });
</script>

@endsection