if (typeof Prototype != 'undefined') { // check that Prototype is loaded
  function parseComments() {
    var comments = $$('#order_history_block ul.note-list li');
    if (comments.length == 0) {
      return;
    }

    comments.each(function(comm) {
      var cont = comm.innerHTML;
      if (cont.match(/\.{3}customer\s*$/)) {
        cont = cont.gsub(/\.{3}customer\s*$/, '');
        comm.addClassName('customer-comment').update(cont).insert({top: '<strong>Customer:</strong><br/>'});
      }
    });
  }

  function submitAndReloadArea(area, url) {
    if ($(area)) {
      var fields = $(area).select('input', 'select', 'textarea');
      var data = Form.serializeElements(fields, true);
      url = url + (url.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true');
      new Ajax.Request(url, {
        parameters: $H(data),
        loaderArea: area,
        onSuccess: function(transport) {
          try {
            if (transport.responseText.isJSON()) {
              var response = transport.responseText.evalJSON()
              if (response.error) {
                alert(response.message);
              }
              if (response.ajaxExpired && response.ajaxRedirect) {
                setLocation(response.ajaxRedirect);
              }
            } else {
              $(area).update(transport.responseText);
            }
          }
          catch (e) {
            $(area).update(transport.responseText);
          }
          parseComments();
        }
      });
    }
  }

  Event.observe(window, 'load', parseComments);
}
