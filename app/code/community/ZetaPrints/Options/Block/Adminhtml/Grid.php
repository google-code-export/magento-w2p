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
    /*$collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('has_options', array('eq'=>'1'), 'left')
            ->load();
    $options = $this->toOptionArray($collection, 'entity_id');
    array_unshift($options, array('value' => 0, 'label' => $this->__('Select source product')));
    */
    $massAction = $this->getMassactionBlock();
    $help = '<div id=\"zpoptions_help\" style=\"display:none;position:absolute;min-width:320px;top:-120px;left:-150px;background:#fff\">';
    $help .= '<ol style=\"list-style:decimal inside; border:1px solid #EA7601;padding:3px 5px;\">';
    $help .= '<li>' . $this->__('Find the ID of product you would like to use as source.') . '</li>';
    $help .= '<li>' . $this->__('Select products you would like to copy options to.') . '</li>';
    $help .= '<li>' . $this->__('Choose \'Copy Custom Options\' from drop down.') . '</li>';
    $help .= '<li>' . $this->__('Type/paste source product ID in text box and click \'Submit\'.') . '</li>';
    $help .= '</ol>';
    $help .= '<span style=\"font-weight:bold;\">' . $this->__('Click help link again to close this help.') . '</span>';
    $help .= '</div>';
    $script = '<script type="text/javascript">
    $("src_product_note").observe("click", function(e){
      Event.stop(e);
      var help = $("zpoptions_help");
      if(help){
        help.toggle();
      }else {
        var helpContent = "' . $help . '";
        $(this.parentNode).makePositioned().insert(helpContent);
        $("zpoptions_help").show();
      }
    });
    </script>';
    $massAction->addItem('zpoptions', array(
        'label' => Mage::helper('catalog')->__('Copy Custom Options'),
        'url' => $this->getUrl('*/zp-options/masscopy', array('_current' => true)),
        'additional' => array(
          'src_product_id' => array(
             'name' => 'source',
             'type' => 'text',
             'class' => 'required-entry',
             'label' => Mage::helper('catalog')->__('Source Product ID'),
//             'values' => $options
           ),
          'src_product_note' => array(
            'after_element_html' => $script,
            'type' => 'link',
            'value' => 'What is source product ID?',
            'href' => '#'
          )
         )
      ));
    $massAction->getParentBlock();
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
