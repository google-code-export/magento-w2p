<?php
class Biinno_Api_Model_Observer
    extends Mage_Core_Model_Abstract 
{
	/**
	  * Export an order on completion
	  *
	  * @triggeredby: checkout_type_onepage_save_order_after 
	  *               (when sales_order_place_after fires the order doesn't have an id yet)
	  * @param $eventArgs array "order"=>$order
	  */
	public function exportOnOrderEvent($observer) 
	{
		//if (Mage::getStoreConfig('w2p/settings/enabled'))
		//{
			$order = $observer->getEvent()->getOrder();
			$saleOrder = $this->saveSaleOrder($order);
		//}
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
		$logic = Mage::getModel('api/w2puser');
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
