<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kris
 * Date: 11-4-25
 * Time: 20:11
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_Options_ZpoptionsController
  extends  Mage_Adminhtml_Controller_Action
{
  public function masscopyAction()
  {
    $request = $this->getRequest();
    $srcId =  $request->getParam('source'); // source product id
    $productIds = $request->getParam('product'); // affected products IDs
    if(!is_numeric($srcId)){
      $this->_getSession()->addError('Product ID should be an integer.');
      $this->_return();
      return;
    }elseif(!(int) $srcId > 0) { // if a dodgy srcId is passed, return
      $this->_getSession()->addError('Source product does not exist.');
      $this->_return();
      return;
    }
    $copier = Mage::getModel('zpoptions/copy');
    /* @var $copier ZetaPrints_Options_Model_Copy */
    if($copier->copy($srcId, $productIds)){
      $this->_getSession()->addSuccess($this->__('Options from product ID %s copied to product(s) %s', $srcId, implode(', ', $productIds)));
    }
    $this->_return(); // return to products page
  }

  protected function _return()
  {
    $this->_redirect('*/catalog_product');
  }

}
