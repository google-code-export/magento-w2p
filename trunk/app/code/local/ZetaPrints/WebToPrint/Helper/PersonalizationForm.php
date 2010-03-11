<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/ZetaPrints/Zpapi/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

class ZetaPrints_WebToPrint_Helper_PersonalizationForm extends Mage_Core_Helper_Abstract {

  private function get_template_guid_from_product ($product) {

    //Get template GUID from webtoprint_template attribute if such attribute exists
    //and contains value, otherwise use product SKU as template GUID
    if (!($product->hasWebtoprintTemplate() && $template_guid = $product->getWebtoprintTemplate()))
      $template_guid = $product->getSku();

    if (strlen($template_guid) != 36)
      return false;

    return $template_guid;
  }

  public function get_template_id ($product) {
    if ($template_guid = $this->get_template_guid_from_product ($product))
      return Mage::getModel('webtoprint/template')->getResource()->getIdByGuid($template_guid);
  }

  private function get_form_part_html ($form_part = null, $product) {
    $template_guid = $this->get_template_guid_from_product($product);

    if (!$template_guid)
      return false;

    //$template = Mage::getModel('webtoprint/template')->load($template_guid);

    //if (!$template->getId())
    //  return false;

    if (! $template_xml = Mage::registry('webtoprint-template-xml')) {
      $url = Mage::getStoreConfig('zpapi/settings/w2p_url');
      $key = Mage::getStoreConfig('zpapi/settings/w2p_key');

      $w2p_user = Mage::getModel('zpapi/w2puser');

      $user_credentials = $w2p_user->get_credentials();

      $data = array(
        'ID' => $user_credentials['id'],
        'Hash' => zetaprints_generate_user_password_hash($user_credentials['password']) );

      $template_xml = zetaprints_get_template_details_as_xml($url, $key, $template_guid,
                                                 $data);

      Mage::register('webtoprint-template-xml', $template_xml);
    }

    try {
      $xml = new SimpleXMLElement($template_xml);
    } catch (Exception $e) {
      zetaprints_debug("Exception: {$e->getMessage()}");
      return false;
    }

    //if ($form_part === 'input-fields' || $form_part === 'stock-images')
    //  $this->add_values_from_cache($xml);

    if ($form_part === 'stock-images')
      $this->add_user_images($xml);

    $params = array(
      'zetaprints-api-url' => Mage::getStoreConfig('zpapi/settings/w2p_url') . '/',
      'ajax-loader-image-url' => Mage::getDesign()->getSkinUrl('images/opc-ajax-loader.gif'),
      'user-image-edit-button' => Mage::getDesign()->getSkinUrl('images/image-edit/edit.png')
    );

    return zetaprints_get_html_from_xml($xml->asXML(), $form_part, $params);
  }

  public function add_values_from_cache ($xml) {
    $session = Mage::getSingleton('customer/session');

    $text_cache = $session->getTextFieldsCache();
    $image_cache = $session->getImageFieldsCache();

    if ($text_cache && is_array($text_cache))
      foreach ($xml->Fields->Field as $field) {
        $name = (string)$field['FieldName'];

        if (!isset($text_cache[$name]))
          continue;

        $field->addAttribute('Value', $text_cache[$name]);
      }

    if ($image_cache && is_array($image_cache))
      foreach ($xml->Images->Image as $image) {
        $name = (string)$image['Name'];

        if (!isset($image_cache[$name]))
          continue;

        $image->addAttribute('Value', $image_cache[$name]);
      }
  }

  public function is_personalization_step ($context) {
    return $context->getRequest()->has('personalization') && $context->getRequest()->getParam('personalization') == '1';
  }

  public function get_next_step_url ($context) {
    if (!$this->is_personalization_step($context)) {
      echo $context->getProduct()->getProductUrl() . '?personalization=1';
      return true;
    }
    else
      return false;
  }

