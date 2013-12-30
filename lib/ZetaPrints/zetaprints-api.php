<?php

define("ZP_API_VER", '2.0.0');

//ZP errors

define('ZP_ERR_UKNOWN', 0);
define('ZP_ERR_WRONG_ID_HASH_COMBO', 1);

$_zp_error_handlers = null;

require_once 'mage-logging.php';

function zetaprints_generate_guid () {
  return strtoupper(sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
    mt_rand(0, 65535), mt_rand(0, 65535),
    mt_rand(0, 65535),
    mt_rand(0, 4095),
    bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
    mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) ));
}

function zetaprints_generate_password () {
  return substr(md5(time()), 0, 6);
}

/**
 * Generate md5 hash from user's password and server ip address.
 *
 * Param password - user's password
 * Returns string contains hash
 */
function zetaprints_generate_user_password_hash ($password) {
  _zetaprints_debug();
  $ip = $_SERVER["SERVER_ADDR"];

  //Enter the external ip address here
  //if it doesn't match the server's address (e.g. translated by a router)
  //$ip = 'a.b.c.d';

  _zetaprints_debug("Server IP: {$ip}");

  return md5($password.$ip);
}

function _zetaprints_string_to_date ($value) {
  if (!$value)
    return date('Y-m-d h:i:s');

  return date('Y-m-d h:i:s', strtotime($value));
}

/**
 * Transform template details xml to html form.
 *
 * Param template_xml - string contains template details xml
 * Returns string contains html form
 */
function zetaprints_get_html_from_xml ($xml, $xslt, $params) {
  if (is_string($xml)) {
    $xml_dom = new DOMDocument();
    $xml_dom->loadXML($xml);
  } else
    $xml_dom = $xml;

  $xslt_dom = new DOMDocument();
  $xslt_dom->load(dirname(__FILE__) . '/xslt/' . $xslt . '.xslt');

  $proc = new XSLTProcessor();
  $proc->importStylesheet($xslt_dom);

  $proc->setParameter('', $params);
  return $proc->transformToXML($xml_dom);
}

function zetaprints_get_list_of_catalogs ($url, $key) {
  _zetaprints_debug();
  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-catalogs;ApiKey=$key");

  if (zetaprints_has_error($response))
    return $response['content'];

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }


  $catalogs = array();

  foreach ($xml->channel[0]->item as $item)
    $catalogs[] = array('title' => (string)$item->title,
                        'link' => (string)$item->link,
                        'guid' => (string)$item->id,
                        'domain' => (string)$item->domain,
                        'templates' => (int)$item->templates,
                        'users' => (int)$item->users,
                        'orders' => (int)$item->orders,
                        'created' => strtotime($item->created),
                        'public' => (string)$item->access == 'public' ? true : false,
                        'keywords' => (string)$item->keywords);

  _zetaprints_debug(array('catalogs' => $catalogs));

  return $catalogs;
}

function zetaprints_get_templates_from_catalog ($url, $key, $catalog_guid) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-templates;CorporateID=$catalog_guid;ApiKey=$key");

  if (zetaprints_has_error($response))
    return $response['content'];

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  $templates = array();

  foreach ($xml->channel[0]->item as $item)
    $templates[] = array('title' => (string)$item->title,
                         'link' => (string)$item->link,
                         'guid' => (string)$item->id,
                         'catalog_guid' => (string)$item->cid,
                         'description' => (string)$item->description,
                         'date'
                             => _zetaprints_string_to_date($item->lastModified),
                         'thumbnail' => (string)$item->thumbnail,
                         'image' => (string)$item->image);

  _zetaprints_debug(array('templates' => $templates));

  return $templates;
}

