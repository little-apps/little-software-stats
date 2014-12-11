$(document).ready(function () {

    // Dropdown slider script
    $(".showhide-applications").click(function (e) {
        $(".applications-content").slideToggle("fast");
        $(this).toggleClass("active");
        return false;
    });
    
    // Duplicate reCaptcha
    $('#forgot-captcha').html($('#login-captcha').clone(true,true));

    // Show login or forgot password screen
    $(".forgot-pwd").click(function () {
            $("#loginbox").hide();
            $("#forgotbox").show();
            return false;
    });

    $(".back-login").click(function () {
            $("#loginbox").show();
            $("#forgotbox").hide();
            return false;
    });

    // Close/Open sliders by clicking elsewhere on page
    $(document).bind("click", function (e) {
        if (e.target.id != $(".showhide-applications").attr("class")) $(".applications-content").slideUp();
    });
 
    // Dynamic year stamp for footer
    $('#spanYear').html(new Date().getFullYear());
    
    // styled select box script version 1
    if (!$('html').hasClass('ie7'))
        $('.styledselect').selectbox({ inputClass: "selectbox_styled" });

    // styled select box script version 2
    $('.styledselect_form_1').selectbox({ inputClass: "styledselect_form_1" });
    $('.styledselect_form_2').selectbox({ inputClass: "styledselect_form_2" });

    // styled select box script version 3
    $('.styledselect_pages').selectbox({ inputClass: "styledselect_pages" });

    // tooltip
    $('a.info-tooltip ').tooltip({
        track: true,
        delay: 0,
        fixPNG: true, 
        showURL: false,
        showBody: " - ",
        top: -35,
        left: 5
    });

    // Navigation scripts
    refreshUrl = function () {
        if (rewriteEnabled)
            url = baseUrl + "/"+id+"/"+ver+"/"+graphBy+"/"+page+"/"+start+"/"+end;
        else
            url = baseUrl + "/index.php?id="+id+"&ver="+ver+"&graphBy="+graphBy+"&page="+page;

        window.location.href = url;
    }
    
    if (!appExists)
        $("#invalididbox").bPopup();
        
    $("div#graphBy_container ul").click(function () {
        graphBy = $("#graphBy_container ul li.selected2").attr('title');
        refreshUrl();
        return false;
    });
        
    $("div#versions_container ul").click(function () { console.log('ver');
        ver = $("#versions_container ul li.selected2").attr('title'); 
        refreshUrl();
        return false;
    });

    // Message Box Fading Scripts
    $(".close-yellow").click(function () {
        $("#message-yellow").fadeOut("slow");
    });
    $(".close-red").click(function () {
        $("#message-red").fadeOut("slow");
    });
    $(".close-blue").click(function () {
        $("#message-blue").fadeOut("slow");
    });
    $(".close-green").click(function () {
        $("#message-green").fadeOut("slow");
    });

});
