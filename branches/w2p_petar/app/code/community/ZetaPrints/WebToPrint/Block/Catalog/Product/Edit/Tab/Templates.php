<?php

class ZetaPrints_WebToPrint_Block_Catalog_Product_Edit_Tab_Templates extends Mage_Adminhtml_Block_Widget_Grid {
  public function __construct() {
    parent::__construct();
    $this->setId('webtoprint_templates_grid');
    $this->setDefaultSort('guid');
    $this->setUseAjax(true);
  }

  protected function _prepareCollection () {
    $this->setCollection(Mage::getModel('webtoprint/template')->getCollection());
    return parent::_prepareCollection();
  }

  public function getMainButtonsHtml()
  {
    $html = parent::getMainButtonsHtml();
    $html .= $this->getChildHtml('web_to_print_source_btn');
    return $html;
  }
  protected function _prepareColumns () {
    $this->addColumn('selected', array(
      'header_css_class' => 'a-center',
      'type'      => 'radio',
      'html_name'      => 'product[webtoprint_template]',
      'values'    => array($this->get_template_guid ()),
      'align'     => 'center',
      'index'     => 'guid',
      'sortable'  => false,
    ));

    $this->addColumn('title', array(
      'header'    => Mage::helper('catalog')->__('Name'),
      'sortable'  => true,
      'index'     => 'title' ));

    $this->addColumn('created', array(
      'type'      => 'datetime',
      'header'    => Mage::helper('catalog')->__('Created'),
      'sortable'  => true,
      'index'     => 'date' ));
  }

  private function get_template_guid () {
    return Mage::registry('product')->getWebtoprintTemplate();
  }

  public function getGridUrl() {
    return $this->getData('grid_url') ? $this->getData('grid_url') : $this->getUrl('*/*/templates', array('_current' => true));
  }

  protected function _prepareLayout()
  {
    $product = Mage::registry('product'); // get current product
    $type = $product->getTypeId(); // get its type
    $fname = 'ZetaPrints %s products creation';
    $name = sprintf($fname, $type); // make the name
    $profileId = $this->_getProfileId($name); // get profile id
    if($profileId) {
      $this->_addSourceBtn($profileId);
    }
    return parent::_prepareLayout();
  }

  protected function getUpdateProfileAction($profileId)
  {
    $action = 'window.location=\'';
    $productId = Mage::registry('product')->getId();
    $url = $this->getUrl('*/*/updateProfile', array('profile' => $profileId, 'src' => $productId));
    return $action . $url . '\'';
  }

  protected function _getProfileId($name = 'ZetaPrints simple products creation')
  {
    $profile_model = Mage::helper('webtoprint')->getProfileByName($name);
    if($profile_model instanceof Mage_Dataflow_Model_Profile) {
      return $profile_model->getId();
    }
    return null;
  }

  protected function _addSourceBtn($profileId)
  {
        $this->setChild('web_to_print_source_btn',
              $this->getLayout()
                   ->createBlock('adminhtml/widget_button')
                   ->setData(array(
                            'label'     => Mage::helper('adminhtml')->__('Use this product as source'),
                            'onclick'   => $this->getUpdateProfileAction($profileId),
                            'class'   => 'task'
                          )
                        )
                    );
    return $this;
  }
}

?>
