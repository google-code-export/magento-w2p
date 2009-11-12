<?php
class ZetaPrints_WebToPrint_Helper_Image extends Mage_Catalog_Helper_Image
{
    public function __toString()
    {
		try{
			/* Add By Pham Tri Cong <phtcong@gmail.com>
			  * Check if have w2p_image, will display w2p_image (To view ZP Image - MAGENTO-ZP).
			  */
			if ($this->getProduct()->getData("w2p_image")){
				return $this->getProduct()->getData("w2p_image");
			}else{
				$new = Mage::getModel('catalog/product')->load($this->getProduct()->getId());
				if ($new && $new->getData("w2p_image")){
					$this->getProduct()->setData("w2p_image",$new->getData("w2p_image"));
					return $new->getData("w2p_image");
				}	
			}
        } catch( Exception $e ) {            
        }
        return parent::__toString();
    }
}