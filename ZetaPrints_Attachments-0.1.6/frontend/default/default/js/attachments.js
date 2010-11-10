/**
 * @author      Petar Dzhambazov
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Attachments class prototype
 */
var attachments = Class.create({
  action: undefined,    // form action, this is where all uploads will be submitted
  form: undefined,      // form from which we will get data, this should be original Magento form ID from product page
  idx: undefined,       // Option index, very important for magento, every custom option has this index, it is used to process options
  uploading: false,     // flag if there is upload going on at the moment
  nextId: 0,            // counter for this object's uploads. It should be increased with every added file
  container: undefined, // container to hold our forms
  original: undefined,  // original container Div that has original content which will be replaced by attachments forms
  formempty: false,     // a flag showing if current visible file input has a file attached to it or not
 /*
  * This is file registry and queue. All files added to queue.
  * We use queue to keep the order and registry to keep AjaxUpload
  * objsects them selves. Items in queue corespond to keys in
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
  initialize:function(fileid, action, form, options){
    this.action = action;
    this.form = form;
    this.settings = {  // for proper inheritance to work, we need to assign values here
        placementDiv: 'zp-file-upload-',
        containerDiv: 'zp-form-container-',
        containerDivClass: 'zp-form-container',
        attachmentsListName: 'zp-attachments-list-',
        formName: 'zp-attachments-form-',
        frameName: 'zp-attachments-frame-',
        fileName: 'options_#{id}_file',       // every file upload has to have the same name for Magento options to work
        origFileId: 'option_#{id}_file',      // original file upload ID template
        fileId: 'option_#{id}_file_#{queue}', // we add queue id so that we don't break html rules too much
        // if custom filename, file id strings are provided,
        // make sure they have #{id} and #{queue} in them respectively or this will not work
        removeLinkClass: "zp-remove-file-link",
        cancelLinkClass: "zp-cancel-file-link",
        listItemId: '_list_item',
        listItemClass: 'zp-file-list-item',
        listId: '_file_list',
        listClass: 'zp-file-list',
        spinner: '../images/opc-ajax-loader.gif', // default spinner image
        updateListEvent: 'attachment:listupdate'  // custom event that we are firing to update containers positions
    };
    this.idx = fileid;
    this.registry = $H();
    this.queue = [];
    this.setOptions(options);
  },
  addFirstUpload:function(){
    var cont = this.createContainer(this.idx);
    this.original = $(this.settings.placementDiv + this.idx);
    var or = $(this.original);
    cont.absolutize();
    var dim = or.getDimensions();
    or.setStyle({width: dim.width+'px', height: 'auto'});
    cont.clonePosition(this.original);
    cont.originalPosition = cont.positionedOffset();
    this.hideOriginalContent();
    this.addUpload();
  },
  hideOriginalContent:function(){
    var div = $(this.original);
    if(div){
      var originalFileId = this.settings.origFileId.sub('#{id}', this.idx);
      var replDiv = new Element('div', {id: 'repl-' + originalFileId}).clonePosition($(originalFileId), {
        setLeft: false,  //clones source’s left CSS property onto element.
        setTop: false,  //clones source’s top CSS property onto element.
        setWidth: true,  //clones source’s width onto element.
        setHeight: false}
      ).update('&nbsp;');
      var height = $(this.container).getHeight();
      replDiv.setStyle({height: height + 'px'});
      Element.replace(originalFileId, replDiv);
      $(this.container).observe(this.settings.updateListEvent, function(e){
        var div = 'repl-' + this.settings.origFileId.sub('#{id}', this.idx);
        $(this.container).clonePosition($(div), {setLeft:false, setTop: true, setWidth:false, setheight:false});
      }.bindAsEventListener(this));
    }
  },
  showList:function(list){
    var l = $(list);
    l.up().show();
//    this.updateContainer(list);
    this.fireListUpdate();
  },
  updateContainer: function(list){
    var l = $(list.parentNode);
    var listHeight = l.getHeight();
    var offset = this.container.originalPosition;
    var top = offset.top + listHeight;
    this.container.setStyle({top: top + 'px'});
  },
  createContainer:function(id){
    var cont = new Element('div', {id: this.settings.containerDiv + id, 'class': this.settings.containerDivClass});
    document.body.appendChild(cont);
    this.container = cont;
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
    var link = new Element('a', {href: '#', "class": this.settings.removeLinkClass, id : this.getRemoveLinkId(id)}).update('Remove'); // compose remove link
    return link;
  },
  getStopLink:function(id) // link to stop files from upload
  {
    var link = new Element('a', {href: '#', "class": this.settings.cancelLinkClass, id : this.getStopLinkId(id)}).update('Cancel'); // compose cancel link
    return link;
  },
  getRemoveLinkId:function(id) // following functions will help us add events to links by providing us with their ID's
  {
    return this.settings.removeLinkClass + "-" + this.idx + "-" + id;
  },
  getStopLinkId:function(id){
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
  getUploadList:function(){
    var list = this.getUploadListId(this.idx);
    var attachmentsList = $(list);
    if(undefined == attachmentsList){
      alert('Upload list not found');
      return;
    }
    return attachmentsList;
  },
  getUploadListId:function(id){
    return this.settings.attachmentsListName + this.idx;
  },
  getListItemId:function(id){
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
  fireListUpdate:function(){
    var containers = $$('.' + this.settings.containerDivClass);
    containers.each(function(c){
      $(c).fire('attachment:listupdate');
    });
  },
  updateUploadsList:function(item, el_id){ // update el_id content
    return $(el_id).update(item);
  },
  removeFromUploadList:function(el_id){ // remove el_id from file list
    $(el_id).remove();
//    this.updateContainer(this.getUploadList());
    this.fireListUpdate();
  },
  clearUploadList:function(){ // clear content of file list
    var attachmentsList = this.getUploadList();
    if(attachmentsList){
      attachmentsList.update('');
    }
    this.fireListUpdate();
  },
  getFileName: function(file){
    return file.replace(/.*(\/|\\)/, "");
  },
  getNewForm:function(id, iFrame, data){
    var form = new Element('form', {
      id: this.getFormName(id),
      action: this.action,
      enctype:"multipart/form-data",
      target: iFrame.name,
      method: 'post'
    });
    var input = this.getUpload(id);
    form.appendChild(input);
    // Create hidden input element for each data key
    for (var prop in data) {
      if (data.hasOwnProperty(prop)){
        var el = new Element('input', {type: 'hidden', name: prop, value: data[prop]});
        form.insert(el);
      }
    }
    this.container.insert({top:form});
    return form;
  },
  getIframe:function(id){
    var frame = new Element('iframe', {
      id: this.getFrameName(id),
      name: this.getFrameName(id),
      src: 'javascript:false;',
      style: 'display: none'
    });
    document.body.appendChild(frame);
    return frame;
  },
  remFrame:function(id){
    var frid = this.getFrameName(id);
    if($(frid)){
      $(frid).stopObserving(); // prevent any events from firing;
      $(frid).src = "about:blank"; // stop any uploads
      $(frid).remove(); // and remove iframe
    }
  },
  remForm:function(id){
    var frid = this.getFormName(id);
    if($(frid)){
      $(frid).remove();
    }
  },
  getUpload:function(id){
    var upname = this.getUploadFileName();
    var upid = this.getUploadId(id);
    var input = new Element('input',{type: 'file', name: upname, id: upid});
    return input;
  },
  getUploadId:function(id){
    return this.settings.fileId.sub('#{id}', this.idx).sub('#{queue}', id);
  },
  getUploadFileName:function(){
    return this.settings.fileName.sub('#{id}', this.idx);
  },
  getFrameName:function(id){
    return this.settings.frameName + this.idx + '-' + id;
  },
  getFormName:function(id){
    return this.settings.formName + this.idx + '-' + id;
  },
  getQueueItem:function(){ // get item from queue we use ONLY first item nothing else
    var length = this.queue.size();
    if(length > 0){
      return this.queue.shift();
    }
    return false;
  },
  getRegistryItem:function(id){
    return this.registry.get(id);
  },
  addToQueue:function(id){ // add upload id to queue, from where it will be taken on its time
    if(this.queue.indexOf(id) != -1){
      return false;
    }
    return this.queue.push(id);
  },
  addToRegistry:function(id, upload){ // register upload to registry, from where it will be submitted
    if(undefined != this.registry.get(id)){
      return false;
    }
    return this.registry.set(id, upload);
  },
  removeFromQueue:function(id){
    if(this.queue.indexOf(id) != -1){
      var idx = this.queue.indexOf(id);
      this.queue.splice(idx, 1);
    }
  },
  removeFromRegistry:function(id){ //
    var upload = this.registry.unset(id);
    if(upload){
      this.remForm(id); // remove form
      this.remFrame(id); // remove frame
    }
  },
  addUpload:function(){
    var id = ++this.nextId;
    var iFrame = this.getIframe(id);
    var data = $(this.form).serialize(true);
    var form = this.getNewForm(id, iFrame, data);
    // adding events should be done only after elements are added to screen.
    $(form).observe('submit', this.onFormSubmit);
    $(this.getUploadId(id)).observe('change', this.onFileChange.bindAsEventListener(this, {form: form, frame: iFrame}));
    this.formempty = true;
  },
  updateFileList:function(id, content){
    if(!$(id + this.settings.listItemId)){
      this.addToUploadList(content, id);
    }else{
      this.updateUploadsList(content, id + this.settings.listItemId);
    }
  },
  clearFromFileList:function(id){
    if($(this.getListItemId(id))){
      this.removeFromUploadList(this.getListItemId(id));
    }
  },
  removeUpload:function(id){
    this.removeFromQueue(id);
    this.removeFromRegistry(id);
  },
  startUpload:function(id){
    var upload = this.getRegistryItem(id);
    if(upload){
      var form = upload.form;
      var frame = upload.frame;
      var name = this.getFileName(form[this.getUploadFileName()].value);
      var uploadingText = this.getLoadingText(name, id);
      this.updateFileList(id, uploadingText);
      var params = {frame: frame, form: form, id: id, file: name};
      $(frame).observe('load', this.onLoaded.bindAsEventListener(this, params));
      var cancelLinkId = this.getStopLinkId(id);
      $(cancelLinkId).observe('click', this.onCanceled.bindAsEventListener(this, params));
      form.submit();
      this.onFormSubmit();
      this.uploading = true;
    }
  },
  cancelUpload:function(id){
    var upload = this.getRegistryItem(id);
    if(upload){
      this.removeUpload(id);
    }
  },
  isQueueEmpty:function(){
    return this.queue.size() > 0 ? false : true;
  },
  nextInQueue:function(){
    var id = this.getQueueItem();
    if(id !== false){
      this.startUpload(id);
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
  onFormSubmit:function(e){
    var frm = $(this.getFormName(this.nextId));
    frm.hide();
    this.addUpload();
  },
  onFileChange:function(e){
    var upload = arguments[1];
    var id = this.nextId;
    this.addToQueue(this.nextId);
    this.addToRegistry(this.nextId, upload);
    this.formempty = false;
    var name = this.getFileName(Event.element(e).value);
    var content = this.getAddedText(name, this.nextId);
    this.updateFileList(this.nextId, content);
    var remLink = this.getRemoveLinkId(this.nextId);
    var params = {id: this.nextId};
    $(remLink).observe('click', this.onRemoved.bindAsEventListener(this, params));
    if(this.uploading == false){ // if we are not uploading, strat
      this.nextInQueue();
    }else{
      var item = Event.element(e);
      if(item.value != ''){ // if we have file input with value - meaning a file was chosen
        this.onFormSubmit(); // hide current form and add next
      }
    }
  },
  // event handlers
  onLoaded: function(e){
    this.uploading = false;
    var params = arguments[1];
    var frame = params.frame;
    var form = params.form;
    var uploadId = params.id;
    var fileName = params.file;
    var response = this.getResponse(frame);
    if(response == null){
      return;
    }
    this.updateUploadsList(fileName, this.getListItemId(uploadId));
    this.removeUpload(uploadId);
    this.nextInQueue();
  },
  onCanceled: function(e){
    e.stop();
    var params = arguments[1];
    var uploadId = params.id;
    this.removeUpload(uploadId);
    this.clearFromFileList(uploadId);
    this.uploading = false;
    this.nextInQueue();
    return false;
  },
  onRemoved: function(e){
    e.stop();
    var params = arguments[1];
    var uploadId = params.id;
    var last = this.isLastInQueue(uploadId);
    this.removeUpload(uploadId);
    this.clearFromFileList(uploadId);
    if(last && this.formempty == false){ // if we removed last queued item, there is no form, so we add one.
      this.addUpload();
    }
    return false;
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

