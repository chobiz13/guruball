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

CKEDITOR.plugins.add('videotag', {
	requires: 'dialog',
	init: function( editor ) {
		editor.addCommand( 'videotag', new CKEDITOR.dialogCommand( 'videotag' ) );
		editor.ui.addButton && editor.ui.addButton( 'Video', {
			label: vBulletin.phrase.get('insert_video'),
			command:  'videotag',
			icon: window.pageData.baseurl + '/core/images/editor/video.png'
		});
		CKEDITOR.dialog.add( 'videotag', vBulletin.ckeditor.config.pluginPath + 'videotag/dialogs/videotag.js' );
	}
});

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 83435 $
|| #######################################################################
\*=========================================================================*/
