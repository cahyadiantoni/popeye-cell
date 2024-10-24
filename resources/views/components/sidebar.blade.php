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
        <div class="pcoded-navigatio-lavel">Data User</div>
        <ul class="pcoded-item pcoded-left-item">
            <li class="{{ Request::is('data-user*') ? 'active' : '' }}">
                <a href="<?= url('/data-user') ?>">
                    <span class="pcoded-micon"><i class="feather icon-user"></i></span>
                    <span class="pcoded-mtext">Data User</span>
                </a>
            </li>
        </ul>
    </div>
</nav>