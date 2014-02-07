<?php

class ZetaPrints_WebToPrint_Helper_Category
  extends ZetaPrints_WebToPrint_Helper_Data {

  //Cache for loaded categories
  private $_mapping = array();

  /**
   * Get list of categories IDs by template details.
   * The only required item in the template details is 'catalogue',
   * which is a name of catalogue in ZetaPrints service.
   *
   * Set 'fullName' parameter to false if full category path in Magento
   * is not required.
   *
   * Search categories by store-specific name if 'store' parameter is set.
   *
   * @param array $template
   * @param bool $fullPath
   * @param Mage_Core_Model_Store|null $store
   *
   * @return array|null
   */
  public function getCategoriesIds ($template, $fullPath = true, $store = null) {
    if (!(isset($template['catalogue']) && ($name = $template['catalogue'])))
      return;

    if (!array_key_exists($name, $this->_mapping)) {
      $category = $this->getCategory($name, true, null, $store);

      $this->_mapping[$name] = $category && $category->getId()
                                 ? $category
                                   : null;
    }

    if (!$category = $this->_mapping[$name])
      return;

    $categoryIds = $fullPath
                     ? $category->getPathIds()
                       : array($category->getId());

    if (!isset($template['tags']))
      return $categoryIds;

    foreach ($template['tags'] as $tag) {
      $subName = $name . '/' . $tag;

      if (!array_key_exists($subName, $this->_mapping)) {
        $subCategory = $this->getCategory($tag, false, $category, $store);

        $this->_mapping[$subName] = $subCategory && $subCategory->getId()
                                      ? $subCategory
                                        : null;
      }

      if ($subCategory = $this->_mapping[$subName])
        $categoryIds[] = $subCategory->getId();
    }

    return $categoryIds;
  }

  /**
   * Search category by the name from root category or specified one.
   * Create category when it doesn't exist if $createIfNotExists
   * parameter is set.
   * Search category by store-specific name if $store parameter is set.
   *
   * @param string $name
   * @param bool $createIfNotExists
   * @param Mage_Catalog_Model_Category $parent
   * @param null|Mage_Core_Model_Store $store
   *
   * @return Mage_Catalog_Model_Category|null
   */
  public function getCategory ($name,
                               $createIfNotExists = false,
                               $parent = null,
                               $store = null) {

    $store = $store instanceof Mage_Core_Model_Store
               ? $store
                 : Mage::app()->getStore();

    $collection = ($parent && $parentId = $parent->getId())
                    ? $parent
                        ->setStoreId($store->getId())
                        ->getCollection()
                        ->addFieldToFilter('parent_id', $parentId)
                      : Mage::getModel('catalog/category')
                          ->setStoreId($store->getId())
                          ->load($store->getRootCategoryId())
                          ->getCollection();

    $collection->addAttributeToFilter('name', $name);

    if ($collection->count())
      return $collection->getFirstItem();

    if (!$createIfNotExists)
      return;

    if ($parent && $parent->getId())
      $rootCategory = $parent;
    else {
      $collection = Mage::getModel('catalog/category')
        ->getCollection()
        ->addAttributeToFilter('parent_id', 1);

      if (count($collection) != 1)
        return null;

      $rootCategory = $collection->getFirstItem();

      if (!$rootCategory->getId())
        return null;
    }

    $model = Mage::getModel('catalog/category');

    $model
      ->setStoreId($rootCategory->getStoreId())
      ->setData(array(
          'name' => $name,
          'is_active' => 1,
          'include_in_menu' => 1
        ))
      ->setPath($rootCategory->getPath())
      ->setAttributeSetId($model->getDefaultAttributeSetId());

    try {
      $model->save();
    } catch (Exception $e) {
      return null;
    }

    return $model;
  }
}
