<?php

class ZetaPrints_Fixedprices_Block_Catalog_Product_Edit_Fixedprices
 extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
    {
      /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::registry('product');
        $values = $product->getData();
        $helper = Mage::helper('fixedprices');
        /* @var $helper ZetaPrints_Fixedprices_Helper_Data */
        $attributes = $helper->getAttributes($product);


        $form = new Varien_Data_Form();
        $form->setDataObject($product);
        $fieldset = $form->addFieldset('fixed_price_fieldset',
                                      array(
                                      	'legend'=>Mage::helper('fixedprices')->__('Fixed Quantities'),
                                        'class' => 'fieldset-wide'
                                      ));
        $this->_setFieldset($attributes, $fieldset);
        $form->getElement(ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE)->setRenderer(
            $this->getLayout()->createBlock('fixedprices/catalog_product_edit_price_fixedprices')
        );
        /**
         * Set attribute default values for new product
         */
        if (!$product->getId()) {
          foreach ($attributes as $attribute) {
            if (!isset($values[$attribute->getAttributeCode()])) {
              $values[$attribute->getAttributeCode()] = $attribute->getDefaultValue();
            }
          }
        }

        if ($product->hasLockedAttributes()) {
          foreach (Mage::registry('product')->getLockedAttributes() as $attribute) {
            if ($element = $form->getElement($attribute)) {
              $element->setReadonly(true, true);
            }
          }
        }
        $form->setValues($values);
        $form->setFieldNameSuffix('product');

        $this->setForm($form);
    }
}
