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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["add_moderator_gforumdisplay","confirm_delete_group_channel","delete_a_group_channel","delete_group","edit_group_icon","error_adding_moderator","error_deleting_group","group_icon","group_title_exists","groups","manage_subscribers","transfer_group_ownership","unexpected_error","kilobytes"]);var sgIconUploadDlg=false;var sgIconEditDlg=false;(function(A){var B=[".blogadmin-widget",".summary-widget"];if(!vBulletin.pageHasSelectors(B)){return false}A(document).ready(function(){A(".btnGroupReset").off("click").on("click",function(J){if(A(".groupIcon .groupIconUrl").val()){A(".groupIcon .groupIconImg").attr("src",A(".groupIcon .groupIconUrl").val());A(".groupIcon .groupFileDataId").val(A(".groupIcon .initFiledataid").val())}else{A(".groupIcon .groupIconImg").addClass("h-hide");A(".groupIcon .groupFileDataId").val("")}return true});var G=new vBulletin_Autocomplete(A(".moderator_input"),{apiClass:"user",maxItems:1,containerClass:"group-moderator-autocomplete"});A(document).off("click",".add_moderator").on("click",".add_moderator",function(J){J.stopPropagation();$moderatorForm=A(this).closest("form");$channelid=$moderatorForm.find(':input[name="nodeid"]').val();$recipient=G.getElements();$recipient=$recipient[0]["value"];A("body").css("cursor","wait");A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/node/requestChannel",type:"POST",data:{channelid:$channelid,recipientname:$recipient,requestType:"sg_moderator_to"},dataType:"json",success:function(K){if(K&&!K.errors){location.reload()}else{if(A.isArray(K.errors)&&K.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("add_moderator_gforumdisplay"),message:vBulletin.phrase.get("error_x",K.errors[0][0]),iconType:"error"})}}},complete:function(){A("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("add_moderator_gforumdisplay"),message:vBulletin.phrase.get("error_adding_moderator"),iconType:"error"})}})});A(document).off("click",".btnRemoveContributor").on("click",".btnRemoveContributor",function(J){J.stopPropagation();$moderatorForm=A(this).closest("form");$channelid=$moderatorForm.find(':input[name="nodeid"]').val();$userid=A(this).attr("userid");$usergroupid=A(this).attr("usergroupid");A("body").css("cursor","wait");A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/blog/removeChannelModerator",type:"POST",data:{userId:$userid,channelId:$channelid},dataType:"json",success:function(K){if(K&&!K.errors){location.reload()}else{if(A.isArray(K.errors)&&K.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("add_moderator_gforumdisplay"),message:vBulletin.phrase.get("error_x",K.errors[0][0]),iconType:"error"})}}},complete:function(){A("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("add_moderator_gforumdisplay"),message:vBulletin.phrase.get("error_adding_folder"),iconType:"error"})}})});A(document).off("click",".btnCancelTransfer").on("click",".btnCancelTransfer",function(N){N.stopPropagation();var M=A(this),K=M.closest("form"),L=K.find(':input[name="nodeid"]').val(),J=M.attr("userid");A("body").css("cursor","wait");vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/blog/cancelChannelTransfer",error_phrase:"error_transfer_ownership",data:{userId:J,channelId:L},success:function(O){location.reload()},complete:function(){A("body").css("cursor","default")}})});A(document).off("click",".btnTransferOwnership").on("click",".btnTransferOwnership",function(M){var K=A(this).closest("form");var J=K.find(':input[name="nodeid"]').val();var L=openConfirmDialog({title:vBulletin.phrase.get("transfer_group_ownership"),message:"Loading...",width:500,dialogClass:"transfer-ownership-dialog loading",buttonLabel:{yesLabel:vBulletin.phrase.get("send_request"),noLabel:vBulletin.phrase.get("cancel")},onClickYes:function(){if(A(".transfer-ownership-dialog .transfer_owner_select:visible").length>0){$user="";$userid=A(".transfer-ownership-dialog .transfer_owner_select").val()}else{$user=A('.transfer-ownership-dialog :input[name="transfer_owner_autocomplete"]').val();$userid=0}if($user.length==0&&($userid.length==0||$userid==0)){return false}A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/node/requestChannel",type:"POST",data:{channelid:J,recipient:$userid,recipientname:$user,requestType:"owner_to"},dataType:"json",success:function(N){if(N&&!N.errors){location.reload()}else{if(A.isArray(N.errors)&&N.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("transfer_group_ownership"),message:vBulletin.phrase.get("error_x",N.errors[0][0]),iconType:"error"})}}},complete:function(){A("body").css("cursor","default")},error:function(P,O,N){openAlertDialog({title:vBulletin.phrase.get("transfer_group_ownership"),message:vBulletin.phrase.get("error_transfer_ownership",N),iconType:"error"})}})}});A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/render/sgadmin_transferownership",data:({nodeid:J}),type:"POST",dataType:"json",success:function(N){console.log("/ajax/render/sgadmin_transferownership");if(N&&!N.errors){A(".transfer-ownership-dialog").removeClass("loading");A(".dialog-content .message",L).html(N).find("[placeholder]").placeholder();L.dialog("option","position",{of:window});transferOwnerAutocomplete=new vBulletin_Autocomplete(A(".transfer_owner_autocomplete"),{apiClass:"user",maxItems:1});A(".transfer_ownership_tabs").tabs()}else{console.log("/ajax/render/sgadmin_transferownership successful, but response was not valid");openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("invalid_server_response_please_try_again"),iconType:"error",onAfterClose:function(){L.dialog("close")}})}},error:function(P,O,N){console.log("/ajax/render/sgadmin_transferownership failed, error: "+N);openAlertDialog({title:vBulletin.phrase.get("move_posts"),message:"Error loading move form. (Error code {0})<br /><br />Please try again.".format(P.status),iconType:"error",onAfterClose:function(){L.dialog("close")}})}})});var I=new vBulletin_Autocomplete(A(".moderator_members_input"),{apiClass:"user",containerClass:"group-moderator-autocomplete"});A(".groupadmin-widget .groupAdminRight .manage_moderators_row .moderators_right #select-all").off("change").on("change",function(){var L=A(this);var K=L.is(":checked");var J=L.closest(".moderators_right").find(".subscribers-list-container");if(K){J.find(":input[type=checkbox]").attr("checked",true)}else{J.find(":input[type=checkbox]").attr("checked",false)}});A(".groupRemoveSubscriber").off("click").on("click",removeSubscriber);A(".groupRemoveMember").off("click").on("click",removeMember);A(".groupAdminSubscriberPaging .right-arrow, .groupAdminSubscriberPaging .left-arrow").off("click").on("click",subscriberChangePage);A(".groupAdminSubscriberPaging .right-arrow ").removeClass("h-disabled");A(".groupAdminSubscriberPaging .pagenav .textbox").off("change").on("change",subscriberChangePage);A("#btnGroupDelete").off("click").on("click",function(){var J=A(this).closest("form");var K=J.find(':input[name="nodeid"]').val();openConfirmDialog({title:vBulletin.phrase.get("delete_a_group_channel"),message:vBulletin.phrase.get("confirm_delete_group_channel"),iconType:"warning",onClickYes:function(){A.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/content_channel/delete",type:"POST",data:{nodeid:K},dataType:"json",success:function(L){if(L&&!L.errors){window.location.href=pageData.baseurl+"/social-groups";return false}else{if(A.isArray(L.errors)&&L.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("delete_group"),message:vBulletin.phrase.get("error_x",L.errors[0][0]),iconType:"error"})}}},complete:function(){A("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("delete_group"),message:vBulletin.phrase.get("error_deleting_group"),iconType:"error"})}});return false},onClickNo:function(){}});return false});(function(){try{var J=A("#upload-button-placeholder");var K=A(".groupicon-upload-form");J.height(K.height());K.css({position:"absolute"}).offset(J.offset()).removeClass("h-hide")}catch(L){}});var C=A.cookie(pageData.cookie_prefix+"group_title");if(C!=null){A('.groupAdminForm :input[name="title"]').val(C.replace(/\+/g," "));A.cookie(pageData.cookie_prefix+"group_title",null,{path:pageData.cookie_path,domain:pageData.cookie_domain})}var H=A.cookie(pageData.cookie_prefix+"group_description");if(H!=null){A('.groupAdminForm :input[name="description"]').val(H.replace(/\+/g," "));A.cookie(pageData.cookie_prefix+"group_description",null,{path:pageData.cookie_path,domain:pageData.cookie_domain})}var E=A.cookie(pageData.cookie_prefix+"groupadmin_error");if(E!=null){openAlertDialog({title:vBulletin.phrase.get("groups"),message:vBulletin.phrase.get(E),iconType:"error"});A.cookie(pageData.cookie_prefix+"groupadmin_error",null,{path:pageData.cookie_path,domain:pageData.cookie_domain})}A(".editGroupIcon").off("click").on("click",function(J){if(!sgIconEditDlg){sgIconEditDlg=A(J.target).closest(".groupSummaryContainer").find(".sgIconUploader").dialog({width:600,autoOpen:false,modal:true,title:vBulletin.phrase.get("edit_group_icon"),dialogClass:"dialog-container"})}A(sgIconEditDlg).dialog("open");vBulletin.Responsive.Modal.init();return false});A(".sgAddIcon").off("click").on("click",function(J){if(!sgIconUploadDlg){sgIconUploadDlg=A(J.target).closest(".blogAdminRight").find(".sgIconUploader").dialog({width:600,autoOpen:false,modal:true,title:vBulletin.phrase.get("edit_group_icon"),dialogClass:"dialog-container"})}A(sgIconUploadDlg).dialog("open");vBulletin.Responsive.Modal.init();return false});A(".btnSGIconCancel").off("click").on("click",function(J){A(J.target).closest(".sgIconUploader").dialog("close")});A(".btnSGIconUrlSubmit").off("click").on("click",uploadIconFromUrl);A(".sgRadioIconFile").off("click").on("click",function(J){A(J.target).closest(".sgIconUploader ").find(".sgIconFileChooser").removeClass("h-hide");A(J.target).closest(".sgIconUploader ").find(".sgIconUrlInput ").addClass("h-hide")});A(".sgRadioIconUrl").off("click").on("click",function(J){A(J.target).closest(".sgIconUploader ").find(".sgIconFileChooser").addClass("h-hide");A(J.target).closest(".sgIconUploader ").find(".sgIconUrlInput ").removeClass("h-hide")});var F=A(".groupIconImg");var D=vBulletin.getAjaxBaseurl()+"/uploader/"+(F.length>0?"uploadSGIcon":"upload-file");A(".js-sg-admin__upload-icon").fileupload({formData:function(J){return[{name:"nodeid",value:J.data("nodeid")},{name:"securitytoken",value:pageData.securitytoken}]},dataType:"json",url:D,add:function(L,K){var J=/(gif|jpg|jpeg|jpe|png)$/i;if(J.test(K.files[0].type)){A(".sgIconUploader .js-upload-progress").removeClass("h-hide");K.submit()}else{openAlertDialog({title:vBulletin.phrase.get("upload"),message:vBulletin.phrase.get("invalid_image_allowed_filetypes_are"),iconType:"error"})}},done:function(K,J){if(J){if(J.result.errors){if(typeof (J.result.errors[0])=="undefined"){openAlertDialog({title:vBulletin.phrase.get("group_icon"),message:vBulletin.phrase.get(J.result.errors),iconType:"error"})}else{openAlertDialog({title:vBulletin.phrase.get("group_icon"),message:vBulletin.phrase.get(J.result.errors[0]),iconType:"error"})}}else{if(J.result.imageUrl){if(F.length>0){A(".groupIconImg").attr("src",J.result.thumbUrl);A(sgIconEditDlg).dialog("close")}else{A(".sgIconPreview").html('<img src="'+J.result.thumbUrl+'" alt="">');A(".sGIconfiledataid").val(J.result.filedataid);A(sgIconUploadDlg).dialog("close")}}else{openAlertDialog({title:vBulletin.phrase.get("group_icon"),message:vBulletin.phrase.get("unable_to_upload_file"),iconType:"error"})}}}else{openAlertDialog({title:vBulletin.phrase.get("group_icon"),message:vBulletin.phrase.get("invalid_server_response_please_try_again"),iconType:"error"})}},fail:function(M,K){var L=vBulletin.phrase.get("error_uploading_image");var J="error";if(K&&K.files.length>0){switch(K.files[0].error){case"acceptFileTypes":L=vBulletin.phrase.get("invalid_image_allowed_filetypes_are");J="warning";break}}openAlertDialog({title:vBulletin.phrase.get("upload"),message:L,iconType:J,onAfterClose:function(){$editProfilePhotoDlg.find(".fileText").val("");$editProfilePhotoDlg.find(".browse-option").focus()}})},always:function(){A(".sgIconUploader .js-upload-progress").addClass("h-hide")}});A(".sgAdminForm").submit(checkSgContentValid)})})(jQuery);removeMember=function(B){var A=$(B.target).closest(".manage_moderators_row");userid=$(B.target).attr("data-userid");groupid=$(B.target).attr("data-groupid");nodeid=$(B.target).attr("data-nodeid");if(userid&&(userid>0)&&groupid&&(groupid>0)&&nodeid&&(nodeid>0)){$.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/blog/removeChannelMember",type:"POST",data:{userId:userid,channelId:nodeid},dataType:"json",success:function(C){if(C&&!C.errors){A.remove()}else{if($.isArray(C.errors)&&C.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("manage_subscribers"),message:vBulletin.phrase.get("error_x",C.errors[0][0]),iconType:"error"})}}},complete:function(){$("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("manage_subscribers"),message:vBulletin.phrase.get("error_adding_folder"),iconType:"error"})}})}};removeSubscriber=function(B){var A=$(B.target).closest(".manage_moderators_row");userid=$(B.target).attr("data-userid");nodeid=$(B.target).attr("data-nodeid");if(userid&&(userid>0)&&nodeid&&(nodeid>0)){$.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/follow/delete",type:"POST",data:{follow_item:nodeid,type:"follow_channel",userid:userid},dataType:"json",success:function(C){if(C&&!C.errors){A.remove()}else{if($.isArray(C.errors)&&C.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("manage_subscribers"),message:vBulletin.phrase.get("error_x",C.errors[0][0]),iconType:"error"})}}},complete:function(){$("body").css("cursor","default")},error:function(){openAlertDialog({title:vBulletin.phrase.get("manage_subscribers"),message:vBulletin.phrase.get("error_adding_folder"),iconType:"error"})}})}};var pageno=1;subscriberChangePage=function(D){pageCount=parseInt($(D.target).closest(".groupAdminSubscriberPaging").attr("data-pagecount"));if($(D.target).hasClass("left-arrow")||$(D.target).parent().hasClass("left-arrow")){var B=pageno-1;if(B<1){$(D.target).addClass("h-disabled");return }}else{if($(D.target).hasClass("right-arrow")||$(D.target).parent().hasClass("right-arrow")){var B=pageno+1;if(B>pageCount){$(D.target).addClass("h-disabled");return }}else{if($(D.target).hasClass("textbox")){var B=parseInt($(D.target).val());if(isNaN(B)||(B>pageCount)||(B<1)){$(D.target).val(this.pageno);return }}else{return }}}var A=vBulletin.getAjaxBaseurl()+"/ajax/render/sgadmin_subscriberlist";var C={pageno:B,nodeid:$(D.target).closest(".groupAdminEditPage").attr("data-nodeid")};$.ajax({url:A,data:C,type:"POST",dataType:"json",success:function(E){$(D.target).closest(".groupAdminEditPage").find(".subscriberList").html(E);if(B>=pageCount){$(D.target).closest(".groupAdminEditPage").find(".right-arrow").addClass("h-disabled")}else{$(D.target).closest(".groupAdminEditPage").find(".right-arrow").removeClass("h-disabled")}if(B<=1){$(D.target).closest(".groupAdminEditPage").find(".left-arrow").addClass("h-disabled")}else{$(D.target).closest(".groupAdminEditPage").find(".left-arrow").removeClass("h-disabled")}$(D.target).closest(".groupAdminEditPage").find(".pagenav .textbox").val(B);pageno=B;$(".groupRemoveSubscriber").off("click").on("click",removeSubscriber)}})};uploadIconFromUrl=function(C){var B=$(".groupIconImg");var A=vBulletin.getAjaxBaseurl()+"/uploader/"+(B.length>0?"uploadSGIcon":"url");$(".sgIconUploader .js-upload-progress").removeClass("h-hide");vBulletin.AJAX({url:A,error_phrase:"upload_errors",data:{nodeid:$(C.target).closest(".sgicon-upload-form").data("nodeid"),url:$(C.target).parent(".sgIconUrlInput").find("#imgUrl").val(),urlupload:$(C.target).parent(".sgIconUrlInput").find("#imgUrl").val()},success:function(D){if(B.length>0){$(".groupIconImg").attr("src",D.imageUrl);$(sgIconEditDlg).dialog("close")}else{$(".sgIconPreview").html('<img src="'+D.imageUrl+'" alt="">');$(".sGIconfiledataid").val(D.filedataid);$(sgIconUploadDlg).dialog("close")}},complete:function(){$(".sgIconUploader .js-upload-progress").addClass("h-hide")}})};checkSgContentValid=function(C){category=$(C.target).find(".sgCategory");if(parseInt(category.val())<=0){openAlertDialog({title:vBulletin.phrase.get("groups"),message:vBulletin.phrase.get("please_select_category"),iconType:"error"});return false}title=$(C.target).find(".sGtitle");if(title.length&&title.val().length<4){openAlertDialog({title:vBulletin.phrase.get("groups"),message:vBulletin.phrase.get("please_enter_title"),iconType:"error"});return false}var B=$(C.target).find(".blogAdminNodeId").val();if(B>0){$.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/user/hasPermissions",data:{nodeid:B,group:"forumpermissions2",permission:"canconfigchannel",},type:"POST",dataType:"json",async:false,success:function(D){if(!D.errors){if(D===false){C.preventDefault();openAlertDialog({title:vBulletin.phrase.get("social_groups"),message:vBulletin.phrase.get("cannot_edit_group_info"),iconType:"warning"});return false}}else{openAlertDialog({title:vBulletin.phrase.get("social_groups"),message:vBulletin.phrase.get(D.errors[0][0]),iconType:"warning"});return false}}})}var A=$(C.target);if(A.find('input[name="securitytoken"]').length){A.find('input[name="securitytoken"]').val(pageData.securitytoken)}else{A.append('<input type="hidden" name="securitytoken" value="'+pageData.securitytoken+'" />')}};