<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 4/7/11
 * Time: 5:38 PM
 */

class ZetaPrints_Theme_Block_Abstract
  extends Mage_Core_Block_Template
{
  /**
   * @var ZetaPrints_Theme_Helper_Data
   */
  protected $helper;
  protected $scriptPath;

  public function _construct()
  {
    $this->helper = Mage::helper('themeswitch');
  }

  public function renderView()
  {
    $dir = $this->getScriptPath();
    $this->setScriptPath($dir);
    $html = $this->fetchView($this->getTemplate());
    return $html;
  }

  protected function getScriptPath()
  {
    if (!isset($this->scriptPath)) {
      $this->scriptPath = $this->helper->getExtBaseDir();
    }
    $dir = realpath($this->scriptPath . '/template/');
    return $dir;
  }
}
