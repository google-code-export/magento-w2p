<?php
/**
 * @package ZetaPrints
 * @category Attachement
 * @author Petar Dzhambazov
 */

class ZetaPrints_Attachment_Model_Events_Observer
{

  /**
   * Add uploaded files to order
   *
   * Since we are going to handle file upload asynchronously
   * we need a way to attach files and orders.
   */
  public function addAttachemntsToOrder() {
    ;
  }

  /**
   * Store attachment ids into session
   */
  public function storeAttachments() {
    $request = $observer->getEvent()->getControllerAction()->getRequest();
  }

  /**
   * Handle case of cancelled orders
   *
   * In case that order has been cancelled or discarded
   * for any reason, we could make sure that we delete all
   * attached files. Some design files can get quite large
   * so having housekeeping function like this might be good
   * idea.
   */
  public function deleteAttachemnts() {
    // for now we deal deleting manually via controller action
    return;
  }
}

