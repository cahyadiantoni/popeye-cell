@extends('layouts.main')

@section('title', 'Master Harga')
@section('content')

{{-- Pastikan jQuery sudah di-load --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

{{-- CSS PERBAIKAN TAMPILAN --}}
<style>
    /* Mengatasi masalah alignment pada tabel yang kompleks */
    table.dataTable {
        width: 100% !important;
        margin: 0 auto;
        border-collapse: collapse !important; /* Pastikan border menyatu */
    }
    .dataTables_scrollHeadInner, .dataTables_scrollHeadInner table.dataTable {
        width: 100% !important;
        margin: 0 !important;
    }

    .dataTables_scrollHead {
        margin-bottom: -1px !important;
    }
</style>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-header">
            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="page-header-title">
                        <div class="d-inline">
                            <h4>Master Harga</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="page-header-breadcrumb">
                        <ul class="breadcrumb-title">
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="{{ url('/') }}"> <i class="feather icon-home"></i> </a>
                            </li>
                            <li class="breadcrumb-item" style="float: left;">
                                <a href="#!">Master Harga</a>
                            </li>
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
                            <form action="{{ route('master-harga.index') }}" method="GET">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="grade">Grade</label>
                                        <select name="grade" id="grade" class="form-control">
                                            <option value="">-- Semua Grade --</option>
                                            @foreach($grades as $grade)
                                                <option value="{{ $grade }}" {{ $grade == $selectedGrade ? 'selected' : '' }}>
                                                    {{ $grade }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="start_date">Tanggal Mulai</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filterStartDate }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="end_date">Tanggal Selesai</label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filterEndDate }}">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="submit" class="btn btn-primary btn-round">Filter</button>
                                    <a href="{{ route('master-harga.index') }}" class="btn btn-secondary btn-round mx-2">Reset</a>
                                </div>
                            </form>
                        </div>
                        <div class="card-header">
                            <button type="button" class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#importPivotModal">
                                Import Data
                            </button>
                            <a href="{{ route('master-harga.export') }}" class="btn btn-success btn-round">
                                Export Excel
                            </a>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="tableMasterHarga" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Tipe</th>
                                            <th>Grade</th>
                                            @foreach($tanggalHeaders as $tanggal)
                                                <th>{{ $tanggal->format('d-M-y') }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($dataPivot as $row)
                                            <tr>
                                                <td>{{ $row->tipe }}</td>
                                                <td>{{ $row->grade }}</td>
                                                @foreach($tanggalHeaders as $tanggal)
                                                    <td>
                                                        @php
                                                            $tanggalKey = $tanggal->format('Y-m-d');
                                                            $harga = $row->harga_per_tanggal[$tanggalKey] ?? null;
                                                        @endphp
                                                        @if(isset($harga))
                                                            {{ number_format($harga, 0, ',', '.') }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
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
<div class="modal fade" id="importPivotModal" tabindex="-1" aria-labelledby="importPivotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importPivotModalLabel">Impor dari Tabel Pivot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="importPivotForm">
                <div class="modal-body">
                    @csrf
                    <div class="alert alert-info">
                        <strong>Petunjuk:</strong> Buka file Excel hasil ekspor. Salin (Copy) semua data tabel, **termasuk baris header (Tipe, Grade, Tanggal, ...)**, lalu tempel (Paste) ke dalam kotak di bawah ini.
                    </div>
                    <div class="form-group">
                        <textarea name="pasted_data" class="form-control" rows="15" placeholder="Salin dan tempel data tabel dari Excel di sini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Proses Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#tableMasterHarga').DataTable({
        "scrollX": true, 
        "scrollCollapse": true,
        "fixedColumns": {
            left: 2
        }
    });

    // Sesuaikan ulang kolom setelah inisialisasi
    setTimeout(function() {
        table.columns.adjust().draw();
    }, 10);

    $('#importPivotForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var submitButton = $(this).find('button[type="submit"]');
        var originalButtonText = submitButton.html();
        submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...').prop('disabled', true);

        $.ajax({
            url: "{{ route('master-harga.importPivot') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#importPivotModal').modal('hide');
                alert(response.message);
                window.location.reload(); 
            },
            error: function(xhr) {
                var response = xhr.responseJSON;
                alert('Terjadi kesalahan: ' + (response.message || 'Silakan cek kembali data yang Anda tempel.'));
            },
            complete: function() {
                submitButton.html(originalButtonText).prop('disabled', false);
            }
        });
    });
});
</script>

@endsection