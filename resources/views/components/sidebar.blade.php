<nav class="pcoded-navbar">
    <div class="pcoded-inner-navbar main-menu">
        <div class="pcoded-navigatio-lavel">Popeye Cell</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('/') ? 'active' : '' }}">
                <a href="{{ url('/') }}">
                    <span class="pcoded-micon"><i class="fas fa-tachometer-alt"></i></span>
                    <span class="pcoded-mtext">Dashboard</span>
                </a>
            </li>
        </ul>
        <div class="pcoded-navigatio-lavel">Master Data</div>
        <ul class="pcoded-item pcoded-left-item">
            <!-- <li class="{{ Request::is('data-user*') ? 'active' : '' }}">
                <a href="{{ url('/data-user') }}">
                    <span class="pcoded-micon"><i class="fas fa-users"></i></span>
                    <span class="pcoded-mtext">Data User</span>
                </a>
            </li> -->
            <!-- <li class="{{ Request::is('data-gudang*') ? 'active' : '' }}">
                <a href="{{ url('/data-gudang') }}">
                    <span class="pcoded-micon"><i class="fas fa-warehouse"></i></span>
                    <span class="pcoded-mtext">Data Gudang</span>
                </a>
            </li> -->
            <li class="{{ Request::is('data-barang') ? 'active' : '' }}">
                <a href="{{ url('/data-barang') }}">
                    <span class="pcoded-micon"><i class="fas fa-box"></i></span>
                    <span class="pcoded-mtext">Data Barang</span>
                </a>
            </li>
            <li class="{{ Request::is('data-barang/create*') ? 'active' : '' }}">
                <a href="{{ url('/data-barang/create') }}">
                    <span class="pcoded-micon"><i class="fas fa-upload"></i></span>
                    <span class="pcoded-mtext">Upload Barang</span>
                </a>
            </li>
            <li class="{{ Request::is('mass-edit-barang*') ? 'active' : '' }}">
                <a href="{{ url('/mass-edit-barang') }}">
                    <span class="pcoded-micon"><i class="fas fa-edit"></i></span>
                    <span class="pcoded-mtext">Mass Edit Barang</span>
                </a>
            </li>
        </ul>
        <div class="pcoded-navigatio-lavel">Stok Gudang</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('choice-gudang*') ? 'active' : '' }}">
                <a href="{{ url('/choice-gudang') }}">
                    <span class="pcoded-micon"><i class="fas fa-boxes"></i></span>
                    <span class="pcoded-mtext">Stok Opname Gudang</span>
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
        </ul>
        <div class="pcoded-navigatio-lavel">Transaksi</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('transaksi-jual/create') ? 'active' : '' }}">
                <a href="{{ url('/transaksi-jual/create') }}">
                    <span class="pcoded-micon"><i class="fas fa-store"></i></span>
                    <span class="pcoded-mtext">Transaksi Jual (Offline)</span>
                </a>
            </li>
            <li class="{{ Request::is('transaksi-faktur') ? 'active' : '' }}">
                <a href="{{ url('/transaksi-faktur') }}">
                    <span class="pcoded-micon"><i class="fas fa-file"></i></span>
                    <span class="pcoded-mtext">Transaksi Faktur (Offline)</span>
                </a>
            </li>
            <li class="{{ Request::is('transaksi-jual-online*') ? 'active' : '' }}">
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
            <li class="{{ Request::is('transaksi-return*') ? 'active' : '' }}">
                <a href="{{ url('/transaksi-return') }}">
                    <span class="pcoded-micon"><i class="fas fa-undo"></i></span>
                    <span class="pcoded-mtext">Transaksi Return</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
