<?php
/**
 * AccessControl
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
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @copyright  Copyright (c) 2010 ZetaPrints Ltd. http://www.zetaprints.com/
 * @attribution Vinai Kopp http://www.magentocommerce.com/extension/reviews/module/635
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog category helper
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Helper_Category extends
  Mage_Catalog_Helper_Category {

  /**
   * Check if a category can be shown
   * @param  Mage_Catalog_Model_Category|int $category
   * @return boolean
   */
  public function canShow ($category) {
    if (is_int($category))
      $category = Mage::getModel('catalog/category')->load($category);

    return parent::canShow($category)
      && Mage::helper('accesscontrol')->has_customer_group_access_to_category($category);
  }

  /**
   * If the flat catalog is enabled there is no event that we can attach to :-/
   * So we need to load the store categories as a collection and return the items array
   * if expexted, that way the event that filters the categories is triggered.
   *
   * @param bool $sorted
   * @param bool $asCollection
   * @param bool $toLoad
   * @return array
   */
  public function getStoreCategories($sorted = false, $asCollection = false,
                                     $toLoad = true) {
    if (!Mage::helper('catalog/category_flat')->isEnabled())
      return parent::getStoreCategories($sorted, $asCollection, $toLoad);

    //Allways load as a collection so the filter in the event is applied

    if ($asCollection)
      return parent::getStoreCategories($sorted, true, $toLoad);

    return parent::getStoreCategories($sorted, true, $toLoad)
             ->load()
             ->getItems();
  }
}

?>
