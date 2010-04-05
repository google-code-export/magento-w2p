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

    //Append translations to xml
    $locale_file = Mage::getBaseDir('locale').DS.Mage::app()->getLocale()->getLocaleCode().DS.'zetaprints_w2p.csv';

    if (file_exists($locale_file)) {
      $cache = Mage::getSingleton('core/cache');
      $out = $cache->load("XMLTranslation".Mage::app()->getLocale()->getLocaleCode());

      if (strlen($out) == 0) {
        $locale = @file_get_contents($locale_file);
        preg_match_all('/"(.*?)","(.*?)"(:?\r|\n)/', $locale, $array, PREG_PATTERN_ORDER);

        if (is_array($array) && count($array[1]) > 0) {
          $out = '<trans>';

          foreach ($array[1] as $key => $value) {
            if (strlen($value) > 0 && strlen($array[2][$key]) > 0) {
              $out .= "<phrase key=\"".$value."\" value=\"".$array[2][$key]."\"/>";
            }
          }

          $out .= "</trans>";
          $cache->save($out,"XMLTranslation".Mage::app()->getLocale()->getLocaleCode(),array('TRANSLATE'));
        }
      }

      $doc = new DOMDocument();
      $doc->loadXML($out);
      $node = $doc->getElementsByTagName("trans")->item(0);
      $xml_dom = new DOMDocument();
      $xml_dom->loadXML($xml->asXML());
      $node = $xml_dom->importNode($node, true);
      $xml_dom->documentElement->appendChild($node);
    } else {
      $xml_dom = new DOMDocument();
      $xml_dom->loadXML($xml->asXML());
    }

    return zetaprints_get_html_from_xml($xml_dom, $form_part, $params);
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
    return false;
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
<script type="text/javascript" src="<?php echo $design->getSkinUrl('js/zp-personalization-form.js'); ?>"></script>
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
    if (! $template_id = $this->get_template_id($context->getProduct()))
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
      $previews_from_session = true;
    } else {
      $template = Mage::getModel('webtoprint/template')->loadById($template_id);

      Mage::log($template_id);

      if ($template->getId()) {
        try {
          $xml = new SimpleXMLElement($template->getXml());
        } catch (Exception $e) {
          zetaprints_debug("Exception: {$e->getMessage()}");
        }

        if ($xml) {
          $template_details = zetaprints_parse_template_details($xml);

          foreach ($template_details['pages'] as $page_details)
            $previews_array .= '\'' . $page_details['preview-image'] . '\', ';

          $previews_array = substr($previews_array, 0, -2);
        }
      }
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

  previews = [<?php echo $previews_array; ?>];
  template_id = '<?php echo $this->get_template_guid_from_product($context->getProduct()); ?>';
  previews_from_session = <?php echo isset($previews_from_session) ? 'true' : 'false'; ?>;
  is_personalization_step = <?php echo $this->is_personalization_step($context) ? 'true' : 'false' ?>;

  w2p_url = '<?php echo Mage::getStoreConfig('zpapi/settings/w2p_url'); ?>';

  preview_controller_url = '<?php echo $context->getUrl('web-to-print/preview'); ?>';
  upload_controller_url = '<?php echo $context->getUrl('web-to-print/upload'); ?>';
  image_controller_url = '<?php echo $context->getUrl('web-to-print/image/update'); ?>';

  edit_button_text = "<?php echo $this->__('Edit');?>";

  preview_generation_response_error_text = "<?php echo $this->__('Can\'t get preview image:'); ?>";
  preview_generation_error_text = "<?php echo $this->__('There was an error in generating or receiving preview image.\nPlease try again.'); ?>";
  uploading_image_error_text = "<?php echo $this->__('Error was occurred while uploading image'); ?>";
  click_to_close_text = "<?php echo $this->__('Click to close'); ?>";
  click_to_view_in_large_size = "<?php echo $this->__('Click to view in large size');?>";

  cant_delete_text = "<?php echo $this->__('Can\'t delete image'); ?>";
  delete_this_image_text = "<?php echo $this->__('Delete this image?'); ?>";

  personalization_form();
});
//]]>
</script>
<?php
  }
}
?>
