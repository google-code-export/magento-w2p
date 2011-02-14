<?php
/**
 * Created by JetBrains PhpStorm.
 * User: pp
 * Date: 2/14/11
 * Time: 8:45 PM
 * To change this template use File | Settings | File Templates.
 */

class ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Order
  extends ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Common
{
  public function render(Varien_Object $row)
  {
    $incId =  $row->getData($this->getColumn()->getIndex());
      if($incId){
      $order = Mage::getModel('sales/order');
      try{
        /* @var $order Mage_Sales_Model_Order */
        $order->loadByIncrementId($incId);
        $link = Mage::getUrl('*/sales_order/view', array('order_id' => $order->getId()));
        return $this->getLinkHtml($incId, $link);
      }catch(Exception $e){
        return 'N/A';
      }
    }
    return 'N/A';
  }
}
