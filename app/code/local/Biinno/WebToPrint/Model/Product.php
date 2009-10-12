<?php
class Biinno_Catalog_Model_Product extends Mage_Catalog_Model_Product {

  public function getMediaGalleryImages() {
    if (!$this->hasData('w2p_image')) return parent::getMediaGalleryImages();

    if (!$this->hasData('media_gallery_images') && $this->hasData('w2p_image_links')) {
      $preview_image_urls = explode(',', $this->getData('w2p_image_links'));

      if (count($preview_image_urls) == 1) return $this->getData('media_gallery_images');

      $images = new Varien_Data_Collection();

      foreach ($preview_image_urls as $preview_image_url) {
        $image = array();

        $image['url'] = $preview_image_url;
        $image['id'] = md5($preview_image_url);
        $image['value_id'] = $image['id'];
        //$image['path'] = null;

        $images->addItem(new Varien_Object($image));
      }

      $this->setData('media_gallery_images', $images);
    }

    return $this->getData('media_gallery_images');
  }
}
