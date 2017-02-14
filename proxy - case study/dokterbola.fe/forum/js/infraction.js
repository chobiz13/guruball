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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["error_adding_infraction","error_adding_warning","error_reversing_infraction","error_sending_private_message","give_infraction_ginfraction","infraction_added","infraction_reversed","please_check_the_box_to_reverse_infraction","please_specify_reason_to_reverse_infraction","received_infraction","received_warning","warning_added"]);vBulletin.infraction=vBulletin.infraction||{};(function(E){var D=E("#infractions-tab");vBulletin.infraction.infractUser=function(L,G){var M=E(this);if(M.data("ajaxstarted")){return false}var K=M.hasClass("js-infraction-received"),I,H,J;if(K){I="received_infraction_form";H="received_infraction";J="receive-infraction-dialog"}else{I="give_infraction_form";H="give_infraction_ginfraction";J="give-infraction-dialog"}M.data("ajaxstarted",true);vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/render/"+I,data:{userid:M.data("userid"),nodeid:M.closest(".js-post-controls").data("node-id")||M.data("nodeid"),userInfraction:M.data("userinfraction")},error_phrase:"error_x",complete:function(){M.data("ajaxstarted",null)},success:function(N){if(E("."+J).length){E("."+J).replaceWith(N)}else{E(N).appendTo(document.body).hide()}var O=E("."+J);O.dialog({title:vBulletin.phrase.get(H),autoOpen:true,modal:true,resizable:false,closeOnEscape:false,showCloseButton:false,width:O.hasClass("error-infraction-dialog")?500:(K?600:700),dialogClass:"dialog-container infraction-dialog-container dialog-box",close:function(){var P=E(".js-editor",this);if(P.length&&vBulletin.ckeditor.editorExists(P)){vBulletin.ckeditor.destroyEditor(P)}E(this).dialog("destroy").remove()},open:function(){var S=this;if(!K){var W=E(".infraction-send-pm",S);vBulletin.ajaxForm.apply(E(S),[{dataType:"json",error_phrase:"error_adding_infraction",success:function(a,Y,e,g){console.log("Infraction result:");console.dir(a);var f=(a.infractionNodeid&&!a.infractionNodeid.errors),c=(a.pmNodeid?1:0),d=(a.pmNodeid&&!a.pmNodeid.errors),b="",Z="warning";if(f&&(!c||d)){b=a.isWarning?"warning_added":"infraction_added";Z=""}else{if(f&&c&&!d){b="error_sending_private_message"}else{b=a.isWarning?"error_adding_warning":"error_adding_infraction"}}openAlertDialog({title:vBulletin.phrase.get("give_infraction_ginfraction"),message:vBulletin.phrase.get(b),iconType:Z});if(f){if(typeof G=="function"){G.apply(M.get(0),[a])}E(S).dialog("close")}},beforeSerialize:function(Y){},beforeSubmit:function(c,a,i){var h=E(".infraction-level-control option:selected",a).val();if(h=="0"){if(E.trim(E(".custom-reason",a).val())==""){U(E(".infraction-level",S));openAlertDialog({title:vBulletin.phrase.get("give_infraction_ginfraction"),message:vBulletin.phrase.get("please_specify_custom_reason"),iconType:"warning",onAfterClose:function(){E(".custom-reason",a).focus()}});return false}var g=Number(E(".custom-points",a).val());if(isNaN(g)||g<=0){U(E(".infraction-level",S));openAlertDialog({title:vBulletin.phrase.get("give_infraction_ginfraction"),message:vBulletin.phrase.get("please_specify_custom_points"),iconType:"warning",onAfterClose:function(){E(".custom-points",a).focus()}});return false}var e=E(".custom-period option:selected",a).val(),Y=Number(E(".custom-expires",a).val());if(e!="N"&&(isNaN(Y)||Y<=0)){U(E(".infraction-level",S));openAlertDialog({title:vBulletin.phrase.get("give_infraction_ginfraction"),message:vBulletin.phrase.get("please_specify_custom_expires"),iconType:"warning",onAfterClose:function(){E(".custom-expires",a).focus()}});return false}}var Z=E(".infraction-ban-reason .ban-reason",S);if(Z.is(":visible")&&!E.trim(Z.val())){U(E(".infraction-ban",S));E(".ban-reason-desc",S).removeClass("h-hide");Z.focus();E(".dialog-content",S).scrollTop(0).scrollTop(E(".infraction-ban-reason",S).position().top);return false}var b=Number(W.data("required"));if(b){var f=false,d=E(".js-editor",a);if(vBulletin.ckeditor.editorExists(d)){d=vBulletin.ckeditor.getEditor(d);if(!E.trim(d.getData())){f=true}}else{if(!E.trim(d.val())){f=true}}if(f){openAlertDialog({title:vBulletin.phrase.get("give_infraction_ginfraction"),message:vBulletin.phrase.get("please_specify_infraction_pm"),iconType:"warning",onAfterClose:function(){U(W);d.focus()}});return false}}}}]);var R=function(Y){if(Y){Q.removeClass("h-hide").find("input, select").prop("disabled",false).end().find(".selectBox").removeClass("selectBox-disabled")}else{Q.addClass("h-hide").find("input, select").prop("disabled",true).end().find(".selectBox").addClass("selectBox-disabled")}};E(".infraction-level-control",S).on("change",function(c,b,a){var f=E(this.options[this.selectedIndex]),a=a&&a.length==1?a:E(this).closest(".infraction-level").find(".infraction-warning-control input");if(!b){if(f.data("allow-warning")){a.prop("disabled",false).val(this.value).parent().removeClass("h-hide");R(false)}else{a.prop("disabled",true).parent().addClass("h-hide");if(this.value=="0"){R(true);E(".textbox",Q).first().focus()}}}var d=0,Y=0;if(!a.prop("checked")&&this.value!="0"){d=Number(f.data("points"))||0;Y=1}else{if(this.value=="0"){d=Number(E(".custom-infraction-info .custom-points",S).val())||0;Y=1}}var Z=E(".infraction-dashboard-stats").data();if(T(Number(Z.points)+d,Number(Z.infractions)+Y)){V(true)}else{V(false)}P()});E(".infraction-warning-control input",S).on("click",function(){(this.checked)?V(false):E(".infraction-level-control",S).trigger("change",[true,this])});E(".custom-points",S).on("change",function(){E(".infraction-level-control",S).trigger("change",[true])});var Q=E(".custom-infraction-info",S).removeClass("h-hide");E("select",S).selectBox();Q.addClass("h-hide");E(".js-content-entry-panel, .js-editor",W).data("callback",function(){P()});vBulletin.ckeditor.initEditorComponents(W,true);E(".toggle-button",S).on("click",function(c){var a=E(this),Y=a.closest(".blockrow-head"),b=Y.next(".blockrow-body");if(a.hasClass("expand")){b.show();Y.removeClass("collapsed");P()}else{b.hide();Y.addClass("collapsed");P()}a.toggleClass("collapse expand");var Z=a.attr("title");a.attr("title",a.data("toggle-title")).data("toggle-title",Z);return false});var U=function(Y){E(".toggle-button.expand",Y).trigger("click")};var T=function(Z,Y){if(Z==0&&Y==0){return false}var a=false;E(".infraction-ban-list tbody tr",S).each(function(b,c){var d=E(this).data();if((d&&Number(d.points)&&Z>=Number(d.points))||(d&&Number(d.infractions)&&Y>=Number(d.infractions))){a=true;return false}});return a};var V=function(Y){var Z=E(".infraction-ban-reason",S);if(Y){Z.removeClass("h-hide");U(E(".infraction-ban",S))}else{Z.addClass("h-hide")}};var X=E(".dialog-content",S),P=function(){if(!X[0]){X[0]=S}X[(X[0].scrollHeight>parseFloat(X.css("max-height")))?"addClass":"removeClass"]("has-scrollbar")};P();E(".infraction-level-control",S).trigger("change")}else{vBulletin.ajaxForm.apply(E(".infraction-reverse-form",S),[{dataType:"json",error_phrase:"error_reversing_infraction",success:function(Z,a,b,Y){openAlertDialog({title:vBulletin.phrase.get("reverse_this_infraction"),message:vBulletin.phrase.get("infraction_reversed")});if(typeof G=="function"){G.apply(M.get(0),[Z])}E(S).dialog("close")},beforeSerialize:function(Y){},beforeSubmit:function(a,Z,Y){if(!E(".infraction-nodeid",Z).is(":checked")){openAlertDialog({title:vBulletin.phrase.get("reverse_this_infraction"),message:vBulletin.phrase.get("please_check_the_box_to_reverse_infraction"),iconType:"warning",onAfterClose:function(){E(".infraction-nodeid",Z).focus()}});return false}else{if(!E.trim(E(".infraction-reason",Z).val())){openAlertDialog({title:vBulletin.phrase.get("reverse_this_infraction"),message:vBulletin.phrase.get("please_specify_reason_to_reverse_infraction"),iconType:"warning",onAfterClose:function(){E(".infraction-reason",Z).focus()}});return false}}return true}}]);E(".reverse-infraction",S).on("click",function(){E(".infraction-reverse-form",S).submit()})}E(".close-infraction",S).on("click",function(){E(S).dialog("close")});E(".ckeditor-bare-box.ckeditor-load-on-focus",S).on("focus",function(){vBulletin.ckeditor.initEditor(this.id,{complete:function(Y){P()},error:function(Y){E("#"+Y).prop("disabled",false).removeClass("ckeditor-load-on-focus")}})})}})}})};vBulletin.infraction.loadUserInfractions=function(G){E.post(vBulletin.getAjaxBaseurl()+"/ajax/render/user_infractions",{userid:G.userid,pagenum:G.pageNumber},function(H,M,L){G.container.html(H);if(E(".pagenav-form",D).length){var I=new vBulletin.pagination({context:D,tabParamAsQueryString:false,allowHistory:D.find(".conversation-toolbar-wrapper").data("allow-history")==1,onPageChanged:function(N,O){vBulletin.infraction.loadUserInfractions({container:D,userid:D.data("userid"),pageNumber:N,replaceState:true})}})}if(typeof G.callback=="function"){G.callback(H)}if(G.pushState||G.replaceState){var K=vBulletin.makePaginatedUrl(location.href,G.pageNumber);if(!A){B=D.find(".conversation-toolbar-wrapper").data("allow-history")=="1";A=new vBulletin.history.instance(B)}if(A.isEnabled()){var J={from:"infraction_filter",page:G.pageNumber,tab:D.data("url-path")?D.data("url-path"):"#"+D.attr("id")};A[G.pushState?"pushState":"setDefaultState"](J,document.title,K)}}},"json")};var A,B;vBulletin.infraction.setHistoryStateChange=function(){if(!A){B=D.find(".conversation-toolbar-wrapper").data("allow-history")=="1";A=new vBulletin.history.instance(B)}if(A.isEnabled()){A.setStateChange(function(K){var J=A.getState();if(J.data.from=="infraction_filter"){A.log(J.data,J.title,J.url);var G=D.closest(".ui-tabs"),I=G.find(".ui-tabs-nav > li").filter('li:has(a[href*="#{0}"])'.format(D.attr("id")));if(I.hasClass("ui-tabs-active")){vBulletin.infraction.loadUserInfractions({container:D,userid:D.data("userid"),pageNumber:J.data.page,pushState:false})}else{var H=I.index();vBulletin.selectTabByIndex.call(G,H)}}},"infraction_filter")}};vBulletin.infraction.markInfractions=function(){var G,H,I;E(".infractions-list .list-item").each(function(){I=E(this);H=I.data("nodeId");G=vBulletin.cookie.fetchBbarrayCookie("discussion_view",H);if(G){I.addClass("read")}})};E(document).off("click",".js-post-control__infraction").on("click",".js-post-control__infraction",function(G){vBulletin.infraction.infractUser.apply(this,[G,function(I){var K=E(this),H=K.find(".b-icon"),J=K.hasClass("js-infraction-received");H.removeClass("b-icon__tickets--neutral b-icon__tickets--warned b-icon__tickets--infracted");if(J){K.removeClass("js-infraction-received").attr("title",vBulletin.phrase.get("give_infraction_ginfraction"));H.addClass("b-icon__tickets--neutral")}else{K.addClass("js-infraction-received");if(I.isWarning){K.attr("title",vBulletin.phrase.get("received_warning"));H.addClass("b-icon__tickets--warned")}else{K.attr("title",vBulletin.phrase.get("received_infraction"));H.addClass("b-icon__tickets--infracted")}}}])});D.off("click",".infractionCtrl").on("click",".infractionCtrl",function(G){vBulletin.infraction.infractUser.apply(this,[G,function(H){vBulletin.infraction.loadUserInfractions({container:D,userid:E(this).data("userid"),pageNumber:1,pushState:Number(E('.pagenav-form input[name="page"]',D).val())!=1})}])});D.on("click",".view-infraction",function(G){vBulletin.infraction.infractUser.apply(this,[G,function(H){var I=Number(E('.pagenav-form input[name="page"]',D).val())||1;vBulletin.infraction.loadUserInfractions({container:D,userid:E(this).data("userid"),pageNumber:I,pushState:I!=1})}])});E(document).off("click","#privateMessageContainer .js-button-group .view-infraction").on("click","#privateMessageContainer .js-button-group .view-infraction",function(G){vBulletin.infraction.infractUser.apply(this,[G,function(H){}])});E(".infraction-delete").off("click").on("click",function(I){$button=E(this);var G,H=false;if($button.parents("#pmFloatingBarContent").hasClass("infractions-paginator")){G=getSelectedMessages()}else{G=[E("#privateMessageContainer .js-conversation-starter").data("nodeId")];H=true}if(G.length>0){openConfirmDialog({title:vBulletin.phrase.get("messages_header"),message:vBulletin.phrase.get("are_you_sure_delete_infractions"),iconType:"warning",onClickYes:function(){vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/node/deleteNodes",data:{nodeids:G,hard:0},success:function(J){if(H){location.href=E("#pmBtnBackToInfractions").prop("href")}else{location.reload()}}})}})}});E(".infraction-mark_as_read").off("click").on("click",function(J){var I=this;var H=getSelectedMessages();if(H.length>0){if(pageData.threadmarking=="0"||pageData.userid=="0"){for(var G in H){vBulletin.cookie.setBbarrayCookie("discussion_view",H[G],Math.round(new Date().getTime()/1000));E("[data-node-id={0}]".format(H[G]),".infractions-list").addClass("read").find(".privateMessageActionCheck").attr("checked",false)}}else{vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/node/markReadMultiple",data:{nodeids:H},success:function(K){E(K).each(function(){E("[data-node-id={0}]".format(this),".infractions-list").addClass("read").find(".privateMessageActionCheck").attr("checked",false);var L=E("[data-node-id={0}]".format(this),".infractions-list");L.addClass("read");L.find(".privateMessageActionCheck").attr("checked",false)})}})}}});E(".infraction-mark_as_unread").off("click").on("click",function(J){var I=this;var H=getSelectedMessages();console.log(H);if(H.length>0){if(pageData.threadmarking=="0"||pageData.userid=="0"){for(var G in H){vBulletin.cookie.unsetBbarrayCookie("discussion_view",H[G]);E("[data-node-id={0}]".format(H[G]),".infractions-list").removeClass("read").find(".privateMessageActionCheck").attr("checked",false)}}else{vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/node/markUnreadMultiple",data:{nodeids:H},success:function(K){E(K).each(function(){E("[data-node-id={0}]".format(this),".infractions-list").removeClass("read").find(".privateMessageActionCheck").attr("checked",false)})}})}}});E(document).ready(function(){if(E(".infractions-list").length>0){if(pageData.threadmarking=="0"||pageData.userid=="0"){vBulletin.infraction.markInfractions()}}if(E("#pmBtnBackToInfractions").length>0){var G=E("#privateMessageContainer .conversation-list .b-post--infraction").data("nodeId");if(pageData.threadmarking=="0"||pageData.userid=="0"){vBulletin.cookie.setBbarrayCookie("discussion_view",G,Math.round(new Date().getTime()/1000))}else{vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/node/markRead",data:{nodeid:G}})}}});E("#infractionFilters").trigger("reset").find(".filter-options input").off("click").on("click",function(G){F.apply(this)});E(document).off("click","#privatemessagePaging .infractionsPrev").on("click","#privatemessagePaging .infractionsPrev",function(H){H.preventDefault();var G=E(this).closest("#privatemessagePaging").find(':input[type=hidden][name="prev-page"]').val();C(E(this),G)});E(document).off("click","#privatemessagePaging .infractionsNext").on("click","#privatemessagePaging .infractionsNext",function(H){H.preventDefault();var G=E(this).closest("#privatemessagePaging").find(':input[type=hidden][name="next-page"]').val();C(E(this),G)});E(document).off("keypress","#privatemessagePaging .infractionsPageTo").on("keypress","#privatemessagePaging .infractionsPageTo",function(H){if(H.keyCode==13){H.preventDefault();var I=E(this);var G=parseInt(I.val(),10);if(isNaN(G)){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("please_enter_a_valid_page_number"),iconType:"error"});return false}C(I,G)}});function C(G,J,O){if(!G){G=E("#private-message-toolbar .infractions-paginator .infractionsPageTo");if(G.length==0){return false}}if(!O){O=0}var L=G.closest(".infractions-paginator");var N=E("#privateMessageContainer .main-pane .pending-posts-container");var I=L.find("#maxPageNum").filter(":input[type=hidden]").val();var K=parseInt(L.find(":input[type=hidden][name=pagenum]").val(),10);var M=parseInt(L.find(":input[type=hidden][name=per-page]").val(),10);J=parseInt(J,10);if(isNaN(J)||isNaN(I)||isNaN(K)){return false}if((J<1)||(J>I)){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("please_enter_a_valid_page_number"),iconType:"error"});return false}else{if((J==K)&&!O){return false}}var H={setCurrentPage:J,setPerPage:M,getPagingInfo:O,options:{}};E("#infractionFilters input:checked").each(function(){H.options[this.name]=this.value});if(H.options.time){H.options.time={from:H.options.time}}E.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/render/privatemessage_infraction_main",type:"POST",data:H,dataType:"json",success:function(R){N.html(R);if(E(".infractions-list").length>0){if(pageData.threadmarking=="0"||pageData.userid=="0"){vBulletin.infraction.markInfractions()}}var Q=0;var P=0;if(J<I){if(J>1){P=J+1;Q=J-1}else{P=J+1}}else{Q=J-1}L.find(":input[type=hidden][name=pagenum]").val(J);L.find(":input[type=hidden][name=next-page]").val(P);L.find(":input[type=hidden][name=prev-page]").val(Q);if(P){L.find(".infractionsNext").removeClass("h-disabled")}else{L.find(".infractionsNext").addClass("h-disabled")}if(Q){L.find(".infractionsPrev").removeClass("h-disabled")}else{L.find(".infractionsPrev").addClass("h-disabled")}L.find(".infractionsPageTo").val(J);if(O){var S=E("#privateMessageContainer .main-pane .pending-posts-container .pending-posts-pageinfo");L.find("#maxPageNum").filter(":input[type=hidden]").val(parseInt(S.find(".totalpages").val(),10));L.find(".infractionsPageCount").text(parseInt(S.find(".totalpages").val(),10));E(".folder-list .folder-item.pending-posts .count",$pmWidget).text(parseInt(S.find(".totalcount").val(),10))}},error:function(){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("unable_to_contact_server_please_try_again"),iconType:"error"})}})}var F=function(H){$paginateButton=E("#private-message-toolbar .infractions-paginator .infractionsPageTo");if($paginateButton.length){C($paginateButton,1,true);return }var G={};E(this).closest(".filter-options-list").find("input:checked").each(function(){G[this.name]=E(this).val()});if(G.time){G.time={from:G.time}}E(this).attr("checked","checked");G.page=1;G.perpage=pmPerPage;var I={options:G};E.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/render/privatemessage_infraction_main",type:"POST",dataType:"json",data:I,success:function(J){E("#privateMessageContainer .pending-posts-container").html(J);pmPageNum=1;if(E(".infractions-list").length>0){if(pageData.threadmarking=="0"||pageData.userid=="0"){vBulletin.infraction.markInfractions()}}}})};E(document).ready(function(){if(E("#infractionBtnReply").length){vBulletin.conversation.replyWithQuotes.getPostReplyButtonSelector=function(){return"#infractionBtnReply"}}})})(jQuery);