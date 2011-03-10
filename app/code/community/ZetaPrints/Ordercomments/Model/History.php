<?php
/**
 * Override default history model
 *
 * We need to know if a comment comes from customer,
 * this is simplest way to mark it.
 * A better way would be to wrap comment in an html tag
 * and use classes to separate it.
 * This however does not work because all comment content
 * is escaped before rendering, so we will end up with
 * html markup displayed to user.
 */
class ZetaPrints_Ordercomments_Model_History
  extends Mage_Sales_Model_Order_Status_History
{
  protected $customerCommentTpl = '%s...customer';
  public function getComment()
  {
    $comment = parent::getComment();
    $id = $this->getId();
    $customerComment = Mage::getModel('ordercomments/comment')->load($id, 'comment_id');
    if($customerComment->getId()){
      $comment = sprintf($this->customerCommentTpl, $comment);
    }
    return $comment;
  }
}
