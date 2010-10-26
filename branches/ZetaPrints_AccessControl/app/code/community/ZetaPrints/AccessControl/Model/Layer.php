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
 * Catalog search view layer model
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Model_CatalogSearch_Layer extends Mage_CatalogSearch_Model_Layer
{
  /**
   * Prepare add groups catalog product filter to a prepared product collection
   *
   * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
   * @return Mage_Catalog_Model_Layer
   */
  public function prepareProductCollection($collection) {
    parent::prepareProductCollection($collection);
    Mage::helper('accesscontrol')->filter_out_products($collection);
    return $this;
  }
}

?>
