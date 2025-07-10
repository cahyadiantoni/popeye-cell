@extends('layouts.main')

@section('title', 'Master Harga')
@section('content')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
    /* Mengatasi masalah alignment pada tabel yang kompleks */
    table.dataTable {
        width: 100% !important;
        margin: 0 auto;
        border-collapse: collapse !important;
    }
    .dataTables_scrollHeadInner, .dataTables_scrollHeadInner table.dataTable {
        width: 100% !important;
        margin: 0 !important;
    }
    .dataTables_scrollHead {
        margin-bottom: -1px !important;
    }

    /* Style untuk sel yang bisa diedit */
    .editable-cell {
        cursor: pointer;
        position: relative;
    }
    .editable-cell:hover {
        background-color: #f0f8ff;
    }
    .editable-cell input {
        width: 100%;
        box-sizing: border-box;
        border: 2px solid #007bff;
        border-radius: 4px;
        text-align: right;
        padding: 2px 4px;
    }
    .editable-cell span {
        padding: 5px;
        display: block;
        min-height: 25px;
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
                        @if(session('success'))
                            <div class="alert alert-success mx-4 mt-3">
                                {{ session('success') }}
                            </div>
                        @endif

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
                            <button type="button" class="btn btn-info btn-round" data-bs-toggle="modal" data-bs-target="#addDataModal">
                                Tambah Data Manual
                            </button>
                            <button type="button" class="btn btn-primary btn-round" data-bs-toggle="modal" data-bs-target="#importPivotModal">
                                Import dari Pivot
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
                                                    @php
                                                        $tanggalKey = $tanggal->format('Y-m-d');
                                                        $harga = $row->harga_per_tanggal[$tanggalKey] ?? null;
                                                    @endphp
                                                    <td class="editable-cell" 
                                                        data-tipe="{{ $row->tipe }}" 
                                                        data-grade="{{ $row->grade }}" 
                                                        data-tanggal="{{ $tanggalKey }}">
                                                        <span>
                                                            @if(isset($harga))
                                                                {{ number_format($harga, 0, ',', '.') }}
                                                            @else
                                                                -
                                                            @endif
                                                        </span>
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

<div class="modal fade" id="addDataModal" tabindex="-1" aria-labelledby="addDataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDataModalLabel">Tambah/Edit Data Harga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('master-harga.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if ($errors->any() && !$errors->has('tipe') && !$errors->has('grade') && !$errors->has('harga') && !$errors->has('tanggal'))
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group mb-3">
                        <label for="tipe" class="form-label">Tipe</label>
                        <input type="text" class="form-control @error('tipe') is-invalid @enderror" name="tipe" placeholder="Masukkan tipe barang" value="{{ old('tipe') }}" required>
                        @error('tipe')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label for="grade" class="form-label">Grade</label>
                        <input type="text" class="form-control @error('grade') is-invalid @enderror" name="grade" placeholder="Masukkan grade" value="{{ old('grade') }}" required>
                        @error('grade')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label for="harga" class="form-label">Harga</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control @error('harga') is-invalid @enderror" name="harga" placeholder="Contoh: 150" value="{{ old('harga') }}" required>
                            <span class="input-group-text">.000</span>
                        </div>
                        @error('harga')
                            <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label for="tanggal" class="form-label">Tanggal Berlaku</label>
                        <input type="date" class="form-control @error('tanggal') is-invalid @enderror" name="tanggal" value="{{ old('tanggal') }}" required>
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
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
                        <strong>Petunjuk:</strong> Salin (Copy) semua data tabel dari Excel, <strong>termasuk baris header (Tipe, Grade, Tanggal, ...)</strong>, lalu tempel (Paste) ke dalam kotak di bawah ini.
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

    setTimeout(function() {
        table.columns.adjust().draw();
    }, 10);

    @if ($errors->any())
        var addDataModal = new bootstrap.Modal(document.getElementById('addDataModal'), {
            keyboard: false
        });
        addDataModal.show();
    @endif

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

    $('#tableMasterHarga tbody').on('click', 'td.editable-cell', function() {
        var cell = $(this);
        if (cell.find('input').length > 0) return;

        var originalContent = cell.find('span').text().trim();
        var originalValue = originalContent === '-' ? '' : originalContent.replace(/\./g, '');
        
        var input = $('<input type="number" class="form-control form-control-sm" style="width: 100px;">');
        input.val(originalValue);

        cell.find('span').hide();
        cell.append(input);
        input.focus();

        input.on('blur keydown', function(e) {
            if (e.type === 'keydown' && e.which !== 13) return;
            e.preventDefault();

            var newContent = $(this).val();
            var cellToUpdate = $(this).closest('td');
            
            if (newContent === originalValue) {
                cellToUpdate.find('span').show();
                $(this).remove();
                return;
            }

            var tipe = cellToUpdate.data('tipe');
            var grade = cellToUpdate.data('grade');
            var tanggal = cellToUpdate.data('tanggal');
            
            $.ajax({
                url: "{{ route('master-harga.updateCell') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    tipe: tipe,
                    grade: grade,
                    tanggal: tanggal,
                    harga: newContent
                },
                beforeSend: function() {
                    cellToUpdate.find('span').text('...').show();
                    input.remove();
                },
                success: function(response) {
                    if (response.success) {
                        cellToUpdate.find('span').text(response.formatted_harga);
                    } else {
                        alert('Gagal: ' + response.message);
                        cellToUpdate.find('span').text(originalContent);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan koneksi.');
                    cellToUpdate.find('span').text(originalContent);
                }
            });
        });
    });
});
</script>

@endsection