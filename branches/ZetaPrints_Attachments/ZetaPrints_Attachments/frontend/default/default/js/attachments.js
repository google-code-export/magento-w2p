window.uploadInProgress = [];// global flag register if there is upload going on at the moment
/**
 * @author      Petar Dzhambazov
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Attachments class prototype
 *
 * This is designed to upload files in Magento in AJAX like way.
 * Principles used can be applied to any other web environment but
 * using as it is will probably not work.
 * this class uses Prototype JavaScript framework. See http://www.prototypejs.org/api for details
 */
var attachments = Class.create({
  action: undefined,    // form action, this is where all uploads will be submitted
  form: undefined,      // form from which we will get data, this should be original Magento form ID from product page
  idx: undefined,       // Option index, very important for magento, every custom option has this index, it is used to process options
  nextId: 0,            // counter for this object's uploads. It should be increased with every added file
  container: undefined, // container to hold our forms
  original: undefined,  // original container Div that has original content which will be replaced by attachments forms
  formempty: false,     // a flag showing if current visible file input has a file attached to it or not
 /*
  * This is file registry and queue. All files added to queue.
  * We use queue to keep the order and registry to keep AjaxUpload
  * objects them selves. Items in queue correspond to keys in
  * registry.
  * So we have in queue [file_1, file_2, file_3]  and
  * {file_1:upload1, file_3:upload3, file_2:upload2}
  *
  * when we do queue.shift(); we get file_1 and queue is reduced
  * then we get upload from registry.find(file_1) and submit it.
  */
  queue: undefined,
  registry: undefined,
  settings: undefined,    // All UI options go here - IDs, classes, image paths
  /**
   * Initialize object
   *
   * Set initial values and options
   *
   * @param fileid int - option id
   * @param action String - submit location
   * @param form String - id of original magento form
   * @param options Object - hash of key:value pairs that will be used for changing default settings.
   */
  initialize:function(fileid, action, form, options){ // initialize instance
    this.action = action;
    this.form = form;
    this.settings = {  // for proper inheritance to work, we need to assign values here
        placementDiv: 'zp-file-upload-',            // this is container of sever side html
        containerDiv: 'zp-form-container-',         // this is container where generated forms will be
        containerDivClass: 'zp-form-container',     // this is class for all js containers that are used which helps in styling and positioning them
        attachmentsListId: 'zp-attachments-list-',  // file list container id
        formId: 'zp-attachments-form-',             // form ID
        frameId: 'zp-attachments-frame-',           // iFrame ID
        fileName: 'options_#{id}_file',             // every file upload has to have the same name for Magento options to work
        origFileId: 'option_#{id}_file',            // original file upload ID template
        fileId: 'option_#{id}_file_#{queue}',       // we add queue id so that we don't break html rules too much
        // if custom filename, file id strings are provided,
        // make sure they have #{id} and #{queue} in them respectively or this will not work
        removeLinkClass: "zp-remove-file-link",     // class name for remove links
        cancelLinkClass: "zp-cancel-file-link",     // class name for cancel links
        listItemId: '_list_item',                   // ilst item ID
        listItemClass: 'zp-file-list-item',         // list item class
        listId: '_file_list',                       // ul list ID
        listClass: 'zp-file-list',                  // ul list class
        spinner: '../images/opc-ajax-loader.gif',   // default spinner image
        useskinnedupload: true,                     // use skinned version of the upload
        updateListEvent: 'attachment:listupdate',    // custom event that we are firing to update containers positions
        addtoHide: 'zp-att-hidden'
    };
    this.idx = fileid;
    this.registry = $H();
    this.queue = [];
    window.uploadInProgress[this.idx] = false;
    this.setOptions(options);
  },
  addFirstUpload:function(){ // after initialize, prepare container and add first form
    var cont = this.createContainer(this.idx); // create form container
    this.original = $(this.settings.placementDiv + this.idx); // get server side container
    var or = $(this.original);
    cont.absolutize();  // make our container, absolutely positioned

    var dim = or.getDimensions(); // get original container dimensions
    or.setStyle({width: dim.width+'px', height: 'auto'}); // hard code original container width to avoid unwanted changes
    cont.clonePosition(this.original); // then clone position and dimensions to our container. This places it on top of original
    cont.originalPosition = cont.positionedOffset(); // save first position for future needs
    this.addUpload(); // and add first form
    this.hideOriginalContent(); // hide some of the original container content
  },
  hideOriginalContent:function(){ // hides some of the original container content
    var div = $(this.original);
    if(div){ // if we have reference to original container
      var originalFileId = this.settings.origFileId.sub('#{id}', this.idx); // get original file upload id
      var replDiv = new Element('div', {id: 'repl-' + originalFileId}).clonePosition($(originalFileId), { // create replacement div with same width
        setLeft: false,  //clones source’s left CSS property onto element.
        setTop: false,  //clones source’s top CSS property onto element.
        setWidth: true,  //clones source’s width onto element.
        setHeight: false}
      ).update('<input type="hidden" name="'+ this.settings.fileName.sub('#{id}', this.idx) + '" class="product-custom-option" id="' + originalFileId +'" />');
      var height = $(this.container).getHeight(); // get height from container, so any remaining content is pushed below our form
      height += 5; // make some space
      replDiv.setStyle({height: height + 'px'});
      var oldFile = Element.replace(originalFileId, replDiv); // replace original element with replacement div
      $(this.container).observe(this.settings.updateListEvent, function(e){ // add listener for our custom event which fires whenever file list content changes so
        var div = 'repl-' + this.settings.origFileId.sub('#{id}', this.idx);// that we can adjust position of the forms
        $(this.container).clonePosition($(div), {setLeft: true, setTop: true, setWidth:false, setheight:false});
      }.bindAsEventListener(this));
    }
  },
  showList:function(list){ // shows file list, when there is content in it
    var l = $(list);
    l.up().show();
    this.fireListUpdate(); // update attachments containers positions
  },
  createContainer:function(id){ // create attachments container
    var cont = new Element('div');
    document.body.appendChild(cont); // add it to document
    cont.id = this.settings.containerDiv + id;
    cont.addClassName(this.settings.containerDivClass);
    cont.setStyle({textAlign: 'left'});
    this.container = cont; // save reference to it
    return this.container;
  },
  setOptions:function(options){
    // Merge the users options with our defaults
    for (var i in options) {
      if (options.hasOwnProperty(i)){
        this.settings[i] = options[i];
      }
    }
  },
  getRemoveLink:function(id) // link to remove files from queue
  {
    var link = new Element('a', {href: '#'}).update('Remove'); // create remove link
    link.id = this.getRemoveLinkId(id);
    link.className = this.settings.removeLinkClass;
    return link;
  },
  getStopLink:function(id) // link to stop files from upload
  {
    var link = new Element('a', {href: '#'}).update('Cancel'); // create cancel link
    link.id = this.getStopLinkId(id);
    link.className = this.settings.cancelLinkClass;
    return link;
  },
  getDeleteLink:function(response, id){ // link to trigger file deletion
    var href = response.delete_url;
    var link = '';
    if(href && response.title){
      link = response.title + '&nbsp;<a href="' + href + '" title="Delete ' + response.title + '" ';
      link += 'id="' + this.getRemoveLinkId(id) + '" ';
      link += 'class="' + this.settings.removeLinkClass + '">Delete</a>';
    }else if (response.title) {
      link = response.title;
    }
    return link;
  },
  getRemoveLinkId:function(id) // compose remove link id
  {
    return this.settings.removeLinkClass + "-" + this.idx + "-" + id;
  },
  getStopLinkId:function(id){// compose cancel link id
    return this.settings.cancelLinkClass + "-" + this.idx + "-" + id;
  },
  getAddedText:function(name, id) // text when file is added
  {
    var spn = new Element('span', {'class':this.settings.listItemClass + '-lbl'}).update('<strong>' + name + '</strong> added to queue &nbsp;&nbsp;');
    spn.insert(this.getRemoveLink(id));
    return spn;
  },
  getSpinner:function() // spinning gif image
  {
    return new Element('img', {src: this.settings.spinner, alt: 'Loading ...'});
  },
  getLoadingText:function(name, id) // text when upload starts
  {
    var spn = new Element('span', {'class':this.settings.listItemClass + '-lbl'}).update('Uploading <strong>' + name + '</strong> &nbsp;&nbsp;');
    spn.insert(this.getStopLink(id)).insert(this.getSpinner());
    return  spn;
  },
  getUploadList:function(){ // get reference to file list container, alert pops up when element ID is not found
    var list = this.getUploadListId(this.idx);
    var attachmentsList = $(list);
    if(undefined == attachmentsList){
      alert('Upload list not found');
      return;
    }
    return attachmentsList;
  },
  getUploadListId:function(id){ // compose list id, each option has single list so we only use option id for uniqueness
    return this.settings.attachmentsListId + this.idx;
  },
  getListItemId:function(id){ // compose item id, to make it unique, include option id and nextid
    var liId = id + '_' + this.idx + this.settings.listItemId;
    return liId;
  },
  addToUploadList:function(item, lid){  // add anottation to file list
    var attachmentsList = this.getUploadList(); // get file list container
    var list = $(attachmentsList).identify(); // get its ID
    var ulId = list + this.settings.listId; // that is actual UL list id
    if (undefined == $(ulId)) { // if it does not exist
      attachmentsList.insert(new Element('ul', { // create it
        id : ulId,
        'class': this.settings.listClass
      }));
    }
    var ul = $(ulId); // get UL reference
    var liId = this.getListItemId(lid); // that is next LI item id
    if (undefined == $(liId)) { // if it does not exist, create it;
      ul.insert(new Element('li', {
        'class' : this.settings.listItemClass,
        id : liId
      }));
    }
    $(liId).update(item); // update LI contents
    this.showList(attachmentsList); // show the list
    this.fireListUpdate();
    return $(lid + this.settings.listItemId); // return LI reference
  },
  fireListUpdate:function(){ // this functions handles correct positioning of div containers that hold the forms
    var containers = $$('.' + this.settings.containerDivClass); // get a collection of containers
    containers.each(function(c){ // make each of them fire custom update event
      $(c).fire(this.settings.updateListEvent);
    }.bind(this));
  },
  updateUploadsList:function(item, el_id){ // update el_id content
    return $(el_id).update(item);
  },
  removeFromUploadList:function(el_id){ // remove el_id from file list
    $(el_id).remove();
    this.fireListUpdate(); // update containers positioning
  },
  clearUploadList:function(){ // clear content of file list
    var attachmentsList = this.getUploadList();
    if(attachmentsList){
      attachmentsList.update('');
    }
    this.fireListUpdate();
  },
  getFileName: function(file){ // get base file name from file input value
    return file.replace(/.*(\/|\\)/, "");// different browsers set different values for it so it is best to have just base file name, no folder path
  },
  getNewForm:function(id, iFrame, data){ // create and return form
    var formName = this.getFormName(id);
    var form = new Element('form', { // create form element
      id: this.getFormName(id),
      action: this.action,
      enctype:"multipart/form-data", // has to be like this
      target: iFrame.name, // set its submit target
      method: 'post' // file uploads work only with post
    });
    if(document.all){ //  ie hacks
      form = toElement('<form method="post" enctype="multipart/form-data"></form>');
      form = $(form);
      form.id = formName;
      form.action = this.action;
      form.target = iFrame.name;
    }
    this.container.insert({top:form}); // add it to div container that handles positioning

    var input = this.getFileInput(id); // get the file input
    form.appendChild(input); // add it
    // Create hidden input element for each data key
    for (var prop in data) {
      if (data.hasOwnProperty(prop)){
        var el = new Element('input', {type: 'hidden', name: prop, value: data[prop]});
        form.insert(el);
      }
    }

    return form;
  },
  getIframe:function(id){ // create and return iFrame
    var frName = this.getFrameName(id);
    var frame = new Element('iframe', { // create new iFrame element
      id: frName,
      name: frName,
      src: 'javascript:false;', // set empty source
      style: 'display: none' // hide it
    });
    if(document.all){ //  ie hacks
      frame = toElement('<iframe src="javascript:false;" name="' + frName + '" />');
      frame = $(frame);
      frame.id = frName;
      frame.writeAttribute({style: 'display: none'});
      frame.width = 0;
      frame.height = 0;
    }
    document.body.appendChild(frame); // add it to document
    return frame;
  },
  remFrame:function(id){// remove an iFrame
    var frid = this.getFrameName(id);// get the iFrame ID
    if($(frid)){
      $(frid).stopObserving(); // prevent any events from firing;
      $(frid).src = "about:blank"; // stop any uploads
      $(frid).remove(); // and remove iframe
    }
  },
  remForm:function(id){ // remove a form
    var frid = this.getFormName(id); // get the form ID
    if($(frid)){ // if it exists
      $(frid).remove(); // remove it from document
    }
  },
  getFileInput:function(id){
    var upname = this.getUploadFileName(); // get inout name
    var upid = this.getUploadId(id); // ID
    if(this.settings.useskinnedupload == false){ // if not using skinned input, return normal HTML file input
      var input = new Element('input',{type: 'file', name: upname, id: upid}); // create element
    }else{
      var options = { // build some options for widget
          inputId: upid,   // real input id
          inputName: upname
      };
      if(undefined != this.settings.icon){ // if you pass Icon path to constructor, it should get passed here
        options.icon = this.settings.icon;
      }
      var skin = new SkinnableFileInput(options);
      var input = skin.widget();
    }
    return input;
  },
  getUploadId:function(id){// format file input ID and return it, it should be the unique for all files per option so we add nextid to it
    return this.settings.fileId.sub('#{id}', this.idx).sub('#{queue}', id);
  },
  getUploadFileName:function(){ // format file input name and return it, it should be the same for all files per option
    return this.settings.fileName.sub('#{id}', this.idx);
  },
  getFrameName:function(id){// format iFrame name and return it. Format is made of a prefix plus current option ID, plus current file id for this option
    return this.settings.frameId + this.idx + '-' + id;
  },
  getFormName:function(id){ // format form name and return it. Format is made of a prefix plus current option ID, plus current file id for this option
    return this.settings.formId + this.idx + '-' + id;
  },
  getQueueItem:function(){ // get item from queue we use ONLY first item nothing else
    var length = this.queue.size();
    if(length > 0){ // if there are ids in queue, get first in line
      return this.queue.shift();
    }
    return false;
  },
  getRegistryItem:function(id){ // get upload from registry
    return this.registry.get(id);
  },
  addToQueue:function(id){ // add upload id to queue, from where it will be taken on its time
    if(this.queue.indexOf(id) != -1){ // if this upload ID is not in queue - add it
      return false;
    }
    return this.queue.push(id);
  },
  addToRegistry:function(id, upload){ // register upload to registry, from where it will be submitted
    if(undefined != this.registry.get(id)){ // each upload object is added only once. It holds the form and its target
      return false;
    }
    return this.registry.set(id, upload);
  },
  removeFromQueue:function(id){ // remove upload id from queue
    if(this.queue.indexOf(id) != -1){ // check if it is in queue and then remove it
      var idx = this.queue.indexOf(id);
      this.queue.splice(idx, 1);
    }
  },
  removeFromRegistry:function(id){ // clear upload from registry and also remove iFrame and form created, so that we do not polute document
    var upload = this.registry.unset(id);
    if(upload){
      this.remForm(id); // remove form
      this.remFrame(id); // remove frame
    }
  },
  addUpload:function(){ // add upload form and frame to document
    var id = ++this.nextId; // get unique id for this upload widget
    var iFrame = this.getIframe(id); // create iFrame
    var data = $(this.form).serialize(true); // get major form's data (it includes product id, option id and hash that we need)
    var form = this.getNewForm(id, iFrame, data); // get the form
    // adding events should be done only after elements are added to screen.
    // $(form).observe('submit', this.onFormSubmit); // this is not working when submiting via JS
    $(this.getUploadId(id)).observe('change', this.onFileChange.bindAsEventListener(this, {form: form, frame: iFrame})); // listen for when user selects a file and act accordingly
    this.formempty = true; // set flag that the form is new so in event of last queue item is removed we do not add another form by accident
  },
  updateFileList:function(id, content){ // proxy function to add content to file list
    if(!$(id + this.settings.listItemId)){ // if list item does not exist, create it and add content
      this.addToUploadList(content, id);
    }else{ // else simply update it
      this.updateUploadsList(content, id + this.settings.listItemId);
    }
  },
  clearFromFileList:function(id){ // clear file list
    if($(this.getListItemId(id))){
      this.removeFromUploadList(this.getListItemId(id));
    }
  },
  removeUpload:function(id){ // unregister upload
    this.removeFromQueue(id);
    this.removeFromRegistry(id);
  },
  startUpload:function(id){  // start upload of a queue item
    var upload = this.getRegistryItem(id); // get upload pair, form + iFrame
    if(upload){
      var form = upload.form;
      var frame = upload.frame;
      var name = this.getUploadId(id); // get file name for display purposes
      name = $(name);
      name = name.value;
      var uploadingText = this.getLoadingText(name, id); // format message
      this.updateFileList(id, uploadingText); // and display it for user
      var params = {frame: frame, form: form, id: id, file: name}; // prepare params to pass to events
      $(frame).observe('load', this.onLoaded.bindAsEventListener(this, params));  // set event listener to frame on when it is loaded
      var cancelLinkId = this.getStopLinkId(id); // get id of cancel link
      $(cancelLinkId).observe('click', this.onCanceled.bindAsEventListener(this, params)); // and add listener to it, with Prototype this should be done AFTER link is displayed
      form.submit(); // submit upload
      this.onFormSubmit(); // this shold of been listener for form, but it does not work when submitting via JS
      this.hideAddtoCartButton(); // if we have strated upload, make sure all add to cart buttons are not visible.
      window.uploadInProgress[this.idx] = true;// set flag that upload is in progress
    }
  },
  isQueueEmpty:function(){ // check if we have more items in queue
    return this.queue.size() > 0 ? false : true;
  },
  nextInQueue:function(){ // move queue forward
    var id = this.getQueueItem();
    if(id !== false){ // if there is queued item
      this.startUpload(id);  // start upload
    }else{ // no ites in queue
      this.showAddtoCartButton();  // we can show add to cart button
    }
  },
  deleteUpload:function(){
    // @todo
  },
  getResponse: function(iframe){ // this was taken from another script, and ported here. We expect only JSON formatted responses
    if (// For Safari
        iframe.src == "javascript:'%3Chtml%3E%3C/html%3E';" ||
        // For FF, IE
        iframe.src == "javascript:'<html></html>';"){
      return null;
    }

    var doc = iframe.contentDocument ? iframe.contentDocument : window.frames[iframe.id].document;

    // fixing Opera 9.26,10.00
    if (doc.readyState && doc.readyState != 'complete') {
      // Opera fires load event multiple times
      // Even when the DOM is not ready yet
      // this fix should not affect other browsers
      return null;
    }

    // fixing Opera 9.64
    if (doc.body && doc.body.innerHTML == "false") {
      // In Opera 9.64 event was fired second time
      // when body.innerHTML changed from false
      // to server response approx. after 1 sec
      return null;
    }

    var response;

    if (doc.XMLDocument) {
      // response is a xml document Internet Explorer property
      response = doc.XMLDocument;
    } else if (doc.body){
      // response is html document or plain text
      response = doc.body.innerHTML;
      // If the document was sent as 'application/javascript' or
      // 'text/javascript', then the browser wraps the text in a <pre>
      // tag and performs html encoding on the contents.  In this case,
      // we need to pull the original text content from the text node's
      // nodeValue property to retrieve the unmangled content.
      // Note that IE6 only understands text/html
      if (doc.body.firstChild && doc.body.firstChild.nodeName.toUpperCase() == 'PRE') {
        response = doc.body.firstChild.firstChild.nodeValue;
      }

      if (response) {
        // there are great library functions that parse json,
        // but here firbug ruins things in Firefox, so this is proxy function
        response = response.sub(/{.*}/, '#{0}');
        try {
          response = response.evalJSON();
        } catch (e) {
          response = {
            error : e.message
          };
        }
      } else {
        response = {};
      }
    } else {
      // response is a xml document
      response = doc;
    }
    return response;
  },
  hideAddtoCartButton:function(){ // hide add to cart button  so that all uploads complete before continuing
    var btns = $$('.btn-cart');   // 'btn-cart' is the class currently used in Magento, if this is not working lookup what has replaced the class
    btns.invoke('addClassName', this.settings.addtoHide); // add class to hide button, hopefully this will prevent collisions with W2P
  },
  showAddtoCartButton:function(){ // show add to cart buttons when there are no more running uploads
    var uploading = window.uploadInProgress; // get all uploading ids
    var show = true; // show by default
    uploading.each(function(u){
      if(u == true){ // if any upload is still true
        show = false; // don't show
      }
    });
    if(show){
      var btns = $$('.btn-cart');
      btns.invoke('removeClassName', this.settings.addtoHide); // remove hide class
    }
  },
  // event handlers
  onFormSubmit:function(e){ // this is supposed to be handler right before the upload starts
    var frm = $(this.getFormName(this.nextId)); // get current form
    frm.hide(); // hide it
    this.addUpload(); // and add next
  },
  onFileChange:function(e){ // this handles file input change event, meaning that it is fired when user has chosen a file
    var upload = arguments[1]; // we have passed some parameters upon registering as listener
    var id = this.nextId; // get current file id
    this.addToQueue(this.nextId); // add the id to queue
    this.addToRegistry(this.nextId, upload); // and add upload object holding {form: form, frame: iframe} to registry
    this.formempty = false; // form is not empty no more, so if this upoad is removed from queue, we will need to add new form
    var name = this.getFileName(Event.element(e).value); // get file name for display list
    var content = this.getAddedText(name, this.nextId);  // prepare text content
    this.updateFileList(this.nextId, content);           // add it to file list
    var remLink = this.getRemoveLinkId(this.nextId);     // get id of remove link
    var params = {id: this.nextId};
    $(remLink).observe('click', this.onRemoved.bindAsEventListener(this, params)); // add listener to remove upload from queue, passing correct upload id
    if(window.uploadInProgress[this.idx] == false){ // if we are not uploading, strat
      this.nextInQueue();
    }else{
      this.onFormSubmit();       // hide current form and add next
    }
  },
  onLoaded: function(e){ // handles iframe load event
    window.uploadInProgress[this.idx] = false; // set uploading flag to false
    var params = arguments[1]; // get our params from arguments
    var frame = params.frame;
    var form = params.form;
    var uploadId = params.id;
    var fileName = params.file;
    var response = this.getResponse(frame); // get response from iFrame
    if(response == null){ // if something very bad has happened
      return; // do nothing
    }
    if(undefined != response.title){
      var loadedText = this.getDeleteLink(response, uploadId);
      this.updateUploadsList(loadedText, this.getListItemId(uploadId)); // if title is set, that is valid response from server
      var fake_input = this.settings.origFileId.sub('#{id}', this.idx);
      fake_input = $(fake_input);
      if(fake_input){
        fake_input.value += loadedText;
        opConfig.reloadPrice(); // supposed to add price to total
      }
      var delLink = $(this.getRemoveLinkId(uploadId));
      delLink.observe('click', function(e){
        Event.stop(e);
        if(!confirm('Are you sure you want to delete this file?')){
          return false;
        }
        var url = this.href;
        var id = $(this).identify();
        new Ajax.Request(url, {
          method: 'get',
          onSuccess: function(transp){
            var link = $(id);
            var parent = link.up('li');
            link.hide();
            var content = parent.innerHTML;
            parent.update('<del>' + content + '</del>&nbsp;' + transp.responseText);
            fake_input.value = fake_input.value.sub(loadedText, ''); // remove
            opConfig.reloadPrice();
          },
          onFailure: function(transp){
            var link = $(id);
            var parent = link.up('li');
            parent.insert('&nbsp;' + transp.responseText);
          }
        });
        return false;
      });
    }else{ // else an error is occured give out general message
      console.warn(response.error);
      this.updateUploadsList('A problem has occured, please try again', this.getListItemId(uploadId));
    }
    this.removeUpload(uploadId); // remove from queue
    this.nextInQueue(); // continue with next
  },
  onCanceled: function(e){ // handles when running upload is canceled
    e.stop(); // stop the event that caused it 'click' for example
    var params = arguments[1]; // get params
    var uploadId = params.id;
    this.removeUpload(uploadId); // remove upload from queue
    this.clearFromFileList(uploadId); // clear file list
    window.uploadInProgress[this.idx] = false; // we are not uploading any more
    this.nextInQueue();     // move queue
    return false;           // should do the same as e.stop() for older browsers
  },
  onRemoved: function(e){  // handles when queued upload is removed from queue
    e.stop(); // stop the event that caused it 'click' for example
    var params = arguments[1]; // get params
    var uploadId = params.id;
    var last = this.isLastInQueue(uploadId); // check if this id is lat in queue, if it is removing its form will leave us without form
    this.removeUpload(uploadId);  // remove it from queue
    this.clearFromFileList(uploadId); // clear file list
    if(last && this.formempty == false){ // if we removed last queued item, there is no form.
      this.addUpload();
    }
    return false;           // should do the same as e.stop() for older browsers
  },
  isLastInQueue: function(id){ // determine if given ID is last in queue, which means that its form is currently displayed
    if(this.queue.indexOf(id) != -1){
      var idx = this.queue.indexOf(id);
      var len = this.queue.length; // queue size
      var lastPos = len - 1; // this is last position in queue
      return idx == lastPos; // if ID index is same as last position - then it is last
    }
    return false;
  }
});

