<<<<<<< .mine
<?php
class ZetaPrints_Fixedprices_Block_Catalog_Product_View_Fixedprices
 extends Mage_Catalog_Block_Product_Price
 {
   protected $_fixedCode;

   /**
    * @var Mage_Catalog_Model_Product
    */
   protected $_product;

   /**
		*@var ZetaPrints_Fixedprices_Model_Fixedprices
    */
   protected $_priceModel;

   protected $_defaultInputTemplate = '<input id="fixed-price-%d" class="fixed-price-option" type="radio" name="fixed-price" value="%.2f" %s/>&nbsp;';

   protected function _construct()
   {
     parent::_construct();
     // save some typing
     $this->_fixedCode = ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE;
   }

   public function useFixedPrices()
   {
     $return = Mage::helper('fixedprices')->isFixedPriceEnabled($this->getProduct());
     return $return;
   }

   public function getFixedPrices()
   {
     if (!$this->hasData($this->_fixedCode)){
       $product = $this->getProduct();
       $prices = $product->getData($this->_fixedCode);
       $prid = $this->getProduct()->getId();
       $store = Mage::app()->getStore();
       foreach ($prices as $key => $price)
       {
         $prices[$key]['website_price'] = $price['price'];
         $prices[$key]['formated_price'] = $store->formatPrice($store->convertPrice(Mage::helper('tax')->getPrice($product, $price['website_price'])));
         $prices[$key]['formated_price_incl_tax'] = $store->formatPrice($store->convertPrice(Mage::helper('tax')->getPrice($product, $price['website_price'], true)));
         $prices[$key]['product_id'] = $prid;
       }
       $this->setData($this->_fixedCode, $prices);
     }
     return $this->getData($this->_fixedCode);
   }

   /**
    * (non-PHPdoc)
    * @see Mage_Catalog_Block_Product_Price::getProduct()
    * @return Mage_Catalog_Model_Product
    */
   public function getProduct()
   {
     if(!isset($this->_product)){
       $this->_product = parent::getProduct();
     }
     return $this->_product;
   }

   public function getFixedPriceIdInput($price)
   {
     return sprintf('<input type="hidden" name="fixed-price-id" id="fixed-price-id" value="%d"/>', $price['price_id']);
   }

   public function getInput($price)
   {
     $ch = ($price['active'] == 1)?'checked="checked"':'';
     return sprintf($this->_defaultInputTemplate,$price['price_id'], $price['price'], $ch);
   }

   /**
    * Get JavaScript data
    * Get needed js data. Such as json representation
    * of fixed prices, plus qty per unit.
    * We use this to manipulate qty that is sent to cart, etc.
    *
    * @return string
    */
   public function getJsData()
   {
     $data = $this->getFixedPrices();
     return Zend_Json::encode($data, true);
   }
 }
=======
<?php
class ZetaPrints_Fixedprices_Block_Catalog_Product_View_Fixedprices
 extends Mage_Catalog_Block_Product_Price
 {
   protected $_fixedCode;

   /**
    * @var Mage_Catalog_Model_Product
    */
   protected $_product;

   /**
		*@var ZetaPrints_Fixedprices_Model_Fixedprices
    */
   protected $_priceModel;

   protected $_defaultInputTemplate = '<input id="fixed-price-%d" class="fixed-price-option" type="radio" name="fixed-price" value="%.2f" %s/>&nbsp;';

   protected function _construct()
   {
     parent::_construct();
     // save some typing
     $this->_fixedCode = ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE;
   }

   public function useFixedPrices()
   {
     $return = Mage::helper('fixedprices')->isFixedPriceEnabled($this->getProduct());
     return $return;
   }

   public function getFixedPrices()
   {
     if (!$this->hasData($this->_fixedCode)){
       $product = $this->getProduct();
       $prices = $product->getData($this->_fixedCode);
       $prid = $this->getProduct()->getId();
       $store = Mage::app()->getStore();
       foreach ($prices as $key => $price)
       {
         $prices[$key]['website_price'] = $price['price'];
         $prices[$key]['formated_price'] = $store->formatPrice($store->convertPrice(Mage::helper('tax')->getPrice($product, $price['website_price'])));
         $prices[$key]['formated_price_incl_tax'] = $store->formatPrice($store->convertPrice(Mage::helper('tax')->getPrice($product, $price['website_price'], true)));
         $prices[$key]['product_id'] = $prid;
       }
       $this->setData($this->_fixedCode, $prices);
     }
     return $this->getData($this->_fixedCode);
   }

   /**
    * (non-PHPdoc)
    * @see Mage_Catalog_Block_Product_Price::getProduct()
    * @return Mage_Catalog_Model_Product
    */
   public function getProduct()
   {
     if(!isset($this->_product)){
       $this->_product = parent::getProduct();
     }
     return $this->_product;
   }

   public function getFixedPriceIdInput($price)
   {
     return sprintf('<input type="hidden" name="fixed-price-id" id="fixed-price-id" value="%d"/>', $price['price_id']);
   }

   public function getInput($price)
   {
     $ch = ($price['active'] == 1)?'checked="checked"':'';
     return sprintf($this->_defaultInputTemplate,$price['price_id'], $price['price'], $ch);
   }

   /**
    * Get JavaScript data
    * Get needed js data. Such as json representation
    * of fixed prices, plus qty per unit.
    * We use this to manipulate qty that is sent to cart, etc.
    *
    * @return string
    */
   public function getJsData()
   {
     $data = $this->getFixedPrices();
     return Zend_Json::encode($data, true);
   }
 }
>>>>>>> .r1756
