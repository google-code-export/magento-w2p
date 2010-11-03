var AttachedFilesRegistry = new Hash();
/**
 * Adding file to upload
 *
 * Used parts of ajaxupload.js
 *
 * @param
 * @param String the_action
 * @return void
 */
function addAttachment(button, action, form, spinner)
{
  var btn = $(button);
  var idx = btn.readAttribute('id').sub(/zp-btn-upload-(\d+)/, '#{1}');
  var name = 'options_' + idx + '_file';
  var theData = $(form).serialize(true);
  if(undefined == AttachedFilesRegistry.get(name)){
	  AttachedFilesRegistry[name] = $H();
	  AttachedFilesRegistry[name].counter = 0;
  }

  var listItemId = name + 'list';
  var upload = new AjaxUpload(btn,{
    'action':action,
    'name':name,
    'data':theData,
    'responseType':'json',
    onChange: function (file, extension) {
  	},
    onSubmit: function (file, extension) {
  	  AttachedFilesRegistry[name].counter++;
  	  listItemId += AttachedFilesRegistry[name].counter;
	  updateAttachmentsList('attachments-list', listItemId, {title:'Loading <strong>' + file + '</strong>&nbsp;&nbsp;<img src="' + spinner + '" alt="loading"/>'});
      btn.disabled = 'disabled';
//      btn.hide();
      if(undefined == $('add-new-file')){
    	  var add = new Element('button', {id:'add-new-file' + idx, type:'button'}).update('Add another file');
      }
      addFileNameToForm(form, file, idx);
//      btn.up('div.zp-upload').insert(add);
      btn.insert({after:add});
      add.observe('click', function(e){
        btn.disabled = '';
        btn.show();
        this.remove();
      });
      return true;
    },
    onComplete: function (file, response) {
      updateAttachmentsList('attachments-list', listItemId, response);
    }
  });
}

/**
 * Update list of uploaded files
 * @param String|Element list
 * @param Object response
 * @return void
 */
function updateAttachmentsList(list, lid, response)
{
    var attachmentsList = $(list);
    if(undefined == $('file-list')){
        attachmentsList.insert(new Element('ul', {'id':'file-list'}));
    }
    var ul = $('file-list');
    if(undefined == $(lid)){
    	ul.insert(new Element('li', {'class':'file-list-item', id: lid}));
    }
    $(lid).update(response.title);
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

function confirmDelete(e)
{
	e.stop();
	var do_delete = confirm('Do you really want to delete this file?');
	if(do_delete){
		var link = e.currentTarget;
		var url = link.readAttribute('href');
		var container = link.previous('.zp-attachment-file');
		new Ajax.Request(url, {method:'get',
			  onSuccess: function(transport) {
				container.update(transport.responseText);
			  }
		});
	}
	return false;
}
Event.observe(window, 'load', function(e){
	var delLinks = $$('.zp-delete-file');
	delLinks.each(function(link){
		link.observe('click', confirmDelete);
	});
});
