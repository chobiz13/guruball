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
(function(A){window.vBulletin=window.vBulletin||{};var B=[".forgot-password-widget"];if(!vBulletin.pageHasSelectors(B)){return false}window.vBulletin.options=window.vBulletin.options||{};window.vBulletin.options.precache=window.vBulletin.options.precache||[];window.vBulletin.options.precache=A.merge(window.vBulletin.options.precache,[]);window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=A.merge(window.vBulletin.phrase.precache,["lostpw_email_sent","forgot_password_title","invalid_email_address","please_enter_your_email_address"]);A(document).ready(function(){setTimeout(vBulletin.hv.reset,0);A("#frmLostpw").ajaxForm({dataType:"json",beforeSubmit:function(F,E,D){var C="";if(A.trim(A(".email",E).val())==""){C=vBulletin.phrase.get("please_enter_your_email_address")}else{if(!isValidEmailAddress(A(".email",E).val())){C=vBulletin.phrase.get("invalid_email_address")}}if(C){openAlertDialog({title:vBulletin.phrase.get("error"),message:C,iconType:"warning",onAfterClose:function(){A(".email",E).focus()}});return false}return true},success:function(D,E,F,C){if(D&&D.response&&D.response.errors){var G=[];A.each(D.response.errors,function(H,I){if(I[0]!="exception_trace"&&I[0]!="errormsg"){G.push(vBulletin.phrase.get(I))}});openAlertDialog({title:vBulletin.phrase.get("error"),message:G.join("<br />"),iconType:"warning",onAfterClose:function(){if(typeof (vBulletin.hv.reset)!="undefined"){vBulletin.hv.reset(true)}}})}else{openAlertDialog({title:vBulletin.phrase.get("forgot_password_title"),message:vBulletin.phrase.get("lostpw_email_sent"),onAfterClose:function(){window.location.href=pageData.baseurl}})}}})})})(jQuery);