<?php
/**
 * AccessControl
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @copyright  Copyright (c) 2010 ZetaPrints Ltd. http://www.zetaprints.com/
 * @attribution Vinai Kopp http://www.magentocommerce.com/extension/reviews/module/635
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Extend sitemap resource product collection model to remove hidden products
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Model_Resource_Sitemap_Mysql4_Product
  extends Mage_Sitemap_Model_Mysql4_Catalog_Product {

  /**
   * Get product collection array and filter out hidden categories
   *
   * @return array
   */
  public function getCollection ($storeId) {
    $products = parent::getCollection($storeId);
    $helper = Mage::helper('accesscontrol');

    if ($helper->is_extension_enabled()) {
      $tmp = array();

      foreach ($products as $item) {
        // $item is a Varien_Object, no descendant of
        // Mage_Catalog_Model_Resource_Eav_Mysql4_Collection_Abstract
        $product = Mage::getModel('catalog/product')->setId($item->getId());

        if ($helper->has_customer_group_access_to_product($product,
          Mage_Customer_Model_Group::NOT_LOGGED_IN_ID))

          $tmp[$item->getId()] = $item;
      }

      $products = $tmp;
    }

    return $products;
  }
}

?>