function zetaprints_parse_template_details ($xml) {
  if ($xml->getName() !== 'TemplateDetails')
    return null;

  $download = false;

  if (isset($xml['Download']) && ((string)$xml['Download'] == 'allow'
      || (string)$xml['Download'] == 'only'))
    $download = true;

  $template = array('guid' => (string) $xml['TemplateID'],
                     'corporate-guid' => (string) $xml['CorporateID'],
                     'created' => _zetaprints_string_to_date($xml['Created']),
                     'comments' => (string) $xml['Comments'],
                     'url' => (string) $xml['AccessURL'],
                     'product-reference' => (string) $xml['ProductReference'],
                     'download' => $download,
                     'missed_pages' => (string) $xml['MissedPages'],
                     'pdf' => isset($xml['GeneratePdf'])
                                  ? (bool) $xml['GeneratePdf'] : false,
                     'jpeg' => isset($xml['GenerateJpg'])
                                  ? (bool) $xml['GenerateJpg'] : false,
                     'png' => isset($xml['GenerateGifPng'])
                                  ? (bool) $xml['GenerateGifPng'] : false,
                     'dataset-integrity-enforce'
                       => isset($xml['DatasetIntegrityEnforce'])
                            ? (bool) $xml['DatasetIntegrityEnforce'] : false );

  if (!$xml->Pages->Page) {
    _zetaprints_debug("No pages in tempalate [{$template['guid']}]");

    return $template;
  }

  $template['pages'] = array();

  $page_number = 1;

  $field_to_shape_mapping = array();

  foreach ($xml->Pages->Page as $page) {
    $template['pages'][$page_number] = array(
      'name' => (string) $page['Name'],
      'preview-image' => (string) $page['PreviewImage'],
      'thumb-image' => (string) $page['ThumbImage'],
      'static' => isset($page['Static']) ? (bool) $page['Static'] : false,
      'width-in' => (float) $page['WidthIn'],
      'height-in' => (float) $page['HeightIn'],
      'width-cm' =>  (float) $page['WidthCm'],
      'height-cm' =>  (float) $page['HeightCm'] );

    if (isset($page['PreviewUrl']))
      $template
        ['pages']
        [$page_number]
        ['preview-url'] = (string) $page['PreviewUrl'];

    if (isset($page['ThumbUrl']))
      $template
        ['pages']
        [$page_number]
        ['thumb-url'] = (string) $page['ThumbUrl'];

    if (isset($page['PreviewImageUpdated'])) {
      $updated_preview = (string) $page['PreviewImageUpdated'];

      $template
        ['pages']
        [$page_number]
        ['updated-preview-image'] = $updated_preview;

      if (isset($page['ThumbImageUpdated']))
        $updated_thumb = (string) $page['ThumbImageUpdated'];
      else
        $updated_thumb = 'thumb' . substr($updated_preview, 7);

      $template
        ['pages']
        [$page_number]
        ['updated-thumb-image'] = $updated_thumb;
    }

    if (isset($page['PreviewUrlUpdated']))
      $template
        ['pages']
        [$page_number]
        ['updated-preview-url'] = (string) $page['PreviewUrlUpdated'];

    if (isset($page['ThumbUrlUpdated']))
      $template
        ['pages']
        [$page_number]
        ['updated-thumb-url'] = (string) $page['ThumbUrlUpdated'];

    //Check for templates with old shape coordinates system
    $is_page_2_box_empty = (string) $page['Page2BoxX'] == ''
                            && (string)$page['Page2BoxY'] == ''
                            && (string)$page['Page2BoxW'] == ''
                            && (string)$page['Page2BoxH'] == '';

    //Ignore shapes with old coordinates system
    if (!$is_page_2_box_empty && $page->Shapes) {
      $template['pages'][$page_number]['shapes'] = array();

      $field_to_shape_mapping[$page_number] = array();

      foreach ($page->Shapes->Shape as $shape) {
        $name = (string) $shape['Name'];
        $template['pages'][$page_number]['shapes'][$name] = array(
          'name' => $name,
          'x1' => (float) $shape['X1'],
          'y1' => (float) $shape['Y1'],
          'x2' => (float) $shape['X2'],
          'y2' => (float) $shape['Y2'],
          'anchor-x' => (float) $shape['AnchorX'],
          'anchor-y' => (float) $shape['AnchorY'],
          'hidden' => $page_number > 1,
          'has-value' => false );

        foreach (explode('; ', $name) as $_name)
          $field_to_shape_mapping[$page_number][$_name] = $name;
      }

      $template['pages'][$page_number]['shapes'] =
                      array_reverse($template['pages'][$page_number]['shapes']);
    }

    $page_number++;
  }

  foreach ($xml->Images->Image as $image) {
    $image_array = array(
      'name' => (string) $image['Name'],
      'width' => (int) $image['Width'],
      'height' => (int) $image['Height'],
      'color-picker' => isset($image['ColourPicker'])
                            ? (string) $image['ColourPicker'] : null,
      'allow-upload' => isset($image['AllowUpload'])
                            ? (bool)(string) $image['AllowUpload'] : false,
      'allow-url' => isset($image['AllowUrl'])
                            ? (bool)(string) $image['AllowUrl'] : false,
      'clipped' => isset($image['Clipped'])
                            ? (bool) $image['Clipped'] : false,
      //We get lowercase GUID in value for user images.
      //Convert to uppercase while the issue will be fixed in ZP side
      'value' => isset($image['Value'])
                   ? strtoupper((string) $image['Value']) : null );

    if ($image->StockImage) {
      $image_array['stock-images'] = array();

      foreach ($image->StockImage as $stock_image)
        $image_array['stock-images'][] = array(
          'guid' => (string) $stock_image['FileID'],
          'mime' => (string) $stock_image['MIME'],
          'thumb' => (string) $stock_image['Thumb']
        );
    }

    $page_number = (int) $image['Page'];

    if (!isset($template['pages'][$page_number]['images']))
      $template['pages'][$page_number]['images'] = array();

    $template['pages'][$page_number]['images'][(string) $image['Name']]
                                                                = $image_array;

    if (isset($field_to_shape_mapping[$page_number][$image_array['name']])) {
      $shape_name = $field_to_shape_mapping[$page_number][$image_array['name']];

      $shape = & $template['pages']
                          [$page_number]
                          ['shapes']
                          [$shape_name];

      if ($image_array['value'])
        $shape['has-value'] = true;

      if ($page_number > 1)
        $shape['hidden'] = false;
    }
  }

  foreach ($xml->Fields->Field as $field) {
    $field_array = array(
      'name' => (string) $field['FieldName'],
      'hint' => (string) $field['Hint'],
      'min-length' => isset($field['MinLen']) ? (int) $field['MinLen'] : null,
      'max-length' => isset($field['MaxLen']) ? (int) $field['MaxLen'] : null,
      'multiline' => isset($field['Multiline'])
                        ? (bool) $field['Multiline'] : false,
      'colour-picker' => isset($field['ColourPickerFill'])
                           ? (string) $field['ColourPickerFill'] : null,
      'story' => isset($field['Story'])
                           ? (string) $field['Story'] : null,
      'story-as-default' => isset($field['StoryAsDefault'])
                           ? (int) $field['StoryAsDefault'] : null,
      'combobox' => isset($field['Combobox'])
                           ? (bool) $field['Combobox'] : false,
      'value' => isset($field['Value'])
                   ? (string) $field['Value'] : null );

    if ($field->Value) {
      $field_array['values'] = array();

      foreach ($field->Value as $value)
        $field_array['values'][] = (string) $value;
    }

    if ($field->DataSet) {
      $field_array['dataset'] = array();

      $cell_number = 0;

      foreach ($field->DataSet->Cell as $cell) {
        $field_array['dataset'][$cell_number] = array( 'lines' => array() );

        foreach ($cell->Para as $para) {
          $field_array['dataset'][$cell_number]['lines'][] = (string) $para;
        }

        $field_array['dataset'][$cell_number]['text']
                                                       = (string) $cell['text'];

        $cell_number++;
      }
    }

    if (isset($field['Meta'])) {
      $field_array['metadata'] = array();

      foreach (explode(';', (string) $field['Meta']) as $token)
        if ($token) {
          list($key, $value) = explode('=', $token);
          $field_array['metadata'][$key] = $value;
        }
    }

    $page_number = (int) $field['Page'];

    if (!isset($template['pages'][$page_number]['fields']))
      $template['pages'][$page_number]['fields'] = array();

    $template['pages'][$page_number]['fields'][(string) $field['FieldName']]
                                                                = $field_array;

    if (isset($field_to_shape_mapping[$page_number][$field_array['name']])) {
      $shape_name = $field_to_shape_mapping[$page_number][$field_array['name']];

      $shape = & $template['pages']
                          [$page_number]
                          ['shapes']
                          [$shape_name];

      if ($field_array['value'])
        $shape['has-value'] = true;

      if ($page_number > 1)
        $shape['hidden'] = false;
    }
  }

  if ($xml->Tags) {
    $tags = array();

    foreach ($xml->Tags->Tag as $tag) {
      $tags[] = (string) $tag;
    }

    if (count($tags))
      $template['tags'] = $tags;
  }

  if ($xml->Quantities) foreach ($xml->Quantities->Quantity as $quantity)
    $template['quantities'][] = array(
      'price' => (float) $quantity['Price'],
      'title' => (string) $quantity['Title'],
    );

  _zetaprints_debug(array('template' => $template));

  return $template;
}

