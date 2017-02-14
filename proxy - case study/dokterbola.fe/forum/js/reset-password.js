/*=======================================================================*\
|| ###################################################################### ||
|| # vBulletin 5.2.3
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2016 vBulletin Solutions Inc. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||["error","password_must_be_at_least_four_chars","passwords_must_match"];(function(B){function C(){var D=B("#reset-password-form");D.submit(function(G){var E=D.find(':input[name="new-password"]'),F=D.find(':input[name="new-password-confirm"]');if(E.val()==""||E.val().length<4){A("password_must_be_at_least_four_chars",E);return false}else{if(E.val()!=F.val()){A("passwords_must_match",F);return false}}return true})}function A(E,D){if(E){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get(E),iconType:"warning",onAfterClose:function(){D.focus()}});return false}return true}B(document).ready(C)})(jQuery);