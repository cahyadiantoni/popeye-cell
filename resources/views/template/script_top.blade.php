<!DOCTYPE html>
<html lang="en">
  <head>
    <title>@yield('title')</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="#" />
    <meta name="keywords" content="Popeye Cell, Jakarta Barat" />
    <meta name="author" content="#" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Favicon icon -->
    <link rel="icon" href="{{ asset('files/assets/images/favicon.ico')}}" type="image/x-icon" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Google font-->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet" />
    <!-- Required Fremwork -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/bootstrap/dist/css/bootstrap.min.css')}}" />
    <!-- feather Awesome -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/assets/icon/feather/css/feather.css')}}" />
    <!-- Data Table Css -->
    <!-- <link rel="stylesheet" type="text/css" href="../files/assets/pages/data-table/css/jquery.dataTables.min.css"> -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/datatables.net-bs4/css/dataTables.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('files/assets/pages/data-table/css/buttons.dataTables.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('files/bower_components/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{ asset('files/assets/pages/data-table/extensions/select/css/select.dataTables.min.css')}}">
    <script src="{{ asset('files/assets/pages/data-table/extensions/select/js/select-custom.js')}}"></script>
    <!-- Style.css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('files/assets/css/style.css')}}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('files/assets/css/jquery.mCustomScrollbar.css')}}" />

    {{-- Custom head --}}
    @yield('heads')

    <style>
      /* Untuk Chrome, Safari, Edge, Opera */
      input::-webkit-outer-spin-button,
      input::-webkit-inner-spin-button {
          -webkit-appearance: none;
          margin: 0;
      }

      /* Untuk Firefox */
      input[type=number] {
          -moz-appearance: textfield;
      }
    </style>

    <style>
      /* Styling untuk kotak pencarian menu */
      .pcoded-search-box {
          padding: 10px 15px; /* Sesuaikan padding */
          border-bottom: 1px solid #e0e0e0; /* Garis pemisah opsional */
      }

      #menuSearch {
          width: 100%;
          padding: 8px 10px;
          border: 1px solid #ccc;
          border-radius: 4px;
          box-sizing: border-box; /* Agar padding tidak menambah lebar total */
      }

      /* Opsional: Styling untuk menyembunyikan item menu yang tidak cocok */
      .pcoded-item.hidden-by-search {
          display: none !important;
      }
      .pcoded-navigatio-lavel.hidden-by-search {
          display: none !important;
      }

      .pcoded-navbar {
          /* ... properti lain yang sudah ada ... */
          /* Pastikan pcoded-navbar bisa membatasi tinggi anaknya jika perlu */
          display: flex; /* Jika belum, ini bisa membantu dalam beberapa layout */
          flex-direction: column; /* Jika belum */
          overflow: hidden; /* Untuk memastikan tidak ada yang meluber keluar dari nav utama */
      }
    </style>
  </head>