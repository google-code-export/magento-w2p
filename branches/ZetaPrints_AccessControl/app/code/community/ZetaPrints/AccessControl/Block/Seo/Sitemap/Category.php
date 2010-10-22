<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    ZetaPrints
 * @package     ZetaPrints_AccessControl
 * @copyright   Copyright (c) 2010 ZetaPrints Ltd. http://www.zetaprints.com/
 * @attribution Magento Core Team <core@magentocommerce.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */



/**
 * SEO Categories Sitemap block
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Block_Seo_Sitemap_Category
  extends Mage_Catalog_Block_Seo_Sitemap_Category {

  /**
   * Initialize categories collection
   *
   * @return Mage_Catalog_Block_Seo_Sitemap_Category
   */
  protected function _prepareLayout () {
    parent::_prepareLayout();

    //Get AccessControl's general helper
    $helper = Mage::helper('accesscontrol');

    //Check if extension is enabled then...
    if ($helper->is_extension_enabled()) {
      //... initialize collection
      $categories = new Varien_Data_Collection();

      //Go throw list of categories
      foreach ($this->getCollection() as $category)
        //Check if current customer's groups has access to a category
         if ($helper->has_customer_group_access_to_category(
              Mage::getModel('catalog/category')->setId($category->getId())))
          //If the group has access to the category then add it to
          //collection of categories
          $categories->addItem($category);

      //Save prepared collection of categories
      $this->setCollection($categories);
    }

    return $this;
  }
}
