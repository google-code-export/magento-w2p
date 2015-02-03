<?php

/**
 * Backend model for Support for old custom themes setting
 *
 * @package ZetaPrints/WebToPrint
 * @author Anatoly A. Kazantsev <anatoly@zetaprints.com>
 */
class ZetaPrints_WebToPrint_Model_Setting_Backend_Oldtheme
  extends Mage_Core_Model_Config_Data
{
  const NO = ZetaPrints_WebToPrint_Model_Setting_Source_Oldtheme::NO;

  protected $_handles = array(
    '_webtoprint_common' => '<update handle="webtoprint_common" />',
    '_webtoprint' => '<update handle="webtoprint" />'
  );

  /**
   * This method is called before saving the model.
   * Remove No value if several values are selected
   */
  protected function _beforeSave () {
    $values = $this->getValue();

    if (!$values || (count($values) == 1 && $values[0] == self::NO))
      return;

    foreach ($values as $id => $value)
      if ($value == self::NO) {
        unset($values[$id]);
        continue;
      }

    $this
      ->setValue($values)
      ->setDesigns($values);
  }

  /**
   * This method is called after saving the model.
   * Remove existing layout updates and add new if themes are selected
   */
  protected function _afterSave () {
    if (!$this->isValueChanged())
      return;

    $resource = Mage::getSingleton('core/resource');
    $conn = $resource->getConnection(
      Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE
    );

    $layoutUpdateTable = $resource->getTableName('core/layout_update');
    $layoutLinkTable = $resource->getTableName('core/layout_link');

    $conn->delete(
      $layoutUpdateTable,
      $conn->prepareSqlCondition(
        'handle',
        array('in' => array_keys($this->_handles))
      )
    );

    $designs = $this->getDesigns();

    if (!$designs)
      return;

    $data = array(
      'store_id' => 0,
      'area' => 'frontend',
    );

    foreach ($designs as $k => $design) {
      list($data['package'], $data['theme']) = explode('/', $design);

      foreach ($this->_handles as $handle => $xml) {
        $conn->insert(
          $layoutUpdateTable,
          array(
            'handle' => $handle,
            'xml' => $xml
          )
        );

        $data['layout_update_id'] = $conn->lastInsertId($layoutUpdateTable);

        $conn->insert($layoutLinkTable, $data);
      }
    }
  }
}
