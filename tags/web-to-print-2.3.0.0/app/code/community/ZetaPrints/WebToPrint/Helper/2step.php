<?php

class ZetaPrints_WebToPrint_Helper_2step
  extends ZetaPrints_WebToPrint_Helper_Data
{
  public function isPersonalisationStep () {
    return Mage::registry('webtoprint_is_personalisation_step');
  }

  public function getNextStepRoute ($product) {
    $data = array(
      'route' => 'catalog/product/view',
      'params' => array(
        'id' => $product->getId(),
        's' => $product->getUrlKey(),
        '_use_rewrite' => true,

        //Add personalization parameter to URL
        '_query' => array('personalization' => '1')
      )
    );

    if ($product->getCategoryId() && !$product->getDoNotUseCategoryId())
      $data['params']['category'] = $product->getCategoryId();

    $request = Mage::app()->getRequest();

    //Check if the product page was requested with reorder parameter
    //then proxy the parameter to personalization step
    if ($request->has('reorder'))
      $data['_query']['reorder'] = $request->getParam('reorder');

    //Check if the product page was requested with for-item parameter
    //then proxy the parameter to personalization step and ignore last
    //visited page (need it to distinguish cross-sell product and already
    //personalized product)
    if ($request->has('for-item'))
      $data['_query']['for-item'] = $request->getParam('for-item');
    else {
      //Check that the product page was opened from cart page (need for
      //automatic first preview update for cross-sell product)

      $pos = strpos(
        Mage::getSingleton('core/session')->getData('last_url'),
        'checkout/cart'
      );

      if ($pos !== false)
        //Send update-first-preview query parameter to personalization step
        $data['_query']['update-first-preview'] = 1;
    }

    return $data;
  }

  public function getImageType ($default = 'image') {
    return $this->isPersonalisationStep()
             ? $default
               : 'small_image';
  }

  public function getImageLabel ($context, $default = null) {
    if ($this->isPersonalisationStep())
      return $default ? $default : $context->getImageLabel();

    return $context->getImageLabel(null, 'small_image');
  }
}
