const mix = require('laravel-mix');
mix.styles([
    'resources/assets/css/bootstrap.css',
    'resources/assets/css/font-awesome.css',
    'resources/assets/css/jquery.dataTables.css',
    'resources/assets/css/select.dataTables.css',
    'resources/assets/css/sweetalert2.min.css',
    'resources/assets/css/buttons.dataTables.min.css',
    'resources/assets/css/AdminLTE.css',
    'resources/assets/css/jquery.contextMenu.css',
    'resources/assets/css/jquery-ui.css',
    'resources/assets/css/themes/*.css',
    'resources/assets/css/jstree.css',
    'resources/assets/css/bootstrap-datepicker.css',
    'resources/assets/css/bootstrap-timepicker.css',
    'resources/assets/css/select2.min.css',
    'resources/assets/css/toastr.min.css',
], 'public/css/liman.css').version();
mix.combine([
    'resources/assets/js/jquery.js',
    'resources/assets/js/jquery-ui.js',
    'resources/assets/js/jquery.contextMenu.js',
    'resources/assets/js/bootstrap.js',
    'resources/assets/js/bootstrap-datepicker.js',
    'resources/assets/js/bootstrap-timepicker.js',
    'resources/assets/js/datatables.js',
    'resources/assets/js/adminlte.js',
    'resources/assets/js/select2.min.js',
    'resources/assets/js/sweetalert2.min.js',
    'resources/assets/js/Chart.js',
    'resources/assets/js/jstree.js',
    'resources/assets/js/buttons.html5.min.js',
    'resources/assets/js/dataTables.buttons.min.js',
    'resources/assets/js/jszip.min.js',
    'resources/assets/js/pdfmake.min.js',
    'resources/assets/js/vfs_fonts.js',
    'resources/assets/js/jquery.inputmask.min.js',
    'resources/assets/js/toastr.min.js',
    'resources/assets/js/echo.common.js',
    'resources/assets/js/pusher.min.js',
    'resources/assets/js/liman.js',
], 'public/js/liman.js').version();
