@extends('layouts.main')

@section('title', 'Cek SO Barang')
@section('content')

    <!-- Main-body start -->
    <div class="main-body">
        <div class="page-wrapper">
            <!-- Page-header start -->
            <div class="page-header">
                <div class="row align-items-end">
                    <div class="col-lg-8">
                        <div class="page-header-title">
                            <div class="d-inline">
                                <h4>Cek SO Barang</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="page-header-breadcrumb">
                            <ul class="breadcrumb-title">
                                <li class="breadcrumb-item" style="float: left;">
                                    <a href="<?= url('/') ?>"> <i class="feather icon-home"></i> </a>
                                </li>
                                <li class="breadcrumb-item" style="float: left;"><a
                                        href="#!">Cek SO Barang</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page-header end -->

            <!-- Page-body start -->
            <div class="page-body">
                {{-- Pesan Berhasil --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{-- Pesan Gagal --}}
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <div class="row">
                    <div class="col-sm-12">
                        <!-- Zero config.table start -->
                        <div class="card">
                            <div class="card-block">
                                <button class="btn btn-success" id="addCekSOBtn">Cek SO Barang</button>
                                <hr>
                                <div class="dt-responsive table-responsive">
                                    <table id="simpletable" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Kode</th>
                                                <th>Gudang</th>
                                                <th>Petugas</th>
                                                <th>Jumlah Scan/Stok</th>
                                                <th>Tgl Mulai</th>
                                                <th>Hasil</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @php $no = 1; @endphp
                                        @foreach($cekSOs as $cekso)
                                            <tr>
                                                <td>{{ $no++ }}</td>
                                                <td>{{ $cekso->kode }}</td>
                                                <td>{{ $cekso->nama_gudang ?? 'N/A' }}</td>
                                                <td>{{ $cekso->petugas }}</td>
                                                <td>{{ $cekso->jumlah_scan }}/{{ $cekso->jumlah_stok }}</td>
                                                <td>{{ $cekso->waktu_mulai }}</td>
                                                <td>
                                                    @switch($cekso->hasil)
                                                        @case(0)
                                                            <span class="badge bg-danger">Belum Sesuai</span>
                                                            @break
                                                        @case(1)
                                                            <span class="badge bg-success">Sesuai</span>
                                                            @break
                                                        @case(2)
                                                            <span class="badge bg-warning text-dark">Lok_SPK Belum sesuai</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">Tidak Diketahui</span>
                                                    @endswitch
                                                </td>
                                                <td>
                                                    @switch($cekso->is_finished)
                                                        @case(0)
                                                            <span class="badge bg-warning text-dark">Belum Selesai</span>
                                                            @break
                                                        @case(1)
                                                            <span class="badge bg-success">Selesai</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">Tidak Diketahui</span>
                                                    @endswitch
                                                </td>
                                                <td>
                                                    <!-- Tombol View -->
                                                    @if ($cekso->is_finished == 1)
                                                        <a href="{{ route('cekso.showFinish', $cekso->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @else
                                                        <a href="{{ route('cek-so.show', $cekso->id) }}" class="btn btn-info btn-sm">View</a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>No</th>
                                                <th>Kode</th>
                                                <th>Gudang</th>
                                                <th>Petugas</th>
                                                <th>Jumlah Scan/Stok</th>
                                                <th>Tgl Mulai</th>
                                                <th>Hasil</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Zero config.table end -->
                    </div>
                </div>
            </div>
            <!-- Page-body end -->
        </div>
    </div>
    <!-- Main-body end -->

    <!-- Modal Add Cek SO -->
    <div class="modal fade" id="addCekSOModal" tabindex="-1" aria-labelledby="addCekSOModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('cek-so.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCekSOModalLabel">Buat Cek SO Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="kode" class="form-label">Kode SO</label>
                            <input type="text" class="form-control" id="kode" name="kode" readonly required>
                        </div>
                        <div class="mb-3">
                            <label for="petugas" class="form-label">Keterangan</label>
                            <input type="text" class="form-control" id="petugas" name="petugas" placeholder="Tambahkan nama petugas" required>
                        </div>
                        <div class="mb-3">
                            <label for="waktu_mulai" class="form-label">Waktu Mulai</label>
                            <input type="datetime-local" class="form-control" id="waktu_mulai" name="waktu_mulai" required value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label col-sm-2 col-form-label">Select Gudang</label>
                            <div class="col-sm-10">
                                <select name="penerima_gudang_id" class="form-select form-control" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach($allgudangs as $gudang)
                                        <option value="{{ $gudang->id }}">{{ $gudang->nama_gudang }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Buat Cek SO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const addCekSOBtn = document.getElementById('addCekSOBtn');
            if (addCekSOBtn) {
                addCekSOBtn.addEventListener("click", function () {
                    let addCekSOModal = new bootstrap.Modal(document.getElementById("addCekSOModal"));
                    addCekSOModal.show();
                }, { once: true }); // Tambahkan hanya sekali
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
        let gudangKode = {
            1: "SOBWH",
            2: "SOZLF",
            3: "SOTKP",
            5: "SOVR"
        };

        document.querySelector("select[name='penerima_gudang_id']").addEventListener("change", function () {
            let gudangId = this.value;
            let kodeAwal = gudangKode[gudangId] || "SOXXX"; // Default jika gudang_id tidak ditemukan
            
            if (gudangId) {
                fetch(`/get-last-kode/${gudangId}`)
                    .then(response => response.json())
                    .then(data => {
                        let bulan = new Date().getMonth() + 1; // 1-12
                        let tahun = new Date().getFullYear().toString().substr(-2); // 2 digit terakhir tahun

                        let bulanStr = bulan.toString().padStart(2, '0'); // Format 2 digit
                        let nextNumber = data.next_number.toString().padStart(3, '0'); // Format 3 digit

                        let kodeBaru = `${kodeAwal}${bulanStr}${tahun}${nextNumber}`;
                        document.getElementById("kode").value = kodeBaru;
                    })
                    .catch(error => console.error("Error fetching last kode:", error));
                }
            });
        });
    </script>
@endsection()