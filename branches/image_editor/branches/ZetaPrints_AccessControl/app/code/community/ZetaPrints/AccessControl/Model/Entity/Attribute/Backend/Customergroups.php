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
 * Backend model for attribute with multiple values;
 * with changes for ZetaPrints AccessControl extension.
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Model_Entity_Attribute_Backend_Customergroups
  extends Mage_Eav_Model_Entity_Attribute_Backend_Abstract {

  /**
   * The name of the db field to grow (of the attribute value table)
   *
   * @var string
   */
  protected $_dbFieldName = 'value';

  /**
   * When growing the field length, make the new length a multiple of this factor
   *
   * @var int
   */
  protected $_dbFieldLengthFactor = 512;

  /**
   * Prepare the customer groups selecton array before saving, and clear the layered navigation cache if needed.
   *
   * @param Mage_Catalog_Model_Product $object
   * @return nothing afaik :)
   */
  public function beforeSave($object) {
    $data = $object->getData($this->getAttribute()->getAttributeCode());
    $helper = Mage::helper('accesscontrol');

    //Default to using the default - don't let the customer select nothing
    if (empty($data))
      $data = array(ZetaPrints_AccessControl_Helper_Data::USE_DEFAULT);
    elseif (count($data) > 1) {
      if (in_array(ZetaPrints_AccessControl_Helper_Data::USE_DEFAULT, $data)) {

        //Remove the "use default" value if other groups are selcted with it
        $data_tmp = array();

        foreach ($data as $v)
          if ($v != ZetaPrints_AccessControl_Helper_Data::USE_DEFAULT)
            $data_tmp[] = $v;

        $data = $data_tmp;

        Mage::getSingleton('adminhtml/session')->addNotice(
          $helper->__('The USE DEFAULT selection was ignored because you also selected other customer groups.') );
      }

      if (in_array(ZetaPrints_AccessControl_Helper_Data::NONE, $data)) {

        //Remove all groups but the "none" value
        $data = array(ZetaPrints_AccessControl_Helper_Data::NONE);

        Mage::getSingleton('adminhtml/session')->addNotice(
          $helper->__('Customer groups besides NONE where removed from the selection.') );
      } elseif (in_array(ZetaPrints_AccessControl_Helper_Data::ALL, $data)) {
        $data = array(ZetaPrints_AccessControl_Helper_Data::ALL);

        Mage::getSingleton('adminhtml/session')->addNotice($helper
          ->__('Customer groups besides ALL were removed from the selection.') );
      } elseif (in_array(ZetaPrints_AccessControl_Helper_Data::REGISTERED, $data)) {
        $data = array(ZetaPrints_AccessControl_Helper_Data::REGISTERED);

        Mage::getSingleton('adminhtml/session')->addNotice($helper
          ->__('Customer groups besides REGISTERED were removed from the selection.') );
      }
    }

    if (is_array($data))
      $data = implode(',', $data);

    $object->setData($this->getAttribute()->getAttributeCode(), $data);

    $this->checkDbFieldLength($object);

    return parent::beforeSave($object);
  }

  protected function _getAggregator () {
    return Mage::getSingleton('catalogindex/aggregation');
  }

  public function checkDbFieldLength ($object) {
    $attributeCode = $this->getAttribute()->getAttributeCode();
    $requiredLength = strlen($object->getData($attributeCode));
    $adapter = $object->getResource()->getWriteConnection();
    $fieldLength = $this->getDbFieldLength($adapter);

    if ($fieldLength -2 < $requiredLength) {
      if (!Mage::helper('accesscontrol')->get_store_config_value('grow_db_field')) {
        $this->_warnDbFieldLength($fieldLength, $requiredLength);
        return;
      }

      $newLength = $this->_getNewDbFieldLength($requiredLength);
      $definition = sprintf("VARCHAR(%d) NOT NULL DEFAULT ''", $newLength);
      $adapter->modifyColumn($this->getTable(), $this->_dbFieldName, $definition);
    }
  }

  public function getDbFieldLength ($adapter = null) {
    if (!isset($adapter))
      $adapter = Mage::getResourceModel('catalog/product')->getWriteConnection();

    $info = $adapter->describeTable($this->getTable());

    return $info[$this->_dbFieldName]['LENGTH'];
  }

  protected function _getNewDbFieldLength ($requiredLength) {
    return (floor($requiredLength / $this->_dbFieldLengthFactor) +1) * $this->_dbFieldLengthFactor;
  }

  protected function _warnDbFieldLength ($fieldLength, $requiredLength) {
    Mage::getSingleton('adminhtml/session')->addError(
      Mage::helper('groupscatalog')->__('The db field size is %s bytes to small to save the group permissions. Please enable the configuration setting to dynamicaly grow the field length and try again.',
        ($requiredLength - $fieldLength > 0 ? $requiredLength - $fieldLength : 0) ) );
  }
}

?>
