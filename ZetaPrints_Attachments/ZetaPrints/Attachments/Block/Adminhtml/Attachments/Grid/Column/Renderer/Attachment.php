<?php

class ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Attachment
 extends ZetaPrints_Attachments_Block_Adminhtml_Attachments_Grid_Column_Renderer_Common
{
  protected $images = array(
  	'image/jpeg',
  	'image/pjpeg',
  	'image/gif',
  	'image/png',
  	'image/x-ms-bmp',
  	'image/x-bmp',
  );

  public function render(Varien_Object $row){
//    $values = $this->getColumn()->getValues();
    /* @var $row ZetaPrints_Attachments_Model_Attachments */
    $value  = $row->getData($this->getColumn()->getIndex());
    $attachments = unserialize($value);
    $base = Mage::getBaseDir();
    $baseUrl = Mage::getBaseUrl();
    $filePath = $base . $attachments['order_path'];
    $fileUrl = $baseUrl . $attachments['order_path'];
    if (!is_file($filePath) || !is_readable($filePath)) {
      // try get file from quote
      $filePath = $base . $attachments['quote_path'];
      if (!is_file($filePath) || !is_readable($filePath)) {
//        throw new Exception();
        $filePath = null;
      }
    }
    $name = $attachments['title'];
//    $type = $attachments['type'];
    $downloadRoute = 'adminhtml/attachments/download';
    $params = array('id' => $row->getData('attachment_id'),
                    'att' => $attachments['secret_key'],
                    'name'=> rawurlencode($name)
    );
    $link = Mage::getModel('adminhtml/url')->getUrl($downloadRoute, $params);

    return $this->getLinkHtml($name, $link);
  }

  protected function getImageHtml($fileUrl, $type, $title)
  {
    if(!in_array($type, $this->images)){
      return '';
    }

    $img = '<img src="" alt="" class=""/>';
    /* @todo if decided to actually display thumbnails of images, we will need to
     * resize them
     */
  }


}

