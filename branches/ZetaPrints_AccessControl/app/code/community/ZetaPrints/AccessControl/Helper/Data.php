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
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Core helper
 * Catalog and product access logic.
 *
 * @category   ZetaPrints
 * @package    ZetaPrints_AccessControl
 * @author     Anatoly A. Kazantsev <anatoly.kazantsev@gmail.com>
 */
class ZetaPrints_AccessControl_Helper_Data extends Mage_Core_Helper_Abstract {

  const ACCESS_GROUPS_ATTRIBUTE_ID = 'accesscontrol_show_group';

  const USE_DEFAULT = '-2';
  const NONE = '-1';
  const REGISTERED = '-3';
  const ALL = '-4';

  public function has_customer_group_access_to_product ($product, $customer_group = null) {
    $category = $product->getCategory();

    //If current category is know then check access to the category
    if ($category && $category->getId)
      return $this->has_customer_group_access_to_category($category, $customer_group);

    if (!$category_ids = $product->getCategoryIds())
      return false;

    foreach ($category_ids as $category_id) {
      $category = Mage::getModel('catalog/category')->load($category_id);

      if ($category->getId()
        && $this->has_customer_group_access_to_category($category, $customer_group))

        return true;
    }

    return false;
  }

  public function has_customer_group_access_to_category ($category, $customer_group = null) {
    if (!$this->is_extension_enabled() || $this->is_in_admin_panel())
      return false;

    if (!isset($customer_group))
      $customer_group = $this->get_current_customer_group();

    $access_groups = $this->get_access_groups_for_category($category);

    if (!$this->has_category_custom_access($access_groups)) {
      $access_groups = explode(',', $this->get_store_config_value('default_category_groups'));
    }

    return $this->is_customer_group_in_access_groups($customer_group, $access_groups);
  }

  public function filter_out_categories ($collection) {
    foreach ($collection as $item)
      if (!$this->has_customer_group_access_to_category($item))
        $collection->removeItemByKey($item->getId());
  }

  public function filter_out_products ($collection) {
    foreach ($collection as $item)
      if (!$this->has_customer_group_access_to_product($item))
        $collection->removeItemByKey($item->getId());
  }

  protected function has_category_custom_access ($access_groups) {
    return !(count($access_groups) == 1 && $access_groups[0] == self::USE_DEFAULT);
  }

  private function is_customer_group_in_access_groups ($customer_group, $access_groups) {
    if ($this->is_all_customers_rule($access_groups) && $customer_group >= 0)
      return true;

    if ($this->is_registered_customers_rule($access_groups) && $customer_group > 0)
      return true;

    return in_array($customer_group, $access_groups);
  }

  private function is_registered_customers_rule ($access_groups) {
    return count($access_groups) == 1 && $access_groups[0] == self::REGISTERED;
  }

  private function is_all_customers_rule ($access_groups) {
    return count($access_groups) == 1 && $access_groups[0] == self::ALL;
  }

  public function is_extension_enabled () {
    return (bool) $this->get_store_config_value('extension_status');
  }

  protected function is_in_admin_panel () {
    return Mage::app()->getStore()->isAdmin();
  }

  protected function get_store_config_value ($key) {
    return Mage::getStoreConfig("catalog/accesscontrol/{$key}", Mage::app()->getStore());
  }

  //Check for calling number: 1
  protected function get_current_customer_group () {
    return Mage::getSingleton('customer/session')->getCustomerGroupId();
  }

  public function get_access_groups_for_category ($category) {
    $accessGroups = $category->getDataUsingMethod(self::ACCESS_GROUPS_ATTRIBUTE_ID);

    if (!isset($access_groups) || $access_groups === '')
      //Try to load that attribute in case it's just not loaded
      //I do that on a clone so the store view name doesn't get overwritten
      //UGLY hack but it works until I find a better way
      $access_groups = $category->setStoreId(Mage::app()->getStore()->getId())->load($category->getId())->getDataUsingMethod(self::ACCESS_GROUPS_ATTRIBUTE_ID);

    //for reference:
    // from moshe:
    //$attr = Mage::getSingleton('eav/config')->getAttribute('catalog_category', 'your_attribute');
    //will give you all the info about attribute, including table and attribute_id,
    //which you can use to populate objects in category collection, so basically you could load
    //records for all category ids and attribute_id from table_name and
    //foreach ($collection as $o) $o->setData('your_attribute', $rows[$o->getId()]); :)

    //if it really isn't set fall back to use store default
    if (!isset($access_groups) || $access_groups === '')
      return array(self::USE_DEFAULT);

    if (is_string($access_groups))
      return explode(',', $access_groups);

    return $access_groups;
  }


  public function addGroupsFilterToProductCollection(Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection, $customerGroup = null) {
    if ($this->isExtensionEnabled() && (! $this->isInAdminPanel() || isset($customerGroupId)))
    {
      if (! isset($customerGroupId))
      {
        $customerGroupId = $this->getCurrentCustomerGroup();
      }

      $attribute = Mage::getModel('catalog/product')->getResource()->getAttribute(self::ACCESS_GROUPS_ATTRIBUTE_ID);

            $tableAlias = '_' . self::ACCESS_GROUPS_ATTRIBUTE_ID . '_table';
            $attributeValueCol = 'IFNULL(' . $tableAlias . '.value' . ', ' . $attribute->getDefaultValue() . ')';

            $select = $collection->getSelect();

            $tableCondition = 'e.entity_id='.$tableAlias.'.entity_id AND ' .
                $tableAlias.'.attribute_id' . '=' .$attribute->getId();

            $select->joinLeft(
                array($tableAlias => $attribute->getBackend()->getTable()),
                $tableCondition, 'value'
            );

            $commonConditionsSql = sprintf(
                    $attributeValueCol . " = '%1\$s' OR " .
                    "(" .
                        $attributeValueCol . " like '%1\$s,%%' OR " .
                        $attributeValueCol . " like '%%,%1\$s' OR " .
                        $attributeValueCol . " like '%%,%1\$s,%%'" .
                    ")",
                    $customerGroupId
            );


      Mage::log($commonConditionsSql);

            if ($this->isCustomerGroupInAccessGroups($customerGroupId, explode(',', $this->getStoreConfigValue('default_product_groups'))))
            {
                $select->where(
                    $attributeValueCol . " = ? OR ( " .
                    $commonConditionsSql . ")",
                    self::USE_DEFAULT
                );
            }
            else
            {
        Mage::log('No group access');
                $select->where(
                    $attributeValueCol . " != ? AND ( " .
                    $commonConditionsSql . ")",
                    self::USE_DEFAULT
                );
            }
    }
  }
}

?>
