$(document).ready(function() {
    // Styled Select Box
    $('.styledselect_pages_1').selectbox({inputClass: "styledselect_form_1"});
	
    // UI Checkboxes
    //$('input').checkBox();
    $('#toggle-all').click(function(){
        $('#toggle-all').toggleClass('toggle-checked');
        $('#mainform input[type=checkbox]').checkBox('toggle');
        return false;
    });
    
    // Exception Info Expander
    $("a#exceptiondetails").click(function() {
        var exceptionid = $(this).parent().parent().attr("exceptionid");
        $("div[class='exceptiondetails']").hide();
        $(".exceptiondetailscontainer").show();
        $("div[class='exceptiondetails'][exceptionid='"+exceptionid+"']").show();
        $.scrollTo( $(".exceptiondetailscontainer"), 800 );
    });
	
    // Tab Switching
    $("#tabs, #graphs").tabs();

    // Select all checkboxes
    $("#checkboxall").click(function() {
        var checked_status = this.checked;
        $("input[name=checkall]").each(function() {this.checked = checked_status;});
    });
	
    // Action Slider
    $(".action-slider").click(function () {
        $("#actions-box-slider").slideToggle("fast");
        $(this).toggleClass("activated");
        return false;
    });
	
    // Data Tables
    $('.datatable').dataTable({"sPaginationType": "full_numbers"});

    // Validate input
    $("input#validate-text").keyup(function () {
        if ($(this).val() == "") {
            $(this).removeClass("inp-form"); 
            $(this).addClass("inp-form-error");

            $(this).parent().parent().find("td#error").html("<div class='error-left'></div><div class='error-inner'>This field is required.</div>");
        } else {
            $(this).addClass("inp-form"); 
            $(this).removeClass("inp-form-error");

            $(this).parent().parent().find("td#error").html("");
        }
    });
    
    // Settings
    var refreshSettings = function() { 
        $('tr#recaptcha-settings').each(function() {
            if ($('input[name=recaptcha]:checked').val() == 'true')
                $(this).show(); 
            else
                $(this).hide();
        });
        
        $('tr#mail-smtp').each(function() {
            if ($('input[name=protocol]:checked').val() == 'smtp')
                $(this).show(); 
            else
                $(this).hide();
        });
        
        $('tr#mail-sendmail').each(function() {
            if ($('input[name=protocol]:checked').val() == 'sendmail')
                $(this).show(); 
            else
                $(this).hide();
        });
        
        if ($('input[name=geoips-service]:checked').val() == 'api')
            $('#geoips-api').show(); 
        else
            $('#geoips-api').hide();
        
        if ($('input[name=geoips-service]:checked').val() == 'database')
            $('#geoips-database').show(); 
        else
            $('#geoips-database').hide();
    }
    
    refreshSettings();
    
    $('input[name=recaptcha]').click( function() { refreshSettings() } );
    $('input[name=protocol]').click( function() { refreshSettings() } );
    $('input[name=geoips-service]').click( function() { refreshSettings() } );

    // Integration
    var appName;
    var programmingLang;
    var installer;

    var refreshIntegration = function () {
        $("div#integration div").each(function() {$(this).hide();});
        $("div#"+programmingLang).show();
        $("div#"+installer).show();
    }

    $("input#appname").keyup(function () {
        appName = $(this).val(); 
        if (appName == "") { 
            $("input#appname").removeClass("inp-form"); 
            $("input#appname").addClass("inp-form-error");
            $("tr#appname td#error").html("<div class='error-left'></div><div class='error-inner'>This field is required.</div>");
        } else {
            $("input#appname").addClass("inp-form"); 
            $("input#appname").removeClass("inp-form-error");
            $("tr#appname td#error").html("");
        }
    });
    
    $("div#language_container ul").click(function () {programmingLang = $("#language_container ul li.selected2").attr('title');refreshIntegration();});
    $("div#installer_container ul").click(function () {installer = $("#installer_container ul li.selected2").attr('title');refreshIntegration();});
});
