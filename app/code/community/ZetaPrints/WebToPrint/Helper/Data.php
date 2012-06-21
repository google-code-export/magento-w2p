<?php

class ZetaPrints_WebToPrint_Helper_Data extends Mage_Core_Helper_Abstract
  implements ZetaPrints_Api {

  //ZetaPrints cookie life time in seconds (180 days)
  const COOKIE_LIFETIME = 15552000;

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

    return Mage::getStoreConfig('webtoprint/settings/url') . '/preview/'
           . $guid;
  }

  public function get_thumbnail_url ($guid, $width = 0, $height = 0) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/thumbnail/get',
                              array('guid' => $guid, 'width' => $width,
                              'height' => $height, '_secure' => true) );

    //Check if width or height is setted
    if (($width + $height) != 0)
      $guid = str_replace('.', "_{$width}x{$height}.", $guid);

    return Mage::getStoreConfig('webtoprint/settings/url') . '/thumb/' . $guid;
  }

  public function get_photo_thumbnail_url ($guid, $width = 0, $height = 0) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/photothumbnail/get',
                              array('guid' => $guid, 'width' => $width,
                              'height' => $height, '_secure' => true) );

    //Check if width or height is setted
    if (($width + $height) != 0)
      $guid = str_replace('.', "_{$width}x{$height}.", $guid);

    return Mage::getStoreConfig('webtoprint/settings/url') . '/photothumbs/'
           . $guid;
  }

  public function get_image_editor_url ($guid) {
    if ($this->_getRequest()->getScheme() == Zend_Controller_Request_Http::SCHEME_HTTPS)
      return parent::_getUrl('web-to-print/image/',
                              array('id' => $guid,
                                    '_secure' => true) );

    return parent::_getUrl('web-to-print/image/', array('id' => $guid) );
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

  public function replacePreviewImages ($template, $previews) {
    $pageNumber = 0;
    $numberOfPreviews = 0;

    foreach ($template->Pages->Page as $page)
      if (isset($previews[$pageNumber]) && $guid = $previews[$pageNumber++]) {
        $numberOfPreviews++;

        $page['PreviewImageUpdated'] = 'preview/' . $guid;
      }

    return $numberOfPreviews;
  }

  public function generateImageUrls ($template) {
    foreach ($template->Pages->Page as $page) {
      $preview = explode('preview/', (string) $page['PreviewImage']);
      $thumb = explode('thumb/', (string) $page['ThumbImage']);

      $page['PreviewUrl'] = $this->get_preview_url($preview[1]);
      $page['ThumbUrl'] = $this->get_thumbnail_url($thumb[1], 100, 100);

      if (isset($page['Static']) && (bool) $page['Static']
          && !isset($page['PreviewImageUpdated']))
        $page['PreviewImageUpdated'] = (string) $page['PreviewImage'];

      if (isset($page['PreviewImageUpdated'])) {
        $preview = explode('preview/', (string) $page['PreviewImageUpdated']);

        $page['ThumbImageUpdated'] = 'thumb/' . $preview[1];

        $page['PreviewUrlUpdated'] = $this->get_preview_url($preview[1]);
        $page['ThumbUrlUpdated'] = $this
                                     ->get_thumbnail_url($preview[1], 100, 100);
      }
    }
  }

  function get_zetaprints_credentials () {
    $session = Mage::getSingleton('customer/session');

    if ($has_customer = $session->isLoggedIn()) {
      $customer = $session->getCustomer();

      if ($id = $customer->getZetaprintsUser()) {
        $this->restore_zp_cookie($id);

        return array('id' => $id,
                     'password' => $customer->getZetaprintsPassword() );
      }
    }

    $credentials = null;

    if ($id = $session->getData('w2puser')) {
      $session->setZetaprintsUser($id);
      $session->setZetaprintsPassword($session->getData('w2ppass'));

      $session->unsetData('w2puser');
      $session->unsetData('w2ppass');
    }

    if ($id = $session->getZetaprintsUser())
      $credentials = array('id' => $id,
                           'password' => $session->getZetaprintsPassword() );
    else
      $credentials = $this->get_credentials_from_zp_cookie();

    if (!$credentials) {
      $id = zetaprints_generate_guid();
      $password = zetaprints_generate_password();

      $url = Mage::getStoreConfig('webtoprint/settings/url');
      $key = Mage::getStoreConfig('webtoprint/settings/key');

      if (zetaprints_register_user($url, $key, $id, $password)) {
        $credentials = array('id' => $id, 'password' => $password);

        $this->set_credentials_to_zp_cookie($credentials);
      }
    } else
      $this->restore_zp_cookie($credentials['id']);

    if (!$credentials)
      return null;

    if ($has_customer) {
      $customer->setZetaprintsUser($credentials['id']);
      $customer->setZetaprintsPassword($credentials['password']);

      $customer->save();
    } else {
      $session->setZetaprintsUser($credentials['id']);
      $session->setZetaprintsPassword($credentials['password']);
    }

    return $credentials;
  }

  function get_credentials_from_zp_cookie () {
    //Get ZetaPrints user id from cookie
    $id = Mage::getSingleton('core/cookie')->get('ZP_ID');

    if (!$id)
      return false;

    //connecting to DB
    $db = Mage::getSingleton('core/resource')->getConnection('core_write');

    //Get password for user from DB
    $password = $db
      ->fetchOne("select pass from zetaprints_cookies where user_id=?",
                 array($id));

    //If there's no password for user in DB then...
    if (strlen($password) != 6) {
      //... remove cookie
      Mage::getSingleton('core/cookie')->delete('ZP_ID');

      return false;
    }

    return array('id' => $id, 'password' => $password);
  }

  function set_credentials_to_zp_cookie ($credentials) {
    Mage::getSingleton('core/cookie')->set('ZP_ID',
                                           $credentials['id'],
                                           self::COOKIE_LIFETIME );

    //connecting to DB
    $db = Mage::getSingleton('core/resource')->getConnection('core_write');
    //adding password to DB
    $db->insert('zetaprints_cookies',
                array('user_id' => $credentials['id'],
                      'pass'=> $credentials['password']) );
  }

  function restore_zp_cookie ($id) {
    Mage::getSingleton('core/cookie')->set('ZP_ID', $id, self::COOKIE_LIFETIME);
  }

  function getCustomOptions ($path = null) {
    return Mage::getSingleton('webtoprint/config')->getOptions($path);
  }

  public function getProfileByName ($name) {
    $collection = Mage::getModel('dataflow/profile')
                    ->getCollection();

    $collection
      ->getSelect()
      ->where('name = ?', $name);

    if ($collection->count())
      return $collection->getFirstItem();

    return null;
  }

  public function getCategory ($name, $createIfNotExists = false,
                               $parent = null) {

    if ($parent && $parent->getId()) {
      foreach ($parent->getChildrenCategories() as $child)
        if ($child->getName() == $name)
          return $child;
    } else {
      $collection = Mage::getModel('catalog/category')
                      ->getCollection()
                      ->addAttributeToFilter('name', $name);

      if ($collection->count())
        return $collection->getFirstItem();
    }

    if (!$createIfNotExists)
      return;

    if ($parent && $parent->getId())
      $rootCategory = $parent;
    else {
      $collection = Mage::getModel('catalog/category')
                      ->getCollection()
                      ->addAttributeToFilter('parent_id', 1);

      if (count($collection) != 1)
        return null;

      $rootCategory = $collection->getFirstItem();

      if (!$rootCategory->getId())
        return null;
    }

    $model = Mage::getModel('catalog/category');

    $model
      ->setStoreId($rootCategory->getStoreId())
      ->setData(array(
                  'name' => $name,
                  'is_active' => 1,
                  'include_in_menu' => 1 ))
      ->setPath($rootCategory->getPath())
      ->setAttributeSetId($model->getDefaultAttributeSetId());

    try {
      $model->save();
    } catch (Exception $e) {
      return null;
    }

    return $model;
  }


  public function getTemplateDetailsByGUID ($guid) {
    $template = Mage::getModel('webtoprint/template')->load($guid);

    if (!$template->getId())
      return null;

    try {
      $xml = new SimpleXMLElement($template->getXml());
    } catch (Exception $e) {
      Mage::log("Exception: {$e->getMessage()}");

      return null;
    }

    if (!$xml)
      return null;

    return zetaprints_parse_template_details($xml);
  }

  public function getTemplateGuidFromProduct ($product) {

    //Get template GUID from webtoprint_template attribute if such attribute exists
    //and contains value, otherwise use product SKU as template GUID
    if (!($product->hasWebtoprintTemplate()
          && $templateGuid = $product->getWebtoprintTemplate()))
      $templateGuid = $product->getSku();

    if (strlen($templateGuid) != 36)
      return null;

    return $templateGuid;
  }

  private function replace_user_input_from_order_details ($template, $order_guid) {
    $url = Mage::getStoreConfig('webtoprint/settings/url');
    $key = Mage::getStoreConfig('webtoprint/settings/key');

    $order_details = zetaprints_get_order_details($url, $key, $order_guid);

    if (!$order_details)
      return;

    //Replace text field values from order details
    foreach ($template->Fields->Field as $field)
      foreach ($order_details['template-details']['pages'] as $page)
        if ($value = $page['fields'][(string) $field['FieldName']]['value']) {
          $field['Value'] = $value;
          break;
        }

    //Replace image field values from order details
    foreach ($template->Images->Image as $image)
      foreach ($order_details['template-details']['pages'] as $page)
        if ($value = $page['images'][(string) $image['Name']]['value']) {
          $image['Value'] = $value;
          break;
        }
  }

  public function getTemplateXmlForCurrentProduct () {
    if (! $xml = Mage::registry('webtoprint-template-xml')) {
      if (! $product = Mage::registry('product'))
        return;

      if (! $product->getId())
        return;

      if (! $templateGuid = $this->getTemplateGuidFromProduct($product))
        return;

      //This flag shows a status of web-to-print user registration
      $user_was_registered = true;

      //Check a status of web-to-print user registration on ZetaPrints
      //and if it's not then set user_was_registered flag to false
      if (!($user_credentials = $this->get_zetaprints_credentials())) {
        $template = Mage::getModel('webtoprint/template')->load($templateGuid);

        if ($template->getId())
          $user_was_registered = false;
      }

      //Remember a status of web-to-print user registrarion for subsequent
      //function calls
      Mage::register('webtoprint-user-was-registered', $user_was_registered);

      if ($user_was_registered) {
        $url = Mage::getStoreConfig('webtoprint/settings/url');
        $key = Mage::getStoreConfig('webtoprint/settings/key');

        $data = array(
          'ID' => $user_credentials['id'],
          'Hash' => zetaprints_generate_user_password_hash(
                                              $user_credentials['password']) );

        if ($product->getConfigureMode()
            && $orderId = Mage::registry('webtoprint-order-id'))
          $data['OrderID'] = $orderId;

        $template_xml = zetaprints_get_template_details_as_xml($url, $key,
                                                        $templateGuid, $data);

        //!!! Load XML for the template from DB
        //    if loading from ZP was unsuccessful
      } else
        $template_xml = $template->getXml();

      try {
        $xml = new SimpleXMLElement($template_xml);
      } catch (Exception $e) {
        Mage::log("Exception: {$e->getMessage()}");

        return false;
      }

      $session = Mage::getSingleton('core/session');

      if ($session->hasData('zetaprints-previews')) {
        $previews = unserialize($session->getData('zetaprints-previews'));

        if (is_array($previews)
            && !$this->replacePreviewImages($xml, $previews))
          $session->setData('zetaprints-previews', '');
      }

      $this->generateImageUrls($xml);

      //If product page was requested with reorder parameter...
      if ($this->_getRequest()->has('reorder')
          && strlen($this->_getRequest()->getParam('reorder')) == 36)
        //...then replace field values from order details
        $this->replace_user_input_from_order_details($xml,
                                    $this->_getRequest()->getParam('reorder'));

      //If product page was requested with for-item parameter...
      if ($this->_getRequest()->has('for-item'))
        //...then replace various template values from item's options
        $this->replace_template_values_from_cart_item($xml,
                                    $this->_getRequest()->getParam('for-item'));

      Mage::register('webtoprint-template-xml', $xml);
    }

    return $xml;
  }

  public function getTemplateDetailsForCurrentProduct () {
    if (! $temlateDetails = Mage::registry('webtoprint-template-details')) {
      if (! $xml = $this->getTemplateXmlForCurrentProduct())
        return;

      $temlateDetails = zetaprints_parse_template_details($xml);

      Mage::register('webtoprint-template-details', $temlateDetails);
    }

    return $temlateDetails;
  }

  public function get_template_id ($product) {
    if ($template_guid = $this->getTemplateGuidFromProduct($product))
      return Mage::getModel('webtoprint/template')
               ->getResource()
               ->getIdByGuid($template_guid);
  }

  public function completeZetaPrintsOrder ($id) {
    $url = Mage::getStoreConfig('webtoprint/settings/url');
    $key = Mage::getStoreConfig('webtoprint/settings/key');

    //New GUID for completed order
    $newId = zetaprints_generate_guid();

    $details = zetaprints_complete_order($url, $key, $id, $newId);

    if (!$details) {
      //_zetaprints_debug('Order wasn\'t completed '
      //                  . "(old ID: {$id}, new ID: {$newId})");

      //Check if saved order exists on ZetaPrints...
      if (zetaprints_get_order_details($url, $key, $id)) {
        //_zetaprints_debug('Order with old ID exists '
        //                  . "(old ID: {$id}, new ID: {$newId})");

        //... then try again to complete the order
        $details = zetaprints_complete_order($url, $key, $id, $newId);

        //If it fails...
        if (!$details) {
          //_zetaprints_debug('Order wasn\'t completed second time '
          //                  . "(old ID: {$id}, new ID: {$newId})");

          $message = $this->__('Use the link to ZP order to troubleshoot.');

          //... then return error.
          return array('error' => true,
                       'message' => $message);
        }
      }
      //... otherwise try to get order details by new GUID and if completed
      //order doesn't exist in ZetaPrints...
      else if (!$details = zetaprints_get_order_details($url, $key, $newId)) {
        //_zetaprints_debug('Orders with old and new ID don\'t exist '
        //                  . "(old ID: {$id}, new ID: {$newId})");

        $message = $this
             ->__('Failed order. Contact admin@zetaprints.com ASAP to resolve.');

        //... then return error.
        return array('error' => true,
                     'message' => $message);
      }
    }

    $data = array('error' => false,
                  'files' => array());

    $types = array('pdf', 'gif', 'png', 'jpeg', 'cdr');

    foreach ($types as $type)
      if (strlen($details[$type]))
          $data['files'][$type] = $url . '/' . $details[$type];

    $data['id'] = $details['guid'];

    return $data;
  }

  public function completeOrderItem ($item) {
    $options = $item->getProductOptions();

    if (isset($options['info_buyRequest']['zetaprints-order-completed']))
      return;

    if (isset($options['info_buyRequest']['zetaprints-dynamic-imaging'])
        && $options['info_buyRequest']['zetaprints-dynamic-imaging'])
      return;

    if (!isset($options['info_buyRequest']['zetaprints-order-id']))
      return;

    
    //If the item was reordered skip it (we can't complete already completed
    //order on ZetaPrints)
    if (isset($options['info_buyRequest']['zetaprints-reordered'])
        && $options['info_buyRequest']['zetaprints-reordered'])
      return;

    //GUID for ZetaPrints order which was saved on Add to cart step
    $id = $options['info_buyRequest']['zetaprints-order-id'];

    //Complete order on ZetaPrints
    $result = $this->completeZetaPrintsOrder($id);

    //If error then...
    if ($result['error']) {
      //... set state for order in M. as problems and add comment
      $item
        ->getOrder()
        ->setState('problems', true, $result['message'])
        ->save();

      return;
    }

    //Mark order item as completed on ZP
    $options['info_buyRequest']['zetaprints-order-completed'] = true;

    //Remember GUID of completed order
    $options['info_buyRequest']['zetaprints-order-id'] = $result['id'];

    //Save links to generated files
    foreach ($result['files'] as $type => $link)
      $options['info_buyRequest']['zetaprints-file-'.$type] = $link;

    //Update options and save the order item
    $item
      ->setProductOptions($options)
      ->save();
  }
}
