<?php

class ZetaPrints_WebToPrint_Model_Convert_Mapper_Product_Creating
  extends  Mage_Dataflow_Model_Convert_Mapper_Abstract
  implements ZetaPrints_Api {

  public function map () {
    //Always print debug information. Issue #80
    $this->debug = true;

    $this->warning('Product type: ' .
                       $this->getAction()->getParam('product-type', 'simple') );

    //Get all web-to-print templates
    $templates = Mage::getModel('webtoprint/template')->getCollection()->load();

    //Get all products
    $products = Mage::getModel('catalog/product')
                  ->getCollection()
                  ->addAttributeToSelect('webtoprint_template')
                  ->load();

    //If there're products then...
    if ($has_products = (bool) count($products)) {
      //... create array to store used web-to-print template GUIDs
      $used_templates = array();

      //For every product...
      foreach($products as $product) {
        //... remember its ID
        $used_templates[$product->getId()] = null;

        //And if it has web-to-print attribute set then...
        if($product->hasWebtoprintTemplate() && $product->getWebtoprintTemplate())
          //... also remember the value of the attribute
          $used_templates[$product->getWebtoprintTemplate()] = null;
      }
    }

    unset($products);

    $line = 0;

    $number_of_templates = count($templates);
    $number_of_created_products = 0;

    foreach ($templates as $template) {
      $line++;

      if ($has_products)
        if (array_key_exists($template->getGuid(), $used_templates)) {
          $this->debug("{$line}. Product {$template->getGuid()} already exists");

          continue;
        }

      $product_model = Mage::getModel('catalog/product');

      if (Mage::app()->isSingleStoreMode())
        $product_model->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
      else
        $this->debug('Not a single store mode');

      $product_model->setAttributeSetId($product_model->getDefaultAttributeSetId())
        ->setSku(zetaprints_generate_guid() . '-rename-me')
        ->setTypeId($this->getAction()->getParam('product-type', 'simple'))
        ->setName($template->getTitle())
        ->setDescription($template->getDescription())
        ->setShortDescription($template->getDescription())
        ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
        ->setVisibility(0)
        ->setRequiredOptions(true)
        ->setWebtoprintTemplate($template->getGuid());

      try {
        $product_model->save();
      } catch (Zend_Http_Client_Exception $e) {
        $this->error("{$line}. Error creating product from template: {$template->getGuid()}");
        $this->error($e->getMessage());

        continue;
      }

      $stock_item = Mage::getModel('cataloginventory/stock_item');

      $stock_item->setStockId(1)
        ->setUseConfigManageStock(0)
        ->setProduct($product_model)
        ->save();

      $this->debug("{$line}. Product for template {$template->getGuid()} was created.");

      $number_of_created_products++;

      unset($product_model);
      unset($stock_item);
    }

    $this->notice("Number of templates: {$number_of_templates}");
    $this->notice("Number of created products: {$number_of_created_products}");

    $this->warning('Warning: products were created with general set of properties. Update other product properties using bulk edit to make them operational.');
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
