<?php
/**
  * Project	magento-w2p
  * Author	Pham Tri Cong <phtcong@gmail.com>
  * Issue 1 : Data import from W2P 
  */
class ZetaPrints_Zpapi_Model_ITag
    extends Mage_Core_Model_Abstract
{
	function getInvitationTags($id){
		$model = Mage::getModel('tag/tag');
		$tags = $model->getResourceCollection()
	                ->addPopularity()
	                ->addStatusFilter($model->getApprovedStatus())
	                ->addProductFilter($id)
	                ->addStoreFilter(Mage::app()->getStore()->getId())
	                ->setActiveFilter()
	                ->load();
		if (count($tags) < 1) return "";
		$ret = "";
		$comma = "";
		$tpl_tags 	= '<p class="tags"><label class="bolds"><strong>Tags: </strong></label>%s</p>';
        $tpl_tag 	='<a href="%s">%s</a>(%s)';
		foreach( $tags as $tag ){
			$ret .= $comma . sprintf($tpl_tag, $tag->getTaggedProductsUrl(),$tag->getName(), $tag->getPopularity());
			$comma = ", ";
        }
		return sprintf($tpl_tags, $ret);
	}
}

?>