  public function get_params_from_previous_step ($context) {
    if (!$this->is_personalization_step($context))
      return;

    foreach ($context->getRequest()->getParams() as $key => $value) {
      if (is_array($value))
        foreach ($value as $option_key => $option_value)
          echo "<input type=\"hidden\" name=\"{$key}[{$option_key}]\" value=\"$option_value\" />";
      else
        echo "<input type=\"hidden\" name=\"$key\" value=\"$value\" />";
    }
  }

  public function get_product_image ($context, $product) {
    return false;
  }

  public function get_cart_image ($context) {
    $options = unserialize($context->getItem()->getOptionByCode('info_buyRequest')->getValue());

    if (!isset($options['zetaprints-previews']))
      return false;

    $images = explode(',', $options['zetaprints-previews']);

    if (count($images) == 1)
     $message = Mage::helper('webtoprint')->__('Click to enlarge image');
    else
     $message = Mage::helper('webtoprint')->__('Click to see more images');

    $first_image = true;

    $group = 'group-' . mt_rand();

    foreach ($images as $image) {
      $href = Mage::getStoreConfig('zpapi/settings/w2p_url') . "/preview/{$image}";
      $src = Mage::getStoreConfig('zpapi/settings/w2p_url') . "/thumb/{$image}";

      if ($first_image) {
        echo "<a class=\"in-dialog\" href=\"$href\" rel=\"{$group}\" title=\"{$message}\">";
        $first_image = false;
      } else
        echo "<a class=\"in-dialog\" href=\"$href\" rel=\"{$group}\" style=\"display: none\">";

      echo "<img src=\"$src\" style=\"max-width: 75px;\" />";
      echo "</a>";
    }
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
  $('a.in-dialog').fancybox({
    'zoomOpacity': true,
    'overlayShow': false,
    'centerOnScroll': false,
    'zoomSpeedChange': 200,
    'zoomSpeedIn': 500,
    'zoomSpeedOut' : 500,
    'callbackOnShow': function () { $('img#fancy_img').attr('title', "<?php echo Mage::helper('webtoprint')->__('Click to close');?>"); } });
});
//]]>
</script>
<?php
    return true;
  }

  public function get_gallery_image ($context) {
    return false;
  }

  public function get_gallery_thumb ($context, $product, $_image) {
    return false;
  }

  public function get_preview_images ($context) {
    if (!$this->get_template_id ($context->getProduct()))
      return false;

    $session = Mage::getSingleton('core/session');

    if (!$session->hasData('zetaprints-previews')) {
      $html = $this->get_form_part_html('preview-images', $context->getProduct());

      if ($html === false)
      return false;

      echo $html;
      return true;
    }

    $previews = explode(',', $session->getData('zetaprints-previews'));

    $url = Mage::getStoreConfig('zpapi/settings/w2p_url');
    $html = '<div class="zetaprints-template-preview-images">';

    foreach ($previews as $position => $preview) {
      $position += 1;
      $html .= "<div id=\"preview-image-page-$position\" class=\"zetaprints-template-preview\">";
      $html .= "<a href=\"$url/preview/$preview\">";
      $html .= "<img title=\"".Mage::helper('webtoprint')->__('Click to view in large size')."\" src=\"$url/preview/$preview\" />";
      $html .= '</a></div>';
    }

    echo $html . '</div>';

    return true;
  }

  public function get_preview_image ($context) {
    if (!$context->getProduct()->getSmallImage())
      return false;

    $img = '<img src="' . $context->helper('catalog/image')->init($context->getProduct(), 'small_image')->resize(265) . '" alt="'.$context->htmlEscape($context->getProduct()->getSmallImageLabel()).'" />';

    echo $context->helper('catalog/output')->productAttribute($context->getProduct(), $img, 'small_image');

    return true;
  }

  public function get_text_fields ($context) {
    $html = $this->get_form_part_html('input-fields', $context->getProduct());

    if ($html === false)
      return false;

    echo $html;
    return true;
  }

  public function get_image_fields ($context) {
    $html = $this->get_form_part_html('stock-images', $context->getProduct());

    if ($html === false)
      return false;

    echo $html;
    return true;
  }

  private function add_user_images ($xml) {
    $url = Mage::getStoreConfig('zpapi/settings/w2p_url');
    $key = Mage::getStoreConfig('zpapi/settings/w2p_key');

    $w2p_user = Mage::getModel('zpapi/w2puser');

    $user_credentials = $w2p_user->get_credentials();

    $data = array(
      'ID' => $user_credentials['id'],
      'Hash' => zetaprints_generate_user_password_hash($user_credentials['password']) );

    $images = zetaprints_get_user_images ($url, $key, $data);

    if ($images === null)
      return;

    foreach ($xml->Images->Image as $image_node)
      if (isset($image_node['AllowUpload']))
        foreach ($images as $image) {
          $user_image_node = $image_node->addChild('user-image');
          $user_image_node->addAttribute('guid', $image['guid']);

          if ($image['mime'] === 'image/jpeg' || $image['mime'] === 'image/jpg')
            $thumbnail = str_replace('.', '_0x100.', $image['thumbnail']);
          else
            $thumbnail = $image['thumbnail'];

          $user_image_node->addAttribute('thumbnail', "{$url}/photothumbs/{$thumbnail}");

          $user_image_node->addAttribute('mime', $image['mime']);
          $user_image_node->addAttribute('description', $image['description']);
          $user_image_node->addAttribute('edit-link',
            Mage::getUrl('web-to-print/image/',
              array('id' => $image['guid'], 'iframe' => 1) ));
        }
  }

  public function get_page_tabs ($context) {
    $html = $this->get_form_part_html('page-tabs', $context->getProduct());

    if ($html === false)
      return false;

    echo $html;
    return true;
  }

  public function get_preview_button ($context) {
    if (!$this->get_template_id($context->getProduct()))
      return false;
?>
    <div class="zetaprints-preview-button">
      <button class="update-preview button">
        <span><span><?php echo Mage::helper('webtoprint')->__('Update preview');?></span></span>
      </button>
      <img src="<?php echo Mage::getDesign()->getSkinUrl('images/opc-ajax-loader.gif'); ?>" class="ajax-loader"/>
      <span class="text"><?php echo Mage::helper('webtoprint')->__('Updating preview image');?>&hellip;</span>
    </div>
<?php
  }

  public function get_next_page_button ($context) {
    if (!$this->get_template_id($context->getProduct()))
      return false;
?>
    <div class="zetaprints-next-page-button">
      <button class="next-page button">
        <span><span><?php echo Mage::helper('webtoprint')->__('Next page');?></span></span>
      </button>
    </div>
<?php
  }

  public function prepare_gallery_images ($context, $check_for_personalization = false) {
    if (!$this->get_template_id($context->getProduct()))
      return false;

    if ($check_for_personalization && !$this->is_personalization_step($context))
      return false;

    $images = $context->getProduct()->getMediaGalleryImages();

    foreach ($images as $image)
      if(strpos(basename($image['path']), 'zetaprints_') === 0)
        $images->removeItemByKey($image->getId());

    //$images = $context->getProduct()->getMediaGallery('images');

    //foreach ($images as &$image)
    //  if(strpos(basename($image['file']), 'zetaprints_') === 0)
    //    $image['disabled'] = 1;

    //$context->getProduct()->setMediaGallery('images', $images);
  }

  public function get_admin_js_css_includes ($context=null) {
    $design = Mage::getDesign();
?>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-1.3.2.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery.fancybox-1.2.6.pack.js'); ?>"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/jquery.fancybox-1.2.6.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/zp-style.css'); ?>" />
<?php
  }

  public function get_js_css_includes ($context=null) {
    $design = Mage::getDesign();
?>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-1.3.2.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-ui-1.7.2.custom.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/colorpicker.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-qtip-1.0.0-rc3.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery.fancybox-1.2.6.pack.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/ajaxupload.js'); ?>"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/colorpicker.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/jquery.fancybox-1.2.6.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/zp-style.css'); ?>" />
<?php
  }

  public function get_image_edit_js_css_includes ($context=null) {
    $design = Mage::getDesign();
?>

<script type="text/javascript" src="<?php echo Mage::getUrl('web-to-print/Trans'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-1.3.2.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/jquery-jcrop.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/zp-image-edit.js'); ?>"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/jquery.jcrop.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/zp-image-edit.css'); ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $design->getSkinUrl('css/jquery.fancybox-1.2.6.css'); ?>" />
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

  public function get_order_preview_images ($context, $item = null) {
    if ($item)
      $options = $item->getProductOptionByCode('info_buyRequest');
    else
      $options = $context->getItem()->getProductOptionByCode('info_buyRequest');

    if (!isset($options['zetaprints-previews']))
      return;

    $previews = explode(',', $options['zetaprints-previews']);
    $width = count($previews) * 155;
    $group = 'group-' . mt_rand();

    $url = Mage::getStoreConfig('zpapi/settings/w2p_url');
?>
    <tr class="border zetaprints-previews">
      <td class="last" colspan="<?php echo $item ? 5 : 10; ?>">
        <div class="zetaprints-previews-box <?php if ($item) echo 'hidden'; ?>">
          <div class="title">
            <a class="show-title">+&nbsp;<span><?php echo Mage::helper('webtoprint')->__('Show previews');?></span></a>
            <a class="hide-title">&minus;&nbsp;<span><?php echo Mage::helper('webtoprint')->__('Hide previews');?></span></a>
          </div>
          <div class="content">
            <ul style="width: <?php echo $width ?>px;">
            <?php foreach ($previews as $preview): ?>
              <li>
                <a class="in-dialog" href="<?php echo "$url/preview/$preview" ?>" target="_blank" rel="<?php echo $group; ?>">
                  <img src="<?php echo "$url/thumb/$preview" ?>" title="<?php echo Mage::helper('webtoprint')->__('Click to enlarge');?>"/>
                </a>
              </li>
            <?php endforeach ?>
            </ul>
          </div>
        </div>
      </td>
    </tr>
<?php
  }

  public function get_js_for_order_preview_images ($context) {
?>
  <script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
  $('div.zetaprints-previews-box').width($('div#sales_order_view').width());
  $('div.zetaprints-previews-box').width($('table#my-orders-table tr.zetaprints-previews td').width()).removeClass('hidden');

  $('div.zetaprints-previews-box a.show-title').each(function () {
    $(this).click(function () {
      $(this).parents('div.zetaprints-previews-box').removeClass('hide');
    });
  });

  $('div.zetaprints-previews-box a.hide-title').each(function () {
    $(this).click(function () {
      $(this).parents('div.zetaprints-previews-box').addClass('hide');
    });
  });

  $('a.in-dialog').fancybox({
    'zoomOpacity': true,
    'overlayShow': false,
    'centerOnScroll': false,
    'zoomSpeedChange': 200,
    'zoomSpeedIn': 500,
    'zoomSpeedOut' : 500,
    'callbackOnShow': function () { $('img#fancy_img').attr('title', '<?php echo Mage::helper('webtoprint')->__('Click to close');?>'); } });
});
//]]>
    </script>
