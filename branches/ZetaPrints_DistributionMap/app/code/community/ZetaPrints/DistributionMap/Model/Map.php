<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/16/11
 * Time: 7:44 PM
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_DistributionMap_Model_Map
  extends Mage_Core_Model_Abstract
{
  /**
   * Table fields
   */
  const COORDS = 'coords';
  const KML = 'kml';
  const ORDERID = 'order_id';     // if product is ordered, then we have this.
  const OPTID = 'option_id';      // every quote may have more than one, so we need a reference
  const QUOTID = 'quote_item_id'; // every product added to cart has one
  const CREATED = 'created';

  const API_VERSION = '3.4';
  protected function _construct(){
    $this->_init('distro_map/map');
  }

  public function setMapData($data) {
    if(isset($data[self::COORDS])){
      $this->setData(self::COORDS, $data[self::COORDS]);
    }
    if(isset($data[self::QUOTID])){
      $this->setData(self::QUOTID, $data[self::QUOTID]);
    }
    if(isset($data[self::OPTID])){
      $this->setData(self::OPTID, $data[self::OPTID]);
    }
    return $this;
  }

  public function save() {
    if ($this->isObjectNew()) {
      $this->setData(self::CREATED, new Zend_Db_Expr('NOW()'));
    }

    if (!$this->_getData(self::KML)) {
      $this->setData(self::KML, $this->generateKml());
    }
    return parent::save();
  }

  public function generateKml() {
    if (!$this->_getData(self::COORDS)) {
      return null;
    }
    return $this->_generateKml();
  }

  protected function _generateKml() {
    $coords = Zend_Json::decode($this->getData(self::COORDS));
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->preserveWhiteSpace = true;
    $doc->formatOutput = true;

    $node = $doc->createElementNS('http://www.opengis.net/kml/2.2', 'kml');
    $kml = $doc->appendChild($node);
    $document = $kml->appendChild($doc->createElement('Document'));
    $document->appendChild($doc->createElement('name', 'Distribution Map'));
    $polyStyle = $doc->createElement('Style');
    $polyStyle->setAttribute('id', 'polyStyle');
    $lineStyle =$doc->createElement('LineStyle');
    $lineStyle->appendChild($doc->createElement('width', '3'));
    $lineStyle->appendChild($doc->createElement('color', '7f0000ff')); // non transparent red color
    $polyFill = $doc->createElement('PolyStyle');
    $polyFill->appendChild($doc->createElement('color', '7f0000ff')); // semi transparent red color
    $polyStyle->appendChild($lineStyle);
    $polyStyle->appendChild($polyFill);
    $document->appendChild($polyStyle);

    $node = $doc->createElement('Placemark');
    $poly = $document->appendChild($node);
    $poly->appendChild($doc->createElement('name', 'Distribution area.'));
    $poly->appendChild($doc->createElement('styleUrl', '#polyStyle'));
    $polygon = $poly->appendChild($doc->createElement('Polygon'));
    $outerBoundary = $polygon->appendChild($doc->createElement('outerBoundaryIs'));
    $ring = $outerBoundary->appendChild($doc->createElement('LinearRing'));
    $c = array();
    foreach($coords as $coordinate) {
      $c[] = $coordinate['lng'] . ',' . $coordinate['lat'];
    }
    $c[] = $coords[0]['lng'] . ',' . $coords[0]['lat']; // close the figure

    $ring->appendChild($doc->createElement('coordinates', implode(' ', $c)));
    $xml = $doc->saveXML();
//    $var = Mage::getBaseDir() . '/var/sample.kml';
//    @file_put_contents($var, $xml);
    return $xml;
  }
}
