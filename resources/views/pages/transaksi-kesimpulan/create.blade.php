@extends('layouts.main')

@section('title', 'Buat Kesimpulan Faktur')
@section('content')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <div class="main-body">
        <div class="page-wrapper">
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
                        <div class="card">
                            <div class="card-header">
                                <h3>Form Kesimpulan Faktur</h3>
                            </div>
                            <div class="card-block">
                                <form method="POST" action="{{ route('transaksi-kesimpulan.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3 row">
                                        <div class="sub-title">Pilih Faktur di bawah ini!</div>
                                        <div class="dt-responsive table-responsive">
                                            <table class="table table-striped table-bordered nowrap" style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Pilih</th>
                                                        <th>No Faktur</th>
                                                        <th>Pembeli</th>
                                                        <th>Tgl Faktur</th>
                                                        <th>jumlah Barang</th>
                                                        <th>Total Harga</th>
                                                        <th>Petugas</th>
                                                        <th>Grade</th>
                                                        <th>Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($fakturs as $faktur)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" name="faktur_id[]" value="{{ $faktur->id }}">
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('transaksi-faktur-bawah.show', $faktur->nomor_faktur) }}">
                                                                {{ $faktur->nomor_faktur }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $faktur->pembeli }}</td>
                                                        <td>{{ $faktur->tgl_jual }}</td>
                                                        <td>{{ $faktur->total_barang }}</td>
                                                        <td>{{ 'Rp. ' . number_format($faktur->total, 0, ',', '.') }}</td>
                                                        <td>{{ $faktur->petugas }}</td>
                                                        <td>{{ $faktur->grade }}</td>
                                                        <td>{{ $faktur->keterangan }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tanggal Jual</label>
                                        <div class="col-sm-10">
                                            {{-- PERUBAHAN HTML: Menambahkan atribut 'readonly' --}}
                                            <input type="date" name="tgl_jual" class="form-control" readonly required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <div class="col-md-6">
                                            <label class="form-label">Total Faktur</label>
                                            <input type="number" id="total_faktur" name="total_faktur" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Total Barang</label>
                                            <input type="number" id="total_barang" name="total_barang" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <div class="col-md-6">
                                            <label class="form-label">Potongan Kondisi (Dalam Rp.)</label>
                                            <input type="number" id="potongan_kondisi" name="potongan_kondisi" class="form-control">
                                            <small class="form-text text-muted" id="potongan_kondisi_display">{{ 'Rp. 0' }}</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Diskon (Dalam %)</label>
                                            <input type="number" id="diskon" name="diskon" class="form-control">
                                            <small class="form-text text-muted" id="diskon_display">{{ 'Rp. 0' }}</small>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <div class="col-md-6">
                                            <label class="form-label">Total Harga</label>
                                            <input type="number" id="total" name="total" class="form-control" readonly>
                                            <small class="form-text text-muted" id="total_display">{{ 'Rp. 0' }}</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Total Potongan</label>
                                            <input type="number" id="total_potongan" name="total_potongan" class="form-control" readonly>
                                            <small class="form-text text-muted" id="total_potongan_display">{{ 'Rp. 0' }}</small>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Grand Total</label>
                                        <div class="col-sm-10">
                                            <input type="number" id="grand_total" name="grand_total" class="form-control" readonly>
                                            <small class="form-text text-muted" id="grand_total_display">{{ 'Rp. 0' }}</small>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Keterangan</label>
                                        <div class="col-sm-10">
                                            <textarea name="keterangan" class="form-control" placeholder="Tambahkan keterangan jika diperlukan" rows="4"></textarea>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <div class="sub-title">Masukan bukti transfer di bawah!</div>
                                        <input type="file" name="foto">
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Nominal Transfer</label>
                                        <div class="col-sm-10">
                                            <input type="number" id="nominal" name="nominal" class="form-control" placeholder="Ketik Nominal Transfer">
                                            <small class="form-text text-muted" id="nominal_display">{{ 'Rp. 0' }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('transaksi-kesimpulan.index') }}" class="btn btn-secondary btn-round">Kembali</a>
                                        <button type="submit" class="btn btn-primary btn-round">Submit Kesimpulan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        </div>
                </div>
            </div>
            </div>
    </div>
    <script>
        $(document).ready(function() {
            // Function to format number as currency
            function formatCurrency(value) {
                return 'Rp. ' + new Intl.NumberFormat('id-ID').format(value);
            }

            $('#potongan_kondisi').on('input', function() {
                const value = $(this).val();
                $('#potongan_kondisi_display').text(formatCurrency(value));
            });

            $('#nominal').on('input', function() {
                const value = $(this).val();
                $('#nominal_display').text(formatCurrency(value));
            });
            
            $('#total').on('input', function() {
                const value = $(this).val();
                $('#total_display').text(formatCurrency(value));
            });

            $('#total_potongan').on('input', function() {
                const value = $(this).val();
                $('#total_potongan_display').text(formatCurrency(value));
            });

            $('#grand_total').on('input', function() {
                const value = $(this).val();
                $('#grand_total_display').text(formatCurrency(value));
            });

            function calculateSummary() {
                let totalFaktur = 0;
                let totalBarang = 0;
                let totalHarga = 0;

                const checkedFakturs = $('input[name="faktur_id[]"]:checked');

                checkedFakturs.each(function() {
                    const row = $(this).closest('tr');
                    totalFaktur += 1;
                    totalBarang += parseInt(row.find('td:eq(4)').text()) || 0;
                    
                    const totalText = row.find('td:eq(5)').text().replace(/[^\d]/g, '');
                    totalHarga += parseInt(totalText) || 0;
                });

                $('#total_faktur').val(totalFaktur);
                $('#total_barang').val(totalBarang);
                $('#total').val(totalHarga).trigger('input');

                // Ambil baris dari faktur terakhir yang dicentang
                const lastCheckedRow = checkedFakturs.last().closest('tr');
                if (lastCheckedRow.length > 0) {
                    // Ambil tanggal dari kolom ke-4 (index 3)
                    const tglJual = lastCheckedRow.find('td:eq(3)').text();
                    // Set nilai input tanggal
                    $('input[name="tgl_jual"]').val(tglJual);
                } else {
                    // Kosongkan input tanggal jika tidak ada yang dipilih
                    $('input[name="tgl_jual"]').val('');
                }

                // Hitung ulang potongan dan grand total setelah update total_barang dan total
                calculatePotonganDanGrandTotal(totalHarga);
            }

            function calculatePotonganDanGrandTotal(totalHarga) {
                const potonganKondisi = parseInt($('#potongan_kondisi').val()) || 0;
                const diskon = parseInt($('#diskon').val()) || 0;

                let potonganHarga = totalHarga;
                
                if (potonganKondisi > 0) {
                    potonganHarga -= potonganKondisi;
                    potonganHarga = Math.max(potonganHarga, 0);
                }
                
                let totalPotongan = potonganKondisi;
                
                if (diskon > 0) {
                    potonganDiskon = potonganHarga * (diskon / 100);
                    totalPotongan += potonganDiskon;
                    
                    $('#diskon_display').text(formatCurrency(potonganDiskon));
                }

                $('#total_potongan').val(Math.round(totalPotongan)).trigger('input');

                const grandTotal = totalHarga - totalPotongan;

                $('#grand_total').val(Math.round(grandTotal)).trigger('input');
            }

            // Event ketika faktur dicentang
            $(document).on('change', 'input[name="faktur_id[]"]', function() {
                calculateSummary();
            });

            // Event saat potongan_kondisi atau diskon berubah
            $('#potongan_kondisi, #diskon').on('input', function() {
                const totalBarang = parseInt($('#total_barang').val()) || 0;
                const totalHarga = parseInt($('#total').val()) || 0;
                calculatePotonganDanGrandTotal(totalHarga);
            });
        });
    </script>
@endsection()