@extends('layouts.main')

@section('title', 'Riwayat Perubahan Barang')
@section('content')

<style>
    /* Wadah utama untuk setiap sel foto, memastikan lebar konsisten */
    .image-action-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 150px; /* Atur lebar kolom agar seragam */
        margin: auto; /* Pusatkan container di dalam sel tabel */
    }

    /* Wadah untuk gambar, memberikan ukuran kotak yang tetap */
    .image-wrapper {
        width: 120px;
        height: 120px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 8px;
        overflow: hidden; /* Sembunyikan bagian gambar yang keluar dari kotak */
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
    }

    /* Styling untuk gambar di dalam wadah */
    .image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Ini magic-nya! Gambar akan mengisi kotak tanpa penyok */
        transition: transform 0.2s;
    }
    
    .image-wrapper img:hover {
        transform: scale(1.1); /* Efek zoom saat hover */
    }

    /* Wadah untuk tombol-tombol agar rapi */
    .button-wrapper {
        display: flex;
        gap: 5px; /* Memberi jarak antar tombol */
    }
</style>

{{-- Pastikan jQuery dan Bootstrap JS sudah di-load, bisa dari layout utama atau di sini --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<div class="main-body">
    <div class="page-wrapper">
        <div class="page-body">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daftar Semua Perubahan</h5>
                            <span>Upload, lihat, edit, atau hapus bukti foto untuk setiap catatan riwayat.</span>
                        </div>
                        <div class="card-block">
                            <div class="dt-responsive table-responsive">
                                <table id="tableRiwayat" class="table table-striped table-bordered nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>Tanggal & Waktu</th>
                                            <th>LOK SPK</th>
                                            <th>Detail Perubahan</th>
                                            <th>User</th>
                                            <th>Foto Barang</th>      {{-- TAMBAHAN --}}
                                            <th>Foto IMEI</th>         {{-- TAMBAHAN --}}
                                            <th>Foto Device Cek</th> {{-- TAMBAHAN --}}
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Tanggal & Waktu</th>
                                            <th>LOK SPK</th>
                                            <th>Detail Perubahan</th>
                                            <th>User</th>
                                            <th>Foto Barang</th>
                                            <th>Foto IMEI</th>
                                            <th>Foto Device Cek</th>
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

<div class="modal fade" id="fotoModal" tabindex="-1" aria-labelledby="fotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fotoModalLabel">Upload Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="fotoForm" enctype="multipart/form-data">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="history_id" id="history_id">
                    <input type="hidden" name="tipe_foto" id="tipe_foto">
                    <div class="mb-3">
                        <label for="foto" class="form-label">Pilih File Gambar</label>
                        <input class="form-control" type="file" id="foto" name="foto" required>
                    </div>
                    <div class="alert alert-danger" id="error-messages" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Inisialisasi DataTable
    var table = $('#tableRiwayat').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('riwayat-barang.index') }}",
        columns: [
            { data: 'created_at', name: 'created_at' },
            { data: 'lok_spk', name: 'lok_spk' },
            { data: 'update', name: 'update' },
            { data: 'user.name', name: 'user.name' },
            { data: 'foto_barang', name: 'foto_barang', orderable: false, searchable: false },
            { data: 'foto_imei', name: 'foto_imei', orderable: false, searchable: false },
            { data: 'foto_device_cek', name: 'foto_device_cek', orderable: false, searchable: false }
        ]
    });

    // --- JAVASCRIPT UNTUK AKSI FOTO ---

    // 1. Event listener untuk tombol 'Upload' dan 'Edit'
    $(document).on('click', '.btn-upload-foto, .btn-edit-foto', function () {
        var historyId = $(this).data('id');
        var tipeFoto = $(this).data('tipe');

        // Isi data ke modal
        $('#history_id').val(historyId);
        $('#tipe_foto').val(tipeFoto);
        $('#fotoModalLabel').text('Upload Foto untuk ' + tipeFoto.replace('_', ' ').toUpperCase());
        $('#error-messages').hide().html('');
        $('#fotoForm')[0].reset(); // Reset form setiap kali dibuka

        // Tampilkan modal
        $('#fotoModal').modal('show');
    });

    // 2. Event listener untuk submit form upload
    $('#fotoForm').on('submit', function (e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        var historyId = $('#history_id').val();
        var url = "{{ url('/riwayat-barang') }}/" + historyId + "/upload-foto";

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $('#fotoModal').modal('hide');
                alert(response.success); // atau gunakan notifikasi yang lebih baik (SweetAlert, Toastr)
                table.ajax.reload(); // Muat ulang data tabel
            },
            error: function (xhr) {
                var errors = xhr.responseJSON.errors;
                var errorHtml = '<ul>';
                $.each(errors, function (key, value) {
                    errorHtml += '<li>' + value[0] + '</li>';
                });
                errorHtml += '</ul>';
                $('#error-messages').html(errorHtml).show();
            }
        });
    });

    // 3. Event listener untuk tombol 'Hapus'
    $(document).on('click', '.btn-hapus-foto', function () {
        if (!confirm('Apakah Anda yakin ingin menghapus foto ini?')) {
            return;
        }

        var historyId = $(this).data('id');
        var tipeFoto = $(this).data('tipe');
        var url = "{{ url('/riwayat-barang') }}/" + historyId + "/hapus-foto";

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                tipe_foto: tipeFoto
            },
            success: function (response) {
                alert(response.success);
                table.ajax.reload();
            },
            error: function (xhr) {
                alert('Terjadi kesalahan. Gagal menghapus foto.');
            }
        });
    });
});
</script>

@endsection