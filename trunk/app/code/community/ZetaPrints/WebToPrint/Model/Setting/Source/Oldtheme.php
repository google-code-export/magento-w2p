<?php

/**
 * Source model for Support for old custom themes setting
 *
 * @package ZetaPrints/WebToPrint
 * @author Anatoly A. Kazantsev <anatoly@zetaprints.com>
 */
class ZetaPrints_WebToPrint_Model_Setting_Source_Oldtheme {

  const NO = 'no';

  protected $_options;

  /**
   * Return list of installed themes for the setting
   *
   * @return array
   *   List of installed themes
   */
  public function toOptionArray () {
    if (!$this->_options) {

      $this->_options = array(
        array(
          'value' => self::NO,
          'label' => Mage::helper('core')->__('No')
        )
      );

      $themes = Mage::getModel('core/design_source_design')
        ->setIsFullLabel(true)
        ->getAllOptions(false);

      foreach ($themes as $theme)
        if (is_array($theme['value']))
          foreach ($theme['value'] as $value)
            $this->_options[] = array(
              'value' => $value['value'],
              'label' => $value['label']
            );
        else
          $this->_options[] = array(
            'value' => $theme['value'],
            'label' => $theme['label']
          );
    }

    return $this->_options;
  }
}