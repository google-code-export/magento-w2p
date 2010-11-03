<?php

/**
 * customers defined options
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */

class ZetaPrints_Attachments_Block_Product_Edit_Tab_Options_Type_Attachment extends
    Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Options_Type_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('catalog/product/edit/options/type/file.phtml');
    }
}
