jQuery(document).ready(function () {
    jQuery('.oldurl_validate').hide();
    let oldurlError = true;
    jQuery('#ocsr_oldurl').change(function () 
    {
        validate_old_url();
    });
    jQuery('.newurl_validate').hide();
    let newurlError = true;
    jQuery('#ocsr_newurl').change(function () 
    {
        validate_new_url();
    });
    function validate_old_url() 
    {
        let oldurl = jQuery('#ocsr_oldurl').val();

        if (oldurl.length == '') 
        {
            jQuery('.oldurl_validate').show();
            jQuery("#ocsr_oldurl").css("border","1px solid #b74134");
            oldurlError = false;
            return false;
        }
        else 
        {
            jQuery('.oldurl_validate').hide();
            jQuery("#ocsr_oldurl").css("border","1px solid #54b948");
            return true;
        }
    } 

    function validate_new_url() 
    {

        let newurl = jQuery('#ocsr_newurl').val();

        if (newurl.length == '') 
        {
            jQuery('.newurl_validate').show();
            jQuery("#ocsr_newurl").css("border","1px solid #b74134");
            oldurlError = false;
            return false;
        }
        else 
        {
            jQuery('.newurl_validate').hide();
            jQuery("#ocsr_newurl").css("border","1px solid #54b948");
            return true;
        }
    } 

    function validate_checkbox(){
        if (jQuery('input:checkbox').filter(':checked').length < 1){
        alert("Please Check at least one Check Box");
        return false;
    }
    else{
        return true;
    }
}


    jQuery('#variations_form').on( 'submit', function(e)
    {
        var oldurl = validate_old_url();
        var newurl = validate_new_url();
        var checkurl = validate_checkbox();
        if(!oldurl || !newurl || !checkurl){
            return false;
        }     

    });


});