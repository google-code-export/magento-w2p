/**
 * @author pp
 */
Event.observe(window, 'load', updateQtys);
Event.observe(window, 'load', hideCartQtys);

function hideCartQtys()
{
  var c_name = 'fp_items'; // cookie name same as ZetaPrints_Fixedprices_Model_Events_Observers_Fixedprices::COOKIE_NAME
  var cookie = getCookie(c_name);
  if(cookie){
    var item_ids = $A(cookie.split(','));
    var qtys = $$('.qty');
    if(qtys.size() == 0){
      return;
    }
    qtys.each(function(qty){
      var name = qty.name;
      if(name.match(/^cart\[(\d+)\]\[qty\]/)){
        var id = name.match(/^cart\[(\d+)\]\[qty\]/)[1];
        if(item_ids.indexOf(id) != -1){
          $(qty).hide();
        }
      }
    });
  }
}

function updateQtys(e)
{
  // if fixed prices are not defined on the page, there is nothing to do
  if(undefined == window.fixedPriceData){
    console.info('Fixed prices not found.'); // debugging message
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
    qty_box.hide();
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
      this.value = $('qty').value;
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
    var price;
    var prid;
    var idx = parceFixedOptionId($(radio).identify());
    $A(fixedPrices).each(function(idx, fp){
      if(fp.price_id == idx){
        value = fp.price_qty;
        price = fp.formated_price;
        prid = fp.product_id;
      }
    }.curry(idx));

    if(undefined !== value && undefined !== fake_qty){
      fake_qty.value = qty.value = value;
    }

    if(undefined !== price){
      priceSwitcher(prid, price);
    }
  }
}

function parceFixedOptionId(fpId){
  var idx = fpId.sub('fixed-price-', '');
  return idx;
}

function priceSwitcher(productId, formattedPrice)
{
  var containers = new Array();
  containers[0] = 'product-price-' + productId;
  containers[1] = 'bundle-price-' + productId;
  containers[2] = 'price-including-tax-' + productId;
  containers[3] = 'price-excluding-tax-' + productId;
  containers[4] = 'old-price-' + productId;

  $H(containers).each(function(pair) {
    if ($(pair.value)){
      $(pair.value).innerHTML = formattedPrice;
    }
  });
}

// copied from http://www.w3schools.com/JS/js_cookies.asp
function getCookie(c_name)
{
var i,x,y,ARRcookies=document.cookie.split(";");
for (i=0;i<ARRcookies.length;i++)
{
  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
  x=x.replace(/^\s+|\s+$/g,"");
  if (x==c_name)
    {
    return unescape(y);
    }
  }
}