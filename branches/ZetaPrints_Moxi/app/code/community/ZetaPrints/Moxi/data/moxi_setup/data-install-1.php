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
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$profiles = array(
  array('name' => 'OpenX advertising plans creation',
        'xml' => '<action type="moxi/advertising-plans-creation" method="map" />' ) );

foreach ($profiles as $profile) {
  $profile_model = Mage::getModel('dataflow/profile');

  if ($profile_model->getResource()->isProfileExists($profile['name'])) {
    $collection = $profile_model->getCollection();
    $collection->getSelect()->where('name = ?', $profile['name']);

    if ($collection->count() == 1)
      $profile_model = $collection->getFirstItem();
  }

  $profile_model
    ->setName($profile['name'])
    ->setActionsXml($profile['xml'])
    ->setGuiData(false)
    ->setDataTransfer('interactive')
    ->save();
}
