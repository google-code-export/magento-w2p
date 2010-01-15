<?php

class ZetaPrints_WebToPrint_Model_Convert_Mapper_Product_Updating extends  Mage_Dataflow_Model_Convert_Mapper_Abstract {

  public function map () {

    //Always print debug information. Issue #80
    $this->debug = true;

    $templates = Mage::getModel('webtoprint/template')->getCollection()->load();

    foreach ($templates as $template) {
      $product_model = Mage::getModel('catalog/product');

      if ($product_id = $product_model->getIdBySku($template->getGuid())) {
        $this->debug("Product {$template->getGuid()} already exists");

        $product = $product_model->load($product_id);

        if (!$product->getWebtoprintTemplate()) {
          $this->debug("Product {$template->getGuid()} doesn't have web-to-print attribute.");

          Mage::register('webtoprint-template-changed', true);
          $product->setSku("{$template->getGuid()}-rename-me")
            ->setRequiredOptions(true)
            ->setWebtoprintTemplate($template->getGuid())
            ->save();
          Mage::unregister('webtoprint-template-changed');

          $this->debug("Web-to-print attribute was added to product {$template->getGuid()}");
        }
        else {
          $this->debug("SKU of product {$template->getGuid()} is equal to its web-to-print attribute");

          Mage::register('webtoprint-template-changed', true);
          $product->setSku("{$template->getGuid()}-rename-me")
            ->setRequiredOptions(true)
            ->save();
          Mage::unregister('webtoprint-template-changed');

          $this->debug("SKU of product {$template->getGuid()} was changed.");
        }
      } else {
        $products = $product_model->getCollection()->addAttributeToFilter('webtoprint_template', array('eq' => $template->getGuid()))->load();

        foreach ($products as $product)
          if (strtotime($product->getUpdatedAt()) <= strtotime($template->getDate())) {
            $full_product = $product_model->load($product->getId());

            $this->debug("Template for product {$full_product->getWebtoprintTemplate()} changed");

            Mage::register('webtoprint-template-changed', true);
            $full_product->save();
            Mage::unregister('webtoprint-template-changed');

            $this->debug("Product {$full_product->getWebtoprintTemplate()} was succesfully updated");
          }
      }
    }
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
