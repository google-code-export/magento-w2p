<<<<<<< .mine
<?php
class ZetaPrints_Fixedprices_Model_Convert_Parser_Product
  extends Mage_Catalog_Model_Convert_Parser_Product
{
	/* (non-PHPdoc)
 * @see Mage_Catalog_Model_Convert_Parser_Product::unparse()
 */
  public function unparse()
  {
    $entityIds = $this->getData(); // list of product IDs
    foreach ($entityIds as $i => $entityId) {
      $product = $this->getProductModel()
          ->setStoreId($this->getStoreId())
          ->load($entityId);
      $row = array();
      $this->setProductTypeInstance($product);
      /* @var $product Mage_Catalog_Model_Product */

      $position = Mage::helper('catalog')->__('Line %d, SKU: %s', ($i+1), $product->getSku());
      $this->setPosition($position);
      $fixedPrices = $product->getFixedPrice();
      $row['sku'] = $product->getSku();
      $row['name'] = $product->getName();
      foreach ($fixedPrices as $i => $fp) {
        $row['qty' . $i] = $fp['price_qty'];
        $row['label' . $i] = $fp['units'];
        $row['price' . $i] = $fp['price'];
        $row['default' . $i] = $fp['active'];
      }

      $batchExport = $this->getBatchExportModel()
                ->setId(null)
                ->setBatchId($this->getBatchModel()->getId())
                ->setBatchData($row)
                ->setStatus(1)
                ->save();
      $product->reset();
    }
  }
}
=======
<?php
class ZetaPrints_Fixedprices_Model_Convert_Parser_Product
  extends Mage_Catalog_Model_Convert_Parser_Product
{
	/* (non-PHPdoc)
 * @see Mage_Catalog_Model_Convert_Parser_Product::unparse()
 */
  public function unparse()
  {
    $entityIds = $this->getData(); // list of product IDs
    foreach ($entityIds as $i => $entityId) {
      $product = $this->getProductModel()
          ->setStoreId($this->getStoreId())
          ->load($entityId);
      $row = array();
      $this->setProductTypeInstance($product);
      /* @var $product Mage_Catalog_Model_Product */

      $position = Mage::helper('catalog')->__('Line %d, SKU: %s', ($i+1), $product->getSku());
      $this->setPosition($position);
      $fixedPrices = $product->getFixedPrice();
      $row['sku'] = $product->getSku();
      $row['name'] = $product->getName();
      foreach ($fixedPrices as $i => $fp) {
        $row['qty' . $i] = $fp['price_qty'];
        $row['label' . $i] = $fp['units'];
        $row['price' . $i] = $fp['price'];
        $row['default' . $i] = $fp['active'];
      }

      $batchExport = $this->getBatchExportModel()
                ->setId(null)
                ->setBatchId($this->getBatchModel()->getId())
                ->setBatchData($row)
                ->setStatus(1)
                ->save();
      $product->reset();
    }
  }
}
>>>>>>> .r1756
