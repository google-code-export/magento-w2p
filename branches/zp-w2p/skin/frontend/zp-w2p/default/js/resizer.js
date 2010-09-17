var glob_id = null; //last resized object
var glob_x = -1;

jQuery.fn.resizer = function($){
  return this.each(function(){
    var me = $(this);
    var id = me.attr('id');
    if(me.attr('tagName').toUpperCase()=='TEXTAREA'){
      me.css('resize','none');
      me.css('outline-style','none');
    }
    var h = parseInt(me.css('height'));
    // create container
    me = me.wrap('<div id="wrap_'+id+'" style="position:relative; width: 100%; height:'+h+'px;"></div>');
    var div = $('#wrap_'+id); // get container
    // add a handle
    me.after(
      $('<div class="zp-resize-handle" id="handle_'+id+'" unselectable="on"></div>').bind('mousedown', function(e) {
	tmp_val = me.val();
	me.val(tmp_val);
        // onMouseDown
        $('body').css('-webkit-user-select','none');
        div.css('z-index',1000);
        glob_id = me.attr('id');
        var _width = parseInt(me.css('width'));
        var height = parseInt(me.css('height'));
        var x = e.pageX;
        var y = e.pageY;
        var mLeft = parseInt(div.css("margin-left"));
	if(glob_x == -1) glob_x = x;
	if(glob_x > x) glob_x = x;

	// onMouseMove
        var moveHandler = function(e){
          if(me.attr('tagName').toUpperCase()=='TEXTAREA')
            me.css('height',Math.max(h,(e.pageY + height - y)));
          if($(document.getElementById('handle_'+id+'')).css('left')=='-4px'){
            if( e.pageX >= glob_x)
              div.css('width', _width + e.pageX - x);
          }else{
            if(e.pageX - x + mLeft <0){
              me.css('width', _width + x - e.pageX);
              div.css("margin-left", e.pageX - x + mLeft);
	    }
          }
        };
	// onMouseUp
        var upHandler = function(e){
          $(document).unbind('mousemove',moveHandler).unbind('mouseup',upHandler);
          me.focus();
          $('body').css('-webkit-user-select','auto');
        };
	// bind listeners
        $(document).bind('mousemove', moveHandler).bind('mouseup', upHandler);
      })
    );
    me.focus(function () {
      glob_id = null;
    });
    // reset object on blur
    me.blur(function () {
      if(me.attr('id')!=glob_id){
        if(me.attr('tagName').toUpperCase()=='TEXTAREA')
          me.css('height','');
        me.css('width','');
        div.css('width','');
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
