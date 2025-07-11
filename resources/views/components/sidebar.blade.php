<nav class="pcoded-navbar">
    <div class="pcoded-inner-navbar main-menu">
        {{-- Bagian untuk Input Pencarian --}}
        <div class="pcoded-search-box">
            <input type="text" id="menuSearch" class="form-control" placeholder="Cari menu...">
        </div>

        <div class="pcoded-navigatio-lavel">Popeye Cell</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('/') ? 'active' : '' }}">
                <a href="{{ url('/') }}">
                    <span class="pcoded-micon"><i class="fas fa-tachometer-alt"></i></span>
                    <span class="pcoded-mtext">Dashboard</span>
                </a>
            </li>
        </ul>
        @if(auth()->user()->sales)
            <div class="pcoded-navigatio-lavel">Master Data</div>
            <ul class="pcoded-item pcoded-left-item">
                @if(auth()->user()->role=="admin")
                    <li class="{{ Request::is('data-user*') ? 'active' : '' }}">
                        <a href="{{ url('/data-user') }}">
                            <span class="pcoded-micon"><i class="fas fa-users"></i></span>
                            <span class="pcoded-mtext">Data User</span>
                        </a>
                    </li>
                    <li class="{{ Request::is('mac-address*') ? 'active' : '' }}">
                        <a href="{{ url('/mac-address') }}">
                            <span class="pcoded-micon"><i class="fas fa-desktop"></i></span>
                            <span class="pcoded-mtext">Data Mac Address</span>
                        </a>
                    </li>
                    <li class="{{ Request::is('data-gudang*') ? 'active' : '' }}">
                        <a href="{{ url('/data-gudang') }}">
                            <span class="pcoded-micon"><i class="fas fa-warehouse"></i></span>
                            <span class="pcoded-mtext">Data Gudang</span>
                        </a>
                    </li>
                @endif
                <li class="{{ Request::is('data-barang') ? 'active' : '' }}">
                    <a href="{{ url('/data-barang') }}">
                        <span class="pcoded-micon"><i class="fas fa-box"></i></span>
                        <span class="pcoded-mtext">Data Barang</span>
                    </a>
                </li>
                @if(auth()->user()->role=="admin")
                    <li class="{{ Request::is('master-harga') ? 'active' : '' }}">
                        <a href="{{ url('/master-harga') }}">
                            <span class="pcoded-micon"><i class="fas fa-money-bill-wave"></i></span>
                            <span class="pcoded-mtext">Data Harga</span>
                        </a>
                    </li>
                @endif
                <li class="{{ Request::is('data-barang-pendingan') ? 'active' : '' }}">
                    <a href="{{ url('/data-barang-pendingan') }}">
                        <span class="pcoded-micon"><i class="fas fa-box"></i></span>
                        <span class="pcoded-mtext">Data Pendingan</span>
                    </a>
                </li>
                <li class="{{ Request::is('data-barang/create*') ? 'active' : '' }}">
                    <a href="{{ url('/data-barang/create') }}">
                        <span class="pcoded-micon"><i class="fas fa-upload"></i></span>
                        <span class="pcoded-mtext">Upload Barang</span>
                    </a>
                </li>
                <li class="{{ Request::is('mass-edit-barang') ? 'active' : '' }}">
                    <a href="{{ url('/mass-edit-barang') }}">
                        <span class="pcoded-micon"><i class="fas fa-edit"></i></span>
                        <span class="pcoded-mtext">Mass Edit Barang</span>
                    </a>
                </li>
                <li class="{{ Request::is('riwayat-barang') ? 'active' : '' }}">
                    <a href="{{ url('/riwayat-barang') }}">
                        <span class="pcoded-micon"><i class="fas fa-history"></i></span>
                        <span class="pcoded-mtext">Riwayat Edit Barang</span>
                    </a>
                </li>
            </ul>
            <div class="pcoded-navigatio-lavel">Stok Gudang</div>
            <ul class="pcoded-item pcoded-left-item">
                <li class="{{ Request::is('buku-stok*') ? 'active' : '' }}">
                    <a href="{{ url('/buku-stok') }}">
                        <span class="pcoded-micon"><i class="fas fa-book"></i></span>
                        <span class="pcoded-mtext">Buku Stok</span>
                    </a>
                </li>
                <li class="{{ Request::is('cek-so*') ? 'active' : '' }}">
                    <a href="{{ url('/cek-so') }}">
                        <span class="pcoded-micon"><i class="fas fa-clipboard-list"></i></span>
                        <span class="pcoded-mtext">Cek SO</span>
                    </a>
                </li>
                <li class="{{ Request::is('choice-gudang*') ? 'active' : '' }}">
                    <a href="{{ url('/choice-gudang') }}">
                        <span class="pcoded-micon"><i class="fas fa-boxes"></i></span>
                        <span class="pcoded-mtext">Master Data Gudang</span>
                    </a>
                </li>
                <li class="{{ Request::is('kirim-barang*') ? 'active' : '' }}">
                    <a href="{{ url('/kirim-barang') }}">
                        <span class="pcoded-micon"><i class="fas fa-truck"></i></span>
                        <span class="pcoded-mtext">Kirim Barang</span>
                    </a>
                </li>
                <li class="{{ Request::is('terima-barang*') ? 'active' : '' }}">
                    <a href="{{ url('/terima-barang') }}">
                        <span class="pcoded-micon"><i class="fas fa-inbox"></i></span>
                        <span class="pcoded-mtext">Request Barang Masuk</span>
                    </a>
                </li>
                <li class="{{ Request::is('negoan*') ? 'active' : '' }}">
                    <a href="{{ url('/negoan') }}">
                        <span class="pcoded-micon"><i class="fas fa-handshake"></i></span>
                        <span class="pcoded-mtext">Negoan Harga</span>
                    </a>
                </li>
            </ul>
            @if(auth()->user()->gudang_id == 1 || auth()->user()->role == 'admin')
            <div class="pcoded-navigatio-lavel">Transaksi Bawah</div>
            <ul class="pcoded-item pcoded-left-item">
                <li class="{{ Request::is('transaksi-jual-bawah/create') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-jual-bawah/create') }}">
                        <span class="pcoded-micon"><i class="fas fa-store"></i></span>
                        <span class="pcoded-mtext">Transaksi Jual Bawah</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-faktur-bawah') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-faktur-bawah') }}">
                        <span class="pcoded-micon"><i class="fas fa-file"></i></span>
                        <span class="pcoded-mtext">Transaksi Faktur Bawah</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-jual-bawah') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-jual-bawah') }}">
                        <span class="pcoded-micon"><i class="fas fa-history"></i></span>
                        <span class="pcoded-mtext">Riwayat Transaksi Bawah</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-kesimpulan') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-kesimpulan') }}">
                        <span class="pcoded-micon"><i class="fas fa-chart-bar"></i></span>
                        <span class="pcoded-mtext">Kesimpulan Bawah</span>
                    </a>
                </li>
            </ul>
            @endif
            @if(auth()->user()->gudang_id == 2 || auth()->user()->gudang_id == 3 || auth()->user()->gudang_id == 5 || auth()->user()->role == 'admin')
            <div class="pcoded-navigatio-lavel">Transaksi Atas</div>
            <ul class="pcoded-item pcoded-left-item">
                <li class="{{ Request::is('transaksi-jual/create') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-jual/create') }}">
                        <span class="pcoded-micon"><i class="fas fa-store"></i></span>
                        <span class="pcoded-mtext">Transaksi Jual Atas</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-faktur') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-faktur') }}">
                        <span class="pcoded-micon"><i class="fas fa-file"></i></span>
                        <span class="pcoded-mtext">Transaksi Faktur Atas</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-jual') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-jual') }}">
                        <span class="pcoded-micon"><i class="fas fa-history"></i></span>
                        <span class="pcoded-mtext">Riwayat Transaksi Atas</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-rekap') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-rekap') }}">
                        <span class="pcoded-micon"><i class="fas fa-chart-bar"></i></span>
                        <span class="pcoded-mtext">Rekap Transaksi Atas</span>
                    </a>
                </li>
            </ul>
            @endif
            @if(auth()->user()->gudang_id == 3 || auth()->user()->gudang_id == 5 || auth()->user()->role == 'admin')
            <div class="pcoded-navigatio-lavel">Transaksi Online</div>
            <ul class="pcoded-item pcoded-left-item">
                <li class="{{ Request::is('transaksi-jual-online/create') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-jual-online/create') }}">
                        <span class="pcoded-micon"><i class="fas fa-globe"></i></span>
                        <span class="pcoded-mtext">Transaksi Jual Online</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-faktur-online*') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-faktur-online') }}">
                        <span class="pcoded-micon"><i class="fas fa-file-alt"></i></span>
                        <span class="pcoded-mtext">Transaksi Faktur Online</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-jual-online') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-jual-online') }}">
                        <span class="pcoded-micon"><i class="fas fa-history"></i></span>
                        <span class="pcoded-mtext">Riwayat Transaksi Online</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-rekap-online') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-rekap-online') }}">
                        <span class="pcoded-micon"><i class="fas fa-network-wired"></i></span>
                        <span class="pcoded-mtext">Rekap Transaksi Online</span>
                    </a>
                </li>
            </ul>
            @endif
            @if(auth()->user()->gudang_id == 8 || auth()->user()->gudang_id == 9 || auth()->user()->gudang_id == 10 || auth()->user()->role == 'admin')
            <div class="pcoded-navigatio-lavel">Transaksi Outlet</div>
            <ul class="pcoded-item pcoded-left-item">
                <li class="{{ Request::is('transaksi-jual-outlet/create') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-jual-outlet/create') }}">
                        <span class="pcoded-micon"><i class="fas fa-store"></i></span>
                        <span class="pcoded-mtext">Transaksi Jual Outlet</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-faktur-outlet') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-faktur-outlet') }}">
                        <span class="pcoded-micon"><i class="fas fa-file"></i></span>
                        <span class="pcoded-mtext">Transaksi Faktur Outlet</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-jual-outlet') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-jual-outlet') }}">
                        <span class="pcoded-micon"><i class="fas fa-history"></i></span>
                        <span class="pcoded-mtext">Riwayat Transaksi Outlet</span>
                    </a>
                </li>
                <li class="{{ Request::is('transaksi-rekap-outlet') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-rekap-outlet') }}">
                        <span class="pcoded-micon"><i class="fas fa-chart-bar"></i></span>
                        <span class="pcoded-mtext">Rekap Transaksi Outlet</span>
                    </a>
                </li>
            </ul>
            @endif
            @if(auth()->user()->role=="admin")
            <div class="pcoded-navigatio-lavel">Admin Menu</div>
            <ul class="pcoded-item pcoded-left-item">
                <li class="{{ Request::is('transaksi-return*') ? 'active' : '' }}">
                    <a href="{{ url('/transaksi-return') }}">
                        <span class="pcoded-micon"><i class="fas fa-undo"></i></span>
                        <span class="pcoded-mtext">Transaksi Return</span>
                    </a>
                </li>
                <li class="{{ Request::is('tokped-deposit') ? 'active' : '' }}">
                    <a href="{{ url('/tokped-deposit') }}">
                        <span class="pcoded-micon"><i class="fas fa-wallet"></i></span>
                        <span class="pcoded-mtext">Tokped Deposit</span>
                    </a>
                </li>
                <li class="{{ Request::is('tokped-order') ? 'active' : '' }}">
                    <a href="{{ url('/tokped-order') }}">
                        <span class="pcoded-micon"><i class="fas fa-shopping-cart"></i></span>
                        <span class="pcoded-mtext">Tokped Order</span>
                    </a>
                </li>
                <li class="{{ Request::is('tokped-deposit/rekap') ? 'active' : '' }}">
                    <a href="{{ url('/tokped-deposit/rekap') }}">
                        <span class="pcoded-micon"><i class="fas fa-file-invoice-dollar"></i></span>
                        <span class="pcoded-mtext">Tokped Rekap</span>
                    </a>
                </li>
                <li class="{{ Request::is('pulsa-master') ? 'active' : '' }}">
                    <a href="{{ url('/pulsa-master') }}">
                        <span class="pcoded-micon"><i class="fas fa-sim-card"></i></span>
                        <span class="pcoded-mtext">Pulsa Master</span>
                    </a>
                </li>
                <li class="{{ Request::is('pulsa-report') ? 'active' : '' }}">
                    <a href="{{ url('/pulsa-report') }}">
                        <span class="pcoded-micon"><i class="fas fa-chart-line"></i></span>
                        <span class="pcoded-mtext">Pulsa Report</span>
                    </a>
                </li>
                <li class="{{ Request::is('history-edit-faktur-atas') ? 'active' : '' }}">
                    <a href="{{ url('/history-edit-faktur-atas') }}">
                        <span class="pcoded-micon"><i class="fas fa-history"></i></span>
                        <span class="pcoded-mtext">Riwayat Edit Faktur Atas</span>
                    </a>
                </li>
            </ul>
            @endif
        @endif
        @if(auth()->user()->adm)
            <div class="pcoded-navigatio-lavel">Administrasi</div>
            <ul class="pcoded-item pcoded-left-item">
                <li class="{{ Request::is('todo-transfer*') ? 'active' : '' }}">
                    <a href="{{ url('/todo-transfer') }}">
                        <span class="pcoded-micon"><i class="fas fa-exchange-alt"></i></span>
                        <span class="pcoded-mtext">Todo Transfer</span>
                    </a>
                </li>
                <li class="{{ Request::is('req-tokped') ? 'active' : '' }}">
                    <a href="{{ url('/req-tokped') }}">
                        <span class="pcoded-micon"><i class="fas fa-shopping-cart"></i></span>
                        <span class="pcoded-mtext">Request Tokped</span>
                    </a>
                </li>
                <li class="{{ Request::is('req-tokped/history') ? 'active' : '' }}">
                    <a href="{{ url('/req-tokped/history') }}">
                        <span class="pcoded-micon"><i class="fas fa-history"></i></span>
                        <span class="pcoded-mtext">History Tokped</span>
                    </a>
                </li>
                @if(auth()->user()->role=="admin")
                <li class="{{ Request::is('item-tokped') ? 'active' : '' }}">
                    <a href="{{ url('/item-tokped') }}">
                        <span class="pcoded-micon"><i class="fas fa-box"></i></span>
                        <span class="pcoded-mtext">Item Tokped</span>
                    </a>
                </li>
                <li class="{{ Request::is('adm-setting') ? 'active' : '' }}">
                    <a href="{{ url('/adm-setting') }}">
                        <span class="pcoded-micon"><i class="fas fa-cogs"></i></span>
                        <span class="pcoded-mtext">Setting Administrasi</span>
                    </a>
                </li>
                @endif
            </ul>
        @endif
    </div>
</nav>
