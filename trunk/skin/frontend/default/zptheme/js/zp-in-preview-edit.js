function precalculate_shapes (shapes, preview_dimensions) {
  for (var page in shapes)
    for (var shape in shapes[page]) {
      shapes[page][shape]._x1 = shapes[page][shape].x1;
      shapes[page][shape].x1 = preview_dimensions[page].width * shapes[page][shape]._x1;

      shapes[page][shape]._y1 = shapes[page][shape].y1;
      shapes[page][shape].y1 = preview_dimensions[page].height * shapes[page][shape]._y1;

      shapes[page][shape]._x2 = shapes[page][shape].x2
      shapes[page][shape].x2 = preview_dimensions[page].width * shapes[page][shape]._x2;

      shapes[page][shape]._y2 = shapes[page][shape].y2;
      shapes[page][shape].y2 = preview_dimensions[page].height * shapes[page][shape]._y2;
    }
}

function get_preview_dimensions (number_of_pages) {
  var dimensions = new Array(number_of_pages);

  for (var page = 1; page <= number_of_pages; page++) {
    var image = jQuery('a#preview-image-page-' + page + ' img')[0];

    dimensions[page] = {
      width: jQuery(image).width(),
      height: jQuery(image).height() };
  }

  return dimensions;
}

function place_shape (shape, container, shape_handler) {
  if (shape.edited)
    var edited_class = ' edited';
  else
    var edited_class = '';

  jQuery('<div class="zetaprints-field-shape bottom hide' + edited_class + '" rel="' + shape.name  +
    '"><div class="zetaprints-field-shape top" /></div>')
    .css({
      top: shape.top,
      left: shape.left,
      width: shape.width,
      height: shape.height })
    .bind('click mouseover mouseout', shape_handler)
    .appendTo(container);
}

function place_all_precalculated_shapes_for_page (page, shapes, container, shape_handler) {
  if (shapes[page])
    for (name in shapes[page])
      place_shape({
        left: shapes[page][name].x1,
        top: shapes[page][name].y1,
        width: shapes[page][name].x2 - shapes[page][name].x1,
        height: shapes[page][name].y2 - shapes[page][name].y1,
        name: name,
        edited: shapes[page][name].edited}, container, shape_handler);
}

function place_all_shapes_for_page (page, shapes, image_dimension, container, shape_handler) {
  if (shapes[page])
    for (name in shapes[page]) {
      var left =  shapes[page][name]._x1 * image_dimension.width;
      var top = shapes[page][name]._y1 * image_dimension.height;

      place_shape({
        left: left,
        top: top,
        width: shapes[page][name]._x2 * image_dimension.width - left,
        height: shapes[page][name]._y2 * image_dimension.height - top,
        name: name,
        edited: shapes[page][name].edited }, container, shape_handler);
    }
}

function remove_all_shapes (container) {
  jQuery('div.zetaprints-field-shape', container).remove();
}

function highlight_shape_by_name (name, container) {
  jQuery('div.zetaprints-field-shape[rel=' + name +']', container).addClass('highlighted');
}

function dehighlight_shape_by_name (name, container) {
  jQuery('div.zetaprints-field-shape[rel=' + name +']', container).removeClass('highlighted');
}

function highlight_field_by_name (name) {
  jQuery(':input[name=zetaprints-_'+ name +'], div.zetaprints-images-selector[rel=zetaprints-#' + name + '] div.head').addClass('highlighted');
}

function dehighlight_field_by_name (name) {
  jQuery(':input[name=zetaprints-_'+ name +'], div.zetaprints-images-selector[rel=zetaprints-#' + name + '] div.head').removeClass('highlighted');
}

function popup_field_by_name (name, position) {
  var shape = jQuery('div.zetaprints-field-shape[rel=' + name + ']', jQuery('div#fancybox-inner'))[0];
  var field = jQuery(':input[name=zetaprints-_'+ name +']');

  if (field.length) {
    field = field[0];
    var full_name = 'zetaprints-_'+ name;

    jQuery(field).css({
      borderWidth: '0px' });

    var width = jQuery(shape).outerWidth();
    if (width <= 150)
      width = 150;
  } else {
    field = jQuery('div.zetaprints-images-selector[rel=zetaprints-#' + name + '] div.selector-content');

    if (!field.length)
      return;

    field = field[0];

    var parent = jQuery(field).parents('div.zetaprints-images-selector');
    if (jQuery(parent).hasClass('expanded'))
      jQuery('a.collapse-expand', parent).click();

    var full_name = 'zetaprints-#' + name;

    var width = 400;
  }

  jQuery('<input type="hidden" name="field" value="' + full_name + '" />').appendTo(shape);

  var box = jQuery(field).wrap('<div class="field" />').parent().wrap('<div class="fieldbox-wrapper" />')
    .parent().prepend('<div class="fieldbox-head"><a href="#" rel="' + full_name + '" /><span>' + name + ':</span></div>')
    .wrap('<div class="fieldbox" />').parent().css({
    zIndex: '10000',
    position: 'absolute',
    width: width });

  var height = jQuery(box).outerHeight();
  var width = jQuery(box).outerWidth();

  if (!position) {
    position = jQuery(shape).offset();
    position.top += jQuery(shape).outerHeight() - 10;
    position.left += 10;
  }

  var window_height = jQuery(window).height() + jQuery(window).scrollTop();
  if ((position.top + height) > window_height)
    position.top -= position.top + height - window_height;

  var window_width = jQuery(window).width();
  if ((position.left + width) > window_width)
    position.left -= position.left + width - window_width;

  jQuery(box).css({
    visibility: 'visible',
    left: position.left,
    top: position.top }).draggable({ handle: 'div.fieldbox-head' });

  jQuery(field).focus();
}

