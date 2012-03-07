<?php

class ZetaPrints_WebToPrint_OrderController
  extends Mage_Adminhtml_Controller_Action
  implements ZetaPrints_Api {

  public function completeAction () {
    $request = $this->getRequest();
  
    if (!$request->has('item'))
      return;

    $item = $request->get('item');

    $item = Mage::getModel('sales/order_item')->load($item);

    if (!$item->getId())
      return;

    Mage::helper('webtoprint')->completeOrderItem($item);

    $this->_redirect('adminhtml/sales_order/view',
                     array('order_id' => $item->getOrder()->getId()));
  }
}
?>
