<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Fixedprices
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
/* @var $installer ZetaPrints_Fixedprices_Model_Resource_Setup */

$installer->removeAttribute('catalog_product', ZetaPrints_Fixedprices_Helper_Data::USE_FIXED_PRICE);


$installer->endSetup();