function zetaprints_get_template_detailes ($url, $key, $template_guid) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-template;TemplateID=$template_guid;ApiKey=$key");

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_template_details($xml);
}

function zetaprints_get_template_details_as_xml ($url, $key, $template_guid,
                                                 $data = null) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-template;TemplateID=$template_guid;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  return $response['content']['body'];
}

function zetaprints_parse_order_details ($xml) {
  $order = array(
    'guid' => (string) $xml['OrderID'],
    'created-by' => (string) $xml['CreatedBy'],
    'created' => _zetaprints_string_to_date($xml['Created']),
    'status' => (string) $xml['Status'],
    'billed-by-zp' => _zetaprints_string_to_date($xml['BilledByZP']),
    'status-history' => (string) $xml['StatusHistory'],
    'product-price' => (float) $xml['ProductPrice'],
    'product-name' => (string) $xml['ProductName'],
    'pdf' => (string) $xml['PDF'],
    'cdr' => (string) $xml['CDR'],
    'gif' => (string) $xml['GIF'],
    'png' => (string) $xml['PNG'],
    'jpeg' => (string) $xml['JPEG'],
    'approval-email' => (string) $xml['ApprovalEmail'],
    'note' => (string) $xml['Note'],
    'cost-centre' => (string) $xml['CostCentre'],
    'delivery-address' => (string) $xml['DeliveryAddress'],
    'quantity-price-choice' => (string) $xml['QuantityPriceChoice'],
    'optional-choice' => (string) $xml['OptionalChoice'],
    'user-reference' => (string) $xml['UserReference'],
    'paid-date-time' => (string) $xml['PaidDateTime'],
    'currency' => (string) $xml['Currency'],
    'delivery-street-1' => (string) $xml['DeliveryStreet1'],
    'delivery-street-2' => (string) $xml['DeliveryStreet2'],
    'delivery-town' => (string) $xml['DeliveryTown'],
    'delivery-state' => (string) $xml['DeliveryState'],
    'delivery-zip' => (string) $xml['DeliveryZip'],
    'delivery-country' => (string) $xml['DeliveryCountry'] );

  $order['template-details'] =
                      zetaprints_parse_template_details($xml->TemplateDetails);

  _zetaprints_debug(array('order' => $order));

  return $order;
}

