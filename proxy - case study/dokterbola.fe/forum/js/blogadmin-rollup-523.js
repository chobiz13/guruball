// ***************************
// js.compressed/sb_blogadmin.js
// ***************************
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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["add_moderator_gforumdisplay","confirm_delete_blog_channel","delete_a_blog_channel","delete_blog","error_adding_moderator","error_deleting_blog","manage_subscribers","transfer_blog_ownership","unexpected_error","blogs","blog_title_exists","kilobytes"]);var blogIconEditDlg=false;(function(A){var B=[".blogadmin-widget",".summary-widget"];if(!vBulletin.pageHasSelectors(B)){return false}A(document).ready(function(){A(".blogadmin-widget select").selectBox();A(".btnBlogReset").off("click").on("click",function(K){if(A(".blogIcon .blogIconUrl").val()){A(".blogIcon .blogIconImg").attr("src",A(".blogIcon .blogIconUrl").val());A(".blogIcon .blogFileDataId").val(A(".blogIcon .initFiledataid").val())}else{A(".blogIcon .blogIconImg").addClass("h-hide");A(".blogIcon .blogFileDataId").val("")}var I=A(this).closest("form");var J=A('input[name="sidebarInfo"]',I).val().split(",");A.each(J,function(L,N){var O=N.split(":");var M=A('.sidebar-modules-container .widget[data-widget-instance-id="'+O[0]+'"]');if(O[1]=="hide"){M.addClass("hidden-widget");A(".module-controls .hide-control",M).attr("checked",true)}else{M.removeClass("hidden-widget");A(".module-controls .hide-control",M).attr("checked",false)}M.appendTo(".sidebar-modules-container")});return true});A(".blog-icon-controls .edit-blog-icon").off("click").on("click",function(I){I.preventDefault();A(".js-blog-admin__upload-icon").trigger("click")});A(".blog-icon-controls .remove-blog-icon").off("click").on("click",function(I){I.preventDefault();A(".blogIcon .blogIconImg").attr("src","").addClass("h-hide");A(".blogIcon .blogFileDataId").val("");A(".blogIcon .blog-icon-controls").addClass("h-hide");A(".js-blog-admin__upload-icon").closest(".b-button--upload").removeClass("h-hide-imp")});moderatorsAutocomplete=new vBulletin_Autocomplete(A(".moderator_input"),{apiClass:"user",maxItems:1,containerClass:"blog-moderator-autocomplete"});A(document).off("click",".add_moderator").on("click",".add_moderator",function(I){I.stopPropagation();$moderatorForm=A(this).closest("form");$channelid=$moderatorForm.find(':input[name="nodeid"]').val();$recipient=moderatorsAutocomplete.getElements();$recipient=$recipient[0]["value"];A("body").css("cursor","wait");A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/node/requestChannel",type:"POST",data:{channelid:$channelid,recipientname:$recipient,requestType:"moderator_to"},dataType:"json",success:function(J){if(J&&!J.errors){location.reload()}else{if(A.isArray(J.errors)&&J.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("add_moderator_gforumdisplay"),message:vBulletin.phrase.get("error_x",J.errors[0][0]),iconType:"error"})}}},complete:function(){A("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("add_moderator_gforumdisplay"),message:vBulletin.phrase.get("error_adding_moderator"),iconType:"error"})}})});A(document).off("click",".btnRemoveContributor").on("click",".btnRemoveContributor",function(I){I.stopPropagation();$moderatorForm=A(this).closest("form");$channelid=$moderatorForm.find(':input[name="nodeid"]').val();$userid=A(this).attr("userid");$usergroupid=A(this).attr("usergroupid");A("body").css("cursor","wait");A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/blog/removeChannelModerator",type:"POST",data:{userId:$userid,channelId:$channelid},dataType:"json",success:function(J){if(J&&!J.errors){location.reload()}else{if(A.isArray(J.errors)&&J.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("add_moderator_gforumdisplay"),message:vBulletin.phrase.get(J.errors[0][0]),iconType:"error"})}}},complete:function(){A("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("add_moderator_gforumdisplay"),message:vBulletin.phrase.get(response.errors[0][0]),iconType:"error"})}})});A(document).off("click",".btnCancelTransfer").on("click",".btnCancelTransfer",function(M){M.stopPropagation();var L=A(this),J=L.closest("form"),K=J.find(':input[name="nodeid"]').val(),I=L.attr("userid");A("body").css("cursor","wait");vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/blog/cancelChannelTransfer",error_phrase:"error_transfer_ownership",data:{userId:I,channelId:K},success:function(N){location.reload()},complete:function(){A("body").css("cursor","default")}})});A(document).off("click",".btnTransferOwnership").on("click",".btnTransferOwnership",function(L){var J=A(this).closest("form");var I=J.find(':input[name="nodeid"]').val();var K=openConfirmDialog({title:vBulletin.phrase.get("transfer_blog_ownership"),message:"Loading...",width:500,dialogClass:"transfer-ownership-dialog loading",buttonLabel:{yesLabel:vBulletin.phrase.get("send_request"),noLabel:vBulletin.phrase.get("cancel")},onClickYes:function(){if(A(".transfer-ownership-dialog .transfer_owner_select:visible").length>0){$user="";$userid=A(".transfer-ownership-dialog .transfer_owner_select").val()}else{$user=A('.transfer-ownership-dialog :input[name="transfer_owner_autocomplete"]').val();$userid=0}if($user.length==0&&($userid.length==0||$userid==0)){return false}A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/node/requestChannel",type:"POST",data:{channelid:I,recipient:$userid,recipientname:$user,requestType:"owner_to"},dataType:"json",success:function(M){if(M&&!M.errors){location.reload()}else{if(A.isArray(M.errors)&&M.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("transfer_blog_ownership"),message:vBulletin.phrase.get("error_x",vBulletin.phrase.get(M.errors[0])),iconType:"error"})}}},complete:function(){A("body").css("cursor","default")},error:function(O,N,M){openAlertDialog({title:vBulletin.phrase.get("transfer_blog_ownership"),message:vBulletin.phrase.get("error_transfer_ownership",M),iconType:"error"})}})}});A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/render/blogadmin_transferownership",data:({nodeid:I}),type:"POST",dataType:"json",success:function(M){console.log("/ajax/render/blogadmin_transferownership");if(M&&!M.errors){A(".transfer-ownership-dialog").removeClass("loading");A(".dialog-content .message",K).html(M).find("[placeholder]").placeholder();K.dialog("option","position",{of:window});transferOwnerAutocomplete=new vBulletin_Autocomplete(A(".transfer_owner_autocomplete"),{apiClass:"user",maxItems:1});A(".transfer_ownership_tabs").tabs()}else{console.log("/ajax/render/blogadmin_transferownership successful, but response was not valid");openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("invalid_server_response_please_try_again"),iconType:"error",onAfterClose:function(){K.dialog("close")}})}},error:function(O,N,M){console.log("/ajax/render/blogadmin_transferownership failed, error: "+M);openAlertDialog({title:vBulletin.phrase.get("move_posts"),message:"Error loading move form. (Error code {0})<br /><br />Please try again.".format(O.status),iconType:"error",onAfterClose:function(){K.dialog("close")}})}})});var H=new vBulletin_Autocomplete(A(".moderator_members_input"),{apiClass:"user",containerClass:"blog-moderator-autocomplete"});A(".blogadmin-widget .blogAdminRight .manage_moderators_row .moderators_right #select-all").off("change").on("change",function(){var K=A(this);var J=K.is(":checked");var I=K.closest(".moderators_right").find(".subscribers-list-container");if(J){I.find(":input[type=checkbox]").prop("checked",true)}else{I.find(":input[type=checkbox]").prop("checked",false)}});A(document).off("click",".blogRemoveSubscriber").on("click",".blogRemoveSubscriber",removeSubscriber);A(document).off("click",".blogAdminSubscriberPaging .right-arrow, .blogAdminSubscriberPaging .left-arrow").on("click",".blogAdminSubscriberPaging .right-arrow, .blogAdminSubscriberPaging .left-arrow",subscriberChangePage);A(".blogAdminSubscriberPaging .right-arrow ").removeClass("h-disabled");A(".blogAdminSubscriberPaging .pagenav .textbox").change(subscriberChangePage);A(document).off("click",".editBlogIcon").on("click",".editBlogIcon",function(I){A(".editBlogIconDlg").dialog()});A(document).off("click",".btnBlogIconClose").on("click",".editBlogIconDlg",function(I){A(".editBlogIconDlg").dialog("close")});A(document).off("click","#btnBlogDelete").on("click","#btnBlogDelete",function(K){var I=A(this).closest("form");var J=I.find(':input[name="nodeid"]').val();openConfirmDialog({title:vBulletin.phrase.get("delete_a_blog_channel"),message:vBulletin.phrase.get("confirm_delete_blog_channel"),iconType:"warning",onClickYes:function(){A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/content_channel/delete",type:"POST",data:{nodeid:J},dataType:"json",success:function(L){if(L&&!L.errors){window.location.href=pageData.baseurl+"/blogs";return false}else{if(A.isArray(L.errors)&&L.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("delete_blog"),message:vBulletin.phrase.get("error_x",L.errors[0][0]),iconType:"error"})}}},complete:function(){A("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("delete_blog"),message:vBulletin.phrase.get("error_deleting_blog"),iconType:"error"})}});return false},onClickNo:function(){}});return false});A(".js-blog-admin__upload-icon").fileupload({dropZone:null,dataType:"json",add:function(K,J){var I=/(gif|jpg|jpeg|jpe|png)$/i;if(I.test(J.files[0].type)){A(".blogIcon .js-upload-progress").removeClass("h-hide");J.form.attr("action",A(this).data("url"));J.submit()}else{openAlertDialog({title:vBulletin.phrase.get("upload"),message:vBulletin.phrase.get("invalid_image_allowed_filetypes_are"),iconType:"error"})}},done:function(J,I){if(I.result&&I.result.thumbUrl){A(".blogIcon .blogIconImg").attr("src",I.result.thumbUrl).removeClass("h-hide");A(".blogIcon .blogFileDataId").val(I.result.filedataid);A(".blogIcon .blog-icon-controls").removeClass("h-hide");A(".js-blog-admin__upload-icon").closest(".b-button--upload").addClass("h-hide-imp")}if(I.result&&I.result.errors){openAlertDialog({title:vBulletin.phrase.get("upload"),message:vBulletin.phrase.get(I.result.errors[0]),iconType:"error"})}},fail:function(L,K){var J=vBulletin.phrase.get("error_uploading_image");var I="error";if(K&&K.files.length>0){switch(K.files[0].error){case"acceptFileTypes":J=vBulletin.phrase.get("invalid_image_allowed_filetypes_are");I="warning";break}}openAlertDialog({title:vBulletin.phrase.get("upload"),message:J,iconType:I})},always:function(){A(".blogIcon .js-upload-progress").addClass("h-hide")}});A(".blogAdminForm button[type=submit]").off("click").on("click",function(){this.form.action=pageData.baseurl+"/create-content/blog"});var C=A.cookie(pageData.cookie_prefix+"blog_title");if(C!=null){A('.blogAdminForm :input[name="title"]').val(C.replace(/\+/g," "));A.cookie(pageData.cookie_prefix+"blog_title",null,{path:pageData.cookie_path,domain:pageData.cookie_domain})}var G=A.cookie(pageData.cookie_prefix+"blog_description");if(G!=null){A('.blogAdminForm :input[name="description"]').val(G.replace(/\+/g," "));A.cookie(pageData.cookie_prefix+"blog_description",null,{path:pageData.cookie_path,domain:pageData.cookie_domain})}var F=A.cookie(pageData.cookie_prefix+"blogadmin_error");if(F!=null){openAlertDialog({title:vBulletin.phrase.get("blogs"),message:vBulletin.phrase.get(F),iconType:"error"});A.cookie(pageData.cookie_prefix+"blogadmin_error",null,{path:pageData.cookie_path,domain:pageData.cookie_domain})}A(document).off("click",".editBlogIcon").on("click",".editBlogIcon",function(I){if(!blogIconEditDlg){blogIconEditDlg=A(I.target).closest(".blogSummaryContainer").find(".sgIconUploader").dialog({width:600,autoOpen:false})}A(blogIconEditDlg).dialog("open");return false});A(document).off("click",".btnSGIconCancel").on("click",".btnSGIconCancel",function(I){A(I.target).closest(".sgIconUploader").dialog("close")});A(document).off("click",".btnSGIconUrlSubmit").on("click",".btnSGIconUrlSubmit",uploadIconFromUrl);A(document).off("click",".sgRadioIconFile").on("click",".sgRadioIconFile",function(I){A(I.target).closest(".sgIconUploader ").find(".sgIconFileChooser").removeClass("h-hide");A(I.target).closest(".sgIconUploader ").find(".sgIconUrlInput ").addClass("h-hide")});A(document).off("click",".sgRadioIconUrl").on("click",".sgRadioIconUrl",function(I){A(I.target).closest(".sgIconUploader ").find(".sgIconFileChooser").addClass("h-hide");A(I.target).closest(".sgIconUploader ").find(".sgIconUrlInput ").removeClass("h-hide")});var E=A(".blogIconImg");var D=vBulletin.getAjaxBaseurl()+"/uploader/"+(E.length>0?"uploadSGIcon":"upload-file");A(".js-sg-admin__upload-icon").fileupload({formData:function(I){return[{name:"nodeid",value:I.data("nodeid")}]},url:D,add:function(K,J){var I=/(gif|jpg|jpeg|jpe|png)$/i;if(I.test(J.files[0].type)){A(".sgIconUploader .js-upload-progress").removeClass("h-hide");J.submit()}else{openAlertDialog({title:vBulletin.phrase.get("upload"),message:vBulletin.phrase.get("invalid_image_allowed_filetypes_are"),iconType:"error"})}},done:function(J,I){if(I){if(I.result.errors){if(typeof (I.result.errors[0])=="undefined"){openAlertDialog({title:vBulletin.phrase.get("blog_icon"),message:vBulletin.phrase.get(I.result.errors),iconType:"error"})}else{openAlertDialog({title:vBulletin.phrase.get("blog_icon"),message:vBulletin.phrase.get(I.result.errors[0]),iconType:"error"})}}else{if(I.result.imageUrl){if(E.length>0){A(".blogIconImg").attr("src",I.result.imageUrl);A(blogIconEditDlg).dialog("close")}else{A(".sgIconPreview").html('<img src="'+I.result.imageUrl+'" alt="">');A(".sGIconfiledataid").val(I.result.filedataid);A(sgIconUploadDlg).dialog("close")}}else{openAlertDialog({title:vBulletin.phrase.get("blog_icon"),message:vBulletin.phrase.get("unable_to_upload_file"),iconType:"error"})}}}else{openAlertDialog({title:vBulletin.phrase.get("blog_icon"),message:vBulletin.phrase.get("invalid_server_response_please_try_again"),iconType:"error"})}},fail:function(L,J){var K=vBulletin.phrase.get("error_uploading_image");var I="error";if(J&&J.files.length>0){switch(J.files[0].error){case"acceptFileTypes":K=vBulletin.phrase.get("invalid_image_allowed_filetypes_are");I="warning";break}}openAlertDialog({title:vBulletin.phrase.get("upload"),message:K,iconType:I,onAfterClose:function(){$editProfilePhotoDlg.find(".fileText").val("");$editProfilePhotoDlg.find(".browse-option").focus()}})},always:function(){A(".sgIconUploader .js-upload-progress").addClass("h-hide")}});A(".sidebar-modules-container").trigger("reset").sortable({revert:true,tolerance:"pointer",placeholder:"widget module-drag-highlight",handle:false,items:".widget",cursor:"move",axis:"y"}).disableSelection();A(".sidebar-modules-container .module-controls .hide-control").off("click").on("click",function(){A(this).closest(".widget")[this.checked?"addClass":"removeClass"]("hidden-widget")});A("form.sidebarForm").off("submit.sidebar").on("submit.sidebar",function(){var I=[];A(".sidebar-modules-container .widget").each(function(){I.push(A(this).attr("data-widget-instance-id")+":"+(A(".module-controls .hide-control",A(this)).prop("checked")?"hide":"show"))});A('input[name="sidebarInfo"]',(this)).val(I.join())})})})(jQuery);removeSubscriber=function(B){var A=$(B.target).closest(".manage_moderators_row");userid=$(B.target).attr("data-userid");groupid=$(B.target).attr("data-groupid");nodeid=$(B.target).attr("data-nodeid");if(userid&&(userid>0)&&groupid&&(groupid>0)&&nodeid&&(nodeid>0)){$.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/blog/removeChannelMember",type:"POST",data:{userId:userid,channelId:nodeid},dataType:"json",success:function(C){if(C&&!C.errors){A.remove()}else{if($.isArray(C.errors)&&C.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("manage_subscribers"),message:vBulletin.phrase.get(C.errors[0][0]),iconType:"error"})}}},complete:function(){$("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("manage_subscribers"),message:vBulletin.phrase.get(response.errors[0][0]),iconType:"error"})}})}};var pageno=1;subscriberChangePage=function(D){pageCount=parseInt($(D.target).closest(".blogAdminSubscriberPaging").attr("data-pagecount"));if($(D.target).hasClass("left-arrow")||$(D.target).parent().hasClass("left-arrow")){var B=pageno-1;if(B<1){$(D.target).addClass("h-disabled");return }}else{if($(D.target).hasClass("right-arrow")||$(D.target).parent().hasClass("right-arrow")){var B=pageno+1;if(B>pageCount){$(D.target).addClass("h-disabled");return }}else{if($(D.target).hasClass("textbox")){var B=parseInt($(D.target).val());if(isNaN(B)||(B>pageCount)||(B<1)){$(D.target).val(this.pageno);return }}else{return }}}var A=vBulletin.getAjaxBaseurl()+"/ajax/render/blogadmin_subscriberlist";var C={pageno:B,nodeid:$(D.target).closest(".blogAdminEditPage").attr("data-nodeid")};$.ajax({url:A,data:C,type:"POST",dataType:"json",success:function(E){$(D.target).closest(".blogAdminEditPage").find(".subscriberList").html(E);if(B>=pageCount){$(D.target).closest(".blogAdminEditPage").find(".right-arrow").addClass("h-disabled")}else{$(D.target).closest(".blogAdminEditPage").find(".right-arrow").removeClass("h-disabled")}if(B<=1){$(D.target).closest(".blogAdminEditPage").find(".left-arrow").addClass("h-disabled")}else{$(D.target).closest(".blogAdminEditPage").find(".left-arrow").removeClass("h-disabled")}$(D.target).closest(".blogAdminEditPage").find(".pagenav .textbox").val(B);pageno=B;$(".blogRemoveSubscriber").off("click").on("click",removeSubscriber)}})};uploadIconFromUrl=function(C){var B=$(".blogIconImg");var A=vBulletin.getAjaxBaseurl()+"/uploader/"+(B.length>0?"uploadSGIcon":"url");$(".sgIconUploader .js-upload-progress").removeClass("h-hide");vBulletin.AJAX({url:A,error_phrase:"upload_errors",data:{nodeid:$(C.target).closest(".sgicon-upload-form").data("nodeid"),url:$(C.target).parent(".sgIconUrlInput").find("#imgUrl").val()},success:function(D){if(B.length>0){$(".blogIconImg").attr("src",D.imageUrl);$(blogIconEditDlg).dialog("close")}else{$(".sgIconPreview").html('<img src="'+D.imageUrl+'" alt="">');$(".sGIconfiledataid").val(D.filedataid);$(blogIconEditDlg).dialog("close")}},complete:function(){$(".sgIconUploader .js-upload-progress").addClass("h-hide")}})};;

