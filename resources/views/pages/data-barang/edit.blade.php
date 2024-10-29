@extends('layouts.main')

@section('title', 'Edit Data Barang')
@section('content')
    <!-- Main-body start -->
    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page body start -->
            <div class="page-body">
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Basic Form Inputs card start -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Form Edit Data Barang</h3>
                            </div>
                            <div class="card-block">
                                <h4 class="sub-title">Data Barang</h4>
                                <form method="POST" action="{{ route('data-barang.update', $barang->lok_spk) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Select Jenis</label>
                                        <div class="col-sm-10">
                                            <select name="jenis" class="form-select form-control" required>
                                                @php
                                                    $jenisOptions = ['hp', 'laptop', 'dslr'];
                                                    $selectedJenis = strtolower(old('jenis', $barang->jenis)); // Convert to lowercase for comparison
                                                @endphp
                                                <option value="hp" {{ $selectedJenis == 'hp' ? 'selected' : '' }}>HP</option>
                                                <option value="laptop" {{ $selectedJenis == 'laptop' ? 'selected' : '' }}>Laptop</option>
                                                <option value="dslr" {{ $selectedJenis == 'dslr' ? 'selected' : '' }}>DSLR</option>
                                                <option value="lain-lain" {{ !in_array($selectedJenis, array_map('strtolower', $jenisOptions)) ? 'selected' : '' }}>Lain Lain</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Select Merek</label>
                                        <div class="col-sm-10">
                                            @php
                                                $merekOptions = ['samsung', 'xiaomi', 'realme', 'infinix', 'oppo', 'apple', 'asus', 'canon', 'sony']; // Convert options to lowercase
                                                $selectedMerek = strtolower(old('merek', $barang->merek)); // Convert selected value to lowercase
                                            @endphp
                                            <select name="merek" class="form-select form-control" required>
                                                <option value="Samsung" {{ $selectedMerek == 'samsung' ? 'selected' : '' }}>Samsung</option>
                                                <option value="Xiaomi" {{ $selectedMerek == 'xiaomi' ? 'selected' : '' }}>Xiaomi</option>
                                                <option value="Realme" {{ $selectedMerek == 'realme' ? 'selected' : '' }}>Realme</option>
                                                <option value="Infinix" {{ $selectedMerek == 'infinix' ? 'selected' : '' }}>Infinix</option>
                                                <option value="Oppo" {{ $selectedMerek == 'oppo' ? 'selected' : '' }}>Oppo</option>
                                                <option value="Apple" {{ $selectedMerek == 'apple' ? 'selected' : '' }}>Apple</option>
                                                <option value="Asus" {{ $selectedMerek == 'asus' ? 'selected' : '' }}>Asus</option>
                                                <option value="Canon" {{ $selectedMerek == 'canon' ? 'selected' : '' }}>Canon</option>
                                                <option value="Sony" {{ $selectedMerek == 'sony' ? 'selected' : '' }}>Sony</option>
                                                <option value="lain-lain" {{ !in_array($selectedMerek, $merekOptions) ? 'selected' : '' }}>Lain Lain</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tipe</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="tipe" class="form-control" value="{{ old('tipe', $barang->tipe) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Imei</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="imei" class="form-control" value="{{ old('imei', $barang->imei) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Kelengkapan</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="kelengkapan" class="form-control" value="{{ old('kelengkapan', $barang->kelengkapan) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Kerusakan</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="kerusakan" class="form-control" value="{{ old('kerusakan', $barang->kerusakan) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Grade</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="grade" class="form-control" value="{{ old('grade', $barang->grade) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Select Gudang</label>
                                            @php
                                                $gudangOptions = ['1', '2', '3', '4'];
                                                $selectedGudang = old('gudang_id', $barang->gudang_id);
                                            @endphp
                                        <div class="col-sm-10">
                                            <select name="gudang_id" class="form-select form-control" required>
                                                <option value="1" {{ $selectedGudang == '1' ? 'selected' : '' }}>Gudang 1</option>
                                                <option value="2" {{ $selectedGudang == '2' ? 'selected' : '' }}>Gudang 2</option>
                                                <option value="3" {{ $selectedGudang == '3' ? 'selected' : '' }}>Gudang 3</option>
                                                <option value="4" {{ $selectedGudang == '3' ? 'selected' : '' }}>Gudang 4</option>
                                                <option value="lain-lain" {{ !in_array($selectedGudang, $gudangOptions) ? 'selected' : '' }}>Lain Lain</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Select Status Barang</label>
                                            @php
                                                $gudangOptions = ['0', '1', '2', '3', '4'];
                                                $selectedStatus = old('status_barang', $barang->status_barang);
                                            @endphp
                                        <div class="col-sm-10">
                                            <select name="status_barang" class="form-select form-control" required>
                                                <option value="0" {{ $selectedStatus == '0' ? 'selected' : '' }}>Proses Kirim/Pindah</option>
                                                <option value="1" {{ $selectedStatus == '1' ? 'selected' : '' }}>Stok Opname</option>
                                                <option value="2" {{ $selectedStatus == '2' ? 'selected' : '' }}>Terjual</option>
                                                <option value="3" {{ $selectedStatus == '3' ? 'selected' : '' }}>Return</option>
                                                <option value="4" {{ !in_array($selectedStatus, $gudangOptions) ? 'selected' : '' }}>Unknown</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">QT Bunga</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="qt_bunga" class="form-control" value="{{ old('qt_bunga', $barang->qt_bunga) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Harga Jual</label>
                                        <div class="col-sm-10">
                                            <input type="number" id="harga_jual" name="harga_jual" class="form-control" 
                                                value="{{ old('harga_jual', $barang->harga_jual) }}" required oninput="updateFormat(this)">
                                            <small class="text-muted" id="harga_jual_format">Format: Rp. 0</small>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Harga Beli</label>
                                        <div class="col-sm-10">
                                            <input type="number" id="harga_beli" name="harga_beli" class="form-control" 
                                                value="{{ old('harga_beli', $barang->harga_beli) }}" required oninput="updateFormat(this)">
                                            <small class="text-muted" id="harga_beli_format">Format: Rp. 0</small>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Keterangan 1</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="keterangan1" class="form-control" value="{{ old('keterangan1', $barang->keterangan1) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Keterangan 2</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="keterangan2" class="form-control" value="{{ old('keterangan2', $barang->keterangan2) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Keterangan 3</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="keterangan3" class="form-control" value="{{ old('keterangan3', $barang->keterangan3) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Nama Petugas</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="nama_petugas" class="form-control" value="{{ old('nama_petugas', $barang->nama_petugas) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tanggal Beli</label>
                                        <div class="col-sm-10">
                                            <input type="date" name="dt_beli" class="form-control" value="{{ old('dt_beli', \Carbon\Carbon::parse($barang->dt_beli)->format('Y-m-d')) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tanggal Lelang</label>
                                        <div class="col-sm-10">
                                            <input type="date" name="dt_lelang" class="form-control" value="{{ old('dt_lelang', \Carbon\Carbon::parse($barang->dt_lelang)->format('Y-m-d')) }}" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="form-label col-sm-2 col-form-label">Tanggal Jatuh Tempo</label>
                                        <div class="col-sm-10">
                                            <input type="date" name="dt_jatuh_tempo" class="form-control" value="{{ old('dt_jatuh_tempo', \Carbon\Carbon::parse($barang->dt_jatuh_tempo)->format('Y-m-d')) }}" required>
                                        </div>
                                    </div>
                                    <!-- Tambahkan tombol submit di sini -->
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('data-barang.index') }}" class="btn btn-secondary btn-round">Kembali</a>
                                        <button type="submit" class="btn btn-primary btn-round">Update Barang</button>
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
        function updateFormat(input) {
            // Mengambil nilai dari input
            let value = input.value;
            // Menghilangkan tanda titik atau koma dari nilai
            value = value.replace(/\D/g, '');
            // Format sebagai mata uang
            let formattedValue = 'Rp. ' + new Intl.NumberFormat('id-ID').format(value);
            // Memperbarui elemen kecil dengan format
            if (input.id === 'harga_jual') {
                document.getElementById('harga_jual_format').innerText = formattedValue;
            } else if (input.id === 'harga_beli') {
                document.getElementById('harga_beli_format').innerText = formattedValue;
            }
        }

        // Inisialisasi format saat halaman dimuat
        window.onload = function() {
            updateFormat(document.getElementById('harga_jual'));
            updateFormat(document.getElementById('harga_beli'));
        }
    </script>
@endsection()