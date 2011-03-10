if (typeof Prototype != 'undefined') { // check that Prototype is loaded
  Event.observe(window, 'load', function(e) {
    var commentsStack = $$('.order-comments dl.order-about');
    if (commentsStack.length != 0) {

      var comments = commentsStack[0].select('dd');
      comments.each(function(comm) {
        var cont = comm.innerHTML;
        if (cont.endsWith('...customer')) {
          var l = cont.length - '...customer'.length;
          cont = cont.substr(0, l);
          comm.addClassName('customer-comment').update(cont).insert({top: '<strong>You:</strong> '});
        }else{
          comm.insert({top: '<strong>Admin:</strong> '});
        }
      });
    }
    var backLink = $$('div.order-details div.buttons-set');
    if (backLink.length) {
      var commentDiv = $('order_comment_block').remove();
      backLink.last().insert({before: commentDiv});
    }
  });
}
