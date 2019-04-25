$("#btnLogin").click(function(event) {
    var form = $("#loginForm")

    event.preventDefault()
    event.stopPropagation()
    form.addClass('was-validated');

    if (form[0].checkValidity()) {
        $.ajax({
            type    : "POST",
            url     : "login.php",
            data    : "action=login&u=" + $("#usr").val() + "&p=" + $("#pwd").val(),
            success : function(text){
                if (text == "success") {
                    location.reload(true);
                }
                else {
                    window.alert("Invalid username and/or password.");
                }
            }
        });
    }
});

$('#btnLogout').click(function(event) {
    $.ajax({
        type    : "POST",
        url     : "login.php",
        data    : "action=logout",
        success : function(text){
            if (text == "success") {
                location.reload(true);
            }
        }
    });
});
