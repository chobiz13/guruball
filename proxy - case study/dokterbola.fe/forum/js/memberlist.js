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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,[]);(function(B){B(document).ready(function(){vBulletin.pagination({context:".memberlist-widget .conversation-toolbar",onPageChanged:A});B(".js-drop-down-startswith").selectBox().change(A);B(document).off("click",".memberlist-widget .js-memberlist__letterfilter .letter").on("click",".memberlist-widget .js-memberlist__letterfilter .letter",A);B(document).off("click",".memberlist-widget .js-sort-label").on("click",".memberlist-widget .js-sort-label",A);B(document).off("click",".js-drop-down-sortby__control").on("click",".js-drop-down-sortby__control",function(){var D=B(this).find(".vb-icon");if(B(this).hasClass("js-open")){C()}else{B(".js-drop-down-sortby__overlay").removeClass("h-hide");D.removeClass("vb-icon-triangle-down-wide").addClass("vb-icon-triangle-up-wide");B(this).addClass("js-open")}});B(document).off("click",".b-memberlist__sortby-overlaylist .js-memberlist__sortby-overlaylist__item").on("click",".b-memberlist__sortby-overlaylist .js-memberlist__sortby-overlaylist__item",A);B(document).off("click",".js-pagenav .js-pagenav-button").on("click",".js-pagenav .js-pagenav-button",function(F){var G=B(".pagenav-form"),E=G.get(0),D=B(this).data("page");G.find(".js-pagenum").val(D);A.call(E,D)})});B("html").on("click",function(D){if(B(D.target).closest(".js-drop-down-sortby__wrapper").size()==0&&B(".js-drop-down-sortby__control").hasClass("js-open")){C()}});function C(){$this=B(".js-drop-down-sortby__wrapper");var D=B(".js-drop-down-sortby__control .vb-icon",$this);B(".js-drop-down-sortby__overlay",$this).addClass("h-hide");D.addClass("vb-icon-triangle-down-wide").removeClass("vb-icon-triangle-up-wide");B(".js-drop-down-sortby__control",$this).removeClass("js-open")}function A(M){var I=B(this);var J={};var H,K,F;J.perpage=B(".memberlist-widget .js-per-page").data("perpage");if(I.hasClass("pagenav-form")){J.pagenumber=M}else{M=B(".memberlist-widget .toolbar-pagenav .js-pagenum").val()}J.pagenumber=M;var E=B(".memberlist-widget .js-memberlist__letterfilter .letter.selected");if(I.hasClass("js-drop-down-startswith")){E.removeClass("selected");delete J.pagenumber;H=B(".js-drop-down-startswith").val();if(B(this.options[this.selectedIndex]).hasClass("all")){H="";B(".js-memberlist__letterfilter .letter.all").addClass("selected")}else{if(B(this.options[this.selectedIndex]).hasClass("numbers")){B(".js-memberlist__letterfilter .letter.numbers").addClass("selected")}else{B(".js-memberlist__letterfilter .letter").each(function(){if(B(this).html()==H){B(this).addClass("selected");return }})}}B(".memberlist-widget .toolbar-pagenav .js-pagenum").val(1)}else{if(I.hasClass("letter")){delete J.pagenumber;H=I.html();if(I.hasClass("all")){H=""}B(".js-drop-down-startswith").val(H);E.removeClass("selected");I.addClass("selected");B(".memberlist-widget .toolbar-pagenav .js-pagenum").val(1)}else{H=E.html();if(E.hasClass("all")){H=""}}}if(H.length){J.startswith=H}if(I.hasClass("js-memberlist__sortby-overlaylist__item")){delete J.pagenumber;K=I.data("sortby");F=I.data("sortorder");B(".memberlist-widget .js-sort-by.selected").removeClass("selected").find(".vb-icon").removeClass("vb-icon-triangle-up-wide vb-icon-triangle-down-wide");var D=B(".js-sort-by[data-sortby={0}]".format(K),".memberlist-widget");D.addClass("selected");D.find(".vb-icon").addClass("vb-icon-triangle-{0}-wide".format((F=="asc")?"up":"down"));B(".memberlist-widget .toolbar-pagenav .js-pagenum").val(1);C()}else{if(I.hasClass("js-sort-label")){delete J.pagenumber;var G=I.parents(".js-sort-by");K=G.data("sortby");F=G.data("sortorder");if(G.hasClass("selected")){F=(F=="asc")?"desc":"asc";G.data("sortorder",F)}B(".memberlist-widget .js-sort-by.selected").removeClass("selected").find(".vb-icon").removeClass("vb-icon-triangle-up-wide vb-icon-triangle-down-wide");G.addClass("selected");I.find(".vb-icon").addClass("vb-icon-triangle-{0}-wide".format((F=="asc")?"up":"down"));B(".memberlist-widget .toolbar-pagenav .js-pagenum").val(1)}else{K=B(".memberlist-widget .js-sort-by.selected").data("sortby");F=B(".memberlist-widget .js-sort-by.selected").data("sortorder")}}J.sortfield=K;J.sortorder=F;var L=B(".js-memberlist-route-info").data("routeinfojson");vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/render/memberlist_items",data:{criteria:J,routeInfoJson:L},error_phrase:"error_x",success:function(N){B(".memberlist-widget .js-memberlist-table-body").replaceWith(N);var O=B(".memberlist-widget .js-memberlist-table-body").data("totalPages");B(".memberlist-widget .toolbar-pagenav .pagetotal").html(O)}})}})(jQuery);