(function ($) {

window.zp_dataset_initialise = function (zp) {
  var $dataset = $('.zp-dataset');

  $dataset
    .find('.zp-dataset-checkbox')
    .hover(
      function () {
        $(this)
          .parent()
          .addClass('zp-dataset-active');
      },
      function () {
        $(this)
          .parent()
          .removeClass('zp-dataset-active');
      });

  var $td = $dataset
              .find('td')
              .filter(':not(.zp-dataset-checkbox)');

  $td
    .mouseenter(function (event) {
      $popup = $(this).children('.zp-dataset-popup');

      if (!$popup.length)
        $popup = $('<div class="zp-dataset-popup" />')
                     .append($(this)
                               .children()
                               .clone())
                     .appendTo(this);

      $popup
        .detach()
        .appendTo($('body'))
        .attr('id', 'zp-dataset-popup-active')
        .css({
          top: event.pageY + 15,
          left: event.pageX + 15 })
        .show();
    })
    .mouseleave(function (event) {
      $('#zp-dataset-popup-active')
        .hide()
        .removeAttr('id')
        .detach()
        .appendTo($(this));
    })
    .mousemove(function (event) {
      $('#zp-dataset-popup-active')
        .css({
          top: event.pageY + 15,
          left: event.pageX + 15 });
    })
    .click(function () {
      var $this = $(this);

      if (zp.template_details['dataset-integrity-enforce']) {
        $this
          .parent()
          .find('> .zp-dataset-checkbox > input')
          .mousedown()
          .click();

        return;
      }

      var page = zp.template_details.pages[zp.current_page];
      var name = $this.attr('class');

      if (!(page.fields && page.fields[name] && page.fields[name].dataset))
        return false;

      var $tr = $this.parent();
      var $tbody = $tr.parent();

      $tbody
        .children('.zp-dataset-selected')
        .removeClass('zp-dataset-selected')
        .find('input')
          .prop('checked', false)
        .end()
        .children()
        .slice(1)
        .addClass('zp-dataset-selected');

      var index = $tbody
                    .children()
                    .index($tr);

      $('#input-fields-page-' + zp.current_page)
        .find('[name="zetaprints-_' + name + '"]')
        .val(page.fields[name].dataset[index].text);

      $tbody
        .find('td')
        .filter('.' + name.replace(/ /g, '.'))
        .removeClass('zp-dataset-selected');

      $('#product_addtocart_form').removeClass('zp-not-modified');

      $this.addClass('zp-dataset-selected');
    });

  $inputs = $dataset.find('input');

  $inputs
    .mousedown(function () {
      $inputs
        .filter(':checked')
        .prop('checked', false);
    })
    .click(function () {
      var page = zp.template_details.pages[zp.current_page];

      if (!page.fields)
        return;

      var $tr = $(this)
                  .parent()
                  .parent();

      $tr
        .parent()
        .find('.zp-dataset-selected')
        .removeClass('zp-dataset-selected');

      var index = $tr
                    .parent()
                    .children()
                    .index($tr);

      var $fields = $('#input-fields-page-' + zp.current_page);

      for (var name in page.fields)
        if (page.fields[name].dataset) {
          $fields
            .find('[name="zetaprints-_' + name + '"]')
            .val(page.fields[name].dataset[index].text);
        }

      $('#product_addtocart_form').removeClass('zp-not-modified');

      $tr.addClass('zp-dataset-selected');
    });

  $('#zp-dataset-button').click(function () {
    $.fancybox({
      'type': 'inline',
      'href': '#zp-dataset-page-' + zp.current_page
    });
  });
}

window.zp_dataset_update_state = function (zp, name, state) {
  $table = $('#zp-dataset-table-page-' + zp.current_page);

  $table
    .find('tr.zp-dataset-selected')
    .removeClass('zp-dataset-selected')
    .find('input')
      .prop('checked', false)
    .end()
    .children()
    .slice(1)
    .addClass('zp-dataset-selected');

  name = '.' + name.replace(/ /g, '.') + '.zp-dataset-selected';

  $table
    .find(name)
    .removeClass('zp-dataset-selected');
}

})(jQuery);
