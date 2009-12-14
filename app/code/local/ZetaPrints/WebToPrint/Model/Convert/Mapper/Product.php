<?php

class ZetaPrints_WebToPrint_Model_Convert_Mapper_Product extends  Mage_Dataflow_Model_Convert_Mapper_Abstract {

  public function map () {
    $this->debug = (bool)Mage::getStoreConfig('zpapi/settings/w2p_debug');

    $templates = Mage::getModel('webtoprint/template')->getCollection()->load();

    foreach ($templates as $template) {
      $product_model = Mage::getModel('catalog/product');

      if ($product_id = $product_model->getIdBySku($template->getGuid())) {
        $this->debug("Product {$template->getGuid()} already exists");

        $product = $product_model->load($product_id);

        if (!$product->getWebtoprintTemplate()) {
          $product->setSku("{$template->getGuid()}-rename-me")
            ->setRequiredOptions(true)
            ->setWebtoprintTemplate($template->getGuid())
            ->save();
          $this->debug("Product {$template->getGuid()} was updated.");
        }

        continue;
      }
      else {
        $products = $product_model->getCollection()->addAttributeToFilter('webtoprint_template', array('eq' => $template->getGuid()))->load();

        if (count($products) === 1) {
          $this->debug("Product {$template->getGuid()} already exists");
          continue;
        }

        if ($number = count($products) > 1) {
          $this->warning("Template {$template->getGuid()} is used by {$number} products");
          continue;
        }
      }

      $this->debug("Product {$template->getGuid()} was created.");

      if (Mage::app()->isSingleStoreMode())
        $product_model->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
      else
        $this->debug('Not a single store mode');

      $product_model->setAttributeSetId($product_model->getDefaultAttributeSetId())
        ->setSku($template->getGuid())
        ->setTypeId('simple')
        ->setName($template->getTitle())
        ->setDescription($template->getDescription())
        ->setShortDescription($template->getDescription())
        ->setVisibility(0)
        ->setRequiredOptions(true)
        ->setWebtoprintTemplate($template->getGuid())
        ->save();

      $stock_item = Mage::getModel('cataloginventory/stock_item');
      $stock_item->setStockId(1)
        ->setProduct($product_model)
        ->save();
    }

    $this->warning('Warning: products were created with general set of properties. Update other product properties using bulk edit to make them operational.');
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
