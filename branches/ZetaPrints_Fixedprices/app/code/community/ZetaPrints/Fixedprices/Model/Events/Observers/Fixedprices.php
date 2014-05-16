<?php

class ZetaPrints_Fixedprices_Model_Events_Observers_Fixedprices
{
  const COOKIE_NAME = 'fp_items';
  public function getItemId($observer){
    $quote_items = $observer->getEvent()->getCart()->getItems(); // get all items
    $ids = array();

    foreach ($quote_items as $quote_item){ // loop them
      /* @var $quote_item Mage_Sales_Model_Quote_Item */
      $product = $quote_item->getProduct();
      if ($product->getData(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE)){ // if item product uses FQ
        $id = $quote_item->getId(); // get quote item id - it should be unique for each product you add


        if(!in_array($id, $ids)){
          $ids[] = $id;
        }
      }
    } // end foreach

    $cookie = implode(',', $ids);
    $session = Mage::getSingleton('checkout/session');
    /* @var $session Mage_Checkout_Model_Session */
    $session->setData(self::COOKIE_NAME, $cookie);
  }

  /**
   * Set product to have required options
   *
   * If product has fixed prices, set it as if it has required options,
   * this way client cannot add to cart from product list page, but has to
   * pick a FQ.
   *
   * @event catalog_product_save_before
   * @param  Varien_Event_Observer $observer
   * @return void
   */
  public function setRequiredOption($observer)
  {
    $product = $observer->getEvent()->getProduct();
    /*
     * @var $product Mage_Catalog_Model_Product
     */
    if(Mage::helper('fixedprices')->isFixedPriceEnabled($product)){
      $product->setRequiredOprions(TRUE);
    }
  }

  public function addNotice ($observer) {
    $item = $observer
      ->getEvent()
      ->getQuoteItem();

    $helper = Mage::helper('fixedprices');

    $unit = $helper->getFixedUnits(
      $item->getProduct(),
      $item->getQty()
    );

    if ($unit === false)
      return;

    $option = array(
      'label' => '',
      'value' => $unit
    );

    $optionModels = Mage::getModel('sales/quote_item_option')
      ->getCollection()
      ->addItemFilter($item);

    foreach ($optionModels as $_optionModel)
      if ($_optionModel['code'] == 'additional_options') {
        $optionModel = $_optionModel;

        break;
      }

    if (isset($optionModel)) {
      $options = unserialize($optionModel->getValue());

      $options['fixedqtys_option'] = $option;

      $optionModel
        ->setValue(serialize($options))
        ->save();
    } else {
      $item->addOption(array(
        'code' => 'additional_options',
        'value' => serialize(array('fixedqtys_option' => $option))
      ));
    }
  }

  public function copyNoticeToOrderItem ($observer) {
    $option = $observer
      ->getItem()
      ->getOptionByCode('additional_options');

    if (!$option)
      return;

    $value = unserialize($option->getValue());

    if (!isset($value['fixedqtys_option']))
      return;

    $orderItem = $observer->getOrderItem();
    $options = $orderItem->getProductOptions();

    $options['options'][] = $value['fixedqtys_option'];

    $orderItem->setProductOptions($options);
  }
}
