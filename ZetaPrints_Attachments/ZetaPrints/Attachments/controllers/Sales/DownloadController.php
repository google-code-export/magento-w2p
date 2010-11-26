<?php
/**
 * @author 			Petar Dzhambazov
 * @category    ZetaPrints
 * @package     ZetaPrints_Attachments
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales controller for download purposes
 *
 */

class ZetaPrints_Attachments_Sales_DownloadController
  extends Mage_Core_Controller_Front_Action
{

    /**
     * Custom options downloader
     */
    public function downloadCustomOptionAction ()
    {
        $quoteItemOptionId = $this->getRequest()->getParam('id');
        $secretKey = $this->getRequest()->getParam('key');
        $option = Mage::getModel('sales/quote_item_option')->load($quoteItemOptionId);

        if ($option->getId()) {
            try {
                $value = unserialize($option->getValue()); // we can have many files
                $found = false;
                foreach ($value as $info) { // cycle them all untill we find requested file
                  if ($secretKey != $info['secret_key']) {
                      continue;
                  }
                  $found = true;
                  break;
                }
                if(false === $found){ // if we did not find the requested file - stop
                  throw new Exception();
                }


                $filePath = Mage::getBaseDir() . $info['order_path'];
                if (!is_file($filePath) || !is_readable($filePath)) {
                    // try get file from quote
                    $filePath = Mage::getBaseDir() . $info['quote_path'];
                    if (!is_file($filePath) || !is_readable($filePath)) {
                        throw new Exception();
                    }
                }

                $this->getResponse()
                    ->setHttpResponseCode(200)
                    ->setHeader('Pragma', 'public', true)
                    ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                    ->setHeader('Content-type', $info['type'], true)
                    ->setHeader('Content-Length', $info['size'])
                    ->setHeader('Content-Disposition', 'inline' . '; filename='.$info['title']);

                $this->getResponse()
                    ->clearBody();
                $this->getResponse()
                    ->sendHeaders();

                readfile($filePath);

            } catch (Exception $e) {
                $this->_forward('noRoute');
            }

        } else {
            $this->_forward('noRoute');
        }
    }
}
