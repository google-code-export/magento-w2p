<<<<<<< .mine
<?php
class ZetaPrints_Fixedprices_Block_Catalog_Product_Edit_Price_Fixedprices
 extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Tier
{
  /**
   * Define tier price template file
   *
   */
  public function __construct()
  {
    $this->setTemplate('catalog/product/edit/price/fixedprices.phtml');
  }

  /**
   * Sort tier price values callback method
   *
   * @param array $a
   * @param array $b
   * @return int
   */
  protected function _sortTierPrices($a, $b)
  {
    if ($a['order'] != $b['order']) {
      return $a['order'] < $b['order'] ? -1 : 1;
    }

    return 0;
  }

  /**
   * Prepare global layout
   * Add "Add tier" button to layout
   *
   * @return Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Tier
   */
  protected function _prepareLayout()
  {
    parent::_prepareLayout();
    $button = $this->getLayout()->
                createBlock('adminhtml/widget_button')->
                setData(array (
                 'label' => Mage::helper('fixedprices')->__('Add Fixed Quantity'),
                 'onclick' => 'return fixedPriceControl.addItem()',
                 'class' => 'add'
                ));
    $button->setName('add_fixed_price_item_button');
    $this->setChild('add_button', $button);
  }
}
=======
<?php
class ZetaPrints_Fixedprices_Block_Catalog_Product_Edit_Price_Fixedprices
 extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Tier
{
  /**
   * Define tier price template file
   *
   */
  public function __construct()
  {
    $this->setTemplate('catalog/product/edit/price/fixedprices.phtml');
  }

  /**
   * Sort tier price values callback method
   *
   * @param array $a
   * @param array $b
   * @return int
   */
  protected function _sortTierPrices($a, $b)
  {
    if ($a['order'] != $b['order']) {
      return $a['order'] < $b['order'] ? -1 : 1;
    }

    return 0;
  }

  /**
   * Prepare global layout
   * Add "Add tier" button to layout
   *
   * @return Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Price_Tier
   */
  protected function _prepareLayout()
  {
    parent::_prepareLayout();
    $button = $this->getLayout()->
                createBlock('adminhtml/widget_button')->
                setData(array (
                 'label' => Mage::helper('fixedprices')->__('Add Fixed Quantity'),
                 'onclick' => 'return fixedPriceControl.addItem()',
                 'class' => 'add'
                ));
    $button->setName('add_fixed_price_item_button');
    $this->setChild('add_button', $button);
  }
}
>>>>>>> .r1756
