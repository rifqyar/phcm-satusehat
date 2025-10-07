<script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap tether Core JavaScript -->
<script src="{{ asset('assets/plugins/popper/popper.min.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
<!-- slimscrollbar scrollbar JavaScript -->
<script src="{{ asset('assets/js/jquery.slimscroll.js') }}"></script>
<!--Wave Effects -->
<script src="{{ asset('assets/js/waves.js') }}"></script>
<!--Menu sidebar -->
<script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>
<!--stickey kit -->
<script src="{{ asset('assets/plugins/sticky-kit-master/dist/sticky-kit.min.js') }}"></script>
<script src="{{ asset('assets/plugins/sparkline/jquery.sparkline.min.js') }}"></script>
<!--Custom JavaScript -->
<script src="{{ asset('assets/js/custom.min.js') }}"></script>
<script src="{{ asset('assets/js/base.js') }}"></script>
<!-- ============================================================== -->
<!-- This page plugins -->
<!-- ============================================================== -->
<!-- chartist chart -->
<script src="{{ asset('assets/plugins/chartist-js/dist/chartist.min.js') }}"></script>
<script src="{{ asset('assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js') }}">
</script>
<!--c3 JavaScript -->
<script src="{{ asset('assets/plugins/d3/d3.min.js') }}"></script>
<script src="{{ asset('assets/plugins/c3-master/c3.min.js') }}"></script>
<script src="{{ asset('assets/js/validation.js') }}"></script>

<script src="{{ asset('assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/plugins/sweetalert/jquery.sweet-alert.custom.js') }}"></script>
<script src="{{ asset('assets/plugins/toast-master/js/jquery.toast.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/datatables.bootstrap4.min.js') }}"></script>
<script src="{{asset('assets/js/datatable-config.js')}}"></script>
<script src="{{asset('assets/plugins/jqueryui/jquery-ui.min.js')}}"></script>
<!-- js menu -->
<script>
    $(function() {
        var parentMenu = $('#sidebarnav').find('a')
        for (let i = 0; i < parentMenu.length; i++) {
            if ($(parentMenu[i]).next().length > 0) {
                $(parentMenu[i]).addClass('has-arrow')
            }
        }
    })
</script>
