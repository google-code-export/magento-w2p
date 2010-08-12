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

      //If the item was reordered skip it (we can't complete already completed
      //order on ZetaPrints)
      if (isset($options['info_buyRequest']['zetaprints-reordered'])
          && $options['info_buyRequest']['zetaprints-reordered'] === true)
        continue;

      $url = Mage::getStoreConfig('zpapi/settings/w2p_url');
      $key = Mage::getStoreConfig('zpapi/settings/w2p_key');

      //GUID for ZetaPrints order which was saved on Add to cart step
      $current_order_id = $options['info_buyRequest']['zetaprints-order-id'];
      //New GUID for completed order
      $new_order_id = zetaprints_generate_guid();

      $order_details = zetaprints_complete_order($url, $key, $current_order_id,
                                                                 $new_order_id);

      if (!$order_details) {
        //Check if saved order exists on ZetaPrints...
        if (zetaprints_get_order_details($url, $key, $current_order_id)) {
          //... then try again to complete the order
          $order_details = zetaprints_complete_order($url, $key,
                                              $current_order_id, $new_order_id);

          //If it fails...
          if (!$order_details) {
            //... then set state for order in M. as problems and add comment
            $order->setState('problems', true,
                'Use the link to ZP order to troubleshoot.')
              ->save();
            return;
          }
        }
        //... otherwise try to get order details by new GUID and if completed
        //order doesn't exist in ZetaPrints...
        else if (!$order_details =
                       zetaprints_get_order_details($url, $key, $new_order_id)) {
          //... then set state for order in M. as problems and add comment about
          //failed order on ZetaPrints side.
          $order->setState('problems', true,
                  'Failed order. Contact admin@zetaprints.com ASAP to resolve.')
            ->save();

          return;
        }
      }

      $types = array('pdf', 'gif', 'png', 'jpeg', 'cdr');

      foreach ($types as $type)
        if (strlen($order_details[$type]))
          $options['info_buyRequest']['zetaprints-file-'.$type] = $url . '/' . $order_details[$type];

      $options['info_buyRequest']['zetaprints-order-id'] = $order_details['guid'];

      $item->setProductOptions($options)->save();
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
