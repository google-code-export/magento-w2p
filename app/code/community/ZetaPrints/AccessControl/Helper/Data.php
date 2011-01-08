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
 * Core helper class.
 * Catalog and product access logic.
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Helper_Data extends Mage_Core_Helper_Abstract {

  /**
   * The attribute code used for customer groups access to category checking.
   *
   * @var string
   */
  const ACCESS_GROUPS_ATTRIBUTE_ID = 'accesscontrol_show_group';

  /**
   * The value of the catalog attribute when the global config settings
   * for customer group access should be used
   */
  const USE_DEFAULT = '-2';

  /**
   * The value of the catalog attribute when access to the catalog should be
   * denied to any customer.
   */
  const NONE = '-1';

  /**
   * The value of the catalog attribute when access to the catalog should be
   * allowed to any registered and logged in customer.
   */
  const REGISTERED = '-3';

  /**
   * The value of the catalog attribute when access to the catalog should be
   * allowed to any customer (logged in or not).
   */
  const ALL = '-4';

  /**
   * Checks whether customer's group has access to product or not.
   *
   * @param Mage_Catalog_Model_Product $product
   * @param int $customer_group
   * @return bool
   */
  public function has_customer_group_access_to_product ($product, $customer_group = null) {
    if (!$this->is_extension_enabled() || $this->is_in_admin_panel())
      return true;

    $category = $product->getCategory();

    //If current category is know then check access to the category.
    if ($category && $category->getId)
      return $this->has_customer_group_access_to_category($category, $customer_group);

    //If product doesn't belong to any category then deny access to it.
    if (!$category_ids = $product->getCategoryIds())
      return false;

    //Check access to every category which the product belongs to.
    foreach ($category_ids as $category_id) {
      $category = Mage::getModel('catalog/category')->load($category_id);

      //If customer's group has access atleast to one category then allow
      //access to the product.
      if ($category->getId()
        && $this->has_customer_group_access_to_category($category, $customer_group))

        return true;
    }

    return false;
  }

  /**
   * Checks whether customer's group has access to catalog or not.
   *
   * @param Mage_Catalog_Model_Category $category
   * @param int $customer_group
   * @return bool
   */
  public function has_customer_group_access_to_category ($category, $customer_group = null) {
    if (!$this->is_extension_enabled() || $this->is_in_admin_panel())
      return true;

    //Tries to get current customer's group id if it's not specified
    //in function params
    if (!isset($customer_group))
      $customer_group = $this->get_current_customer_group();

    $access_groups = $this->get_access_groups_for_category($category);

    //Checks whether the category has custom access or not.
    if (!$this->has_category_custom_access($access_groups))
      //If it hasn't custom access then fetching global categories access params
      $access_groups = explode(',', $this->get_store_config_value('default_category_groups'));

    return $this->is_customer_group_in_access_groups($customer_group, $access_groups);
  }

  /**
   * Filters out categories with denied access from collection.
   *
   * @param $collection
   */
  public function filter_out_categories ($collection) {
    if (!$this->is_extension_enabled() || $this->is_in_admin_panel())
      return true;

    foreach ($collection as $item)
      if (!$this->has_customer_group_access_to_category($item))
        $collection->removeItemByKey($item->getId());
  }

  /**
   * Filters out products with denied access from collection.
   *
   * @param $collection
   */
  public function filter_out_products ($collection) {
    if (!$this->is_extension_enabled() || $this->is_in_admin_panel())
      return true;

    foreach ($collection as $item)
      if (!$this->has_customer_group_access_to_product($item))
        $collection->removeItemByKey($item->getId());
  }

  /**
   * Checks whether category has custom access or not.
   *
   * @param array $access_groups
   * @return bool
   */
  protected function has_category_custom_access ($access_groups) {
    return !(count($access_groups) == 1 && $access_groups[0] == self::USE_DEFAULT);
  }

  /**
   * Checks if customer group is presents in a list of groups
   * with allowed access
   *
   * @param int $customer_group
   * @param array $access_groups
   * @return bool
   */
  private function is_customer_group_in_access_groups ($customer_group, $access_groups) {
    //Checks if "All" option was selected and customer belongs to
    //any group, including "Not logged in" group.
    if ($this->is_all_customers_rule($access_groups) && $customer_group >= 0)
      return true;

    //Checks if "Registered" option was selected and customer belongs to
    //any group, excluding "Not logged in" group.
    if ($this->is_registered_customers_rule($access_groups) && $customer_group > 0)
      return true;

    return in_array($customer_group, $access_groups);
  }

  /*
   * Checks if "Registered" option were selected in access control
   *
   * @return bool
   */
  private function is_registered_customers_rule ($access_groups) {
    return count($access_groups) == 1 && $access_groups[0] == self::REGISTERED;
  }

  /*
   * Checks if "All" option were selected in access control
   *
   * @return bool
   */
  private function is_all_customers_rule ($access_groups) {
    return count($access_groups) == 1 && $access_groups[0] == self::ALL;
  }

  /**
   * Check if the extension is enabled in the system configuration.
   *
   * @return boolean
   */
  public function is_extension_enabled () {
    return ((bool) $this->get_store_config_value('extension_status'))
           && Mage::app()->getRequest()->getModuleName() !== 'api';
  }

  /**
   * Check if the script is called from the adminhtml interface.
   *
   * @return boolean
   */
  protected function is_in_admin_panel () {
    return Mage::app()->getStore()->isAdmin();
  }

  /**
   * Returns the specific config value from store for the extension.
   *
   * @return string
   */
  protected function get_store_config_value ($key) {
    return Mage::getStoreConfig("catalog/accesscontrol/{$key}", Mage::app()->getStore());
  }

  /**
   * Returns current customer's group id
   *
   * @return int
   */
  protected function get_current_customer_group () {
    return Mage::getSingleton('customer/session')->getCustomerGroupId();
  }

  /**
   * Retrieves list of groups with allowed access for category
   *
   * @param Mage_Catalog_Model_Category $category
   * @return array
   */
  public function get_access_groups_for_category ($category) {
    $accessGroups = $category->getDataUsingMethod(self::ACCESS_GROUPS_ATTRIBUTE_ID);

    //Try to load that attribute in case it's just not loaded
    if (!isset($accessGroups) || $accessGroups === '')
      $accessGroups = $this->_loadAttributeValue($category);

    //if it really isn't set fall back to use store default
    if (!isset($accessGroups) || $accessGroups === '')
      return array(self::USE_DEFAULT);

    if (is_string($accessGroups))
      return explode(',', $accessGroups);

    return $accessGroups;
  }

  /**
   * Load value of accesscontrol_show_group attribute,
   * set the attribute on the item and return loaded attribute value.
   *
   * @param Mage_Catalog_Model_Abstract $item
   * @return mixed
   */
  protected function _loadAttributeValue ($category) {
    $resource = $category->getResource();
    $connection = $resource->getReadConnection();
    $select = $connection->select()->reset();

    $categoryId = $category->getId();

    if (Mage::helper('catalog/category_flat')->isEnabled())
      $select->from($resource->getMainTable(), self::ACCESS_GROUPS_ATTRIBUTE_ID)
        ->where('entity_id = ?', $categoryId);
    else {
      $attribute = $resource->getAttribute(self::ACCESS_GROUPS_ATTRIBUTE_ID);

      $table = $attribute->getBackendTable();
      $attributeId = $attribute->getId();
      $typeId = $resource->getTypeId();

      $select->from(array('default_value' => $table), array())
        ->where('default_value.attribute_id = ?', $attributeId)
        ->where('default_value.entity_type_id = ? ', $typeId)
        ->where('default_value.entity_id = ? ', $categoryId)
        ->where('default_value.store_id = 0');

      $joinCondition = $connection->quoteInto('store_value.attribute_id  = ?',
                                              $attributeId)
        . ' AND '
        . $connection->quoteInto('store_value.entity_type_id = ?', $typeId)
        . ' AND '
        . $connection->quoteInto('store_value.entity_id = ?', $categoryId)
        . ' AND '
        . $connection->quoteInto('store_value.store_id = ?',
                                 Mage::app()->getStore()->getId());

      $select->joinLeft(array('store_value' => $table),
                        $joinCondition,
                        array('attr_value' =>
                                'IFNULL(store_value.value, default_value.value)',
                              'default_value.attribute_id') );
    }

    $result = (string) $connection->fetchOne($select);

    $category->setDataUsingMethod(self::ACCESS_GROUPS_ATTRIBUTE_ID, $result);

    return $result;
  }
}

?>