// skinned file input
// has to have the option to be skinned to much greater extent then normal file input
// make this like a widget with possibe settings


var SkinnableFileInput = Class.create({
  _input: undefined,    // actual file input
  _widget: undefined,   // widget container has to be relatively positioned
  _value: undefined,    // text input to which we transfer file input value
  _button: undefined,   // non clickable button to represent overall look, it is a div with desired visual appearance
  _fakeinput: undefined,  // container for our fake input
  inputId: undefined,   // real input id
  inputClass: undefined,  // real input class
  inputName: undefined,   // real input form name
  widgetId: undefined,  // widget ID
  widgetClass: undefined, // widget class
  fakeInputClass: undefined, // fake input class
  valueClass: undefined, // value text input class
  valueName: undefined,  // value text form name
  buttonClass: undefined, // button class
  icon: undefined,  // icon to use in button
  width_in_chars: undefined, // firefox uses size to set actual size of input, for customizing, it is try and error efford
  initialize:function(options){
    // add default optoins, override them with any user options
    this.setDefaults().setOptions(options).prepareWidget();
  },
  setDefaults:function(){
    // setting default widget ids, classes, and names,
    // if only single widget will be used you may change them here
    // but it is not recommended, pass what you need to change
    // via options to constructor.
    this.inputId =        'zp-file-input-id';
    this.inputClass =     'zp-file-input-class';
    this.inputName =      'zp-file-input-name';
    this.widgetId =       'zp-skinnable-file-widget-id';
    this.widgetClass =    'zp-skinnable-file-widget-class';
    this.fakeInputClass = 'zp-file-fakeinput-class';
    this.valueClass =     'zp-file-fake-value-class';
    this.valueName =      'zp-file-fake-value-name';
    this.buttonClass =    'zp-file-button-class';
    this.width_in_chars = 20;
    return this;
  },
  setOptions: function(options){
    // Merge the users options with our defaults
    for (var i in options) {
      if(i == 'inputId'){
        this.inputId = options[i];
        this.widgetId += this.inputId;
      }else if(options.hasOwnProperty(i)){
        this[i] = options[i];
      }
    }
    return this;
  },
  prepareWidget: function(){
    var widget = this.getWidget();
    var input = this.getInput();
    var fake = this.getFakeInput();
    widget.insert(input).insert(fake);
  },
  getWidget: function(){
    if(this._widget == undefined){// @todo get widget, create one if not exists
      var widget = new Element('div');
      widget.id = this.widgetId;
      widget.addClassName(this.widgetClass);
      document.body.appendChild(widget);
      widget.hide();
      this._widget = widget;
    }
    return this._widget;
  },
  getInput: function(){
    // get real file input, create one if not exists
    if(undefined == this._input){
      var input = new Element('input');
      input.id = this.inputId;
      input.type = 'file';
      input.name = this.inputName;
      input.size = this.width_in_chars;
      input.addClassName(this.inputClass);
      input.addClassName('invisible');
      this._input = input;
    }
    return this._input;
  },
  getFakeInput: function(){
    // get fake input, create one if not exists
    if(undefined == this._fakeinput){
      var fake = new Element('div');
      fake.addClassName(this.fakeInputClass);
      fake.insert(this.getValueInput()).insert(this.getButton());
      if(this.icon != undefined){
        this.setBtnIcon(this.icon);
      }
      this._fakeinput = fake;
    }
    return this._fakeinput;
  },
  getValueInput: function(){
    // get value text input, create one if not exists
    if(undefined == this._value){
      var value = new Element('input');
      value.type = 'text';
      value.addClassName(this.valueClass);
      value.addClassName('input-text');
      this._value = value;
    }
    return this._value;
  },
  getButton: function(){
    // get button, create one if not exists
    if(undefined == this._button){
      var btn = new Element('button');
//      btn.type = 'button';
      btn.writeAttribute({type: 'button'});
      btn.addClassName('button'); // this is magento class
      btn.addClassName(this.buttonClass);
      btn.update('<span class="btn-label"><span>Browse</span></span>');
      this._button = btn;
    }
    return this._button;
  },
  setBtnIcon: function(icon){ // icon to display in the browser button, should be full skin path
    var btn = this.getButton();
    var url = 'url(' + icon + ')';
    btn.setStyle({backgroundImage: url});
  },
  widget:function(){ // return functional widget
    var widget = this.getWidget(); // get the widget, show it
    widget.show();
    var input = this.getInput(); // add listeners to input so its value is transferred to text input
    input.observe('change', this.updateValue.bindAsEventListener(this));
    input.observe('change', this.updateValue.bindAsEventListener(this))
         .observe('mouseout', this.updateValue.bindAsEventListener(this));
    return widget.remove(); // removing element from dom returns that element, so we take it from where it was and pass it where we want it to be
  },
  updateValue: function(e){ // event handler
    var input = Event.element(e);
    var lbl = this.getValueInput();
    lbl.value = input.value;
  }
});


