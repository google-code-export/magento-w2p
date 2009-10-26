<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/Biinno/Api/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

class Biinno_WebToPrint_Helper_PersonalizationForm extends Mage_Core_Helper_Abstract {

  private $xml;
  private $personalizable_product = false;

  public function init ($product) {
    if ($product->isPersonalizable()) {
      $url = Mage::getStoreConfig('api/settings/w2p_url');
      $key = Mage::getStoreConfig('api/settings/w2p_key');

      zp_api_init($key, $url);

      $template_id = trim($product->getSku());
      $this->xml = zetaprints_get_template_details_as_xml($template_id);
      $this->personalizable_product = true;
    } else
      $this->personalizable_product = false;

    return $this;
  }

  private function get_form_part_html ($form_part = null) {
    if ($this->xml && $this->personalizable_product) {
      //Converting template details xml to html form
      echo zetaprints_get_personalize_form_part_html($this->xml, $form_part);
      return true;
    }

    return false;
  }

  public function get_product_image ($context, $_product) {
    if (!$_product->getData('w2p_image'))
      return false;

    $src = $context->helper('catalog/image')->init($_product, 'small_image');
    $alt = $context->htmlEscape($context->getImageLabel($_product, 'small_image'));
    $title = $context->htmlEscape($context->getImageLabel($_product, 'small_image'));

    echo "<img style=\"max-width: 135px;\" src=\"$src\" alt=\"$alt\" title=\"$title\" />";

    return true;
  }

  public function get_gallery_image ($context) {
    if (!$context->getProduct()->hasData('w2p_image'))
      return false;

    $src = $context->getImageUrl();
    $alt = $context->htmlEscape($context->getCurrentImage()->getLabel());
    $title = $context->htmlEscape($context->getCurrentImage()->getLabel());

    echo "<img src=\"$src\" alt=\"$alt\" title=\"$title\" id=\"product-gallery-image\" style=\"display:block;\" />";

    return true;
  }

  public function get_gallery_thumb ($context, $_image) {
    if (!$context->getProduct()->hasData('w2p_image'))
      return false;
    
    $gallery_url = $context->getGalleryUrl($_image);
    $src = $_image['url'];
    $alt = $context->htmlEscape($_image->getLabel());
    $title = $context->htmlEscape($_image->getLabel());

    echo "<a href=\"#\" onclick=\"popWin('$gallery_url', 'gallery', 'width=300,height=300,left=50,top=50,location=no,status=yes,scrollbars=yes,resizable=yes'); return false;\"><img src=\"$src\" alt=\"$alt\" title=\"$title\" /></a>";

    return true;
  }

  public function get_preview_images () {
    return $this->get_form_part_html('preview-images');
  }

  public function get_text_fields () {
    return $this->get_form_part_html('input-fields');
  }

  public function get_image_fields () {
    return $this->get_form_part_html('stock-images') . '\n'. $this->get_form_part_html('color-pickers');
  }

  public function get_page_tabs () {
    return $this->get_form_part_html('page-tabs');
  }

  public function get_preview_button ($context = null) {
?>
    <div class="zetaprints-preview-button">
      <input type="button" value="Update preview" class="update-preview form-button" />
      <img src="<?php echo Mage::getDesign()->getSkinUrl('images/opc-ajax-loader.gif'); ?>" class="ajax-loader"/>
      <span>Updating preview image&hellip;</span>
    </div>
<?php
  }

