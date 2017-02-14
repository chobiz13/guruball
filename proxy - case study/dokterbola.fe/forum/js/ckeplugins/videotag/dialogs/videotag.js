/*!=======================================================================*\
|| ###################################################################### ||
|| # vBulletin 5.2.3
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2016 vBulletin Solutions Inc. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/

(function(){
	var exampleDialog = function(editor){

		CKEDITOR.on('dialogDefinition', function(ev){
			var dialogName = ev.data.name;
			var dialogDefinition = ev.data.definition;

			if (dialogName == 'videotag')
			{
				var dialog = CKEDITOR.dialog.getCurrent();

				vBulletin.AJAX(
				{
					async: false,
					url    : vBulletin.getAjaxBaseurl() + '/ajax/api/editor/fetchVideoProviders',
					success: function(result)
					{
						if (result && result.data)
						{
							var dialog_html = '<div>';
							$.each(result.data, function(name, url)
							{
								dialog_html += '<a target="_blank" href="' + url + '" class="videoListLink">' + name + '</a>&nbsp;';
							});
							dialog_html += "</div>";
							dialogDefinition.getContents('videotag').elements[2].html = dialog_html;
						}
						else
						{
							console.log('/ajax/api/editor/fetchVideoProviders was successful, but response was not an array');
							openAlertDialog({
								'title'    : vBulletin.phrase.get('error'),
								'message'  : vBulletin.phrase.get('invalid_server_response_please_try_again'),
								'iconType' : 'error'
							});
						}
					},
					error_phrase: 'error_converting_message'
				});
			}
		});

		return {
			title: vBulletin.phrase.get('insert_video_clip'),

			onOk: function(event){
				var url = this.getValueOf('videotag', 'urlInput');
				url = $.trim(url);
				if (url != '')
				{
					editor.insertText('[VIDEO]' + url + '[/VIDEO]');
				}
			},
			onShow: function(){

			},
			resizable: CKEDITOR.DIALOG_RESIZE_NONE,
			width: '300',
			minWidth : '300',
			minHeight: '200',
			contents: [{
				id: 'videotag',
        elements:[{
        	type: 'text',
          id: 'urlInput',
          label: vBulletin.phrase.get('enter_video_url_below'),
          labelLayout: 'vertical'
        },{
        	type: 'html',
        	html: '<div>' + vBulletin.phrase.get('example_video_url') + '</div><br/>' +
        				'<div>' + vBulletin.phrase.get('supported_videos') + '</div>'
        },{
        	type: 'html',
			className: 'videoList',
        	html: ''
        }]
			}]
		}
	};

	CKEDITOR.dialog.add('videotag', function(editor) {
		return exampleDialog(editor);
	});

})();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision$
|| #######################################################################
\*=========================================================================*/
