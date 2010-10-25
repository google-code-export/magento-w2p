/**
 * Adding file to upload
 *
 * Used parts of ajaxupload.js
 *
 * @param fileUpload HTMLInputElement
 * @param the_action String
 * @return void
 */
function addAttachment(fileUpload, the_action){
    fileUpload.observe('change', function(e){
        if(this.value.length > 0){
            var idx = this.readAttribute('id').sub(/option_(\d+)_file/, '#{1}');
            var submitBtn = $('zp-btn-upload-' + idx);
            submitBtn.observe('click', function(ev){
                var uploadLbl = $('zp-btn-upload-' + idx + '-lbl');
                var theForm = this.form;
                var olAction = this.form.action;
                theForm.action = parseSidUrl(the_action);
                theForm.target = 'upload-target-' + idx;
                theForm.submit();
                theForm.target = '';
                theForm.action = olAction;
                fileUpload.disable();
                fileUpload.up().insert(new Element('button', {
                    id:'add-new-file' + idx
                    }).update('Add another file'));
                ev.stop();
                return false;
            });
            $('upload-target-' + idx).observe('load', function(e){
                var iframe = this;
                if (// For Safari
                    iframe.src == "javascript:'%3Chtml%3E%3C/html%3E';" ||
                    // For FF, IE
                    iframe.src == "javascript:'<html></html>';")
                    {
                    iframe.stopObserving('load');
                    return;
                }
                var doc = iframe.contentDocument ? iframe.contentDocument : window.frames[iframe.id].document;
                // fixing Opera 9.26,10.00
                if (doc.readyState && doc.readyState != 'complete') {
                    return;
                }

                // fixing Opera 9.64
                if (doc.body && doc.body.innerHTML == "false") {
                    return;
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
                        response = eval("(" + response + ")");
                    } else {
                        response = {};
                    }
                } else {
                    // response is a xml document
                    response = doc;
                }
                // Fix IE mixed content issue
                iframe.src = "javascript:'<html></html>';";
                updateAttachmentsList('attachments-list', response);
            });
            submitBtn.disabled = '';
        }
    });
}

/**
 * Update list of uploaded files
 * @param list String|Element
 * @param response Object
 * @return void
 */
function updateAttachmentsList(list, response)
{
    var attachmentsList = $(list);
    if(undefined == $('file-list')){
        attachmentsList.insert(new Element('ul', {
            id:'file-list'
        }));
    }
    var ul = $('file-list');
    ul.insert(new Element('li', {
        'class':'file-list-item'
    }).update(response.title));
    attachmentsList.up().show();
}
