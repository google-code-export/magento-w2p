<?php
/**
 * OpenERP data helper
 */
class ZetaPrints_WebToPrint_Helper_Data extends Mage_Core_Helper_Abstract {

  public function _getUrl($route, $params = array()) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS) {
      $params['_secure'] = true;
      return parent::_getUrl($route, $params);
    }

    return parent::_getUrl($route, $params);
  }

  public function get_preview_url ($guid) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/preview/get',
                              array('guid' => $guid, '_secure' => true) );

    return Mage::getStoreConfig('zpapi/settings/w2p_url') . '/preview/' . $guid;
  }

  public function get_thumbnail_url ($guid, $width = 0, $height = 0) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/thumbnail/get',
                              array('guid' => $guid, 'width' => $width,
                              'height' => $height, '_secure' => true) );

    //Check if width or height is setted
    if (($width + $height) != 0)
      $guid = str_replace('.', "_{$width}x{$height}.", $guid);

    return Mage::getStoreConfig('zpapi/settings/w2p_url') . '/thumb/' . $guid;
  }

  public function get_photo_thumbnail_url ($guid, $width = 0, $height = 0) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/photothumbnail/get',
                              array('guid' => $guid, 'width' => $width,
                              'height' => $height, '_secure' => true) );

    //Check if width or height is setted
    if (($width + $height) != 0)
      $guid = str_replace('.', "_{$width}x{$height}.", $guid);

    return Mage::getStoreConfig('zpapi/settings/w2p_url') . '/photothumbs/' . $guid;
  }

  public function get_image_editor_url ($guid) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/image/',
                              array('id' => $guid, 'iframe' => 1,
                                    '_secure' => true) );

    return parent::_getUrl('web-to-print/image/',
                            array('id' => $guid, 'iframe' => 1) );
  }

  public function create_url_for_product ($product, $query_params) {
    //Get model for URL
    $url_model = $product->getUrlModel();

    $params = array();

    //Set parameter for Session ID in URL
    if (!Mage::app()->getUseSessionInUrl())
      $params['_nosid'] = true;

    //Add query parameters to URL
    $params['_query'] = $query_params;

    return $url_model->getUrl($product, $params);
  }

  protected function replace_template_values_from_cart_item ($template, $item_id) {
    $item = Mage::getSingleton('checkout/session')
              ->getQuote()
              ->getItemById($item_id);

    if (!($item && $item->getId()))
      return;

    $option_model = $item->getOptionByCode('info_buyRequest');
    $options = unserialize($option_model->getValue());

    //Item previews stored as comma-separated string in a quote.
    //Convert it to array.
    //$previews = explode(',', $options['zetaprints-previews']);

    //Replace previews in XML
    //foreach ($previews as $index => $preview) {
    //  $template->Pages->Page[$index]['PreviewImage'] = "preview/{$preview}";
    //  $template->Pages->Page[$index]['ThumbImage'] = "thumb/{$preview}";
    //}

    $fields = array();

    //Prepare fields' values
    foreach ($options as $key => $value)
      if (strpos($key, 'zetaprints-') !== false) {
        $key = substr($key, 11);

        if (strpos($key, '#') === 0 || strpos($key, '_') === 0) {
          $key = str_replace(array('_', "\x0A"), array(' ', '.'), substr($key, 1));

          $fields[$key] = $value;
        }
      }

    //Replace text field values in XML
    foreach ($template->Fields->Field as $field) {
      $name = (string) $field['FieldName'];

      if (isset($fields[$name]))
        $field['Value'] = $fields[$name];
    }

    //Replace image field values in XML
    foreach ($template->Images->Image as $image) {
      $name = (string) $image['Name'];

      if (isset($fields[$name]))
        $image['Value'] = $fields[$name];
    }
  }

  public function replace_preview_images ($template, $previews) {
    $page_number = 0;

    foreach ($template->Pages->Page as $page) {
      $guid = explode('preview/', $previews[$page_number++]);

      $page['PreviewImageUpdated'] = $this->get_preview_url($guid[1]);
      $page['ThumbImageUpdated']
                                 = $this->get_thumbnail_url($guid[1], 100, 100);
    }
  }

  public function update_preview_images_urls ($template) {
    foreach ($template->Pages->Page as $page) {
      $preview_guid = explode('preview/', (string) $page['PreviewImage']);
      $thumb_guid = explode('thumb/', (string) $page['ThumbImage']);

      $page['PreviewImage'] = $this->get_preview_url($preview_guid[1]);
      $page['ThumbImage'] = $this->get_thumbnail_url($thumb_guid[1], 100, 100);
    }
  }
}
