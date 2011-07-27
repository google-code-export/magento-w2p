<<<<<<< .mine
<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 5/18/11
 * Time: 3:29 PM
 */
$mageFilename = 'app/Mage.php';
if (!file_exists($mageFilename)) {
    echo $mageFilename." was not found";
    exit;
}
//Initialize Magento
require_once $mageFilename;

//Run Mage app
Mage::app('default');

//Get setup model
$setup = Mage::getModel('eav/entity_setup',  'core_setup');

$setup->startSetup();

echo 'Removing attribute';

//Remove installed attributes
$setup->removeAttribute('catalog_product', 'fixed_price');

echo '[OK]<br />';

echo 'Removing tables ';

$setup->run("DROP TABLE IF EXISTS {$setup->getTable('zetaprints_product_entity_fixed_price')}");
echo '[OK]<br />';
echo 'Remove resource references';
//Remove record about extension from resource table
$setup->run("
  DELETE FROM {$setup->getTable('core/resource')}
    WHERE code = 'fixedprices_setup'");

echo '[OK]<br />';

$setup->endSetup();

echo 'ZetaPrints Fixed Quantities extension was completely removed';
=======
<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 5/18/11
 * Time: 3:29 PM
 */
$mageFilename = 'app/Mage.php';
if (!file_exists($mageFilename)) {
    echo $mageFilename." was not found";
    exit;
}
//Initialize Magento
require_once $mageFilename;

//Run Mage app
Mage::app('default');

//Get setup model
$setup = Mage::getModel('eav/entity_setup',  'core_setup');

$setup->startSetup();

echo 'Removing attribute';

//Remove installed attributes
$setup->removeAttribute('catalog_product', 'fixed_price');

echo '[OK]<br />';

echo 'Removing tables ';

$setup->run("DROP TABLE IF EXISTS {$setup->getTable('zetaprints_product_entity_fixed_price')}");
echo '[OK]<br />';
echo 'Remove resource references';
//Remove record about extension from resource table
$setup->run("
  DELETE FROM {$setup->getTable('core/resource')}
    WHERE code = 'fixedprices_setup'");

echo '[OK]<br />';

$setup->endSetup();

echo 'ZetaPrints Fixed Quantities extension was completely removed';
>>>>>>> .r1756
