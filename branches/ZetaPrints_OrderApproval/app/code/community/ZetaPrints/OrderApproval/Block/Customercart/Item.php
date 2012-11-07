<?php

class ZetaPrints_OrderApproval_Block_CustomerCart_Item
  extends Mage_Core_Block_Template {

  public function getImages () {
    if (!Mage::helper('orderapproval')->isWebToPrintInstalled())
      return false;

    $options = $this
                 ->getQuoteItem()
                 ->getBuyRequest();

    if (!$guids = $options->getData('zetaprints-previews'))
      return false;

    $guids = explode(',', $guids);

    $helper = Mage::helper('webtoprint/personalization-form');

    $images = array();

    foreach ($guids as $guid)
      $images[] = $helper->get_preview_url($guid);

    return $images;
  }

  public function getPdfProof () {
    if (!Mage::helper('orderapproval')->isWebToPrintInstalled())
      return false;

    $options = $this
                 ->getQuoteItem()
                 ->getBuyRequest();

    if (!$path = $options->getData('zetaprints-order-lowres-pdf'))
      return false;

    return Mage::getStoreConfig('webtoprint/settings/url') . $path;
  }

  public function getApproveUrl () {
    $params = array(
      'item' => $this
                  ->getQuoteItem()
                  ->getId(),
      'state' => ZetaPrints_OrderApproval_Helper_Data::APPROVED
    );

    return $this->getUrl('*/*/updateApprovalState', $params);
  }

  public function getDeclineUrl () {
    $params = array(
      'item' => $this
                  ->getQuoteItem()
                  ->getId(),
      'state' => ZetaPrints_OrderApproval_Helper_Data::DECLINED
    );

    return $this->getUrl('*/*/updateApprovalState', $params);
  }
}
?>
