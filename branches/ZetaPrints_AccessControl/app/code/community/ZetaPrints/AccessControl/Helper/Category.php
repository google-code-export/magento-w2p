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
   * There is no event that we can attach to then flat catalog option is enabled
   * So we need to load store categories as a collection and return items array
   * if expected, that way an event that filters categories will be triggered.
   *
   * @param bool $sorted
   * @param bool $asCollection
   * @param bool $toLoad
   * @return array
   */
  public function getStoreCategories($sorted = false, $asCollection = false,
                                     $toLoad = true) {
    $collection = parent::getStoreCategories($sorted, $asCollection, $toLoad);

    if (!Mage::helper('catalog/category_flat')->isEnabled() || $asCollection)
      return $collection;

    $result = array();

    //We need to filter out categories if flat catalog option is enabled and
    //store categories are not loaded as 3a collection
    foreach ($collection as $category)
      if ($this->canShow($category))
        $result[$category->getId()] = $category;

    return $result;
  }
}

?>