  public function get_js_css_includes ($context = null) {
    $design = Mage::getDesign();
?>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-1.3.2.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-ui-1.7.2.custom.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/colorpicker.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-qtip-1.0.0-rc3.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery.dd.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery.vchecks.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery.fancybox-1.2.1.pack.js'); ?>"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/colorpicker.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/dd.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/checks.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/jquery.fancybox.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/zp-style.css'); ?>" />
<?php
  }

  public function get_js ($context) {
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
  $('#preview-image-page-1').css('display', 'block');
  $('#input-fields-page-1').css('display', 'block');
  $('#stock-images-page-1').css('display', 'block');
  $('#color-pickers-page-1').css('display', 'block');

  $('div.image-tabs li:first').addClass('selected');
  $('fieldset.add-to-cart-box button.form-button').attr('onclick', null).addClass('disable');

  previews = [];
  template_id = '<?php echo $context->getProduct()->getSku(); ?>';
  number_of_pages = $('div.zetaprints-template-preview').length;

  $('<input type="hidden" name="zetaprints-TemplateID" value="' + template_id +'" />').appendTo('#product_addtocart_form');

  $('div.image-tabs li').click(function () {
    $('div.image-tabs li').removeClass('selected');

    $('div.zetaprints-template-preview:visible, div.zetaprints-page-input-fields:visible, div.zetaprints-page-stock-images:visible, zetaprints-page-color-pickers:visible').css('display', 'none');

    $(this).addClass('selected');
    var page = $('img', this).attr('rel');

    $('#preview-image-' + page + ', #input-fields-' + page + ', #stock-images-' + page + ', #color-pickers-' + page).css('display', 'block');
  });

  function save_order_request () {
    var update_preview_button = $('input.update-preview').unbind('click').addClass('disable');
    $('div.save-order img.ajax-loader').css('display', 'inline');

    $.ajax({
      url: '<?php echo $context->getUrl('web-to-print/order'); ?>',
      type: 'POST',
      data: 'id=<?php echo $context->getProduct()->getId(); ?>&zetaprints-TemplateID=' + template_id + '&zetaprints-Previews=' + previews.join(','),
      dataType: 'text',
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        $(update_preview_button).bind('click', update_preview).removeClass('disable');
        $('div.save-order img.ajax-loader').css('display', 'none');
        alert('Can\'t prepare product for ordering: ' + textStatus); },
      success: function (data, textStatus) {
        if (parseInt(data) > 0) {
          var url_array = $('#product_addtocart_form').attr('action').split('/');
          url_array[url_array.length - 2] = data;
          $('#product_addtocart_form').attr('action', url_array.join('/'));
          productAddToCartForm.submit();
        } else {
          alert('Can\'t prepare product for ordering: ' + textStatus);
        } }
    } );
  };

  function update_preview () {
    $('div.zetaprints-preview-button span').css('display', 'inline');
    $('img.ajax-loader').css('display', 'inline');

    var update_preview_button = $('input.update-preview').unbind('click').hide();
    var page = '' + $('div.image-tabs li.selected img').attr('rel');

    if (page == "undefined")
      page = 'page-1';

    $.ajax({
      url: '<?php echo $context->getUrl('web-to-print/preview'); ?>',
      type: 'POST',
      data: $('#product_addtocart_form').serialize() + '&zetaprints-From=' + page.split('-')[1],
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        $('div.zetaprints-preview-button span').css('display', 'none');
        $('img.ajax-loader').css('display', 'none');
        $(update_preview_button).bind('click', update_preview).show();
        alert('Can\'t get preview image: ' + textStatus); },
      success: function (data, textStatus) {
        $('#preview-image-' + page + ' a').attr('href', data);
        $('#preview-image-' + page + ' img').attr('src', data);

        var image_name = data.split('/preview/')[1]
        previews[parseInt(page.split('-')[1]) - 1] = image_name;

        var thumb_url = data.split('/preview/')[0] + '/thumb/' + image_name.split('.')[0] + '_100x100.' + image_name.split('.')[1];
        $('div.image-tabs img[rel=' + page + ']').attr('src', thumb_url);

        if (previews.length == number_of_pages) {
          $('fieldset.add-to-cart-box button.form-button').bind('click', save_order_request).removeClass('disable');
          $('div.save-order span').css('display', 'none');
        }

        $('div.zetaprints-preview-button span').css('display', 'none');
        $('img.ajax-loader').css('display', 'none');
        $(update_preview_button).bind('click', update_preview).show(); }
    });
  }

  $('input.update-preview').click(update_preview);

  $('div.zetaprints-page-color-pickers ul.colors-selector li').each(function () {
    var color_sample = $('div.color-sample', this);
    var color_input = $('input.color', this);

    $(color_sample).ColorPicker({
      color: '#804080',
      onBeforeShow: function (colpkr) {
        $(colpkr).draggable();
        return false;
      },
      onShow: function (colpkr) {
        $(colpkr).fadeIn(500);
        return false;
      },
      onHide: function (colpkr) {
        $(colpkr).fadeOut(500);
        return false;
      },
      onSubmit: function (hsb, hex, rgb, el) {
        $(color_sample).css('backgroundColor', '#' + hex);
        $(color_input).val('#' + hex).attr('checked', true).change();
        $(el).ColorPickerHide();
      }
    });
  });

  $('div.zetaprints-template-preview a').fancybox({
    'zoomOpacity': true,
    'overlayShow': false,
    'centerOnScroll': false,
    'zoomSpeedChange': 200,
    'zoomSpeedIn': 500,
    'zoomSpeedOut' : 500,
    'callbackOnShow': function () { $('img#fancy_img').attr('title', 'Click to close'); } });

  $('div.zetaprints-page-input-fields input[title], div.zetaprints-page-input-fields textarea[title]').qtip({
    position: { corner: { target: 'bottomLeft' } },
        show: { delay: 1, solo: true, when: { event: 'focus' } },
        hide: { when: { event: 'unfocus' } }
  });

  $('div.zetaprints-page-stock-images select[title]').qtip({
    position: { corner: { target: 'topLeft' }, adjust: { y: -30 } },
        show: { delay: 1, solo: true, when: { event: 'focus' } },
        hide: { when: { event: 'unfocus' } }
  });

  $('div.zetaprints-page-stock-images select.stock-images-selector').msDropDown();

  $('div.zetaprints-page-color-pickers ul.colors-selector').vchecks();
});
//]]>
</script>
<?php
  }
}
?>