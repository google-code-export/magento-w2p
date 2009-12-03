<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/ZetaPrints/Api/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

class ZetaPrints_WebToPrint_Helper_PersonalizationForm extends Mage_Core_Helper_Abstract {

  public function get_template_id ($product) {
    $template_guid = $product->getSku();

    if (strlen($template_guid) != 36)
      return false;

    return Mage::getModel('webtoprint/template')->getResource()->getIdByGuid($template_guid);
  }

  private function get_form_part_html ($form_part = null, $product) {
    $template_id = $this->get_template_id ($product);

    if (!$template_id)
      return false;

    $template = Mage::getModel('webtoprint/template')->load($template_id);

    if (!$template->getId())
      return false;

    echo zetaprints_get_html_from_xml($template->getXml(), $form_part, Mage::getStoreConfig('api/settings/w2p_url'));
    return true;
  }

  public function get_product_image ($context, $product) {
    if (! $template_id = $this->get_template_id ($product))
      return false;

    $template = Mage::getModel('webtoprint/template')->load($template_id);

    if (!$template->getId())
      return false;

    $src = $template->getThumbnail();
    $alt = $context->htmlEscape($context->getImageLabel($product, 'small_image'));
    $title = $context->htmlEscape($context->getImageLabel($product, 'small_image'));

    echo "<img style=\"max-width: 135px;\" src=\"$src\" alt=\"$alt\" title=\"$title\" />";

    return true;
  }

  public function get_cart_image ($context) {
    $options = unserialize($context->getItem()->getOptionByCode('info_buyRequest')->getValue());

    if (!isset($options['zetaprints-previews']))
      return false;

    $images = explode(',', $options['zetaprints-previews']);

    $src = Mage::getStoreConfig('api/settings/w2p_url') . "/thumb/$images[0]";
    $alt = $context->htmlEscape($context->getProductName());

    echo "<img src=\"$src\" alt=\"$alt\" style=\"max-width: 75px;\" />";

    return true;
  }

  public function get_gallery_image ($context) {
    if (!$this->get_template_id ($context->getProduct()))
      return false;

    $src = $context->getImageUrl();
    $alt = $context->htmlEscape($context->getCurrentImage()->getLabel());
    $title = $context->htmlEscape($context->getCurrentImage()->getLabel());

    echo "<img src=\"$src\" alt=\"$alt\" title=\"$title\" id=\"product-gallery-image\" style=\"display:block;\" />";

    return true;
  }

  public function get_gallery_thumb ($context, $product, $_image) {
    if (!$this->get_template_id ($product))
      return false;

    $gallery_url = $context->getGalleryUrl($_image);
    $src = $_image['url'];
    $alt = $context->htmlEscape($_image->getLabel());
    $title = $context->htmlEscape($_image->getLabel());

    echo "<a href=\"#\" onclick=\"popWin('$gallery_url', 'gallery', 'width=300,height=300,left=50,top=50,location=no,status=yes,scrollbars=yes,resizable=yes'); return false;\"><img src=\"$src\" alt=\"$alt\" title=\"$title\" /></a>";

    return true;
  }

  public function get_preview_images ($context) {
    $session = Mage::getSingleton('core/session');

    if (!$session->hasData('zetaprints-previews'))
      return $this->get_form_part_html('preview-images', $context->getProduct());

    if (!$this->get_template_id ($context->getProduct()))
      return false;

    $previews = explode(',', $session->getData('zetaprints-previews'));

    $url = Mage::getStoreConfig('api/settings/w2p_url');
    $html = '<div class="zetaprints-template-preview-images">';

    foreach ($previews as $position => $preview) {
      $position += 1;
      $html .= "<div id=\"preview-image-page-$position\" class=\"zetaprints-template-preview\">";
      $html .= "<a href=\"$url/preview/$preview\">";
      $html .= "<img title=\"Click to view in large size\" src=\"$url/preview/$preview\" />";
      $html .= '</a></div>';
    }

    echo $html . '</div>';

    return true;
  }

  public function get_text_fields ($context) {
    return $this->get_form_part_html('input-fields', $context->getProduct());
  }

  public function get_image_fields ($context) {
    return $this->get_form_part_html('stock-images', $context->getProduct()) . '\n'. $this->get_form_part_html('color-pickers', $context->getProduct());
  }

  public function get_page_tabs ($context) {
    return $this->get_form_part_html('page-tabs', $context->getProduct());
  }

