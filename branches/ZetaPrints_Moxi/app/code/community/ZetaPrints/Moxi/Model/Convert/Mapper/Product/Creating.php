<?php

class ZetaPrints_Moxi_Model_Convert_Mapper_Product_Creating
  extends  Mage_Dataflow_Model_Convert_Mapper_Abstract {

  const PRODUCT_TYPE = 'virtual';

  protected $_options = array(
                          array(
                            'title' => 'Banner image',
                            'type' => 'file',
                            'is_require' => '1',
                            'price_type' => 'fixed',
                            'sort_order' => '1'
                          ),
                          array(
                            'title' => 'Destination URL (incl. http://)',
                            'type' => 'field',
                            'is_require' => '1',
                            'price_type' => 'fixed',
                            'sort_order' => '2'
                          ),
                          array(
                            'title' => 'Start date',
                            'type' => 'date',
                            'is_require' => '1',
                            'price_type' => 'fixed',
                            'sort_order' => '3'
                          ),
                          array(
                            'title' => 'End date',
                            'type' => 'date',
                            'is_require' => '0',
                            'price_type' => 'fixed',
                            'sort_order' => '4'
                          )
                        );

  public function map () {
    //Always print debug information.
    $this->debug = true;

    $this->warning('Product type: ' . self::PRODUCT_TYPE);

    $managerId = (int) Mage::getStoreConfig('moxi/settings/manager');
    $manager = new Varien_Object(array('id' => $managerId));

    $helper = Mage::helper('moxi');

    $entityTypeId = Mage::getModel('catalog/product')
                      ->getResource()
                      ->getTypeId();

    $sets = Mage::getModel('eav/entity_attribute_set')
              ->getResourceCollection()
              ->setEntityTypeFilter($entityTypeId)
              ->addFieldToFilter('attribute_set_name', 'OpenX Advertising Plan')
              ->load();

    if (count($sets) == 0) {
      $this->error('OpenX Advertising Plan attribute set doesn\'t exist');
      return;
    }

    $attributeSetId = $sets
                        ->getFirstItem()
                        ->getId();

    $numberOfSites = 0;
    $numberOfZones = 0;
    $numberOfCreatedProducts = 0;

    foreach (Mage::helper('moxi')->getSitesByManager($manager) as $site) {
      $numberOfSites++;

      foreach (Mage::helper('moxi')->getZonesBySite($site) as $zone) {
        $numberOfZones++;

        $this->_options[0]['image_size_x'] = $zone->getWidth();
        $this->_options[0]['image_size_y'] = $zone->getHeight();

        $productModel = Mage::getModel('catalog/product');

        $productModel
          ->getOptionInstance()
          ->unsetOptions();

        if (Mage::app()->isSingleStoreMode())
          $productModel
            ->setWebsiteIds(array(Mage::app()
                                    ->getStore(true)
                                    ->getWebsite()
                                    ->getId() ));
        else
          $this->debug('Not a single store mode');

        $productModel
          ->setAttributeSetId($attributeSetId)
          ->setTypeId(self::PRODUCT_TYPE)
          ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
          ->setVisibility(0)
          ->setSku($site->getName() . ' - ' . $zone->getName())
          ->setName($site->getName() . ' - ' . $zone->getName())
          ->setOpenxWebsiteId($site->getId())
          ->setOpenxZoneId($zone->getId())
          ->setProductOptions($this->_options)
          ->setCanSaveCustomOptions(true);

        try {
          $productModel->save();
        } catch (Exception $e) {
          $this->error("Error creating product for zone: {$zone->getName()}");
          $this->error($e->getMessage());

          continue;
        }

        $stockItem = Mage::getModel('cataloginventory/stock_item');

        $stockItem
          ->setStockId(1)
          ->setUseConfigManageStock(0)
          ->setProduct($productModel);

        try {
          $stockItem->save();
        } catch (Exception $e) {
          $this->error($e->getMessage());

          continue;
        }

        $this->debug("Product for \"{$zone->getName()}\" zone is created");

        $numberOfCreatedProducts++;

        unset($productModel);
        unset($stockItem);
      }
    }

    $this->notice("Number of sites: {$numberOfSites}");
    $this->notice("Number of zones: {$numberOfZones}");
    $this->notice("Number of created products: {$numberOfCreatedProducts}");
  }

  private function error ($message) {
    $this->addException($message, Mage_Dataflow_Model_Convert_Exception::ERROR);
  }

  private function notice ($message) {
    $this->addException($message, Mage_Dataflow_Model_Convert_Exception::NOTICE);
  }

  private function warning ($message) {
    $this->addException($message, Mage_Dataflow_Model_Convert_Exception::WARNING);
  }

  private function debug ($message) {
    if ($this->debug)
      $this->notice($message);
  }
}

?>
