<?php

class ZetaPrints_WebToPrint_Model_Events_Observer {

  public function create_zetaprints_order ($observer) {
    $quote_item = $observer->getEvent()->getQuoteItem();

    if ($quote_item->getParentItem())
      $quote_item = $quote_item->getParentItem();

    $option_model = $quote_item->getOptionByCode('info_buyRequest');
    $options = unserialize($option_model->getValue());

    if (!(isset($options['zetaprints-TemplateID']) || isset($options['zetaprints-previews'])))
      return;

    if (!(isset($options['zetaprints-TemplateID']) && isset($options['zetaprints-previews'])))
      Mage::throwException('Not enough ZetaPrints template parameters');

    $params = array();

    $params['TemplateID'] = $options['zetaprints-TemplateID'];
    $params['Previews'] = $options['zetaprints-previews'];

    $w2p_user = Mage::getModel('zpapi/w2puser');

    //$params['ApiKey'] = $w2p_user->key;

    $user_credentials = $w2p_user->get_credentials();
    $params['ID'] = $user_credentials['id'];
    $params['Hash'] = zetaprints_generate_user_password_hash($user_credentials['password']);

    $order_id = zetaprints_get_order_id (Mage::getStoreConfig('zpapi/settings/w2p_url'), $w2p_user->key, $params);

    if (!preg_match('/^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/', $order_id))
      Mage::throwException('ZetaPrints error');

    $options['zetaprints-order-id'] = $order_id;
    $option_model->setValue(serialize($options));

    Mage::getSingleton('core/session')->unsetData('zetaprints-previews');
  }

  public function store_template_values ($observer) {
    $request = $observer->getEvent()->getControllerAction()->getRequest();

    if (!$request->has('zetaprints-previews'))
      return;

    Mage::getSingleton('core/session')->setData('zetaprints-previews', $request->getParam('zetaprints-previews'));

    $user_input = array();
    foreach ($request->getParams() as $key => $value)
      if (strpos($key, 'zetaprints-') !== false) {
        $_key = substr($key, 11);
        $_key = substr($_key, 0, 1).str_replace('_', ' ', substr($_key, 1));
        $user_input['zetaprints-' . $_key] = str_replace("\r\n", "\\r\\n", $value);
      }

    Mage::getSingleton('core/session')->setData('zetaprints-user-input', serialize($user_input));
  }

  public function set_required_options ($observer) {
    $product = $observer->getEvent()->getProduct();

    if ($product->hasWebtoprintTemplate() && $product->getWebtoprintTemplate())
      $product->setRequiredOptions(true);
  }

  public function process_images ($observer) {
    $product = $observer->getEvent()->getProduct();

    if (!$product->hasWebtoprintTemplate()) return;

    $template_guid = $product->getWebtoprintTemplate();
    $template_guid_orig = $product->getOrigData('webtoprint_template');

    if (($template_guid == $template_guid_orig) && !Mage::registry('webtoprint-template-changed'))
     return;

    $media_gallery = $product->getMediaGallery();

    if (is_array($media_gallery))
      foreach ($media_gallery as &$item)
        if(!is_array($item) && strlen($item) > 0)
          $item = Zend_Json::decode($item);
    else
      $media_gallery = array('images' => array());


    if ($template_guid) {
      $template = Mage::getModel('webtoprint/template')->load($template_guid);

      if (!$template->getId()) return;

      $xml = new SimpleXMLElement($template->getXml());
    }

    //Trying to remove images which no longer exist in template
    foreach ($media_gallery['images'] as &$image) {
      if (!(isset($image['file'])
          && strpos(basename($image['file']), 'zetaprints_') === 0)) continue;

      if (!$template_guid) {
        $image['removed'] = 1;

        if ($product->getSmallImage() == $image['file'])
          $product->setSmallImage('no_selection');

        if ($product->getThumbnail() == $image['file'])
          $product->setThumbnail('no_selection');

        continue;
      }

      foreach ($xml->Pages[0]->Page as $page) {
        $image_id = basename((string)$page['PreviewImage']);

        if (strpos(basename($image['file']), 'zetaprints_' . $image_id))
          break;

        $image['removed'] = 1;

        if ($product->getSmallImage() == $image['file'])
          $product->setSmallImage('no_selection');

        if ($product->getThumbnail() == $image['file'])
          $product->setThumbnail('no_selection');
      }
    }

    $product->setMediaGallery($media_gallery);

    if (!$template_guid) return;

    $first_image = true;

    foreach ($media_gallery['images'] as $image)
      if (!isset($image['removed']) && $image['disabled'] === 1) {
        $first_image = false;
        break;
      }

    $attributes = $product->getTypeInstance(true)->getSetAttributes($product);
    $gallery = $attributes['media_gallery'];

    foreach ($xml->Pages[0]->Page as $page) {
      $image_id = basename((string)$page['PreviewImage']);

      $image_exists = false;
      if (is_array($media_gallery))
        foreach ($media_gallery['images'] as &$image)
          if (!isset($image['removed']) && isset($image['file'])
              && strpos(basename($image['file']), "zetaprints_{$image_id}") === 0)

              $image_exists = true;

      if ($image_exists) break;

      $client = new Varien_Http_Client(Mage::getStoreConfig('zpapi/settings/w2p_url') . '/' . (string)$page['PreviewImage']);
      $response = $client->request()->getHeaders();

      $filename = Mage::getBaseDir('var') . "/tmp/zetaprints_{$image_id}";
      file_put_contents($filename, $client->request()->getBody());

      $file = $gallery->getBackend()->addImage($product, $filename, null, true);

      $data = array('label' => (string)$page['Name']);

      if ($first_image) {
        if (!$product->getSmallImage() || $product->getSmallImage() == 'no_selection')
          $product->setSmallImage($file);

        if (!$product->getThumbnail() || $product->getThumbnail() == 'no_selection')
          $product->setThumbnail($file);

        $first_image = false;
      } else
        $data['exclude'] = 0;

      $gallery->getBackend()->updateImage($product, $file, $data);
    }
  }

  public function specify_option_message ($observer) {
    $request = Mage::app()->getRequest();

    if ($request->getParam('options')) {
      $product = $observer->getEvent()->getProduct();

      if ($product->hasWebtoprintTemplate() && $product->getWebtoprintTemplate()) {
        $notice = $product->getTypeInstance(true)->getSpecifyOptionMessage();
        Mage::getSingleton('catalog/session')->addNotice($notice . ' and/or personalize it');
        $request->setParam('options', 0);
      }
    }
  }
}

?>
