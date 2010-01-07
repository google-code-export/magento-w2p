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
          $product->setSku("{$template->getGuid()}-rename-me")
            ->setRequiredOptions(true)
            ->setWebtoprintTemplate($template->getGuid())
            ->save();
          $this->debug("Product {$template->getGuid()} was updated.");
        }
      }
      else {
        $products = $product_model->getCollection()->addAttributeToFilter('webtoprint_template', array('eq' => $template->getGuid()))->load();

        foreach ($products as $product)
          if (strtotime($product->getUpdatedAt()) <= strtotime($template->getDate())) {
            Mage::register('webtoprint-template-changed', true);
            $product->save();
            Mage::unregister('webtoprint-template-changed');
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
