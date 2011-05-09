<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kris
 * Date: 11-4-25
 * Time: 19:22
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_Options_Block_Adminhtml_Grid
  extends Mage_Adminhtml_Block_Catalog_Product_Grid
{
  protected function _prepareMassaction()
  {
    parent::_prepareMassaction();
    /** @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
    $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('has_options', array('eq'=>'1'), 'left')
            ->load();
    $options = $this->toOptionArray($collection, 'entity_id');
    array_unshift($options, array('value' => 0, 'label' => $this->__('Select source product')));

    $this->getMassactionBlock()
      ->addItem('zpoptions', array(
        'label' => Mage::helper('catalog')->__('Copy Custom Options'),
        'url' => $this->getUrl('*/zp-options/masscopy', array('_current' => true)),
        'additional' => array(
          'visibility' => array(
             'name' => 'source',
             'type' => 'select',
             'class' => 'required-entry',
             'label' => Mage::helper('catalog')->__('Source Product'),
             'values' => $options
           )
         )
      ));
  }

  public function toOptionArray($collection, $value = 'id', $title = 'name', $additional  = array())
  {
    $res = array();
    $additional['value'] = $value;
    $additional['label'] = $title;

    foreach ($collection as $item) {
      foreach ($additional as $code => $field) {
          $data[$code] = $item->getData($field);
      }
      $res[] = $data;
    }
    return $res;
  }
}
