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
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog
 * @copyright  Copyright (c) 2008 Vinai Kopp http://netzarbeiter.com/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog product helper
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog
 * @author     Vinai Kopp <vinai@netzarbeiter.com>
 */
class ZetaPrints_AccessControl_Helper_Product extends Mage_Catalog_Helper_Product {
  /**
   * Check if a product can be shown
   *
   * @param  Mage_Catalog_Model_Product|int $product
   * @return boolean
   */
  public function canShow ($product, $where = 'catalog') {
    if (is_int($product))
      $product = Mage::getModel('catalog/product')->load($product);

    return parent::canShow($product)
      && Mage::helper('accesscontrol')->has_customer_group_access_to_product($product);
  }
}
