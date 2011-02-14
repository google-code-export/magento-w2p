<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ZetaPrints_Attachments_Adminhtml_AttachmentsController
 extends Mage_Adminhtml_Controller_Action
{
  protected $images = array (
    'image/jpeg',
    'image/pjpeg',
    'image/gif',
  	'image/png',
  	'image/x-ms-bmp',
  	'image/x-bmp'
  );

  protected function _initAction($menu = 'attachments/items')
  {
    $this->loadLayout()
          ->_setActiveMenu($menu)
          ->_addBreadcrumb(Mage::helper('adminhtml')->__('Attachments Manager'),
                           Mage::helper('adminhtml')->__('Attachments Manager'));

    return $this;
  }

  public function indexAction()
  {
    $this->_initAction()
         ->renderLayout();
  }

  public function deleteAction()
  {
    if ($this->getRequest()->getParam('attachment_id') > 0) {
      try {
        $model = Mage::getModel('attachments/attachments');
        /* @var $model ZetaPrints_Attachments_Model_Attachments */
        $model->load($this->getRequest()->getParam('attachment_id'))->deleteFile();

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')
                                                ->__('Attachment was successfully deleted'));
        $this->_redirect('*/*/');
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        $this->_redirect('*/*/edit', array ('id' => $this->getRequest()->getParam('id')));
      }
    }
    $this->_redirect('*/*/');
  }

  public function massDeleteAction()
  {
    $attachmentsIds = $this->getRequest()->getParam('attachments');
    if (!is_array($attachmentsIds)) {
      Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')
                                             ->__('Please select attachment(s)'));
    } else {
      try {
        foreach ($attachmentsIds as $attachmentsId) {
          $attachments = Mage::getModel('attachments/attachments')->load($attachmentsId);
          /* @var $attachments ZetaPrints_Attachments_Model_Attachments */
          $attachments->deleteFile();
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')
                                               ->__('Total of %d record(s) were successfully deleted', count($attachmentsIds)));
      } catch (Exception $e) {
        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
      }
    }
    $this->_redirect('*/*/index');
  }

  public function downloadAction()
  {
    $attachmentId = $this->getRequest()->getParam('id');
    $hash = $this->getRequest()->getParam('key');
    $att = Mage::getModel('attachments/attachments')->load($attachmentId);
    if ($att) {
      try {
        $value = unserialize($att->getAttachmentValue());
        if ($hash != $value['secret_key']) {
          $this->_forward('noRoute');
        }

        $filePath = Mage::getBaseDir() . $value['order_path'];
        if (!is_file($filePath) || !is_readable($filePath)) {
          // try get file from quote
          $filePath = Mage::getBaseDir() . $value['quote_path'];
          if (!is_file($filePath) || !is_readable($filePath)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('attachments')
                                                                     ->__('Attachment ID %d: <em>%s</em>  is not found.', $attachmentId, $value['title']));
             $this->_redirect('*/*/index');
            return;
          }
        }

        $disposition = 'attachment'; // set default file disposition to attachment
        if (in_array($value['type'], $this->images)) { // if we have image file, change it to inline
          $disposition = 'inline';
          if ($value['type'] == 'image/pjpeg')
            $value['type'] = 'image/jpeg'; // if file has been uploaded via IE, set correct jpeg header
        }

        $this->getResponse()
             ->setHttpResponseCode(200)
             ->setHeader('Pragma', 'public', true)
             ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
             ->setHeader('Content-type', $value['type'], true)
             ->setHeader('Content-Length', $value['size'])
             ->setHeader('Content-Disposition', $disposition . '; filename="' . $value['title'] . '"');

        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();

        readfile($filePath);
      } catch (Exception $e) {
        $this->_forward('noRoute');
      }
    } else {
      $this->_forward('noRoute');
    }
  }
}
