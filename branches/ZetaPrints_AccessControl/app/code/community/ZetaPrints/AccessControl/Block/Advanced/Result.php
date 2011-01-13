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
 * @copyright   Copyright (c) 2011 ZetaPrints Ltd. http://www.zetaprints.com/
 * @attribution Magento Core Team <core@magentocommerce.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Advanced search result
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Block_Advanced_Result
  extends Mage_CatalogSearch_Block_Advanced_Result {

  public function getResultCount () {
    if (!$this->getData('result_count')) {
      $size = count($this->getSearchModel()->getProductCollection());
      $this->setResultCount($size);

      Mage::helper('accesscontrol')
                          ->filter_out_products($this->_getProductCollection());
    }

    return $this->getData('result_count');
  }
}
