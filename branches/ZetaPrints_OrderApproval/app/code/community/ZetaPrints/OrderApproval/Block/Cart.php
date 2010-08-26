<?php

class ZetaPrints_OrderApproval_Block_Cart extends Mage_Checkout_Block_Cart {
  public function getItems () {
    return $this->getQuote()->getAllVisibleItems(true);
  }

  public function chooseTemplate () {
    if ($this->getQuote()->getAllItemsCount())
      $this->setTemplate($this->getCartTemplate());
    else
      $this->setTemplate($this->getEmptyTemplate());
  }
}
