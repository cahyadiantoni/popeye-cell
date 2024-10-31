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
  </body>
</html>