function zetaprints_get_order_details ($url, $key, $order_id) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/api.aspx?page=api-order;ApiKey=$key;OrderID=$order_id");

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_order_details($xml);
}

function zetaprints_change_order_status ($url, $key, $order_id, $old_status, $new_status) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-order-status;ApiKey=$key;OrderID=$order_id",
                                              array('Status' => $new_status,
                                                    'StatusOld' => $old_status) );

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_order_details($xml);
}

function zetaprints_update_preview ($url, $key, $data) {
  _zetaprints_debug();

  $data['Xml'] = 1;

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-preview;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_template_details($xml);
}

function zetaprints_get_preview_image_url ($url, $key, $data) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-preview;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  return $response['content']['body'];
}

function zetaprints_get_user_images ($url, $key, $data) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-imgs;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  $images = array();

  foreach ($xml->Image as $image)
    $images[(string) $image['ImageID']]
      = array('folder' => (string) $image['Folder'],
              'guid' => (string) $image['ImageID'],
              'created' => _zetaprints_string_to_date($image['Created']),
              'used' => _zetaprints_string_to_date($image['Used']),
              'updated' => _zetaprints_string_to_date($image['Updated']),
              'file_guid' => (string) $image['FileID'],
              'mime' => (string) $image['MIME'],
              'thumbnail' => (string) $image['Thumb'],
              'thumbnail_width' => (int) $image['ThumbWidth'],
              'thumbnail_height' => (int) $image['ThumbHeight'],
              'width' => (int) $image['ImageWidth'],
              'height' => (int) $image['ImageHeight'],
              'description' => (string) $image['Description'],
              'length' => (int) $image['Length'] );

  _zetaprints_debug(array('images' => $images));

  return $images;
}

