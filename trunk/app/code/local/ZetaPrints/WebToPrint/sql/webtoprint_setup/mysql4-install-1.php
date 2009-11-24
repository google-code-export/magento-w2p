<?php

$installer = $this;
$installer->startSetup();

$installer->run("
  CREATE TABLE `{$installer->getTable('webtoprint/template')}` (
    `template_id` int(11) NOT NULL auto_increment,
    `guid` varchar(36),
    `catalog_guid` varchar(36),
    `title` text,
    `link` text,
    `description` text,
    `thumbnail` text,
    `image` text,
    `date` timestamp,
    `public` bool,
    `xml` text,
    PRIMARY KEY  (`template_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;" );

/*$installer->addAttribute('catalog_product', 'webtoprint_template',
  array(
    'type'              => 'varchar',
    'backend'           => '',
    'frontend'          => '',
    //'label'             => '',
    'input'             => '',
    'class'             => '',
    'source'            => '',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'           => false,
    'required'          => false,
    'user_defined'      => false,
    'default'           => '',
    'searchable'        => false,
    'filterable'        => false,
    'comparable'        => false,
    'visible_on_front'  => false,
    'unique'            => true ));*/

$installer->endSetup();

//$profile_model = Mage::getModel('dataflow/profile');
//$profile_model->setName('Zetaprints templates importing')
  //->setActionsXml('<action type="webtoprint/products-creation" method="map" />')
  //->setGuiData(false)
  //->setDirections('import')
  //->setDataTransfer('interactive')
  //->save();

?>
