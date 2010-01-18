/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Netzarbeiter
 * @package    Netzarbeiter_GroupsCatalog
 * @copyright  Copyright (c) 2009 Vinai Kopp http://netzarbeiter.com/
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

var VisibleProducts = Class.create({
	initialize: function(editForm, boxes_css) {
		this.form = $(editForm.formId);
		this.css = boxes_css;
		this.products = $H({});
		this.mainField = false;
	},
	updateProductState: function(index, state)
	{
		this.products.set('' + index, (state ? 'on' : 'off'));
		this.updateMainFieldValue();
	},
	updateMainFieldValue: function()
	{
		if (! this.mainField) this.setupMainField();
		
		var stateStr = '';
		this.products.each(function(state) {
			var states = ('' + state).split(',');
			stateStr += '' + state[0] + ':' + (state[1] == 'on' ? 1 : 0) + ',';
		}.bind(this));
		if (stateStr.length) stateStr = stateStr.substr(0, stateStr.length -1);
		this.mainField.value = stateStr;
	},
	setupMainField: function() {
		this.mainField = Builder.node('input', {type:'hidden', name:'visible_products', value:'', id:'visible-products'});
		Element.insert($(this.form), {top: this.mainField});
	},
	onRowClick: function(grid, event) {
		var trElement = Event.findElement(event, 'tr');
		if (trElement) {
			var checkbox = Element.getElementsBySelector(trElement, 'input');
			if (checkbox[0]) {
				var isInput = Event.element(event).tagName == 'INPUT';
				var checked = isInput ? checkbox[0].checked : ! checkbox[0].checked;
				customer_visible_productsJsObject.setCheckboxChecked(checkbox[0], checked);
				this.updateProductState(checkbox[0].value, checked);
			}
		}
	},
	onRowInit: function(grid, trElement)
	{
		var checkbox = Element.getElementsBySelector(trElement, 'input');
		if (checkbox[0]) {
			var isInput = Event.element(trElement).tagName == 'INPUT';
			if (this.products.get('' + checkbox[0].value)) {
				// saved value overrides load state
				checked = this.products.get('' + checkbox[0].value) == 'on';
				customer_visible_productsJsObject.setCheckboxChecked(checkbox[0], checked);
			} else {
				// init saved values
				this.updateProductState(checkbox[0].value, checkbox[0].checked);
			}
		}
	},
	/*
	 * Select/Deselect All checkboxes
	 */
	updateProductStates: function(element)
	{
		var elements = $$('input.netzarbeiter-visible-products');
		
		if (elements) elements.each(function(checkbox) {
			this.products.set('' + checkbox.value, ($(element).checked ? 'on' : 'off'));
		}.bind(this));
		this.updateMainFieldValue();
	}
});