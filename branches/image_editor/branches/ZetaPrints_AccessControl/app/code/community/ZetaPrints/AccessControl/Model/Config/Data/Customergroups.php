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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @copyright  Copyright (c) 2010 ZetaPrints Ltd. http://www.zetaprints.com/
 * @attribution Vinai Kopp http://www.magentocommerce.com/extension/reviews/module/635
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Config data backend model for customer groups selection
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Model_Config_Data_Customergroups
  extends Mage_Core_Model_Config_Data {

  /**
   * Input validation for the backend data entry
   *
   * @return Netzarbeiter_GroupsCatalog_Model_Config_Data_Customergroups
   */
  protected function _beforeSave () {
    $data = $this->getValue();

    //Default to using the default - don't let the customer select nothing
    if (empty($data))
      $this->setValue(array(ZetaPrints_AccessControl_Helper_Data::NONE));
    elseif (count($data) > 1) {
      if (in_array(ZetaPrints_AccessControl_Helper_Data::NONE, $data)) {
        //Remove all groups but the "none" value
        $data = array(ZetaPrints_AccessControl_Helper_Data::NONE);

        Mage::getSingleton('adminhtml/session')
          ->addNotice(Mage::helper('adminhtml')
            ->__('Customer groups besides NONE were removed from the selection.'));
      } elseif (in_array(ZetaPrints_AccessControl_Helper_Data::ALL, $data)) {
        $data = array(ZetaPrints_AccessControl_Helper_Data::ALL);

        Mage::getSingleton('adminhtml/session')
          ->addNotice(Mage::helper('adminhtml')
            ->__('Customer groups besides ALL were removed from the selection.'));
      } elseif (in_array(ZetaPrints_AccessControl_Helper_Data::REGISTERED, $data)) {
        $data = array(ZetaPrints_AccessControl_Helper_Data::REGISTERED);

        Mage::getSingleton('adminhtml/session')
          ->addNotice(Mage::helper('adminhtml')
            ->__('Customer groups besides REGISTERED were removed from the selection.'));
      }

      $this->setValue($data);
    }

  return parent::_beforeSave();
  }
}

?>
