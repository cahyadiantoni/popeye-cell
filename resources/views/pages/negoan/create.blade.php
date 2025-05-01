@extends('layouts.main')

@section('title', 'Create Negoan Harga')
@section('content')
    <!-- Main-body start -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page body start -->
            <div class="page-body">
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

                @if(session('errors'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul>
                            @foreach (session('errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row">
                    <div class="col-sm-12">
                        <!-- Basic Form Inputs card start -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Form Negoan Harga</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Buat Negoan Harga</h4>
                                <form method="POST" action="{{ route('negoan.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tipe</label>
                                        <div class="col-sm-10">
                                            <input type="text" id="tipe_search" class="form-control" placeholder="Cari Tipe" onkeyup="filterTipe()">
                                            <select name="tipe" id="tipe_select" class="form-control mt-2" required>
                                                <option value="">Pilih Tipe</option>
                                                @foreach($tipeList as $tipe)
                                                    <option value="{{ $tipe }}">{{ $tipe }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Grade</label>
                                        <div class="col-sm-10">
                                            <select name="grade" id="grade" class="form-control" required>
                                                <option value="">Pilih Grade</option>
                                                <option value="Barang JB">Barang JB</option>
                                                <option value="Barang 2nd">Barang 2nd</option>
                                                <option value="IP JB">IP JB</option>
                                                <option value="IP 2nd">IP 2nd</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Harga Awal</label>
                                        <div class="col-sm-10">
                                            <div>
                                                <input type="radio" name="is_manual" value="0" checked onclick="toggleManualInput()"> Ambil dari data
                                                <input type="radio" name="is_manual" value="1" onclick="toggleManualInput()"> Manual
                                            </div>
                                            <input type="number" name="harga_awal" class="form-control mt-2" required id="harga_awal" readonly>
                                            <small class="form-text text-muted" id="harga_awal_display">{{ 'Rp. 0' }}</small>
                                            <input type="number" name="harga_awal_manual" class="form-control mt-2" placeholder="Ketik Harga Awal Manual" id="harga_awal_manual" style="display: none;">
                                            <small class="form-text text-muted" id="harga_awal_manual_display" style="display: none;">{{ 'Rp. 0' }}</small>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Harga Nego</label>
                                        <div class="col-sm-10">
                                            <input type="number" name="harga_nego" class="form-control" placeholder="Ketik Harga Nego" required id="harga_nego">
                                            <small class="form-text text-muted" id="harga_nego_display">{{ 'Rp. 0' }}</small>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Note Nego</label>
                                        <div class="col-sm-10">
                                            <textarea name="note_nego" class="form-control" placeholder="Tambahkan catatan jika diperlukan" rows="4"></textarea>
                                        </div>
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('negoan.index') }}" class="btn btn-secondary btn-round">List Negoan</a>
                                        <button type="submit" class="btn btn-primary btn-round">Submit Negoan Harga</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Basic Form Inputs card end -->
                    </div>
                </div>
            </div>
            <!-- Page body end -->
        </div>
    </div>
    <!-- Main-body end -->

    <script>
        $(document).ready(function() {
            // Function to format number as currency
            function formatCurrency(value) {
                return 'Rp. ' + new Intl.NumberFormat('id-ID').format(value);
            }


            // Update display for harga_awal_manual
            $('#harga_awal_manual').on('input', function() {
                const value = $(this).val();
                $('#harga_awal_manual_display').text(formatCurrency(value));
            });

            // Update display for harga_nego
            $('#harga_nego').on('input', function() {
                const value = $(this).val();
                $('#harga_nego_display').text(formatCurrency(value));
            });

            // Fetch harga_awal based on selected tipe and grade
            $('#tipe_select, #grade').on('change', function() {
                const selectedTipe = $('#tipe_select').val();
                const selectedGrade = $('#grade').val();

                if (selectedTipe && selectedGrade) {
                    $.ajax({
                        url: '{{ route('negoan.harga-awal') }}',
                        method: 'GET',
                        data: {
                            tipe: selectedTipe,
                            grade: selectedGrade
                        },
                        success: function(response) {
                            if (response.harga_awal) {
                                $('#harga_awal').val(response.harga_awal);
                                $('#harga_awal_display').text(formatCurrency(response.harga_awal));
                            } else {
                                $('#harga_awal').val('');
                                $('#harga_awal_display').text('Rp. 0');
                            }
                        }
                    });
                } else {
                    $('#harga_awal').val('');
                    $('#harga_awal_display').text('Rp. 0');
                }
            });
        });

        function toggleManualInput() {
            const isManual = $('input[name="is_manual"]:checked').val() == 1;
            // $('#harga_awal').prop('readonly', isManual);
            $('#harga_awal_manual').toggle(isManual);
            $('#harga_awal_manual_display').toggle(isManual);
            if (!isManual) {
                $('#harga_awal_manual').val(''); // Clear manual input if not in manual mode
            }
        }

        function filterTipe() {
            const input = document.getElementById('tipe_search');
            const filter = input.value.toLowerCase();
            const select = document.getElementById('tipe_select');
            const options = select.options;

            for (let i = 1; i < options.length; i++) { // Start from 1 to skip the first option
                const option = options[i];
                const text = option.text.toLowerCase();
                option.style.display = text.includes(filter) ? '' : 'none';
            }
        }
    </script>
@endsection