function popdown_field_by_name (name) {
  if (name)
    var field = jQuery(':input[value~='+ name +']', jQuery('div#fancybox-inner'));
  else
    var field = jQuery(':input', jQuery('div#fancybox-inner'));

  if (!field.length)
    return;

  if (!name)
    name = jQuery(field).attr('value').substring(12);

  full_name = jQuery(field).attr('value');

  var element = jQuery('div.zetaprints-page-input-fields :input[name=' + full_name + '], div.zetaprints-images-selector[rel=' + full_name + '] div.selector-content')
  jQuery(element).removeAttr('style').unwrap().prev().remove();
  jQuery(element).unwrap().unwrap();

  jQuery(field).remove();

  return name;
}

function mark_shape_as_edited (name, shapes, current_page) {
  jQuery('div.zetaprints-field-shape[rel=' + name + ']').addClass('edited');

  shapes[current_page][name].edited = true;
}

function unmark_shape_as_edited (name, shapes, current_page) {
  jQuery('div.zetaprints-field-shape[rel=' + name + ']').removeClass('edited');

  shapes[current_page][name].edited = false;
}

function mark_shapes_as_edited (shapes) {
  var fields = jQuery('div.zetaprints-page-input-fields, div.zetaprints-page-stock-images');

  for (var page in shapes)
    for (var name in shapes[page]) {
      var field = jQuery('input[name=zetaprints-_' + name + ']:text, '
        + 'textarea[name=zetaprints-_' + name + '], '
        + 'select[name=zetaprints-_' + name + '], '
        + 'input[name=zetaprints-_' + name + ']:checked, '
        + 'input[name=zetaprints-#' + name + ']:checked', fields);

      if (field.length == 1 && field[0].value) {
        shapes[page][name].edited = true;
        continue;
      }
    }
}

function get_current_shapes_container () {
  var container = jQuery('div#fancybox-inner:visible');
  if (container.length)
    return container[0];

  return jQuery('div.product-img-box');
}

function shape_handler (event) {
  var shape = jQuery(event.target).parent();
  if (event.type == 'click') {
    current_field_name = jQuery(shape).attr('rel');
    jQuery('#preview-image-page-' + current_page, jQuery(shape).parent()).click();
  } else if (event.type == 'mouseover') {
    jQuery('#zetaprints-preview-image-container > div.zetaprints-field-shape.bottom')
      .removeClass('highlighted');
    jQuery(shape).addClass('highlighted');

      highlight_field_by_name (jQuery(shape).attr('rel'));
    } else {
      jQuery(shape).removeClass('highlighted');

      dehighlight_field_by_name (jQuery(shape).attr('rel'));
    }
}

function fancy_shape_handler (event) {
  var shape = jQuery(event.target).parent();

  if (event.type == 'click') {
    if (jQuery(shape).children().length > 1)
      return false;

    jQuery('div#fancybox-inner div.zetaprints-field-shape.highlighted[rel!=' + jQuery(shape).attr('rel') + ']').removeClass('highlighted');
    popdown_field_by_name();
    popup_field_by_name(jQuery(shape).attr('rel'), { top: event.pageY, left: event.pageX });

    return false;
  }

  if (event.type == 'mouseover') {
    var highlighted = jQuery('div#fancybox-inner > div.zetaprints-field-shape.highlighted');
    if (jQuery(highlighted).children().length <= 1)
      jQuery(highlighted).removeClass('highlighted');

    jQuery(shape).addClass('highlighted');
  } else
    if (jQuery(shape).children().length <= 1)
      jQuery(shape).removeClass('highlighted');
}

function add_in_preview_edit_handlers () {
  jQuery('div.zetaprints-page-input-fields input, div.zetaprints-page-input-fields textarea, div.zetaprints-page-input-fields select').mouseover(function() {
    highlight_shape_by_name(jQuery(this).attr('name').substring(12), get_current_shapes_container());
  }).mouseout(function() {
    dehighlight_shape_by_name(jQuery(this).attr('name').substring(12), get_current_shapes_container());
  });

  jQuery('div.zetaprints-images-selector').mouseover(function () {
    highlight_shape_by_name(jQuery(this).attr('rel').substring(12), get_current_shapes_container());
  }).mouseout(function () {
    if (!jQuery(this).children('div.fieldbox').length)
      dehighlight_shape_by_name(jQuery(this).attr('rel').substring(12), get_current_shapes_container());
  });

  jQuery('img#fancybox-img').live('click', function () {
    jQuery('div.zetaprints-field-shape.bottom', jQuery('div#fancybox-inner')).removeClass('highlighted');
    popdown_field_by_name();
  });

  jQuery('div.fieldbox-head a').live('click', function () {
    popdown_field_by_name(jQuery(this).attr('rel'));
    dehighlight_shape_by_name(jQuery(this).attr('rel').substring(12), get_current_shapes_container());
    return false;
  });

  var fancybox_center_function = jQuery.fancybox.center;
  jQuery.fancybox.center = function () {
    var orig_position = jQuery('div#fancybox-wrap').position();

    fancybox_center_function();

    var new_position = jQuery('div#fancybox-wrap').position();

    if (orig_position.top != new_position.top
      || orig_position.left != new_position.left)
      popup_field_by_name(popdown_field_by_name());
  }
}