<?php
  }

  public function show_hide_all_order_previews ($context) {
?>
  <a href="#" class="all-order-previews">
    <span class="show-title"><?php echo Mage::helper('webtoprint')->__('Show all order previews');?></span>
    <span class="hide-title"><?php echo Mage::helper('webtoprint')->__('Hide all order previews');?></span>
  </a>

  <script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
  $('a.all-order-previews').toggle(
    function () {
      $(this).addClass('hide-all');
      $('div.zetaprints-previews-box').addClass('hide');
    },
    function () {
      $(this).removeClass('hide-all');
      $('div.zetaprints-previews-box').removeClass('hide');
    }
  );
});
//]]>
    </script>

<?php
  }

  public function get_js ($context) {
    if (!$this->get_template_id($context->getProduct()))
      return false;

    $session = Mage::getSingleton('core/session');

    $previews_array = null;
    $previews = null;
    $user_input = null;

    if ($session->hasData('zetaprints-previews')) {
      $previews = $session->getData('zetaprints-previews');
      $previews_array = '\'' . str_replace(',', '\',\'', $previews) . '\'';
      $user_input = unserialize($session->getData('zetaprints-user-input'));
      $session->unsetData('zetaprints-previews');
    }
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
  <?php
  if ($user_input)
    foreach ($user_input as $key => $value)
      echo "$('[name=$key]').val('$value');\n";
  ?>

  $('div.zetaprints-page-stock-images input:checked').each(function() {
    $(this).parents('div.zetaprints-images-selector').removeClass('no-value');
  });

  $('#stock-images-page-1').removeClass('hidden');
  $('#preview-image-page-1, #input-fields-page-1, div.zetaprints-image-tabs, div.zetaprints-preview-button').css('display', 'block');

  $('div.zetaprints-image-tabs li:first').addClass('selected');

  $('div.tab.user-images').each(function() {
    var tab_button = $('ul.tab-buttons li.hidden', $(this).parents('div.selector-content'));

    if ($('td', this).length > 0)
      $(tab_button).removeClass('hidden');
  });

  previews = [<?php echo $previews_array; ?>];
  template_id = '<?php echo $this->get_template_guid_from_product($context->getProduct()); ?>';
  number_of_pages = $('div.zetaprints-template-preview').length;
  changed_pages = new Array(number_of_pages);

  <?php if ($previews): ?>
  $('div.zetaprints-image-tabs img').each(function () {
    var src = $(this).attr('src').split('thumb');
    var id = src[1].split('_');
    var n = id[0].substring(38, id[0].length);

    var new_id = previews[n].split('.');
    $(this).attr('src', src[0] + 'thumb/' + new_id[0] + '_100x100.' + new_id[1]);
  });

  $('<input type="hidden" name="zetaprints-previews" value="<?php echo $previews; ?>" />').appendTo($('#product_addtocart_form div.no-display'));
  <?php else : ?>
  $('<input type="hidden" name="zetaprints-previews" value="" />').appendTo($('#product_addtocart_form div.no-display'));
  $('div.add-to-cart button.button').css('display', 'none');
  <?php endif; ?>

  $('<input type="hidden" name="zetaprints-TemplateID" value="' + template_id +'" />').appendTo('#product_addtocart_form');

  $('div.zetaprints-image-tabs li').click(function () {
    $('div.zetaprints-image-tabs li').removeClass('selected');

    $('div.zetaprints-template-preview:visible, div.zetaprints-page-input-fields:visible').css('display', 'none');
    $('div.zetaprints-page-stock-images').addClass('hidden');

    $(this).addClass('selected');
    var page = $('img', this).attr('rel');

    $('#preview-image-' + page + ', #input-fields-' + page).css('display', 'block');
    $('#stock-images-' + page).removeClass('hidden');

    var page_number = page.split('-')[1] * 1;
    if (changed_pages[page_number - 1] && page_number < number_of_pages)
      $('div.zetaprints-next-page-button').show();
    else
      $('div.zetaprints-next-page-button').hide();
  });

  function update_preview () {
    $('div.zetaprints-preview-button span.text').css('display', 'inline');
    $('img.ajax-loader').css('display', 'inline');

    var update_preview_button = $('button.update-preview').unbind('click').hide();
    var page = '' + $('div.zetaprints-image-tabs li.selected img').attr('rel');

    if (page == "undefined")
      page = 'page-1';

    $.ajax({
      url: '<?php echo $context->getUrl('web-to-print/preview'); ?>',
      type: 'POST',
      data: $('#product_addtocart_form').serialize() + '&zetaprints-From=' + page.split('-')[1],
      error: function (XMLHttpRequest, textStatus, errorThrown) {
        $('div.zetaprints-preview-button span.text, img.ajax-loader').css('display', 'none');
        $(update_preview_button).bind('click', update_preview).show();
        alert("<?php echo Mage::helper('webtoprint')->__('Can\'t get preview image:');?>" + textStatus); },
      success: function (data, textStatus) {
        if (data.substr(0, 7) != 'http://') {
          alert("<?php echo Mage::helper('webtoprint')->__('There was an error in generating or receiving preview image.\nPlease try again.');?>");
        } else {
          $('#preview-image-' + page + ' a').attr('href', data);
          $('#preview-image-' + page + ' img').attr('src', data);

          var image_name = data.split('/preview/')[1]
          previews[parseInt(page.split('-')[1]) - 1] = image_name;

          var thumb_url = data.split('/preview/')[0] + '/thumb/' + image_name.split('.')[0] + '_100x100.' + image_name.split('.')[1];
          $('div.zetaprints-image-tabs img[rel=' + page + ']').attr('src', thumb_url);

          if (previews.length == number_of_pages) {
            $('input[name=zetaprints-previews]').val(previews.join(','));
            $('div.add-to-cart button.button').show();
            $('div.save-order span').css('display', 'none');
          }
        }

        var page_number = page.split('-')[1] * 1;
        changed_pages[page_number - 1] = true;

        if (page_number < number_of_pages)
          $('div.zetaprints-next-page-button').show();
        else
          $('div.zetaprints-next-page-button').hide();

        $('div.zetaprints-preview-button span.text, img.ajax-loader').css('display', 'none');
        $(update_preview_button).bind('click', update_preview).show(); }
    });

    return false;
  }

  $('button.update-preview').click(update_preview);

  $('div.button.choose-file').each(function () {
    var uploader = new AjaxUpload(this, {
      name: 'customer-image',
      action: '<?php echo $context->getUrl('web-to-print/upload'); ?>',
      autoSubmit: false,
      onChange: function (file, extension) {
        var upload_div = $(this._button).parents('div.upload');
        $('input.file-name', upload_div).val(file);
        $('div.button.upload-file', upload_div).removeClass('disabled');
      },
      onSubmit: function (file, extension) {
        var upload_div = $(this._button).parents('div.upload');
        $('div.button.upload-file', upload_div).addClass('disabled');
        $('img.ajax-loader', upload_div).show();
      },
      onComplete: function (file, response) {
        var upload_div = $(this._button).parents('div.upload');

        if (response == 'Error') {
          $('img.ajax-loader', upload_div).hide();
          alert("<?php echo Mage::helper('webtoprint')->__('Error was occurred while uploading image');?>");
          return;
        }

        $('input.file-name', upload_div).val('');

        response = response.split(';');

        var trs = $('div.zetaprints-page-stock-images div.tab.user-images table tr');

        $(this._button).parents('div.zetaprints-images-selector.no-value')
          .removeClass('no-value');

        var number_of_loaded_imgs = 0;

        $(trs).each(function () {
          var name = $('input[name=parameter]', $(this).parents('div.user-images')).val();

          var td = $('<td><input type="radio" name="zetaprints-#' + name
            + '" value="' + response[0]
            + '" /><a class="edit-dialog" href="' + response[1]
            + 'target="_blank"><img src="' + response[2]
            + '" /><img class="edit-button" src="'
            + '<?php echo Mage::getDesign()->getSkinUrl('images/image-edit/edit.png');?>'
            + '"></a></td>').prependTo(this);

          var tr = this;

          $('img', td).load(function() {
            $('a.edit-dialog', tr).fancybox({
              'padding': 0,
              'hideOnOverlayClick': false,
              'hideOnContentClick': false,
              'centerOnScroll': false });

            if (++number_of_loaded_imgs == trs.length) {
              $('div.tab.user-images input[value=' + response[0] + ']',
                $(upload_div).parent()).attr('checked', 1);

              $('img.ajax-loader', upload_div).hide();
              $('div.zetaprints-page-stock-images ul.tab-buttons li.hidden')
                .removeClass('hidden');
              $(upload_div).parents('div.selector-content').tabs('select', 1);
            }
          });
        });
      }
    });

    $('div.button.upload-file', $(this).parent()).click(function () {
      if (!$(this).hasClass('disabled'))
        uploader.submit();
    });
  })

  $(window).load(function () {
    $('div.zetaprints-images-selector').each(function () {
      var top_element = this;

      var tab_number = 0
      if ($('li.hidden', this).length == 0)
        tab_number = 1;

      var tabs = $('div.selector-content', this).tabs({
        selected: tab_number,
        show: function (event, ui) {
          if ($(ui.panel).hasClass('color-picker') && !$('input', ui.panel).attr('checked'))
            $('div.color-sample', ui.panel).click();

          if ($(ui.panel).hasClass('images-scroller')) {
            var position = $('input:checked', ui.panel).parents('td').position();
            if (position)
              $(ui.panel).scrollLeft(position.left); }}
      });

      $('input', this).change(function () {
        $(top_element).removeClass('no-value');
      });

      $('div.head', this).click(function () {
        if ($(top_element).hasClass('minimized')) {
          $(top_element).removeClass('minimized');
          var panel = $($('a', $('ul.tab-buttons li', top_element)[tabs.tabs('option', 'selected')]).attr('href'));
          if (panel.hasClass('color-picker') && !$('input', panel).attr('checked')) {
            $('div.color-sample', panel).click();
          }

          if ($(panel).hasClass('images-scroller')) {
            var position = $('input:checked', panel).parents('td').position();
            if (position)
              $(panel).scrollLeft(position.left); }
        }
        else
          $(top_element).addClass('minimized').removeClass('expanded').css('width', '100%');

        return false;
      });

      var previews_images = $('div.zetaprints-template-preview-images');

      $('a.image.collapse-expand', this).click(function () {
        if ($(top_element).hasClass('expanded')) {
          $(top_element).removeClass('expanded').css('width', '100%');
          var panel = $($('a', $('ul.tab-buttons li', top_element)[tabs.tabs('option', 'selected')]).attr('href'));
        } else {
          var position = $(top_element).position().left - $(previews_images).position().left;
          $(top_element).addClass('expanded')
            .css({ 'left': -position, 'width': position + $(top_element).outerWidth() })
            .removeClass('minimized');

          var panel = $($('a', $('ul.tab-buttons li', top_element)[tabs.tabs('option', 'selected')]).attr('href'));
          if (panel.hasClass('color-picker') && !$('input', panel).attr('checked')) {
            $('div.color-sample', panel).click();
          }
        }

        if ($(panel).hasClass('images-scroller')) {
          $(panel).scrollLeft(0);
          var position = $('input:checked', panel).parents('td').position();
          if (position)
            $(panel).scrollLeft(position.left); }

        return false;
      });

      var input = $('div.color-picker input', this)[0];
      var color_sample = $('div.color-sample', this);

      var colour = $(input).val();
      if (colour)
        $(color_sample).css('backgroundColor', colour);

      $([color_sample, $('div.color-picker a', this)]).ColorPicker({
        color: '#804080',
        onBeforeShow: function (colpkr) {
          var colour = $(input).val();
          if (colour)
            $(this).ColorPickerSetColor(colour);

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
          $(top_element).removeClass('no-value');
          $(color_sample).css('backgroundColor', '#' + hex);
          $(input).val('#' + hex).attr('checked', 1).attr('disabled', 0);
          $(el).ColorPickerHide();
        }
      });
    });
  });

  $('div.zetaprints-next-page-button').click(function () {
    var page = '' + $('div.zetaprints-image-tabs li.selected img').attr('rel');

    if (page == "undefined")
      page = 'page-1';

    var next_page_number = page.substring(5) * 1 + 1;

    $('div.zetaprints-image-tabs li img[rel=page-' + next_page_number +']').parent().click();

    if (next_page_number >= number_of_pages)
      $(this).hide();

    return false;
  });

  $('div.zetaprints-template-preview a, a.in-dialog').fancybox({
    'zoomOpacity': true,
    'overlayShow': false,
    'centerOnScroll': false,
    'zoomSpeedChange': 200,
    'zoomSpeedIn': 500,
    'zoomSpeedOut' : 500,
    'callbackOnShow': function () { $('img#fancy_img').attr('title', 'Click to close'); } });

  $('a.edit-dialog').fancybox({
    'padding': 0,
    'hideOnOverlayClick': false,
    'hideOnContentClick': false,
    'centerOnScroll': false });

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
});
//]]>
</script>
<?php
  }
}
?>
