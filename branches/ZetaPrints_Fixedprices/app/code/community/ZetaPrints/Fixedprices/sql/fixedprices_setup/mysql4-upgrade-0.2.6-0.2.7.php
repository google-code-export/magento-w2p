<<<<<<< .mine
<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Fixedprices
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
/* @var $installer ZetaPrints_Fixedprices_Model_Resource_Setup */

$installer->updateAttribute('catalog_product', ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE, 'frontend_label', 'Fixed Quantities:');

$installer->updateAttribute('catalog_product', ZetaPrints_Fixedprices_Helper_Data::USE_FIXED_PRICE, 'frontend_label', 'Use Fixed Quantities:');
$installer->endSetup();
=======
<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Fixedprices
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$installer = $this;
/* @var $installer ZetaPrints_Fixedprices_Model_Resource_Setup */

$installer->updateAttribute('catalog_product', ZetaPrints_Fixedprices_Helper_Data::FIXED_PRICE, 'frontend_label', 'Fixed Quantities:');

$installer->updateAttribute('catalog_product', ZetaPrints_Fixedprices_Helper_Data::USE_FIXED_PRICE, 'frontend_label', 'Use Fixed Quantities:');
$installer->endSetup();
>>>>>>> .r1756