function zetaprints_download_customer_image ($url, $key, $data) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/API.aspx?page=api-img-new;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  if (count($xml->Image) != 1) {
    _zetaprints_debug('Number of uploaded customer images is ' . count($xml->Image));
    return null;
  }

  $images = array();

  foreach ($xml->Image as $image)
    $images[] = array('folder' => (string)$image['Folder'],
                      'guid' => (string)$image['ImageID'],
                      'created' =>
                                  _zetaprints_string_to_date($image['Created']),
                      'used' => _zetaprints_string_to_date($image['Used']),
                      'updated' =>
                                  _zetaprints_string_to_date($image['Updated']),
                      'file_guid' => (string)$image['FileID'],
                      'mime' => (string)$image['MIME'],
                      'thumbnail' => (string)$image['Thumb'],
                      'thumbnail_width' => (int)$image['ThumbWidth'],
                      'thumbnail_height' => (int)$image['ThumbHeight'],
                      'width' => (int)$image['ImageWidth'],
                      'height' => (int)$image['ImageHeight'],
                      'description' => (string)$image['Description'],
                      'length' => (int)$image['Length'] );

  _zetaprints_debug(array('images' => $images));

  return $images;
}

function zetaprints_get_edited_image_url ($url, $key, $data) {
  _zetaprints_debug();

  if (!isset($data['action']) || strlen($data['action']) == 0) {
    _zetaprints_debug('No picture edit action specified');

    return null;
  }

  $action = $data['action'];
  unset($data['action']);

  $response = zetaprints_get_content_from_url("{$url}/API.aspx?page=api-{$action};ApiKey={$key}", $data);

  if (zetaprints_has_error($response))
    return null;

  return $response['content']['body'];
}

