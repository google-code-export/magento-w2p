<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 3/23/11
 * Time: 1:20 PM
 */

class ZetaPrints_DistributionMap_IndexController
  extends Mage_Core_Controller_Front_Action
{
  public function kmlAction()
  {
    $req = $this->getRequest();
    $order_id = $req->getParam('ordid');
    $option_id = $req->getParam('optid');
    $quote_item_id = $req->getParam('qiid');
    /** @var $resource ZetaPrints_DistributionMap_Model_Mysql4_Map */
    $resource = Mage::getResourceModel('distro_map/map');
    $conn = $resource->getReadConnection();
    $order_cond = $conn->quoteIdentifier(ZetaPrints_DistributionMap_Model_Map::ORDERID) . '=?';
    $opt_cond = $conn->quoteIdentifier(ZetaPrints_DistributionMap_Model_Map::OPTID) . '=?';
    $quote_cond = $conn->quoteIdentifier(ZetaPrints_DistributionMap_Model_Map::QUOTID) . '=?';
    $select = $conn->select()
                    ->from($resource->getMainTable(), 'kml')
                    ->where($order_cond, $order_id)
                    ->where($quote_cond, $quote_item_id)
                    ->where($opt_cond, $option_id)
                    ->limit(1);
    $result = $conn->fetchOne($select);
    $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-type', 'application/vnd.google-earth.kml+xml', true)
                    ->setHeader('Content-Disposition', 'attachment; filename="Distribution_Map.kml"');

                $this->getResponse()
                    ->clearBody();
                $this->getResponse()
                    ->sendHeaders();

                echo($result);
//    Zend_Debug::dump($result);
  }
}