// ***************************
// js.compressed/blog_summary.js
// ***************************
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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["blog_subscribers_list","blog_subscribers","unable_to_contact_server_please_try_again"]);(function(C){var D=[".summary-widget",".blogadmin-widget"];if(!vBulletin.pageHasSelectors(D)){return false}var A=function(){C("#blogSubscribersSeeAll").off("click").on("click",function(E){B(C(this).attr("data-node-id"));E.stopPropagation();return false})};var B=function(G,F,E){if(typeof H=="undefined"){var H=C("#blogSubscribersAll").dialog({title:vBulletin.phrase.get("blog_subscribers_list"),autoOpen:false,modal:true,resizable:false,closeOnEscape:false,showCloseButton:false,width:450,dialogClass:"dialog-container dialog-box blog-subscribers-dialog"});vBulletin.pagination({context:H,onPageChanged:function(I,J){B(G,I)}});H.off("click",".blog-subscribers-close").on("click",".blog-subscribers-close",function(){H.dialog("close")});H.off("click",".action_button").on("click",".action_button",function(){if(!C(this).hasClass("subscribepending_button")){var I=C(this);var K=parseInt(I.attr("data-userid"),10);var J="";if(I.hasClass("subscribe_button")){J="add"}else{if(I.hasClass("unsubscribe_button")){J="delete"}}if((typeof (K)=="number")&&J){C.ajax({url:vBulletin.getAjaxBaseurl()+"/profile/follow-button",type:"POST",dataType:"json",data:{"do":J,follower:K,type:"follow_members"},success:function(M){if(M==1||M==2){if(J=="add"){var L=(M==1)?"subscribed":"subscribepending";var N=(M==1)?"following":"following_pending";I.removeClass("subscribe_button b-button b-button--special").addClass(L+"_button b-button b-button--secondary").text(vBulletin.phrase.get(N))}else{if(J=="delete"){I.removeClass("subscribed_button unsubscribe_button b-button b-button--special").addClass("subscribe_button b-button b-button--secondary").text(vBulletin.phrase.get("follow"))}}}else{if(M.errors){openAlertDialog({title:vBulletin.phrase.get("profile_guser"),message:vBulletin.phrase.get("error_x",M.errors[0][0]),iconType:"error"})}}},error:function(){openAlertDialog({title:vBulletin.phrase.get("profile_guser"),message:vBulletin.phrase.get("unable_to_contact_server_please_try_again"),iconType:"error"})}})}}})}if(!F){F=1}if(!E){E=10}C.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/render/subscribers_list",type:"POST",data:{nodeid:G,page:F,perpage:E},dataType:"json",success:function(I){if(I&&I.errors){openAlertDialog({title:vBulletin.phrase.get("blog_subscribers"),message:vBulletin.phrase.get(I.errors[0]),iconType:"error"})}else{C(".blog-subscribers-content",H).html(I)}},error:function(){openAlertDialog({title:vBulletin.phrase.get("blog_subscribers"),message:vBulletin.phrase.get("unable_to_contact_server_please_try_again"),iconType:"error"})}});H.dialog("open")};C(document).ready(function(){A();var E;C(".adminmenu_container .admin-mainmenu .mainmenu").off("mouseenter").on("mouseenter",function(F){var G=C(this);E=setTimeout(function(){G.removeClass("pos-top").addClass("hover").closest(".admin-mainmenu").find(".mainmenu .submenu").hide().css("top","");var L={},I=C(".submenu",G).show();if(!vBulletin.isScrolledIntoView(I,L)){var K=C(document).height()-C(window).height(),J=C(document).scrollTop(),H=Math.abs(L.bottom);if(H<=(K-J)){C("html,body").animate({scrollTop:"+="+(J+H)},"slow")}else{I.css("top",-(I.height()+1)).parent().addClass("pos-top");if(!vBulletin.isScrolledIntoView(I,L)){C("html,body").animate({scrollTop:"-="+Math.abs(L.top)},"slow")}}}},200)}).off("mouseleave").on("mouseleave",function(F){clearTimeout(E);C(".submenu",this).hide();C(this).removeClass("hover")})})})(jQuery);;

