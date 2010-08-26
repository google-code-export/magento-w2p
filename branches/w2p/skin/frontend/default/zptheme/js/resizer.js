var globID = null; //last resized object
var handle_path = '/magedev/skin/frontend/default/zptheme/images/handle.gif';

jQuery.fn.resizer = function($){
  return this.each(function(){
    var me = $(this);
    var id = me.attr('id');
    //me.css('height',45);
    var h = parseInt(me.css('height'));
    // create container
    me = me.wrap('<div id="wrap_'+id+'" style="position:relative; width: 100%; height:'+h+'px;"></div>');
    var div = $('#wrap_'+id); // get container
    // add a handle
    me.after(
      $('<div style="cursor:sw-resize; position:relative; background-image:url('+handle_path+'); width:10px; height:10px; top:-10px; -moz-user-select:none; -webkit-user-select:none; user-select:none;" unselectable="on"></div>').bind('mousedown', function(e) {
        // onMouseDown
        div.css('z-index',1000);
        globID = me.attr('id');
        var _width = parseInt(me.css('width'));
        var height = parseInt(me.css('height'));
        var x = e.pageX;
        var y = e.pageY;
        var mLeft = parseInt(div.css("margin-left"));
	// onMouseMove
        var moveHandler = function(e){
          if(me.attr('tagName').toUpperCase()=='TEXTAREA')
            me.css('height',Math.max(h,(e.pageY + height - y)));
	  if(e.pageX - x + mLeft <0){
            me.css('width', _width + x - e.pageX);
            div.css("margin-left", e.pageX - x + mLeft);
	  }
        };
	// onMouseUp
        var upHandler = function(e){ $(document).unbind('mousemove',moveHandler).unbind('mouseup',upHandler); me.focus();};
	// bind listeners
        $(document).bind('mousemove', moveHandler).bind('mouseup', upHandler);
      })
    );
    me.focus(function () {
      globID = null;
    });
    // reset object on blur
    me.blur(function () {
      if(me.attr('id')!=globID){
        if(me.attr('tagName').toUpperCase()=='TEXTAREA')
          me.css('height','');
        me.css('width','');
        div.css("margin-left", '');
        div.css('z-index','');
      }
    });
    div.after('<div style="display:block; width:100%; height:3px;"></div>');
  });
}
jQuery(document).ready(function($) {
  jQuery("dd textarea").resizer($);
  jQuery("dd input:text").resizer($);
});
