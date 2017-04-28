<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.2.1/jquery.form.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        var options_signin = {
            target:        '.alert-response',
        };
        var options_signup = {
            target:        '.register-response',
        };
        $('#form-signin').ajaxForm(options_signin);
        $('#form-signup').ajaxForm(options_signup);
    });
</script>