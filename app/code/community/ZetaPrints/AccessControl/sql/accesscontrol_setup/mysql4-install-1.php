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
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog
 * @copyright  Copyright (c) 2008 Vinai Kopp http://netzarbeiter.com/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @var $this Mage_Eav_Model_Entity_Setup
 */
$this->startSetup();

$this->addAttribute('catalog_category', 'accesscontrol_show_group', array(
  'type'            => 'varchar',
  'label'           => 'Show to customer groups',
  'input'           => 'multiselect',
  'source'          => 'accesscontrol/config_source_customergroups_category',
  'backend'         => 'accesscontrol/entity_attribute_backend_customergroups',
  'backend_model'   => 'accesscontrol/entity_attribute_backend_customergroups',
  'global'          => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
  'required'        => true,
  'default'         => ZetaPrints_AccessControl_Helper_Data::USE_DEFAULT,
  'user_defined'    => 1,
));

$this->endSetup();


// EOF
