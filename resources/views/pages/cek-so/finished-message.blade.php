@extends('layouts.plain')

@section('title', 'Cek SO Telah Selesai')

@section('content')
<style>
    .fullscreen-message {
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        height: 90vh; /* Lebih tinggi karena tidak ada navbar */
        font-family: sans-serif;
    }
    .fullscreen-message .message-box {
        font-size: 2.5rem;
        font-weight: bold;
        padding: 40px;
        border: 5px solid #198754; /* Warna hijau success */
        border-radius: 10px;
        background-color: #d1e7dd; /* Latar belakang hijau muda */
        color: #0f5132;
    }
    .fullscreen-message .fa-check-circle {
        font-size: 4rem;
        margin-bottom: 20px;
    }
</style>

{{-- Jangan lupa tambahkan Font Awesome jika belum ada di layout 'plain' --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<div class="fullscreen-message">
    <div class="message-box">
        <i class="fas fa-check-circle"></i><br>
        Cek SO Sudah Selesai.<br>
        Tidak bisa lagi melakukan scan.
    </div>
</div>
@endsection