/**
 * Creates and returns element from html chunk
 * Uses innerHTML to create an element
 *
 * this is the only way to make form work in IE < 8
 */
var toElement = (function(){
  var div = document.createElement('div');
  return function(html){
    div.innerHTML = html;
    var element = div.firstChild;
    div.removeChild(element);
    return element;
  };
})();

function updatePosition(att) {
  var resize_timer;
  // handle positioning on resize
  Event.observe(window, 'resize', function(e){
    if(resize_timer){
      clearTimeout(resize_timer);
    }
    resize_timer = setTimeout(function(){
      var contClass = '.' + this.settings.containerDivClass;
      var containers = $$(contClass);
      containers.each(function(c){
        $(c).fire(this.settings.updateListEvent);
      }.bind(this));
    }.bind(this), 100);
  }.bindAsEventListener(att));

  var redraw_timer;
  var parent_el = $(att.original).up('form');
  var parent_dims = parent_el.getDimensions();
  redraw_timer = new PeriodicalExecuter(function(){
    var el = att;
    var orig_dims = parent_dims;
    var curr_parent_dims = parent_el.getDimensions();
    if(orig_dims.height != curr_parent_dims.height ||
          orig_dims.width != curr_parent_dims.width){
      parent_dims = curr_parent_dims;
      var contClass = '.' + el.settings.containerDivClass;
      var containers = $$(contClass);
      containers.each(function(c){
        $(c).fire(this.settings.updateListEvent);
      }.bind(el));
    }
  }, 0.1);
}

