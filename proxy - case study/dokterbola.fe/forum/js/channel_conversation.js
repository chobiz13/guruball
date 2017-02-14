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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,[]);window.vBulletin.options=window.vBulletin.options||{};window.vBulletin.options.precache=window.vBulletin.options.precache||[];window.vBulletin.options.precache=$.merge(window.vBulletin.options.precache,["closed","this_topic_is_closed"]);(function(A){var C=[".forum-channel-content-widget",".blog-channel-content-widget",".sg-channel-content-widget",".search-results-widget .topic-list-container"];if(!vBulletin.pageHasSelectors(C)){return false}var B={};vBulletin.conversation.editTopicTitle=function(G){var F=A(G.target),D=F.closest(".js-topic-item"),E=D.find(".js-topic-wrapper"),I=D.data("node-id"),H=D.data("can-edit-title");if(!H){return }if(B[I]){return }B[I]="edit";if(E.find(".js-loading-icon").length==0){A('<img class="loading-icon js-loading-icon" src="'+pageData.baseurl+'/images/misc/progress.gif" />').appendTo(E)}E.find(".js-loading-icon").show();vBulletin.AJAX({call:"/create-content/loadtitleedit",data:{nodeid:I,isAjaxTemplateRender:true},success:function(J){E.find(".js-topic-title").hide();E.find(".js-loading-icon").hide();E.find(".js-topic-title").after(J);var L=E.find(".js-topic-title-edit");L.data("original-title",L.val());var K=30;E.find(".js-prefix, .js-post-icon, .js-topic-prefix, .js-go-to-first-unread").each(function(){K+=A(this).outerWidth(true)});L.outerWidth(L.outerWidth()-K);E.find(".js-topic-title-edit-form").on("submit",vBulletin.conversation.saveTopicTitle);L.on("blur",vBulletin.conversation.saveTopicTitle);L.select().focus()}})};vBulletin.conversation.saveTopicTitle=function(G){G.preventDefault();var F=A(G.target).closest(".js-topic-wrapper"),D=F.find(".js-topic-title-edit-form"),I=D.find(".js-node-id").val(),H=D.find(".js-topic-title-edit").val();if(B[I]=="save"){return }B[I]="save";var J=F.find(".js-topic-title-edit"),E=J.data("original-title");if(E==H){F.find(".js-loading-icon").remove();F.find(".js-topic-title-edit-form").remove();F.find(".js-topic-title").show();B[I]=false}else{F.find(".js-loading-icon").appendTo(D).show();vBulletin.AJAX({call:"/create-content/savetitleedit",data:{nodeid:I,title:H},success:function(K){F.find(".js-loading-icon").remove();F.find(".js-topic-title-edit-form").remove();F.find(".js-topic-title").html(K.title).show();B[I]=false}})}};vBulletin.conversation.openOrCloseTopic=function(K){K.stopPropagation();var L=A(this),J=L.closest(".js-topic-item"),H=J.find(".js-topic-wrapper"),D=J.data("node-id"),G=J.hasClass("closed");if(H.find(".js-loading-icon").length==0){A('<img class="loading-icon js-loading-icon" src="'+pageData.baseurl+'/images/misc/progress.gif" />').appendTo(H)}var F=H.find(".js-loading-icon");F.show();var E;var I={nodeid:D};if(G){E=vBulletin.getAjaxBaseurl()+"/ajax/api/node/openNode"}else{E=vBulletin.getAjaxBaseurl()+"/ajax/api/node/closeNode"}vBulletin.AJAX({url:E,data:{nodeid:D},error_phrase:"error_open_close_topic",api_error:function(N,M){console.log("AJAX "+E+" on node "+D+" failed! (vBulletin.conversation.OpenOrCloseTopic)");var O=(A("#debug-information").length==1);if(O){console.dir(N)}else{console.dir(N[0])}F.hide()},success:function(V){console.log("AJAX "+E+" on node "+D+" success! (vBulletin.conversation.OpenOrCloseTopic)");var N=G;if(N){J.removeClass("closed")}else{J.addClass("closed")}var M=L.attr("title")||"",W=vBulletin.phrase.get("this_topic_is_closed");subStrIndex=M.indexOf(W);if(N&&subStrIndex>=0){var O=(M.slice(0,subStrIndex)+M.slice(subStrIndex+W.length)).trim();L.attr("title",O)}else{if(!N&&subStrIndex<0){L.attr("title",W+" "+M)}}var U=H.find(".js-prefix"),R="",T=[],Q=vBulletin.phrase.get("closed");if(U.length){R=U.text().trim();if(R.charAt(R.length-1)==":"){R=R.substring(0,R.length-1)}if(R.length>0){T=R.split(", ")}if(!N){T.push(Q)}else{if(T.length>0){for(var P=T.length-1;P>=0;P--){if(T[P]==Q){T.splice(P,1)}}}}}else{U=A('<span class="prefix js-prefix"></span>');var S=H.find(".go-to-first-unread");if(S.length){U.insertAfter(S)}else{U.prependTo(H)}if(!N){T.push(Q)}}U.text(T.join(", ")+": ");if(T.length==0){U.hide()}else{U.show()}F.hide()}})};A(document).ready(function(){vBulletin.conversation=vBulletin.conversation||{};var G=A(".channel-content-widget .channel-conversation-list-wrapper"),J=A("#activity-stream-tab",G);if(J.length==0){J=A("#article-tab",G)}var N=A("#recommended-tab",G),O=A("#subscribed-tab",G),e=A("#topic-tab",G),K=A("#media-tab",G),P=A(".conversation-list",J),h=A(".conversation-list",N),a=A(".conversation-list",O),f=A(".conversation-list",e),X=A(".conversation-list",G),H,U,c,Y,L,W,b,D,R=true,F,M=G.find(".widget-tabs-nav .ui-tabs-nav > li"),T=M.filter(".ui-tabs-active"),S=T.index(),k,E=M.parent().data("allow-history")=="1",I=new vBulletin.history.instance(E);if(S==-1){S=0;T=M.first()}k=T.find("> a").attr("href");var Q=function(l){var m=(G.offset().top+(G.outerHeight()-parseFloat(G.css("border-bottom-width")))-l.height());return m};var i=function(l){l=l||k;return M.filter('li:has(a[href*="'+l+'"])').first().index()};M.removeClass("ui-state-disabled");var Z=function(r,p,q,t,l){var n=A(".conversation-toolbar-wrapper.scrolltofixed-floating",r),o=new vBulletin.scrollToFixed({element:n,limit:Q(n)}),m=null,s;if(q){s=new vBulletin.pagination({context:r,allowHistory:E,onPageChanged:function(u,v){m.updatePageNumber(u);if(!v){m.applyFilters(false,true,false,true)}}})}m=new vBulletin.conversation.filter({context:r,autoCheck:t?A(".toolbar-filter-overlay input[type=radio][value=conversations_on]",r).is(":checked"):undefined,scrollToTop:p,pagination:s,allowHistory:E,onContentLoad:l});return{"$bar":n,"$floating":o,pagination:s,filter:m}};var V=function(l){vBulletin.truncatePostContent(l);vBulletin.conversation.processPostContent(l)};var g=function(o,n,l){var m=n.filter;if(n.pagination){n.pagination.setOption("context",o)}m.setOption("context",o);if(typeof m.lastFilters!="undefined"&&A(".conversation-empty:not(.h-hide)",l).length>0){delete m.lastFilters}};var j=function(q,o,p,s,m,n,t){var r=Z(q,o,p,s,function(){r.$floating.updateLimit(Q(r.$bar));if(n){V(n)}if(t){t()}});var l=r.filter;if(m){if(n){V(n)}l.lastFilters={filters:l.getSelectedFilters(A(".toolbar-filter-overlay",q))}}return r};var d=function(o,l,r){var q=(k==r);if(!(r=="#activity-stream-tab"||r=="#article-tab")){if(c){Y.toggleNewConversations(false)}}if(r!="#topic-tab"){if(H){U.toggleNewConversations(false)}}if(r=="#activity-stream-tab"||r=="#article-tab"){if(!c){c=j(J,G,true,true,q,P,null);Y=c.filter}else{g(J,c,l)}Y.applyFilters(false,true)}else{if(r=="#subscribed-tab"){if(!L){L=j(O,G,true,true,q,a,null);W=L.filter}else{g(O,L,l)}W.applyFilters(false,false)}else{if(r=="#topic-tab"){if(!H){H=j(e,G,true,true,q,null,function(){vBulletin.markreadcheck()});U=H.filter}else{g(e,H,l)}U.applyFilters(false,true)}else{if(r=="#media-tab"){if(!b){b=A(".conversation-toolbar-wrapper.scrolltofixed-floating",K);D=new vBulletin.scrollToFixed({element:b,limit:Q(b)});var n=new vBulletin.history.instance(E);if(n.isEnabled()){var p=n.getState();if(!p||A.isEmptyObject(p.data)){var m={from:"filter",page:1,tab:r,filters:vBulletin.getSelectedFilters(A("form.toolbar-filter-overlay",l))};n.setDefaultState(m,document.title,location.href)}n.setStateChange(function(v,w,t){var u=n.getState();if(u.data.from=="filter"){n.log(u.data,u.title,u.url);if(r!=u.data.tab){vBulletin.selectTabByIndex.call(A(o).closest(".ui-tabs-nav"),o.index())}else{var s=A('.media-filter-overlay input[name="filter_time"][value="{0}"]'.format(u.data.filters.filter_time),l).prop("checked",true);if(s.hasClass("js-default-checked")&&s.prop("defaultChecked")){A(".filtered-by .x",l).trigger("click")}else{s.trigger("change",[true])}}}},"filter")}if(k==r){R=false}}else{R=true}if(R&&A("#profileMediaDetailContainer .album-detail",l).length==0){if(!F){F=vBulletin.media.calculatePhotosPerPage(vBulletin.media.TARGET_PHOTOS_PERPAGE)}loadGalleryById({nodeid:-2,channelid:pageData.channelid,pageno:1,dateFilter:A(".filter-options input[name=filter_time]:checked",l).val()||"time_lastweek",perpage:F},true,0,{complete:function(){A(".profile-toolbar .media-toolbar-filter",K).removeClass("h-hide");D.updateLimit(Q(b))}})}}}}}};vBulletin.tabify.call(G,{tabHistory:I,getTabIndexByHash:i,allowHistory:E,tabParamAsQueryString:true,hash:G.find(".js-module-top-anchor").attr("id"),tabOptions:{active:S,beforeActivate:function(n,o){if(Y){Y.hideFilterOverlay()}if(U){U.hideFilterOverlay()}if(W){W.hideFilterOverlay()}var m=G.find(".widget-tabs-panel .ui-tabs-panel:visible");var l=m.find(".list-item-body-wrapper.edit-post .edit-conversation-container");if(l.length>0){openAlertDialog({title:vBulletin.phrase.get("edit_conversation"),message:vBulletin.phrase.get("you_have_a_pending_edit_unsaved"),iconType:"warning",onAfterClose:function(){vBulletin.animateScrollTop(l.closest(".list-item").offset().top,{duration:"slow"})}});return false}},create:function(l,m){d(m.tab,m.panel,m.panel.selector)},activate:function(l,m){d(m.newTab,m.newPanel,m.newPanel.selector)}}});X.off("click",".list-item-poll .view-more-ctrl").on("click",".view-more-ctrl",function(m){var l=A(this).closest("form.poll");var n=l.find("ul.poll");A(this).addClass("h-hide");n.css("max-height","none").find("li.h-hide").slideDown(100,function(){l.find(".js-button-group").removeClass("h-hide").next(".view-less-ctrl").removeClass("h-hide");vBulletin.animateScrollTop(l.offset().top,{duration:"fast"})});return false});X.off("click",".list-item-poll .view-less-ctrl").on("click",".view-less-ctrl",function(m){var l=A(this).closest("form.poll");vBulletin.conversation.limitVisiblePollOptionsInAPost(l,3);l.find("ul.poll").css("max-height","").find("li.h-hide").slideUp(100);return false});X.off("click",".js-post__history").on("click",".js-post__history",vBulletin.conversation.showPostHistory);X.off("click",".js-post-control__ip-address").on("click",".js-post-control__ip-address",vBulletin.conversation.showIp);X.off("click",".js-post-control__edit").on("click",".js-post-control__edit",function(l){vBulletin.conversation.editPost.apply(this,[l,Y])});X.off("click",".js-post-control__vote").on("click",".js-post-control__vote",function(l){if(A(l.target).closest(".bubble-flyout").length==1){vBulletin.conversation.showWhoVoted.apply(l.target,[l])}else{vBulletin.conversation.votePost.apply(this,[l])}return false});X.off("click",".js-post-control__flag").on("click",".js-post-control__flag",vBulletin.conversation.flagPost);X.off("click",".js-post-control__comment").on("click",".js-post-control__comment",vBulletin.conversation.toggleCommentBox);X.off("click",".js-comment-entry__post").on("click",".js-comment-entry__post",function(l){vBulletin.conversation.postComment.apply(this,[l,function(){Y.updatePageNumber(1).applyFilters(false,true)}])});vBulletin.conversation.bindEditFormEventHandlers("all");A(document).off("dblclick",".js-cell-topic").on("dblclick",".js-cell-topic",vBulletin.conversation.editTopicTitle);A(document).off("dblclick",".js-open-close-topic").on("dblclick",".js-open-close-topic",vBulletin.conversation.openOrCloseTopic)})})(jQuery);