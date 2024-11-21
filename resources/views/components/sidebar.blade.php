<nav class="pcoded-navbar">
    <div class="pcoded-inner-navbar main-menu">
        <div class="pcoded-navigatio-lavel">Popeye Cell</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('/') ? 'active' : '' }}">
                <a href="<?= url('/') ?>">
                    <span class="pcoded-micon"><i class="feather icon-home"></i></span>
                    <span class="pcoded-mtext">Dashboard</span>
                </a>
            </li>
        </ul>
        <div class="pcoded-navigatio-lavel">Master Data</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('data-user*') ? 'active' : '' }}">
                <a href="<?= url('/data-user') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Data User</span>
                </a>
            </li>
            <li class="{{ Request::is('data-gudang*') ? 'active' : '' }}">
                <a href="<?= url('/data-gudang') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Data Gudang</span>
                </a>
            </li>
            <li class="{{ Request::is('data-barang*') ? 'active' : '' }}">
                <a href="<?= url('/data-barang') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Data Barang</span>
                </a>
            </li>
        </ul>
        <div class="pcoded-navigatio-lavel">Stok Gudang</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('request-masuk-gudang*') ? 'active' : '' }}">
                <a href="<?= url('/request-masuk-gudang') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Request Barang Masuk</span>
                </a>
            </li>
            <li class="{{ Request::is('stok-opname*') ? 'active' : '' }}">
                <a href="<?= url('/stok-opname') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Stok Opname Gudang</span>
                </a>
            </li>
            <li class="{{ Request::is('history-kirim*') ? 'active' : '' }}">
                <a href="<?= url('/history-kirim') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">History Kirim</span>
                </a>
            </li>
        </ul>
        <div class="pcoded-navigatio-lavel">Transaksi</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('transaksi-jual*') ? 'active' : '' }}">
                <a href="<?= url('/transaksi-jual') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Transaksi Jual</span>
                </a>
            </li>
            <li class="{{ Request::is('transaksi-faktur*') ? 'active' : '' }}">
                <a href="<?= url('/transaksi-faktur') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Transaksi Faktur</span>
                </a>
            </li>
            <li class="{{ Request::is('transaksi-return*') ? 'active' : '' }}">
                <a href="<?= url('/transaksi-return') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Transaksi Return</span>
                </a>
            </li>
        </ul>
    </div>
</nav>