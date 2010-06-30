<?php

if (!defined('ZP_API_VER')) include('zp_api.php');

class ZetaPrints_Zpapi_Model_Observer
    extends Mage_Core_Model_Abstract
{
  /**
    * Export an order on completion
    *
    * @triggeredby: checkout_type_onepage_save_order_after
    *               (when sales_order_place_after fires the order doesn't have an id yet)
    * @param $eventArgs array "order"=>$order
    */
  public function exportOnOrderEvent($observer) {
    $order = $observer->getEvent()->getOrder();

    foreach ($order->getAllItems() as $item) {
      $options = $item->getProductOptions();

      if (!isset($options['info_buyRequest']['zetaprints-order-id']))
        continue;

      $url = Mage::getStoreConfig('zpapi/settings/w2p_url');

      $order_details = zetaprints_complete_order($url, Mage::getStoreConfig('zpapi/settings/w2p_key'), $options['info_buyRequest']['zetaprints-order-id']);

      if (!$order_details) {
        $order->setState('problems', true, 'Problem in order details receiving or in order completion on ZetaPrints')
          ->save;
        return;
      }

      $types = array('pdf', 'gif', 'png', 'jpeg', 'cdr');

      foreach ($types as $type)
        if (strlen($order_details[$type]))
          $options['info_buyRequest']['zetaprints-file-'.$type] = $url . '/' . $order_details[$type];

      $options['info_buyRequest']['zetaprints-order-id'] = $order_details['guid'];

      $item->setProductOptions($options);
    }

    return $this;
  }

  /** Grab the Magento sale order with the requested id
    * @param order : Magento sale order
    * @return
    */
  function saveSaleOrder($order)
  {
    $productArray=array();  // sale order line product wrapper

    // Magento required models
    $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
    $logic = Mage::getModel('zpapi/w2puser');
    $errors = "";
    // walk the sale order lines
    foreach ($order->getAllItems() as $item)
    {
      //print_r($item);exit();
      $logic->saveOrder($item->getSku());
      /*$productArray[] = array(
        "product_sku" => $item->getSku(),
        "product_magento_id" => $item->getProductId(),
        "product_name" => $item->getName(),
        "product_qty" => $item->getQtyOrdered(),
        "product_price" => $item->getPrice(),
        "product_discount_amount" => $item->getDiscountAmount(),
        "product_row_price" => $item->getPrice() - $item->getDiscountAmount(),
      );*/
      //Save order o day
    }
    return $errors;
  }
}
?>
