/**
 * @author pp
 */
Event.observe(window, 'load', updateQtys);
Event.observe(window, 'load', hideQtys);

function hideQtys()
{
  var qtys = $$('.qty');
  if(undefinded == qtys){
    return;
  }
  var qty = qtys[0];
  var name = qty.name;
  if(name.match(/^cart\[/)){
    $$('input').invoke('hide');
  }
}

function updateQtys(e)
{
  // if fixed prices are not defined on the page, there is nothing to do
  if(undefined == window.fixedPriceData){
    console.warn('Fixed prices not found.');
    return;
  }
  // fixed prices should be an array of objects with fixed prices data,
  // they are in the same order as in document
  var fixedPrices = window.fixedPriceData;


  // find default qty box and hide it so that customers cannot enter custom qty's
  // display a p element instead
  var qty_box = $('qty');
  if(qty_box){
    var tooltip = new Element('div', {id: 'fp-edit-forbidden'}).update('Setting quantities directly is not allowed.<br/>Choose one of the options listed.');
    var offsets = qty_box.positionedOffset();
    tooltip.setStyle({
      'display': 'none',
      'left': offsets.left + 'px',
      'top': offsets.top + 'px'
    });
    document.body.appendChild(tooltip);
    qty_box.writeAttribute('type', 'hidden');
    var fake_qty = '<input class="qty input-text" type="text" value="' + qty_box.value + '" id="fake-qty" name="fake-qty"/>';
    var form = $(qty_box.form);
    var temp = Element.replace(qty_box, fake_qty);
    form.insert(temp);
    $('fake-qty').observe('keydown', function(e){
      alert('Manual qty edit, not allowed.');
      return false;
    }).observe('focus', function(e){
      tooltip.show();
    }).observe('blur', function(e){
      tooltip.hide();
    });
  }

  $$('input.fixed-price-option').each(function(btn){
    var qty = qty_box;
    setRadioToQty(qty, btn, fixedPrices);
    btn.observe('click', function(e){
      setRadioToQty(qty, this, fixedPrices);
    });
  });
}
/**
 * Copy radio btn value to qty box
 * @param qty HTMLInputElement
 * @param radio HTMLInputElement
 * @param qty_p String id of qty paragraph element
 */
function setRadioToQty(qty, radio, fixedPrices)
{
  var fake_qty = $('fake-qty');
  if(radio.checked && qty){
    var value;
    var idx = parceFixedOptionId($(radio).identify());
    $A(fixedPrices).each(function(idx, fp){
      if(fp.price_id == idx){
        value = fp.price_qty;
      }
    }.curry(idx));
    if(undefined !== value){
      fake_qty.value = qty.value = value;
    }
  }
}

function parceFixedOptionId(fpId){
  var idx = fpId.sub('fixed-price-', '');
  return idx;
}