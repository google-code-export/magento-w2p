jQuery.fn.vchecks = function() {
	var $ = jQuery;
	object = jQuery(this);
	object.addClass('geogoer_vchecks');
	object.find("li:first").addClass('first');
	object.find("li:last").addClass('last');
	//removing checkboxes
	object.find("input[type=checkbox]").each(function(){
		$(this).hide();
		$(this).change(function () {
			check_li = $(this).parent('li')
			if ($(this).attr('checked') == true) {
			    check_li.removeClass('unchecked');
                            check_li.addClass('checked');
			} else {
                            check_li.removeClass('checked');
                            check_li.addClass('unchecked');
                        }
		});
	});
	//adding images true false
	object.find("li").each(function(){
                ch_val = $(this).find("input[type=checkbox]").val();
		if(ch_val == 'on' || ch_val == ''){
			$(this).addClass('unchecked');
			$(this).append('<div class="check_div"></div>');
		}
		else{
                  $(this).addClass('checked');
                  $(this).append('<div class="check_div"></div>');
		}
	});
	//binding onClick function
	object.find("li").find('span').click(function(e) {
          $(this).parent('li').find("div.color-sample").click();
        });
        object.find("li").find('div.check_div').click(function(e) {
          e.preventDefault();
          check_li = $(this).parent('li');
          checkbox = $(this).parent('li').find("input[type=checkbox]");
          //if(checkbox.attr('checked') == true) {
          //  checkbox.attr('checked',false).removeAttr('value');
	    checkbox.val('');
            check_li.find('div.color-sample').removeAttr('style');
            check_li.removeClass('checked');
            check_li.addClass('unchecked');
	  //}
	});
	//mouse over / out
	//simple
	object.find("li:not(:last,:first)").find('span').bind('mouseover', function(e){
		$(this).parent('li').addClass('hover');
	});
	object.find("li:not(:last,:first)").find('span').bind('mouseout', function(e){
		$(this).parent('li').removeClass('hover');
	});
	//first
	object.find("li:first").find('span').bind('mouseover', function(e){
		$(this).parent('li').addClass('first_hover');
	});
	object.find("li:first").find('span').bind('mouseout', function(e){
		$(this).parent('li').removeClass('first_hover');
	});
	//last
	object.find("li:last").find('span').bind('mouseover', function(e){
		$(this).parent('li').addClass('last_hover');
	});
	object.find("li:last").find('span').bind('mouseout', function(e){
		$(this).parent('li').removeClass('last_hover');
	});
}