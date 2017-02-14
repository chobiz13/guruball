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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["channel_deleted_confirmation","confirm_delete","confirm_delete_channel_has_x_posts_y_forums","emails_sent_successfully","enter_email_message_and_recipients","no_permission"]);window.vBulletin.options=window.vBulletin.options||{};window.vBulletin.options.precache=window.vBulletin.options.precache||[];window.vBulletin.options.precache=$.merge(window.vBulletin.options.precache,[]);(function(G){var E=false,H=function(R){var Q=this;vBulletin.assertUserHasAdminPermission("canusesitebuilder",Q,function(){K.call(Q,R)});return false},K=function(T){var S=G(this),R=G(document.body),Q=!S.hasClass("selected");N(!Q,S);if(Q){G(document.body).addClass("js-edit-mode-style");B();G(".js-config-site-panel").css("display","none").removeClass("h-hide").slideDown(400,L)}else{G(".js-config-site-panel").slideUp(function(){G(document.body).removeClass("js-edit-mode-style");var U=window.location.href;if(U.indexOf("adminAction=quickConfig&")!=-1){U=U.replace("adminAction=quickConfig&","")}else{U=U.replace("?adminAction=quickConfig","")}if(U!=window.location.href){window.location.href=U}else{window.location.reload(true)}})}},N=function(Q,S){var R=function(T){this.blur();T.stopPropagation();return false};if(Q){G("#main-navbar .main-nav a, #main-navbar .secondary-nav .sb-menu a").removeClass("selected").closest("li").removeClass("h-disabled");G("#main-navbar .secondary-nav li").removeClass("h-disabled");S.removeClass("selected").closest("li").removeClass("h-disabled");G(document).off("focus click mousedown","#main-navbar .h-disabled a",R)}else{G("#main-navbar .main-nav a, #main-navbar .secondary-nav .sb-menu a").removeClass("selected").closest("li").addClass("h-disabled");G("#main-navbar .secondary-nav li").addClass("h-disabled");S.addClass("selected").closest("li").removeClass("h-disabled");G(document).on("focus click mousedown","#main-navbar .h-disabled a",R)}},F=function(Q){if(G(Q.target).is(".js-site-config__upload_file")){G(".js-site-config__fileForm").removeClass("h-hide");G(".js-site-config__urlForm").addClass("h-hide");G(".js-site-config__upload_url").attr("checked",false)}else{G(".js-site-config__urlForm").removeClass("h-hide");G(".js-site-config__fileForm").addClass("h-hide");G(".js-site-config__upload_file").attr("checked",false)}},J=function(){G("#main-navbar .sb-menu:not(.h-disabled) .js-config-site").trigger("click")},P=function(){G(".js-config-site-panel").fileupload({dropZone:null,dataType:"json",add:function(R,Q){G("body").css("cursor","wait");G(".js-config-site-panel").find(".js-upload-progress:first").removeClass("h-hide");Q.submit()},done:function(R,Q){if(Q.result&&Q.result.filedataid){G("#header .site-logo img").attr({src:Q.result.imageUrl,"data-fileid":Q.result.filedataid,"data-styleselection":G("#edit-site-logo-dialog .styleselection:checked").val()})}else{openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("error_uploading_logo"),iconType:"error"})}},fail:function(R,Q){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("error_uploading_logo"),iconType:"error"})},always:function(R,Q){G("body").css("cursor","auto");G(".js-config-site-panel").find(".js-upload-progress:first").addClass("h-hide")}})},D=function(){var Q=G.trim(G(".js-site-config__url_form .js-site-config__url").val());if(Q){G("body").css("cursor","wait");G(this).closest(".dialog-content").find(".js-upload-progress:last").removeClass("h-hide");vBulletin.AJAX({call:"/uploader/upload-logo-url",data:({urlupload:Q}),complete:function(){console.log("uploader/url complete.");G("body").css("cursor","auto");G(".js-config-site-panel .js-upload-progress:last").addClass("h-hide")},success:function(R){console.log("saving site logo successful! result:"+R);G("#header .site-logo img").attr({src:R.imageUrl,"data-fileid":R.filedataid,"data-styleselection":G("#edit-site-logo-dialog .styleselection:checked").val()})},error:function(){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("error_processing_site_logo"),iconType:"error",onAfterClose:function(){G(".js-site-config__url").focus()}})}})}else{G("#edit-site-logo-dialog #imageUrl").focus()}},A=function(S){var T=G(S.target),R=T.data("nodeid");if(typeof (R)=="undefined"){G(S.currentTarget).hide()}else{var Q=T.data("postcount");vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/search/getInitialResults",data:{search_json:{channel:R,type:"vBForum_Channel"},perpage:100},title_phrase:"error",error_phrase:"error_deleting_forum",success:function(U){if(U&&U.totalRecords){subforums=U.totalRecords}else{subforums=0}openConfirmDialog({title:vBulletin.phrase.get("confirm_delete"),message:vBulletin.phrase.get("confirm_delete_channel_has_x_posts_y_forums",Q,subforums),iconType:"warning",onClickYes:function(){G("body").css("cursor","wait");vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/content_channel/delete",data:{nodeid:R},title_phrase:"error",error_phrase:"error_deleting_forum",success:function(V){G("body").css("cursor","auto");G(S.target).closest(".js-site-config__forum-record").html(vBulletin.phrase.get("channel_deleted_confirmation")).addClass("h-margin-bottom-xxl")}})},})}})}},O=function(){var Q=G(".js-site-config__more-forums-sample").find(".js-site-config__forum-record").clone();G(".js-site-config__forum-div").append(Q);G(".js-site-config__forum-div .autogrow").last().elastic().off("blur.elastic");G(".js-site-config__forum-div .b-config-site__forum-desc").last().val("").focus().blur()},B=function(){G(".js-site-config__upload_file").trigger("click")},C=function(V){var S=G(V.target),Q=S.val(),U=S.data("nodeid");if(Q==""){return }if(U){vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/content_channel/update",title_phrase:"error",error_phrase:"error_updating_forum",data:{nodeid:U,data:{title:Q}},success:function(W){console.log("update channels successful:"+W)}})}else{var T={title:Q},R=S.closest(".js-site-config__forum-record").find(".b-config-site__forum-desc");if(R.length>0){T.description=R.val()}vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/createcontent/channel",title_phrase:"error",error_phrase:"error_updating_forum",data:T,success:function(W){console.log("add channels successful:"+W);S.data("nodeid",W.nodeid);if(W.candelete){S.closest(".js-site-config__forum-record").find(".b-config-site__delete-link").removeClass("h-hide h-hide-imp").data("nodeid",W.nodeid)}}})}},M=function(S){var Q=G(S.target),R=Q.closest(".js-site-config__forum-record").find(".b-config-site__forum-title");if(!R.data("nodeid")){return }vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/content_channel/update",title_phrase:"error",error_phrase:"error_updating_forum",data:{nodeid:R.data("nodeid"),data:{description:Q.val()}},success:function(T){console.log("update channel successful:"+T)}})},I=function(){var S=G(".b-config-site__email-list").val(),R=G(".b-config-site__email-title").val(),Q=G(".b-config-site__email-content").val();if(S.length<4||Q.length<4){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("enter_message_and_recipients"),iconType:"error"})}else{vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/mail/send",title_phrase:"error",error_phrase:"error_sending_email",data:{to:S,subject:R,message:Q},success:function(T){if(T.success){openAlertDialog({title:vBulletin.phrase.get("emails_sent_successfully"),message:vBulletin.phrase.get("emails_sent_successfully")})}else{if(T.message){openAlertDialog({message:T.message})}}console.log("messages sent successfully")}})}},L=function(){if(E){return }P();G(document).off("click",".js-site-config__upload_file, .js-site-config__upload_url").on("click",".js-site-config__upload_file, .js-site-config__upload_url",F);G(document).off("click",".js-site-config__btn-url").on("click",".js-site-config__btn-url",D);G(document).off("click",".b-config-site__delete-link").on("click",".b-config-site__delete-link",A);G(document).off("click",".js-site-config__more-forums").on("click",".js-site-config__more-forums",O);G(document).off("change",".b-config-site__forum-title").on("change",".b-config-site__forum-title",C);G(document).off("change",".b-config-site__forum-desc").on("change",".b-config-site__forum-desc",M);G(document).off("click",".js-site-config__send-btn").on("click",".js-site-config__send-btn",I);G(document).off("click",".js-site-config__close-btn").on("click",".js-site-config__close-btn",J);G(".js-config-site-panel .autogrow").each(function(){G(this).elastic().off("blur.elastic")});G(".js-site-config__more-forums").click();if(G(".js-config-site-panel .js-theme-selector__container").length==1){window.vBulletin.sitebuilder.initThemesInQuickConfig()}E=true};G(function(){G(document).off("click","#main-navbar .sb-menu:not(.h-disabled) .js-config-site").on("click","#main-navbar .sb-menu:not(.h-disabled) .js-config-site",H);if(typeof (pageData.adminAction)!="undefined"&&pageData.adminAction=="quickConfig"){G(".js-config-site").click()}})})(jQuery);