function zetaprints_create_order ($url, $key, $data) {
  _zetaprints_debug();

  $data['Xml'] = 1;

  $response = zetaprints_get_content_from_url("$url/api.aspx?page=api-order-save;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_order_details($xml);
}

function zetaprints_get_order_id ($url, $key, $data) {
  _zetaprints_debug();

  $response = zetaprints_get_content_from_url("$url/api.aspx?page=api-order-save;ApiKey=$key", $data);

  if (zetaprints_has_error($response))
    return null;

  return $response['content']['body'];
}

function zetaprints_complete_order ($url, $key, $order_guid, $new_guid = null) {
  _zetaprints_debug();

  if ($new_guid)
    $new_guid_parameter = ";IDs={$new_guid}";
  else
    $new_guid_parameter = '';

  $response = zetaprints_get_content_from_url("$url/api.aspx?page=api-order-complete;ApiKey=$key;OrderID=$order_guid{$new_guid_parameter}");

  if (zetaprints_has_error($response))
    return null;

  try {
    $xml = new SimpleXMLElement($response['content']['body']);
  } catch (Exception $e) {
    _zetaprints_debug("Exception: {$e->getMessage()}");
    return null;
  }

  return zetaprints_parse_order_details($xml);
}

function zetaprints_register_user ($url, $key, $user_id, $password, $corporate_id = null) {
  _zetaprints_debug();

  $request_url = "$url/api.aspx?page=api-user-new;ApiKey=$key;UserID=$user_id;Password=$password";

  if ($corporate_id && is_string($corporate_id) && count($corporate_id))
    $request_url .= ";CorporateID=$corporate_id";

  $response = zetaprints_get_content_from_url($request_url);

  if (zetaprints_has_error($response))
    return null;

  return strpos($response['content']['body'], '<ok />') !== false ? true : false;
}

function _zetaprints_parse_http_headers ($headers_string) {
  $lines = explode("\r\n", $headers_string);

  $headers = array();

  foreach ($lines as $line) {
    $key_value = explode(': ', $line);

    if (count($key_value) == 2)
      $headers[$key_value[0]] = $key_value[1];
    else
      $headers[] = $key_value[0];
  }

  return $headers;
}

function _zp_http_request_body_encode ($post) {
  $_post = array();

  while (list($key, $value) = each($post))
    $_post[] = urlencode($key) . '=' . urlencode($value);

  return implode('&', $_post);
}

function _zetaprints_return ($content, $error = false) {
  return array('error' => $error, 'content' => $content);
}

function _zetaprints_ok ($content) {
  return _zetaprints_return($content);
}

function _zetaprints_error ($message) {
  return _zetaprints_return($message, true);
}

function zetaprints_has_error ($response) {
  return !is_array($response) || !isset($response['error']) || !isset($response['content']) || $response['error'];
}

function zp_register_error_handler ($code, $handler) {
  global $_zp_error_handlers;

  if (!$_zp_error_handlers)
    $_zp_error_handlers = array();

  $_zp_error_handlers[$code] = $handler;
}

function _zp_curl_retrieve_data ($url, $data = null) {
  _zetaprints_debug();

  $options = array(
    CURLOPT_URL => $url,
    CURLOPT_HEADER => true,
    CURLOPT_CRLF => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array('Expect:')
  );

  if ($data && is_array($data))
    $options[CURLOPT_POSTFIELDS] = function_exists('http_request_body_encode')
                                     ? http_request_body_encode($data, null)
                                       : _zp_http_request_body_encode($data);

  _zetaprints_debug(compact('options'));

  $curl = curl_init();

  if (!curl_setopt_array($curl, $options)) {
    _zetaprints_debug('Can\'t set options for curl');

    return _zetaprints_error('Can\'t set options for curl');
  }

  $output = curl_exec($curl);
  $info = curl_getinfo($curl);
  $error = curl_error($curl);

  curl_close($curl);

  if ($output === false) {
    _zetaprints_debug(compact('error', 'info'));

    return _zetaprints_error($error);
  }
  
  list($headers, $body) = explode("\r\n\r\n", $output, 2);

  return _zetaprints_ok(compact('info', 'headers', 'body'));
}

function _zp_invoke_error_handler ($message, $error) {
  $error = array(
    'code' => ZP_ERR_UKNOWN,
    'message' => $message,
    'repeate_request' => false,
    'previous' => $error
  );

  if (strpos($message, 'Wrong ID/Hash combo.') === 0)
    $error['code'] = ZP_ERR_WRONG_ID_HASH_COMBO;

  global $_zp_error_handlers;

  if (!($_zp_error_handlers && isset($_zp_error_handlers[$error['code']])))
    return $error;

  $handler = $_zp_error_handlers[$error['code']];

  if (!(function_exists($handler) && is_callable($handler)))
    return $error;

  $result = call_user_func($handler, $error);

  if (is_array($result)) {
    $error['update_request'] = $result;

    $result = true;
  }

  $error['repeate_request'] = $result;

  return $error;
}

function _zp_process_error ($info, $headers, $previous) {
  if ($info['http_code'] == 200)
    return false;

  if (!isset($headers['X-ZP-API-Error-Msg']))
    return array('code' => null,
                 'message' => 'Unknown error',
                 'previous' => $previous);

  return _zp_invoke_error_handler($headers['X-ZP-API-Error-Msg'], $previous);
}

function _zp_repeat ($error) {
  return isset($error['repeate_request']) && $error['repeate_request'];
}

function zetaprints_get_content_from_url ($url, $post = null) {
  _zetaprints_debug();

  $error = null;

  do {
    $_data = _zp_curl_retrieve_data($url, $post);

    if (zetaprints_has_error($_data))
      return $_data;

    //Extract $info, $headers and $body variables
    extract($_data['content']);

    $headers = function_exists('http_parse_headers')
                 ? http_parse_headers($headers)
                   : _zetaprints_parse_http_headers($headers);

    $error = _zp_process_error($info, $headers, $error);

    if ($error) {
      _zetaprints_debug(compact('error', 'info', 'headers', 'body'));

      if (isset($error['update_request']))
        extract($error['update_request']);
    }
  } while ($error && _zp_repeat($error));

  if ($error)
    return _zetaprints_error($error['message']);

  //Do not output images to logs
  $_body = $body;

  if (isset($info['content_type'])
      && strpos($info['content_type'], 'image') === 0)
    $_body = $info['content_type'];

  _zetaprints_debug(compact('headers', '_body'));

  return _zetaprints_ok(compact('headers', 'body'));
}

?>
