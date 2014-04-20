<?php

class ZetaPrints_WebToPrint_Helper_PersonalizationForm
  extends ZetaPrints_WebToPrint_Helper_Data
  implements ZetaPrints_Api {

  private function get_form_part_html ($form_part = null, $product, $params = array()) {
    //$template = Mage::getModel('webtoprint/template')->load($template_guid);

    //if (!$template->getId())
    //  return false;

    if (! $xml = $this->getTemplateXmlForCurrentProduct())
      return;

    //if ($form_part === 'text-fields' || $form_part === 'image-fields')
    //  $this->add_values_from_cache($xml);

    if ($form_part === 'image-fields'
        && Mage::registry('webtoprint-user-was-registered'))
      $this->add_user_images($xml);

    if ($form_part === 'page-size-table'
        && !isset($xml->Pages->Page[0]['WidthIn']))
      return false;

    $params = array_merge(
      $params,
      array('zetaprints-api-url'
                      => Mage::getStoreConfig('webtoprint/settings/url') . '/' )
    );

    //Append translations to xml
    $locale_file = Mage::getBaseDir('locale') . DS
                   . Mage::app()->getLocale()->getLocaleCode() .DS
                   .'ZetaPrints_WebToPrint.csv';

    $custom_translations_file = Mage::getBaseDir('locale') . DS
                                . Mage::app()->getLocale()->getLocaleCode() . DS
                                . 'ZetaPrints_WebToPrintCustomTranslations.csv';

    if (file_exists($locale_file) || file_exists($custom_translations_file)) {
      $cache = Mage::getSingleton('core/cache');
      $out = $cache->load("XMLTranslation".Mage::app()->getLocale()->getLocaleCode());

      if (strlen($out) == 0) {
        $locale = @file_get_contents($locale_file)
                  . @file_get_contents($custom_translations_file);

        preg_match_all('/"(.*?)","(.*?)"(:?\r|\n|$)/', $locale, $array, PREG_PATTERN_ORDER);

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
    return $context->getRequest()->getParam('personalization') == '1';
  }

  public function get_next_step_url ($context) {
    if (!$this->is_personalization_step($context)) {
      //Add personalization parameter to URL
      $params = array('personalization' => '1');

      //Check if the product page was requested with reorder parameter
      //then proxy the parameter to personalization step
      if ($this->_getRequest()->has('reorder'))
        $params['reorder'] = $this->_getRequest()->getParam('reorder');

      //Check if the product page was requested with for-item parameter
      //then proxy the parameter to personalization step and ignore last
      //visited page (need it to distinguish cross-sell product and already
      //personalized product)
      if ($this->_getRequest()->has('for-item'))
        $params['for-item'] = $this->_getRequest()->getParam('for-item');
      else
        //Check that the product page was opened from cart page (need for
        //automatic first preview update for cross-sell product)
        if (strpos(Mage::getSingleton('core/session')->getData('last_url'),
              'checkout/cart') !== false)
          //Send update-first-preview query parameter to personalization step
          $params['update-first-preview'] = 1;

      //Print out url for the product
      echo $this->create_url_for_product($context->getProduct(), $params);

      return true;
    }
    else
      return false;
  }

  public function get_params_from_previous_step ($context) {
    if (!$this->is_personalization_step($context))
      return;

    foreach ($_POST as $key => $value) {
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

  public function get_cart_image ($context, $width = 0, $height = 0) {
    $options = unserialize($context->getItem()->getOptionByCode('info_buyRequest')->getValue());

    if (!isset($options['zetaprints-previews'])
         || !$options['zetaprints-previews'])
      return false;

    $images = explode(',', $options['zetaprints-previews']);

    if (count($images) == 1)
     $message = $this->__('Click to enlarge image');
    else
     $message = $this->__('Click to see more images');

    $first_image = true;

    $group = 'group-' . mt_rand();

    foreach ($images as $image) {
      $href = $this->get_preview_url($image);
      $src = $this->get_thumbnail_url($image);

      if ($first_image) {
        echo "<a class=\"in-dialog product-image\" href=\"$href\" rel=\"{$group}\" title=\"{$message}\">";
        $first_image = false;
      } else
        echo "<a class=\"in-dialog product-image\" href=\"$href\" rel=\"{$group}\" style=\"display: none\">";

      $style = $width
                 ? 'style="max-width: ' . $width . 'px;"'
                   : 'style="max-width: 75px;"';

      echo '<img src="', $src, '" ', $style, ' />';
      echo "</a>";
    }

    //If item has low resolution link to PDF...
    if (isset($options['zetaprints-order-lowres-pdf'])) {
      $href = Mage::getStoreConfig('webtoprint/settings/url')
              . $options['zetaprints-order-lowres-pdf'];

      $title = $this->__('PDF Proof');

      //... show it
      echo "<br /><a class=\"zetaprints-lowres-pdf-link\" href=\"{$href}\">{$title}</a>";
    }
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
  $('a.in-dialog').fancybox({
    'opacity': true,
    'overlayShow': false,
    'transitionIn': 'elastic',
    'changeSpeed': 200,
    'speedIn': 500,
    'speedOut' : 500,
    'titleShow': false });
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

  public function get_preview_image_sharing_link ($context = null) {
    $url = Mage::getModel('catalog/product_media_config')
             ->getTmpMediaUrl('previews/');

    if(substr($url, 0, 1) == '/') {
      $url = $this->_getRequest()->getScheme()
             . '://'
             . $_SERVER['SERVER_NAME']
             . $url;
    }
 ?>

<span class="zetaprints-share-link empty">
  <a href="javascript:void(0)"><?php echo $this->__('Share preview'); ?></a>
  <input id="zetaprints-share-link-input" type="text" value="" />
</span>

<script type="text/javascript">
//<![CDATA[
  var place_preview_image_sharing_link = true;
  var preview_image_sharing_link_template = '<?php echo $url; ?>';

  jQuery(document).ready(function($) {
    $('#zetaprints-share-link-input').focusout(function() {
      $(this).parent().removeClass('show');
    }).click(function () {
      $(this).select();
    }).select(function () {
      var guid = zp
                   .template_details
                   .pages[zp.current_page]['updated-preview-image'];

      $.ajax({
        url: zp.url.preview_download,
        type: 'POST',
        dataType: 'json',
        data: 'guid=' + guid,
        error: function (XMLHttpRequest, textStatus, errorThrown) {
          alert(preview_sharing_link_error_text + ': ' + textStatus);
        },
        success: function (data, textStatus) {
          //Check returned status'
          if (data != 'OK')
            alert(data);
        }
      });
    }).val('');

    $('span.zetaprints-share-link a').click(function () {
      var parent = $(this).parent();

      if (!$(parent).hasClass('empty')) {
        $(parent).addClass('show');
        $('#zetaprints-share-link-input').focus();
      }
    });
  });
//]]>
</script>

<?php
  }

  public function get_preview_image ($context) {
    if (!$context->getProduct()->getSmallImage())
      return false;

    $img = '<img src="' . $context->helper('catalog/image')->init($context->getProduct(), 'small_image')->resize(265) . '" alt="'.$context->htmlEscape($context->getProduct()->getSmallImageLabel()).'" />';

    echo $context->helper('catalog/output')->productAttribute($context->getProduct(), $img, 'small_image');

    return true;
  }

  public function get_text_fields ($context) {
    $html = $this->get_form_part_html('text-fields', $context->getProduct());

    if ($html === false)
      return false;

    echo $html;
    return true;
  }

  public function get_image_fields ($context) {
    $params = array(
      'ajax-loader-image-url'
        => Mage::getDesign()->getSkinUrl('images/spinner.gif'),
      'user-image-edit-button'
        => Mage::getDesign()->getSkinUrl('images/image-edit/edit.png'),
      'photothumbnail-url-height-100-template'
        => $this->get_photo_thumbnail_url('image-guid.image-ext', 0, 100),
      'photothumbnail-url-template'
        => $this->get_photo_thumbnail_url('image-guid.image-ext'),
      'show-image-field'
        => (bool) $this->getCustomOptions('fields/image@show-on-load=1')
    );

    $html = $this->get_form_part_html('image-fields', $context->getProduct(), $params);

    if ($html === false)
      return false;

    echo $html;
    return true;
  }

  private function add_user_images ($xml) {
    $url = Mage::getStoreConfig('webtoprint/settings/url');
    $key = Mage::getStoreConfig('webtoprint/settings/key');

    $user_credentials = $this->get_zetaprints_credentials();

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
            $thumbnail_url = $this->get_photo_thumbnail_url($image['thumbnail'], 0, 100);
          else
            $thumbnail_url = $this->get_photo_thumbnail_url($image['thumbnail']);

          $user_image_node->addAttribute('thumbnail', $thumbnail_url);

          $user_image_node->addAttribute('mime', $image['mime']);
          $user_image_node->addAttribute('description', $image['description']);
          $user_image_node->addAttribute('edit-link',
            $this->_getUrl('web-to-print/image/',
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
    echo $context->getChildHtml('webtoprint_buttons');
  }

  public function get_next_page_button ($context) {
    return false;
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

  public function get_js_css_includes ($context=null) {
?>

<script type="text/javascript">
//<![CDATA[
  alert('<?php echo __FUNCTION__; ?>() function has been deprecated. See release notes in http://code.google.com/p/magento-w2p/wiki/ReleaseNotes');
//]]>
</script>

<?php
    return false;
  }

  public function get_admin_js_css_includes ($context = null) {
?>

<script type="text/javascript">
//<![CDATA[
  alert('<?php echo __FUNCTION__; ?>() function has been deprecated. See release notes in http://code.google.com/p/magento-w2p/wiki/ReleaseNotes');
//]]>
</script>

<?php
    return false;
  }

  public function get_order_webtoprint_links ($context, $item = null) {
    $isAdmin = false;

    if (!$item) {
      $item = $context->getItem();

      $isAdmin = true;
    }

    $options = $item->getProductOptionByCode('info_buyRequest');

    //Check for ZetaPrints Template ID in item options
    //If it doesn't exist or product doesn't have web-to-print features then...
    if (!isset($options['zetaprints-TemplateID']))
      //... just return from the function.
      return;

    $isOrderComplete = isset($options['zetaprints-order-completed'])
                       && $options['zetaprints-order-completed'];

    if ($isAdmin && !$isOrderComplete) {
      $url = Mage::helper('adminhtml')
               ->getUrl('web-to-print-admin/order/complete',
                        array('item' => $item->getId()));

      $title = $this->__('Complete order on ZetaPrints');

      echo '<br />'
           . "<a id=\"zp-complete-order-link\" href=\"{$url}\">{$title}</a>";
    }

    if ($isAdmin && isset($options['zetaprints-previews'])
        && !$options['zetaprints-previews']) {

      $input = array();

      foreach ($options as $key => $value) {
        //Ignore key if it doesn't start with 'zetaprints-' prefix
        if (strpos($key, 'zetaprints-') !== 0)
          continue;

        //Remove prefix from the key
        $_key = substr($key, 11);

        if (!(strpos($_key, '_') === 0 || strpos($_key, '#') === 0
            || strpos($_key, '*') === 0))
          continue;

        if (strpos($_key, '#') === 0) {
          if (! $details = $context->getTemplateDetails()) {
            $details = $this
                  ->getTemplateDetailsByGUID($options['zetaprints-TemplateID']);

            if ($details)
              $context->setTemplateDetails($details);
          }

          if ($details) {
            if (! $stockImages = $context->getStockImages()) {
              $stockImages = array();

              foreach ($details['pages'] as $page)
                foreach ($page['images'] as $imageField)
                  if (isset($imageField['stock-images']))
                    foreach ($imageField['stock-images'] as $image)
                      if (!isset($stockImages[$image['guid']])) {
                        $tokens = explode('.', $image['thumb']);

                        $stockImages[$image['guid']]
                          = array('thumb' => $image['thumb'],
                                  'small-thumb'
                                       => $tokens[0] . '_0x100.' . $tokens[1] );
                      }

              $context->setStockImages($stockImages);
            }

            if (isset($stockImages[$value])) {
              $url = Mage::getStoreConfig('webtoprint/settings/url')
                     . 'photothumbs/'
                     . $stockImages[$value]['thumb'];

              $small_url = Mage::getStoreConfig('webtoprint/settings/url')
                           . 'photothumbs/'
                           . $stockImages[$value]['small-thumb'];

              $value = "<a href=\"{$url}\" target=\"_blank\">" .
                         "<image src=\"{$small_url}\" />" .
                       "</a>";
            } else {
              if (! $userImages = $context->getUserImages()) {
                $customer = Mage::getModel('customer/customer')
                              ->load($item->getOrder()->getCustomerId());

                $userImages = array();

                if ($customer->getId()) {
                  $url = Mage::getStoreConfig('webtoprint/settings/url');
                  $key = Mage::getStoreConfig('webtoprint/settings/key');

                  $data = array(
                    'ID' => $customer->getZetaprintsUser(),
                    'Hash' => zetaprints_generate_user_password_hash(
                                          $customer->getZetaprintsPassword()) );

                  $userImages = zetaprints_get_user_images($url, $key, $data);

                  $context->setUserImages($userImages);
                }
              }

              if (isset($userImages[$value])) {
                $url = Mage::getStoreConfig('webtoprint/settings/url')
                     . 'photothumbs/' 
                     . $userImages[$value]['thumbnail'];

                $tokens = explode('.', $userImages[$value]['thumbnail']);

                $small_url = Mage::getStoreConfig('webtoprint/settings/url')
                             . 'photothumbs/' 
                             . $tokens[0] . '_0x100.' . $tokens[1];

                $value = "<a href=\"{$url}\" target=\"_blank\">" .
                           "<image src=\"{$small_url}\" />" .
                         "</a>";
              }
            }
          }

          if ($value === '#')
            $value = $this->__('Default');

          if ($value === '')
            $value = $this->__('Blank');
        } else
          $value = "<pre>{$value}</pre>";

        //Determine length of field prefix
        $prefix_length = 0;
        if (strpos($_key, '*') === 0)
          $prefix_length = 1;

        //Process field name (key), restore original symbols
        $_key = substr($_key, 0, $prefix_length)
                . str_replace( array('_', "\x0A"),
                               array(' ', '.'),
                               substr($_key, $prefix_length + 1) );

        //Add token to the array
        $input[$_key] = $value;
      }

      if (count($input)) {
        $product = Mage::getModel('catalog/product')->load($options['product']);

        if ($product->getId()) {
          $productUrl = $product->getProductUrl();
          $productName = $product->getName();
        }
?>
        <div style="display: none;">
          <div id ="zp-user-input-table">

            <?php if (isset($productUrl)): ?>
              <a href="<?php echo $productUrl; ?>">
                <?php echo $productName; ?>
              </a>
            <?php endif; ?>

            <table id ="zp-user-input-table">
              <thead>
                <tr>
                  <th><?php echo $this->__('Name'); ?></th>
                  <th><?php echo $this->__('Value'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($input as $name => $value): ?>
                <tr>
                  <td><?php echo $name; ?></td>
                  <td><?php echo $value; ?></td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
        </div>

        <br />

        <a id ="zp-user-input-link" href="#zp-user-input-table">
          <?php echo $this->__('Show customer\'s input data'); ?>
        </a>
<?php
      }
    }

    //Get value of custom option which allows users download files
    //regardless of ZP template setting
    $is_user_allowed_download = Mage::helper('webtoprint')
                              ->getCustomOptions('file-download/users@allow=1');

    //Check that downloading generated files is allowed for users
    if (!$isAdmin && !$is_user_allowed_download) {
      $template = Mage::getModel('webtoprint/template')
                                      ->load($options['zetaprints-TemplateID']);

      if (!$template->getId())
        return;

      try {
        $xml = new SimpleXMLElement($template->getXml());
      } catch (Exception $e) {
        Mage::log("Exception: {$e->getMessage()}");

        return;
      }

      if (!$xml)
        return;

      $template_details = zetaprints_parse_template_details($xml);

      if (!$template_details['download'])
        return;
    }

    $webtoprint_links = '<br />';

    $types = array('pdf', 'gif', 'png', 'jpeg');

    //If function called from admin template
    if ($isAdmin)
      //then add CDR file type to list of available types
      array_push($types, 'cdr');

    foreach ($types as $type)
      if (isset($options['zetaprints-file-'.$type])) {
        $title = strtoupper($type);
        $webtoprint_links .= "<a class=\"zetaprints-order-file-link {$type}\" href=\"{$options['zetaprints-file-'.$type]}\" target=\"_blank\">$title</a>&nbsp;";
      }

    //Check if the item is not null (it means the function was called from admin
    //interface) and ZetaPrints Order ID option is in the item then...
    if ($isAdmin && isset($options['zetaprints-order-id'])) {
      //... create URL to order details on web-to-print site
      $zp_order_url = Mage::getStoreConfig('webtoprint/settings/url')
                      . '?page=order-details;OrderID='
                      . $options['zetaprints-order-id'];

      //Display it on the page
      $webtoprint_links .=" <a target=\"_blank\" href=\"{$zp_order_url}\">ZP order</a>";
    }

    return $webtoprint_links;
  }

  public function get_order_preview_images ($context, $item = null) {
    if ($item)
      $options = $item->getProductOptionByCode('info_buyRequest');
    else
      $options = $context->getItem()->getProductOptionByCode('info_buyRequest');

    if (!(isset($options['zetaprints-previews'])
          || isset($options['zetaprints-downloaded-previews'])))
      return;

    $dynamicImaging = isset($options['zetaprints-dynamic-imaging'])
                        ? $options['zetaprints-dynamic-imaging'] : false;

    $previews = $dynamicImaging ? $options['zetaprints-downloaded-previews']
                                : explode(',', $options['zetaprints-previews']);
    $group = 'group-' . mt_rand();

    $url = Mage::getStoreConfig('webtoprint/settings/url');
?>
    <tr class="border zetaprints-previews">
      <td class="last" colspan="<?php echo $item ? 5 : 10; ?>">
        <div class="zetaprints-previews-box <?php if ($item) echo 'hidden'; ?>">
          <div class="title">
            <a class="show-title">+&nbsp;<span><?php echo $this->__('Show previews');?></span></a>
            <a class="hide-title">&minus;&nbsp;<span><?php echo $this->__('Hide previews');?></span></a>
          </div>
          <div class="content">
            <ul>
            <?php foreach ($previews as $preview): ?>
              <li>
                <?php if ($dynamicImaging): ?>
                  <a href="<?php echo $preview; ?>" target="_blank">
                    <?php echo $this->__('Download image');?>
                  </a>
                  <br />
                  <a class="in-dialog zetaprints-dynamic-imaging"
                     href="<?php echo $preview; ?>"
                     target="_blank"
                     rel="<?php echo $group; ?>">
                    <img src="<?php echo $preview; ?>"
                         title="<?php echo $this->__('Click to enlarge image'); ?>"/>
                  </a>
                <?php else: ?>
                  <a class="in-dialog" href="<?php echo $this->get_preview_url($preview); ?>" target="_blank" rel="<?php echo $group; ?>">
                    <img src="<?php echo $this->get_thumbnail_url($preview); ?>" title="<?php echo $this->__('Click to enlarge image');?>"/>
                  </a>
                <?php endif ?>
              </li>
            <?php endforeach ?>
            </ul>
          </div>
        </div>
      </td>
    </tr>
<?php
  }

  public function getOrderPreviewImagesForEmail ($context, $item) {
    $options = $item->getProductOptionByCode('info_buyRequest');

    if (!(isset($options['zetaprints-previews'])
          || isset($options['zetaprints-downloaded-previews'])))
      return;

    $dynamicImaging = isset($options['zetaprints-dynamic-imaging'])
                        ? $options['zetaprints-dynamic-imaging'] : false;

    $previews = $dynamicImaging ? $options['zetaprints-downloaded-previews']
                                : explode(',', $options['zetaprints-previews']);
?>
    <tr>
      <td colspan="4"
          style=" border-bottom:2px solid #CCCCCC; padding:3px 9px;">
      <?php foreach ($previews as $preview): ?>

      <?php
        $url = $dynamicImaging ? $preview : $this->get_preview_url($preview);
        $thumb = $dynamicImaging ? $preview : $this->get_thumbnail_url($preview);
      ?>
        <a href="<?php echo $this->get_preview_url($preview); ?>"
           style="text-decoration: none"
           target="_blank">
          <img src="<?php echo $this->get_thumbnail_url($preview); ?>"
               title="<?php echo $this->__('Click to enlarge image');?>" />
        </a>
      <?php endforeach ?>
      </td>
    </tr>
<?php
  }

  public function get_reorder_button ($context, $item) {
    $options = $item->getProductOptionByCode('info_buyRequest');

    //Check for ZetaPrints Order ID in item options
    //If it doesn't exist or product doesn't have web-to-print features then...
    if (!isset($options['zetaprints-order-id']))
      //... just return from the function.
      return;

    $product = Mage::getModel('catalog/product')->load($options['product']);

    if (!$product->getId())
      return;

    $url = $product->getUrlInStore(array('_query'
                      => array('reorder' => $options['zetaprints-order-id'])));

    echo "<a class=\"zetaprints-reorder-item-link\" href=\"{$url}\">Reorder</a>";
  }

  public function get_js_for_order_preview_images ($context) {
?>
  <script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function($) {
  var $boxes = $('div.zetaprints-previews-box');

  function set_width_for_boxes () {
    var width = $('#my-orders-table, table.order-tables')
                  .find('tr.zetaprints-previews td')
                  .width();

    if (width != 0) {
      $boxes
        .find('div.content')
        .width(width - 1)
        .end()
        .removeClass('hidden');
    } else
      setTimeout(set_width_for_boxes, 1000);
  }

  function set_width_for_ul () {
    if ($('a.in-dialog img:visible').length != 0)
      $boxes.each(function () {
        var width = 0;

        $(this).find('li').each(function () {
          width += $(this).outerWidth(true);
        });

        $(this).find('ul').width(width);
      });
    else
      setTimeout(set_width_for_ul, 1000);
  }

  $(window).load(function () {
    set_width_for_boxes();
    set_width_for_ul();
  });

  $boxes.find('a.show-title').each(function () {
    $(this).click(function () {
      $(this).parents('div.zetaprints-previews-box').removeClass('hide');
    });
  });

   $boxes.find('a.hide-title').each(function () {
    $(this).click(function () {
      $(this).parents('div.zetaprints-previews-box').addClass('hide');
    });
  });

  $('a.in-dialog').fancybox({
    'opacity': true,
    'overlayShow': false,
    'transitionIn': 'elastic',
    'changeSpeed': 200,
    'speedIn': 500,
    'speedOut' : 500,
    'titleShow': false });

  $('#zp-user-input-link').fancybox();

  $('#zp-complete-order-link').click(function () {
    $('<div class="zp-overlay">' +
        '<div class="zp-overlay-spinner">' +
          '<div />' +
        '</div>' +
        '<span class="zp-overlay-text">' +
          '<?php echo $this->__('Completing order on ZetaPrints'); ?> &hellip;' +
        '</span>' +
      '</div>')
      .appendTo('body');
  });
});
//]]>
    </script>
<?php
  }

  public function show_hide_all_order_previews ($context) {
?>
  <a href="#" class="all-order-previews">
    <span class="show-title"><?php echo $this->__('Show all order previews');?></span>
    <span class="hide-title"><?php echo $this->__('Hide all order previews');?></span>
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

  public function getPageSizeTable ($context, $units = 'in') {
    $params = array(
      'page-size-units' => $units,
      'page-size-icon'
                => Mage::getDesign()->getSkinUrl('images/page-size-icon.png') );

    $result = $this->get_form_part_html('page-size-table',
                                        $context->getProduct(),
                                        $params );

    echo $result ? $result : '';
  }

  public function getDataSetTable ($context) {
    if (! $templateId = $this->get_template_id($context->getProduct()))
      return false;

    if (! $xml = Mage::registry('webtoprint-template-xml')) {
      $template = Mage::getModel('webtoprint/template')->loadById($templateId);

      if ($template->getId())
        try {
          $xml = new SimpleXMLElement($xml = $template->getXml());
        } catch (Exception $e) {
          Mage::log("Exception: {$e->getMessage()}");
        }
    }

    if (!$xml)
      return false;

    $templateDetails = zetaprints_parse_template_details($xml);

    $dataset = array();
    $fieldNames = array();

    foreach ($templateDetails['pages'] as $pageNumber => $page) {
      if (!isset($page['fields']))
        continue;

      $_dataset = array();

      foreach ($page['fields'] as $field)
        if (isset($field['dataset'])) {
          foreach ($field['dataset'] as $number => $data) {
            if (! isset($_dataset[$number]))
              $_dataset[$number] = array();

            $_dataset[$number][] = $data;
          }

          $fieldNames[] = $field['name'];
        }

      if (count($_dataset))
        $dataset[$pageNumber] = $_dataset;
    }

    if (!count($dataset))
      return;
?>
  <div class="zp-dataset-wrapper">
    <?php foreach ($dataset as $pageNumber => $_dataset): ?>
    <div id="zp-dataset-page-<?php echo $pageNumber; ?>" class="zp-dataset">
      <table id="zp-dataset-table-page-<?php echo $pageNumber; ?>" class="zp-dataset-table">
        <thead>
          <tr>
            <th></th>

            <?php
              if (isset($templateDetails['pages'][$pageNumber]['fields'])):
                $fields = $templateDetails['pages'][$pageNumber]['fields'];

                foreach ($fields as $field):
                  if (isset($field['dataset'])):
            ?>

            <th><?php echo $this->__($field['name']); ?></th>

            <?php
                  endif;
                endforeach;
              endif;
            ?>

          </tr>
        </thead>
        <tbody>
          <?php foreach ($_dataset as $set): ?>
          <tr>
            <td class="zp-dataset-checkbox"><input type="checkbox" name="test" /></td>

            <?php foreach ($set as $number => $data): ?>

            <td class="<?php echo $fieldNames[$number]; ?>">
              <?php foreach ($data['lines'] as $line => $text): ?>

              <p <?php if (!$line): ?>class="zp-dataset-first-line"<?php endif ?>><?php echo $text; ?></p>

              <?php endforeach ?>
            </td>

            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <p class="zp-dataset-notice">
        <?php echo $this->__('* Click on a cell to insert the value or click on the first column to insert the entire row.'); ?>
      </p>
    </div>
    <?php endforeach; ?>
  </div>

  <?php $title = $this->__('Database look-up'); ?>

  <button id="zp-dataset-button" class="button hidden" title="<?php echo $title; ?>" type="button">
    <span><span><?php echo $title; ?></span></span>
  </button>

<?php
  }

  /**
   * @deprecated Replaced with camelCased version
   */
  public function get_js ($context) {
    return $this->getJs($context);
  }

  public function getJs ($context) {
    $product = $context->getProduct();

    if (!$this->get_template_id($product))
      return false;

    if (!$details = $this->getTemplateDetailsForCurrentProduct())
      return false;

    $details['pages_number'] = count($details['pages']);

    $name = $product->getName();

    $previews = array();

    foreach ($details['pages'] as $page) {
      $guid = explode('preview/', $page['preview-image']);

      echo '<img src="', $this->get_preview_url($guid[1]), '" alt="Printable ',
           $name, '" class="zp-hidden" />';
    }

    $session = Mage::getSingleton('core/session');

    if ($session->hasData('zetaprints-previews')) {
      $userInput = unserialize($session->getData('zetaprints-user-input'));

      $session->unsetData('zetaprints-previews');
    }

    $request = $this->_getRequest();

    //Check if the product page is requested with 'for-item' parameter
    $hasForItem = $request->has('for-item');

    //Check if the product page is requested
    //with 'update-first-preview' parameter
    $hasUpdateFirstPreview = $request->getParam('update-first-preview') == '1';

    //Check if the product page is requested with 'reorder' parameter
    $hasReorder = strlen($request->getParam('reorder')) == 36;

    $lastUrl = $session->getData('last_url');

    //Check if the product page is opened from the shopping cart
    //to update first preview image for cross-sell products)
    $isFromShoppingCart = strpos($lastUrl, 'checkout/cart') !== false;

    $updateFirstPreview = $hasForItem
                          || $hasReorder
                          || $hasUpdateFirstPreview
                          || $isFromShoppingCart
                          || $product->getConfigureMode();

    $preserveFields = $product->getConfigureMode()
                      || $hasReorder
                      || $hasUpdateFirstPreview
                      || $hasForItem;

    $preserveFields = !$preserveFields;

    $hasShapes = false;

    foreach ($details['pages'] as $page)
      if (isset($page['shapes'])) {
        $hasShapes = true;

        break;
      }

    $data = json_encode(array(
      'template_details' => $details,
      'is_personalization_step' => $this->is_personalization_step($context),
      'update_first_preview_on_load' => $updateFirstPreview,
      'preserve_fields' => $preserveFields,
      'has_shapes' => $hasShapes,
      'w2p_url' => Mage::getStoreConfig('webtoprint/settings/url'),
      'options' => $this->getCustomOptions(),
      'url' => array(
        'preview' => $this->_getUrl('web-to-print/preview'),
        'preview_download' => $this->_getUrl('web-to-print/preview/download'),
        'upload' => $this->_getUrl('web-to-print/upload'),
        'upload_by_url' => $this->_getUrl('web-to-print/upload/byurl'),
        'image' => $this->_getUrl('web-to-print/image/update'),
        'user-image-template'
          => $this->get_photo_thumbnail_url('image-guid.image-ext'),
        'edit-image-template' => $this->get_image_editor_url('')
      )
    ));
?>
<script type="text/javascript">
//<![CDATA[

// Global vars go here
var image_imageName = '';  //currently edited template image
var userImageThumbSelected = null;  //user selected image to edit
// Global vars end

jQuery(document).ready(function($) {
  <?php
  if (isset($userInput) && is_array($userInput))
    foreach ($userInput as $key => $value)
      echo '$(\'[name="' . $key . '"]\').val(\'' . $value . '\');\n';
  ?>

  zp = <?php echo $data ?>;

  edit_button_text = "<?php echo $this->__('Edit');?>";
  delete_button_text = "<?php echo $this->__('Delete'); ?>";
  save_text = "<?php echo $this->__('Save');?>";
  saved_text = "<?php echo $this->__('Saved');?>";
  update_preview_button_text = "<?php echo $this->__('Update preview'); ?>";
  use_image_button_text = "<?php echo $this->__('Use image'); ?>";
  selected_image_button_text = "<?php echo $this->__('Selected image'); ?>";

  updating_preview_image_text = "<?php echo $this->__('Updating preview image'); ?>"
  cannot_update_preview = "<?php echo $this->__('Cannot update the preview. Try again.'); ?>";
  cannot_update_preview_second_time = "<?php echo $this->__('Cannot update the preview. Try again or add to cart as is and we will update it manually.'); ?>";
  preview_sharing_link_error_text = "<?php echo $this->__('Error was occurred while preparing preview image'); ?>";
  uploading_image_error_text = "<?php echo $this->__('Error was occurred while uploading image'); ?>";
  notice_to_update_preview_text = "<?php echo $this->__('Update preview first!'); ?>";
  notice_to_update_preview_text_for_multipage_template = "<?php echo $this->__('Update all previews first!'); ?>";
  warning_user_data_changed = "<?php echo $this->__('Press Update Preview before adding to cart to include your latest changes'); ?>";

  click_to_close_text = "<?php echo $this->__('Click to close'); ?>";
  click_to_view_in_large_size = "<?php echo $this->__('Click to view in large size');?>";
  click_to_delete_text = "<?php echo $this->__('Click to delete'); ?>";
  click_to_edit_text = "<?php echo $this->__('Click to edit'); ?>";

  cant_delete_text = "<?php echo $this->__('Can\'t delete image'); ?>";
  delete_this_image_text = "<?php echo $this->__('Delete this image?'); ?>";

  personalization_form.apply(zp, [$]);
});
//]]>
</script>
<?php
  }
}
?>
