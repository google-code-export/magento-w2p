var zetaprints_resized_text_field_id = null; //last resized object
var zetaprints_text_field_resizer_x = -1;

(function ($) {

$.fn.text_field_resizer = function () {
  return this.each(function () {
    var me = $(this);
    var id = me.attr('id');

    if(me.attr('tagName').toUpperCase() == 'TEXTAREA') {
      me.css('resize','none');
      me.css('outline-style','none');
    }

    var h = parseInt(me.css('height'));

    //Create container
    me = me.wrap('<div id="wrap_'+id+'" style="position:relative; width: 100%; height:'+h+'px;"></div>');

    var div = $('#wrap_'+id); // get container

    //Add a handle
    me.after($('<div class="zp-resize-handle" id="handle_'
                 + id + '" unselectable="on"></div>')
      .bind('mousedown', function(e) {
        tmp_val = me.val();
        me.val(tmp_val);

        // onMouseDown
        $('body').css('-webkit-user-select','none');

        div.css('z-index',1000);

        zetaprints_resized_text_field_id = me.attr('id');

        var _width = parseInt(me.css('width'));
        var height = parseInt(me.css('height'));

        var x = e.pageX;
        var y = e.pageY;

        var mLeft = parseInt(div.css("margin-left"));

        if (zetaprints_text_field_resizer_x == -1)
          zetaprints_text_field_resizer_x = x;

        if (zetaprints_text_field_resizer_x > x)
          zetaprints_text_field_resizer_x = x;

        //onMouseMove
        var moveHandler = function (e) {
          if (me.attr('tagName').toUpperCase() == 'TEXTAREA')
            me.css('height' ,Math.max(h, (e.pageY + height - y)));

          if ($(document.getElementById('handle_'+id+'')).css('left') == '-4px') {
            if (e.pageX >= zetaprints_text_field_resizer_x)
              div.css('width', _width + e.pageX - x);
          } else
            if (e.pageX - x + mLeft <0) {
              me.css('width', _width + x - e.pageX);
              div.css("margin-left", e.pageX - x + mLeft);
            }
        };

        //onMouseUp
        var upHandler = function (e) {
          $(document).unbind('mousemove', moveHandler)
            .unbind('mouseup', upHandler);

          me.focus();

          $('body').css('-webkit-user-select','auto'); };

        //Bind listeners
        $(document).bind('mousemove', moveHandler).bind('mouseup', upHandler);
      }) );

    me.focus(function () {
      zetaprints_resized_text_field_id = null });

    //Reset object on blur
    me.blur(function () {
      if (me.attr('id') != zetaprints_resized_text_field_id) {
        if (me.attr('tagName').toUpperCase() == 'TEXTAREA')
          me.css('height','');

        me.css('width','');

        div.css('width','');
        div.css("margin-left", '');
        div.css('z-index','');
      } });

    div.after('<div style="display:block; width:100%; height:3px;"></div>');
  });
}

})(jQuery);
