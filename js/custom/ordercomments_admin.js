if(typeof Prototype != 'undefined'){ // check that Prototype is loaded
  Event.observe(window, 'load', function(e) {
    var comments = $$('#order_history_block ul.note-list li');
    if (comments.length == 0) {
      return;
    }

    comments.each(function(comm) {
      var cont = comm.innerHTML;
      if(cont.match(/\.{3}customer\s*$/)){
        cont = cont.gsub(/\.{3}customer\s*$/, '');
        comm.addClassName('customer-comment').update(cont).insert({top: '<strong>Customer:</strong><br/>'});
      }
    });
  });
}
