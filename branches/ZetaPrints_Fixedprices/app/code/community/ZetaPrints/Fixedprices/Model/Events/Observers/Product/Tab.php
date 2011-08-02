<?php
class ZetaPrints_Fixedprices_Model_Events_Observers_Product_Tab
{
  protected $_old_attribute_block;

  /**
   * Add fixed prices tab in admin
   *
   * @param Varien_Event_Observer $observer
   */
  public function addFixedPrices(Varien_Event_Observer $observer)
  {
    $product = Mage::registry('product');
    if(!$product
       || $product->isGrouped()
       || $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE)
    {
      return;
    }
    $block = $observer->getEvent()->getBlock();

    if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tabs) {
      if ($this->_getRequest()->getActionName() == 'edit' || $this->_getRequest()->getParam('type')) {
        $block->addTab(ZetaPrints_Fixedprices_Helper_Data::TAB_NAME, array(
          'label'     => 'Fixed Quantities',
          'content'   => $block->getLayout()->createBlock('fixedprices/catalog_product_edit_fixedprices')->toHtml(),
        ));
      }
    }
  }

  /**
   * Remove duplicate form elements
   *
   * @param Varien_Event_Observer $observer
   */
  public function removeDuplicates(Varien_Event_Observer $observer)
  {
    $form = $observer->getEvent()->getForm();
    /* @var $form Varien_Data_Form */

    $attributeCodes = Mage::helper('fixedprices')->getAttributeCodes();
    foreach ($attributeCodes as $ac) {
      $this->_removeDuplicates($ac, $form);
    }
  }

  /**
   * Remove attribute code from form
   *
   * @param int $elId
   * @param Varien_Data_Form $form
   */
  protected function _removeDuplicates($elId, $form)
  {
    $element = $form->getElement($elId);
    if($element){                        // if our element is present
      $elements = $form->getElements();  // get form elements
      if($elements->count() == 1){       // form gets only one root element and that is fieldset
        $fieldset = $elements[0];        // get it !!!
      }
    }
    if($element){
      $form->removeField($elId);
      if(isset($elId) && $fieldset instanceof Varien_Data_Form_Element_Fieldset){
        $fieldset->removeField($elId);
      }
    }

    return $this;
  }

  /**
   * Shortcut to getRequest
   */
  protected function _getRequest()
  {
    return Mage::app()->getRequest();
  }
}
