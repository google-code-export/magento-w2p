<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kris
 * Date: 11-5-6
 * Time: 20:24
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_Options_Helper_Data
  extends Mage_Core_Helper_Data
{
  public function getOptionArray(Mage_Catalog_Model_Product_Option $option)
  {
    $commonArgs = array(
      'is_delete',
      'previous_type',
      'previous_group',
      'title',
      'type',
      'is_require',
      'sort_order',
      'values',
    );
    $priceArgs = array(
      'price_type',
      'price',
      'sku',
    );
    $txtArgs = array('max_characters');
    $fileArgs = array(
      'file_extension',
      'image_size_x',
      'image_size_y'
    );
    $multiArgs = array(
      'option_type_id',
      'is_delete',
      'title',
      'sort_order',
    );

    $multi = array(
      'drop_down',
      'radio',
      'checkbox',
      'multiple',
    );

    $valueArgs = array_merge($multiArgs, $priceArgs);

    $type = $option->getType();
    switch ($type) {
      case 'file':
        $optionArgs = array_merge($commonArgs, $priceArgs, $fileArgs);
        break;
      case 'field':
      case 'area':
        $optionArgs = array_merge($commonArgs, $priceArgs, $txtArgs);
        break;
      case 'date':
      case 'date_time':
      case 'time':
        $optionArgs = array_merge($commonArgs, $priceArgs);
        break;
      default :
        $optionArgs = $commonArgs;
        break;
    }

    $optionArray = $option->toArray($optionArgs);
    if (in_array($type, $multi)) {
      $optionArray['values'] = array();
      foreach ($option->getValues() as $value) {
        $optionArray['values'][] = $value->toArray($valueArgs);
      }
    }

    return $optionArray;
  }
}