  public function get_preview_button ($context) {
    if (!$this->get_template_id($context->getProduct()))
      return false;
?>
    <div class="zetaprints-preview-button">
      <input type="button" value="Update preview" class="update-preview form-button" />
      <img src="<?php echo Mage::getDesign()->getSkinUrl('images/opc-ajax-loader.gif'); ?>" class="ajax-loader"/>
      <span>Updating preview image&hellip;</span>
    </div>
<?php
  }

  public function get_js_css_includes ($context=null) {
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

  public function get_order_webtoprint_links ($context) {
    $options = $context->getItem()->getProductOptionByCode('info_buyRequest');

    if (!isset($options['zetaprints-order-id']))
      return;

    $webtoprint_links = "";

    $types = array('pdf', 'gif', 'png', 'jpeg', 'cdr');
    foreach ($types as $type)
      if (isset($options['zetaprints-file-'.$type])) {
        $title = strtoupper($type);
        $webtoprint_links .= "<a href=\"{$options['zetaprints-file-'.$type]}\" target=\"_blank\">$title</a>&nbsp;";
      }

    return $webtoprint_links;
  }

  public function get_order_preview_images ($context) {
    $options = $context->getItem()->getProductOptionByCode('info_buyRequest');

    if (!isset($options['zetaprints-previews']))
      return;

    $previews = explode(',', $options['zetaprints-previews']);

    $url = Mage::getStoreConfig('api/settings/w2p_url');

    $html = '<ul>';
    foreach ($previews as $preview)
      $html .= "<li><a href=\"$url/preview/$preview\"><img src=\"$url/thumb/$preview\" /></a></li>";

    return $html . '</ul>';
  }

  public function get_js ($context) {
    if (!$this->get_template_id($context->getProduct()))
      return false;

    $session = Mage::getSingleton('core/session');

    $previews_array = null;
    $previews = null;

    if ($session->hasData('zetaprints-previews')) {
      $previews = $session->getData('zetaprints-previews');
      $previews_array = '\'' . str_replace(',', '\',\'', $previews) . '\'';
      $session->unsetData('zetaprints-previews');
    }
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
  $('#preview-image-page-1').css('display', 'block');
  $('#input-fields-page-1').css('display', 'block');
  $('#stock-images-page-1').css('display', 'block');
  $('#color-pickers-page-1').css('display', 'block');

  $('div.image-tabs li:first').addClass('selected');

  previews = [<?php echo $previews_array; ?>];
  template_id = '<?php echo $context->getProduct()->getSku(); ?>';
  number_of_pages = $('div.zetaprints-template-preview').length;

  <?php if ($previews): ?>
  $('div.image-tabs img').each(function () {
    var src = $(this).attr('src').split('thumb');
    var id = src[1].split('_');
    var n = id[0].substring(38, id[0].length);

    var new_id = previews[n].split('.');
    $(this).attr('src', src[0] + 'thumb/' + new_id[0] + '_100x100.' + new_id[1]);
  });

  $('<input type="hidden" name="zetaprints-previews" value="<?php echo $previews; ?>" />').appendTo($('#product_addtocart_form fieldset.no-display'));
  <?php else : ?>
  $('<input type="hidden" name="zetaprints-previews" value="" />').appendTo($('#product_addtocart_form fieldset.no-display'));
  $('fieldset.add-to-cart-box button.form-button').css('display', 'none');
  <? endif; ?>

  $('<input type="hidden" name="zetaprints-TemplateID" value="' + template_id +'" />').appendTo('#product_addtocart_form');

  $('div.image-tabs li').click(function () {
    $('div.image-tabs li').removeClass('selected');

    $('div.zetaprints-template-preview:visible, div.zetaprints-page-input-fields:visible, div.zetaprints-page-stock-images:visible, zetaprints-page-color-pickers:visible').css('display', 'none');

    $(this).addClass('selected');
    var page = $('img', this).attr('rel');

    $('#preview-image-' + page + ', #input-fields-' + page + ', #stock-images-' + page + ', #color-pickers-' + page).css('display', 'block');
  });

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
          //$('<input type="hidden" name="zetaprints-previews" value="' + previews.join(',') + '" />').appendTo($('#product_addtocart_form fieldset.no-display'));
          $('input[name=zetaprints-previews]').val(previews.join(','));
          $('fieldset.add-to-cart-box button.form-button').show();
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
