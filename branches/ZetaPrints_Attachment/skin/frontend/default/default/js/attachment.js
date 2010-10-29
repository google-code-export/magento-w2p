/**
 * Adding file to upload
 *
 * Used parts of ajaxupload.js
 *
 * @param
 * @param String the_action
 * @return void
 */
//function addAttachment(fileUpload, the_action, the_form){
//  fileUpload = $(fileUpload);
//    fileUpload.observe('change', function(e){
//        if(this.value.length > 0){
//            var idx = this.readAttribute('id').sub(/option_(\d+)_file/, '#{1}');
//            var submitBtn = $('zp-btn-upload-' + idx);
//            submitBtn.observe('click', function(ev){
//                ev.stop();
//                this.disabled = 'disabled';
//
//                var theForm = this.form;
//                submitUpload(theForm, the_action, 'upload-target-' + idx);
//
//                var upldGrp = $('upload_group_' + idx);
//                var uploadId = fileUpload.id;
//                var uploadName = fileUpload.name;
//                var uploadValue = fileUpload.value;
//                fileUpload.up('div.zp-upload').insert(new Element('button', {id:'add-new-file' + idx}).update('Add another file'));
//                fileUpload.stopObserving();
//                this.stopObserving();
//                Element.replace(fileUpload, '<input type="hidden" name="' + uploadName + '" id="' + uploadId + '" value="' + uploadValue + '"/>');
//                this.remove();
//                upldGrp.hide();
//                $('add-new-file' + idx).observe('click', function(e){
//                  Element.remove(uploadId);
//                  upldGrp.insert({top:new Element('input', {type:'file', id:uploadId, name:uploadName})});
//                  upldGrp.insert(new Element('button', {type:'button', id:'zp-btn-upload-' + idx, disabled:'disabled'}).update('Upload'));
//                  addAttachment(uploadId, the_action);
//                  upldGrp.show();
//                  this.remove();
//                });
//
//
//                return false;
//            });
//            var loaded = false;
//            $('upload-target-' + idx).observe('load', function(e){
//                var iframe = this;
//                if (// For Safari
//                        iframe.src == "javascript:'%3Chtml%3E%3C/html%3E';" ||
//                        // For FF, IE
//                        iframe.src == "javascript:'<html></html>';")
//                {
//                  if(loaded){
//                    iframe.stopObserving('load');
//                    return;
//                  }
//                }
//                var doc = iframe.contentDocument ? iframe.contentDocument : window.frames[iframe.id].document;
//                // fixing Opera 9.26,10.00
//                if (doc.readyState && doc.readyState != 'complete') {
//                   return;
//                }
//
//                // fixing Opera 9.64
//                if (doc.body && doc.body.innerHTML == "false") {
//                    return;
//                }
//                var response;
//
//                if (doc.XMLDocument) {
//                    // response is a xml document Internet Explorer property
//                    response = doc.XMLDocument;
//                } else if (doc.body){
//                    // response is html document or plain text
//                    response = doc.body.innerHTML;
//                    // If the document was sent as 'application/javascript' or
//                    // 'text/javascript', then the browser wraps the text in a <pre>
//                    // tag and performs html encoding on the contents.  In this case,
//                    // we need to pull the original text content from the text node's
//                    // nodeValue property to retrieve the unmangled content.
//                    // Note that IE6 only understands text/html
//
//                    if (doc.body.firstChild && doc.body.firstChild.nodeName.toUpperCase() == 'PRE') {
//                        response = doc.body.firstChild.firstChild.nodeValue;
//                    }else{
//                      response = response.sub(/(\{.*\})[^\{\}]*/, '#{1}');
//                    }
//
//                    if (response) {
//                        response = eval("(" + response + ")");
//                    } else {
//                        response = {};
//                    }
//                } else {
//                    // response is a xml document
//                    response = doc;
//                }
//                // Fix IE mixed content issue
//                loaded = true;
//                iframe.src = "javascript:'<html></html>';";
//                updateAttachmentsList('attachments-list', response);
//            });
//            submitBtn.disabled = '';
//        }
//    });
//}

function addAttachment(button, action, form)
{
  var btn = $(button);
  var idx = btn.readAttribute('id').sub(/zp-btn-upload-(\d+)/, '#{1}');
  var name = 'options_' + idx + '_file';
  var theData = $(form).serialize(true);
  var upload = new AjaxUpload(btn,{
    'action':action,
    'name':name,
    'data':theData,
    'responseType':'json',
    onChange: function (file, extension) {},
    onSubmit: function (file, extension) {
      btn.disabled = 'disabled';
      btn.hide();
      var add = new Element('button', {id:'add-new-file' + idx, type:'button'}).update('Add another file');
      addFileNameToForm(form, file, idx);
      btn.up('div.zp-upload').insert(add);
      add.observe('click', function(e){
        btn.disabled = '';
        btn.show();
        this.remove();
      });
      return true;
    },
    onComplete: function (file, response) {
      updateAttachmentsList('attachments-list', response);
    }
  });
}

/**
 * Update list of uploaded files
 * @param String|Element list
 * @param Object response
 * @return void
 */
function updateAttachmentsList(list, response)
{
    var attachmentsList = $(list);
    if(undefined == $('file-list')){
        attachmentsList.insert(new Element('ul', {'id':'file-list'}));
    }
    var ul = $('file-list');
    ul.insert(new Element('li', {'class':'file-list-item'}).update(response.title));
    attachmentsList.up().show();
}

function addFileNameToForm(form, filename, index)
{
  var hidden = new Element('input', {type:'hidden', name:'attached_files[' + index +']', value:filename});
  $(form).insert(hidden);
}

function submitUpload(theForm, the_action, the_target)
{
  var olAction = theForm.action;
  var olSubmit = theForm.onsubmit;
  theForm.onsubmit = '';
  theForm.action = parseSidUrl(the_action);
  theForm.target = the_target;
  theForm.submit();
  theForm.target = '';
  theForm.action = olAction;
  theForm.onsubmit = olSubmit;
}
