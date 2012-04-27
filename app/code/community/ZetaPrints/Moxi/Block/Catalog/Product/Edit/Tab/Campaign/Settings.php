<?php

class ZetaPrints_Moxi_Block_Catalog_Product_Edit_Tab_Campaign_Settings
  extends Mage_Adminhtml_Block_Catalog_Form {

  protected function _prepareForm () {
    $form = new Varien_Data_Form();

    $product = Mage::registry('product');

    $form->setDataObject($product);

    $fieldset
      = $form
        ->addFieldset('campaign_settings',
                      array(
                        'legend' => Mage::helper('moxi')
                                      ->__('Campaign Settings'),
                        //'class' => 'fieldset-wide'
                      ) );

    $resource = $product->getResource();

    $attributes = array(
                    $resource
                      ->getAttribute('openx_pricing_model')
                      ->setIsVisible(true),

                    $resource
                      ->getAttribute('openx_rate_price')
                      ->setIsVisible(true),

                    $resource
                      ->getAttribute('openx_impressions')
                      ->setIsVisible(true),

                    $resource
                      ->getAttribute('openx_clicks')
                      ->setIsVisible(true),

                    $resource
                      ->getAttribute('openx_conversions')
                      ->setIsVisible(true),

                    $resource
                      ->getAttribute('openx_campaign_weight')
                      ->setIsVisible(true),
                  );

    $this->_setFieldset($attributes, $fieldset);

    $values = $product->getData();

    // Set default attribute values for new product
    if (! $product->getId())
      foreach ($attributes as $attribute)
        if (!isset($values[$attribute->getAttributeCode()]))
          $values[$attribute->getAttributeCode()]
            = $attribute->getDefaultValue();

    $form->addValues($values);
    $form->setFieldNameSuffix('product');

    $this->setForm($form);
  }
}

?>
