<?php

require_once 'Mage/Adminhtml/controllers/CustomerController.php';

class Netzarbeiter_GroupsCatalog_Override_Adminhtml_CustomerController extends Mage_Adminhtml_CustomerController
{
	/**
	 * Display customer group visible grid in customer account tab
	 */
	public function visibleProductsAction()
	{
		$this->_initCustomer();
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('groupscatalog/adminhtml_customer_edit_tab_visibleproducts')->toHtml()
		);
	}

	public function visibleProductsGridAction()
	{
		$this->_initCustomer();
		if (! Mage::registry('current_customer')->getId())
		{
			return;
		}
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('groupscatalog/adminhtml_customer_edit_tab_visibleproducts')->toHtml()
		);
	}
}