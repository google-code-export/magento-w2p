<?php


class Netzarbeiter_GroupsCatalog_Block_Adminhtml_Widget_Grid_Column_Renderer_Visible
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Checkbox
{
	public function renderHeader()
	{
		if($this->getColumn()->getHeader()) {
			return parent::renderHeader();
		}

		$checked = '';
		if ($filter = $this->getColumn()->getFilter()) {
			$checked = $filter->getValue() ? 'checked="checked"' : '';
		}
		return '<input type="checkbox" name="'.$this->getColumn()->getFieldName().'" onclick="'.$this->getColumn()->getGrid()->getJsObjectName().'.checkCheckboxes(this); visibleProducts.updateProductStates(this);" class="checkbox" '.$checked.' title="'.Mage::helper('adminhtml')->__('Select All').'"/>';
	}
}