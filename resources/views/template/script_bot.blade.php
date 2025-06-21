    <script type="text/javascript" src="{{ asset('files/bower_components/jquery/dist/jquery.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('files/bower_components/jquery-ui/jquery-ui.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('files/bower_components/popper.js/dist/umd/popper.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('files/bower_components/bootstrap/dist/js/bootstrap.min.js')}}"></script>
    <!-- jquery slimscroll js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/jquery-slimscroll/jquery.slimscroll.js')}}"></script>
    <!-- modernizr js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/modernizr/modernizr.js')}}"></script>
    <!-- data-table js -->
    <script src="{{ asset('files/bower_components/datatables.net/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('files/bower_components/datatables.net-buttons/js/dataTables.buttons.min.js')}}"></script>
    <script src="{{ asset('files/assets/pages/data-table/js/jszip.min.js')}}"></script>
    <script src="{{ asset('files/assets/pages/data-table/js/pdfmake.min.js')}}"></script>
    <script src="{{ asset('files/assets/pages/data-table/js/vfs_fonts.js')}}"></script>
    <script src="{{ asset('files/assets/pages/data-table/extensions/select/js/dataTables.select.min.js')}}"></script>
    <script src="{{ asset('files/bower_components/datatables.net-buttons/js/buttons.print.min.js')}}"></script>
    <script src="{{ asset('files/bower_components/datatables.net-buttons/js/buttons.html5.min.js')}}"></script>
    <script src="{{ asset('files/assets/pages/data-table/js/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('files/bower_components/datatables.net-responsive/js/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('files/bower_components/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js')}}"></script>
    <!-- Chart js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/chart.js/dist/Chart.js')}}"></script>
    <!-- amchart js -->
    <script src="{{ asset('files/assets/pages/widget/amchart/amcharts.js')}}"></script>
    <script src="{{ asset('files/assets/pages/widget/amchart/serial.js')}}"></script>
    <script src="{{ asset('files/assets/pages/widget/amchart/light.js')}}"></script>
    <script src="{{ asset('files/assets/js/jquery.mCustomScrollbar.concat.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('files/assets/js/SmoothScroll.js')}}"></script>
    <script src="{{ asset('files/assets/js/pcoded.min.js')}}"></script>
    <!-- i18next.min.js -->
    <script type="text/javascript" src="{{ asset('files/bower_components/i18next/i18next.min.js')}}"></script>
    </script>
    <script type="text/javascript"
        src="{{ asset('files/bower_components/i18next-xhr-backend/i18nextXHRBackend.min.js')}}"></script>
    <script type="text/javascript"
        src="{{ asset('files/bower_components/i18next-browser-languagedetector/i18nextBrowserLanguageDetector.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('files/bower_components/jquery-i18next/jquery-i18next.min.js')}}"></script>
    <!-- custom js -->
    <script src="{{ asset('files/assets/pages/data-table/js/data-table-custom.js')}}"></script> 
    <script src="{{ asset('files/assets/js/vartical-layout.min.js')}}"></script>
    <script type="text/javascript" src="{{ asset('files/assets/pages/dashboard/custom-dashboard.js')}}"></script>
    <script type="text/javascript" src="{{ asset('files/assets/js/script.min.js')}}"></script>

    {{-- Custom Script --}}
    @yield('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const numberInputs = document.querySelectorAll('input[type="number"]');

            numberInputs.forEach(function(input) {
                // Cegah scroll mouse
                input.addEventListener('wheel', function (e) {
                    e.target.blur();
                });

                // Cegah tombol panah ↑ ↓
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const menuSearchInput = document.getElementById('menuSearch');
        const sidebarMainMenu = document.querySelector('.pcoded-inner-navbar.main-menu');

        // Fungsi untuk pencarian menu
        if (menuSearchInput && sidebarMainMenu) {
            menuSearchInput.addEventListener('keyup', function () {
                const searchTerm = this.value.toLowerCase().trim();
                const menuItems = sidebarMainMenu.querySelectorAll('.pcoded-item.pcoded-left-item > li'); // Target <li> langsung di bawah .pcoded-item
                const navLabels = sidebarMainMenu.querySelectorAll('.pcoded-navigatio-lavel');

                // Sembunyikan semua label dulu, akan ditampilkan jika ada item di bawahnya yang cocok
                navLabels.forEach(label => {
                    label.classList.add('hidden-by-search');
                });

                let anyItemVisibleUnderLabel = {}; // Untuk melacak apakah ada item yang terlihat di bawah label

                menuItems.forEach(function (item) {
                    const menuTextElement = item.querySelector('.pcoded-mtext');
                    if (menuTextElement) {
                        const menuText = menuTextElement.textContent.toLowerCase();
                        if (menuText.includes(searchTerm)) {
                            item.classList.remove('hidden-by-search');
                            item.style.display = ''; // Kembalikan ke display default

                            // Cari parent ul dan label di atasnya
                            let parentUl = item.closest('ul.pcoded-item.pcoded-left-item');
                            if (parentUl) {
                                let previousLabel = parentUl.previousElementSibling;
                                while(previousLabel && !previousLabel.classList.contains('pcoded-navigatio-lavel')) {
                                    previousLabel = previousLabel.previousElementSibling;
                                }
                                if (previousLabel && previousLabel.classList.contains('pcoded-navigatio-lavel')) {
                                    previousLabel.classList.remove('hidden-by-search');
                                    previousLabel.style.display = '';
                                }
                            }


                        } else {
                            item.classList.add('hidden-by-search');
                            item.style.display = 'none';
                        }
                    }
                });

                // Jika input pencarian kosong, tampilkan semua menu dan label
                if (searchTerm === "") {
                    menuItems.forEach(item => {
                        item.classList.remove('hidden-by-search');
                        item.style.display = '';
                    });
                    navLabels.forEach(label => {
                        label.classList.remove('hidden-by-search');
                        label.style.display = '';
                    });
                }
            });
        }
    });
    </script>
  </body>
</html>