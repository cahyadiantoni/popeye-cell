@include('template.script_top') 

<body>
    <div id="pcoded" class="pcoded">
        <div class="pcoded-overlay-box"></div>
        <div class="pcoded-container navbar-wrapper">
            @include('components.navbar')
            @include('components.sidebarchat')
            <div class="pcoded-main-container">
                <div class="pcoded-wrapper">
                    @include('components.sidebar')
                    <div class="pcoded-content">
                        <div class="pcoded-inner-content">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@include('template.script_bot') 