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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["cancel","changed_extra_css_warning","error_loading_css","error_setting_theme","loading_themes","no_preview_available","revert","revert_extra_css_warning","save_and_continue","success","there_are_no_themes_installed","confirm_action"]);window.vBulletin.options=window.vBulletin.options||{};window.vBulletin.options.precache=window.vBulletin.options.precache||[];window.vBulletin.options.precache=$.merge(window.vBulletin.options.precache,["styleid"]);(function(L){var J=false,K,U,S=0,a=false,Y=function(i){var h=this;vBulletin.assertUserHasAdminPermission("canusesitebuilder",h,function(){E.call(h)});return false},E=function(h,m){var l=L(this),k=L(document.body),j=!l.hasClass("selected"),i=true;if(typeof h!="undefined"){j=!!h}if(typeof m!="undefined"){i=!!m}c(!j,l);if(j){L(document.body).addClass("js-edit-mode-style");F();D();L(".js-style-panel").css("display","none").removeClass("h-hide").slideDown("400",A)}else{L(".js-style-panel").slideUp("fast",function(){L(document.body).removeClass("js-edit-mode-style");if(!i){return }var n=window.location.href;if(n.indexOf("adminAction=stylePanel&")!=-1){n=n.replace("adminAction=stylePanel&","")}else{n=n.replace("?adminAction=stylePanel","")}if(n!=window.location.href){window.location.href=n}else{window.location.reload(true)}})}},C=function(h){E(false,h)},c=function(h,j){var i=function(k){this.blur();k.stopPropagation();return false};if(h){L("#main-navbar .main-nav a, #main-navbar .secondary-nav .sb-menu a").removeClass("selected").closest("li").removeClass("h-disabled");L("#main-navbar .secondary-nav li").removeClass("h-disabled");j.removeClass("selected").closest("li").removeClass("h-disabled");L(document).off("focus click mousedown","#main-navbar .h-disabled a",i)}else{L("#main-navbar .main-nav a, #main-navbar .secondary-nav .sb-menu a").removeClass("selected").closest("li").addClass("h-disabled");L("#main-navbar .secondary-nav li").addClass("h-disabled");j.addClass("selected").closest("li").removeClass("h-disabled");L(document).on("focus click mousedown","#main-navbar .h-disabled a",i)}},D=function(){},b=function(i,j){var h=L(".js-theme-selector__list:visible .js-theme-selector__theme-radio:checked").val();vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/style/setDefaultStyle",data:{styleid:h},error_phrase:"error_setting_theme",success:function(){var k=new Date();k.setDate(k.getDate()+365);L.cookie(pageData.cookie_prefix+"userstyleid",h,{path:pageData.cookie_path,domain:pageData.cookie_domain,expires:k});if(typeof j=="undefined"||j){C(true)}}});return false},T=function(h){C(false);return false},f=function(h){d(K.getValue(),null,S)},I=function(h){openConfirmDialog({title:vBulletin.phrase.get("revert"),message:vBulletin.phrase.get("revert_extra_css_warning"),iconType:"warning",onClickYes:function(i){U="";K.setValue("");d("",V,S)}});return false},P=function(h){C(true);return false},N=function(h){K.setValue(U)},B=function(l,h,m){if(!h&&l&&l.target){var h=L(l.target).closest(".js-theme-selector__theme").data("preview-url")}if(!m){var m=L(l.target).closest(".js-theme-selector__list")}var k=m.closest(".js-panel").find(".js-theme-selector__theme-preview"),j,i=k.find("> img, > p");if(k.length!=1){return }if(h){j=L("<img />").prop("src",h)}else{j=L("<p />").addClass("b-theme-selector__theme-preview-message").text(vBulletin.phrase.get("no_preview_available"))}if(i.is("p")&&j.is("p")){i.fadeTo(200,"0.5",function(){L(this).fadeTo(400,1)})}else{i.css("zIndex","2");j.css("zIndex","1").hide();k.append(j);i.fadeOut({duration:300,queue:false,complete:function(){L(this).remove()}});j.fadeIn({duration:600,queue:false})}},e=function(){var h=L(this);h.closest(".js-theme-selector__list").find(".js-theme-selector__theme").removeClass("b-theme-selector__theme--selected js-theme-selector__theme--selected");h.closest(".js-theme-selector__theme").addClass("b-theme-selector__theme--selected js-theme-selector__theme--selected")},Z=function(r,m,i){var l=this,k="js-theme-selector__theme b-theme-selector__theme h-margin-right-xl",n=L('<input type="radio" name="styleid" value="'+l.styleid+'" />').addClass("js-theme-selector__theme-radio b-theme-selector__theme-radio h-margin-none h-margin-right-s"),p=L("<label />").prop("title",l.title),o=L("<div />").addClass("h-margin-s h-margin-bottom-none b-theme-selector__theme-title").text(l.title.length>30?l.title.substr(0,20)+" ...":l.title),h=L("<img />").addClass("h-margin-s h-margin-bottom-m").prop("src",l.iconurl),j=L("<div />").addClass("b-theme-selector__theme-thumbnail");var q=function(s){var t=new Image();t.src=s};q(l.iconurl);q(l.previewurl);if(m==l.styleid){n.prop("checked","checked");n.attr("checked","checked");B({},l.previewurl,r);k+=" js-theme-selector__theme--selected b-theme-selector__theme--selected"}j.append(h);o.prepend(n);p.append(j).append(o);L("<div />").addClass(k).data("preview-url",(l.previewurl||"")).append(p).appendTo(r);g.call(h.get(0),2)},g=function(r){var l=L(this);l.css({maxWidth:"none",maxHeight:"none"});var k=l.outerWidth()||0,s=l.outerHeight()||0,h=k>s?"height":"width",t,n,i,o=false;if(k>0&&s>0){var q=false;if(h=="width"&&k>100){t=100/k;n=k*t;i=s*t;q=true}else{if(h=="height"&&s>100){t=100/s;n=k*t;i=s*t;q=true}else{}}if(q){l.removeClass("h-margin-s h-margin-bottom-m").css({maxWidth:n,maxHeight:i});var m=l.parent();var p=L("<div />").addClass("b-theme-selector__image-resize-wrapper h-margin-s h-margin-bottom-m").css({width:100,height:100,overflow:"hidden"});p.append(l);m.append(p);o=true}}else{}if(!o){l.css({maxWidth:"100px",maxHeight:"100px"});if(r>0){var j=this;window.setTimeout(function(){g.call(j,--r)},100)}else{var j=this;l.off("load").on("load",function(){g.call(j,--r)})}}},H=function(j,m){var k,l=0,h=vBulletin.options.get("styleid");m.html("");if(j&&j.themes){L.each(j.themes,function(){Z.apply(this,[m,h,l]);++l})}if(!l){L("<div />").addClass("b-theme-selector__message").text(vBulletin.phrase.get("there_are_no_themes_installed")).appendTo(m)}else{Q(m,l)}},Q=function(h,k){var i=h.find(".js-theme-selector__theme").first().outerWidth(true),j=i*k;h.width(j);h.closest(".js-theme-selector__container").tinyscrollbar({sizethumb:14,axis:"x"});G(h)},G=function(m){var i=m.find(".js-theme-selector__theme--selected");if(i.length==1){var o=m.find(".js-theme-selector__theme"),p=o.first().outerWidth(true),h=o.length,n=parseInt(i.position().left,10),r=p*h,l=m.position().left,u=Math.round(l)*-1,s=m.closest(".js-theme-selector__container"),q=s.innerWidth(),k=q+u-p,j=0;if(n<u){j=n-u}else{if(k<n){j=n-k}}if(j!=0){var t=u+j;s.tinyscrollbar_update(t)}}},M=function(k,l,i){var j=L(this).closest(".js-theme-selector__theme"),h=j.find(".js-theme-selector__theme-radio");if(j.is(".js-theme-selector__theme--selected")){return }h.attr("checked","checked");e.call(this);B.call(this,k);G(l);if(i.liveUpdate){b.call(this,k,false)}},W=function(i){var h={liveUpdate:false,container:document};i=L.extend({},h,i);var k=L(".js-theme-selector__list",i.containerSelector);function j(l){M.call(this,l,k,i)}k.off("change",".js-theme-selector__theme-radio").on("change",".js-theme-selector__theme-radio",j);k.off("click",".js-theme-selector__theme label img").on("click",".js-theme-selector__theme label img",j);vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/ajax/api/style/getThemeInfo",error:function(){var l=L("<div />").addClass("b-theme-selector__message").text(vBulletin.phrase.get("error_fetching_themes"));k.html(l)},success:function(l){H.apply(this,[l,k])}})},R=function(h){if(isNaN(parseFloat(L(h.target).val()))){return false}currentText=K.getValue();if(U!=K.getValue()){X()}else{V()}},V=function(){var i=L(".js_stylepanel_selectStyle");if(!a){var h=L.cookie(pageData.cookie_prefix+"userstyleid");if(h){i.val(h)}i.selectBox();a=true}L.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/template/fetchBulk",type:"POST",dataType:"json",data:{template_names:{0:"css_additional.css"},styleid:i.val(),type:"uncompiled"},success:function(j){if(j!=null){console.log(j);if(j&&!j.errors){if(typeof (j["css_additional.css"])=="undefined"){U=""}else{U=j["css_additional.css"]}K.setValue(U)}else{if(L.isArray(j.errors)&&j.errors.length>0){K.setValue("")}}}S=i.val()},error:function(){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("error_loading_css"),iconType:"error"})}})},X=function(){openConfirmDialog({title:vBulletin.phrase.get("confirm_action"),message:vBulletin.phrase.get("changed_extra_css_warning"),iconType:"warning",buttonLabel:{yesLabel:vBulletin.phrase.get("save_and_continue"),noLabel:vBulletin.phrase.get("cancel")},onClickYes:function(h){d(K.getValue(),V,S)},onClickNo:function(h){L(".js_stylepanel_selectStyle").selectBox("value",S)}})},d=function(j,i,h){L.ajax({url:vBulletin.getAjaxBaseurl()+"/ajax/api/template/saveAdditional",type:"POST",data:{text:j,styleid:h},dataType:"json",success:function(k){U=j;if(L.isArray(k.errors)&&k.errors.length>0){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("error_saving_css"),iconType:"error"})}else{if(typeof (i)=="function"){openAlertDialog({title:vBulletin.phrase.get("success"),message:vBulletin.phrase.get("css_saved"),iconType:"success",onBeforeClose:function(){i()}})}else{openAlertDialog({title:vBulletin.phrase.get("success"),message:vBulletin.phrase.get("css_saved"),iconType:"success"})}}},error:function(){openAlertDialog({title:vBulletin.phrase.get("error"),message:vBulletin.phrase.get("error_saving_css"),iconType:"error"})}})},A=function(){function h(){if(typeof (K)=="undefined"){K=CodeMirror.fromTextArea(L(".sb-style-panel_cssextraedit").get(0),{mode:"css",lineNumbers:true});U="";L(".js-style-panel #admin-stylepanel-csseditor .js_stylepanel_selectStyle").change();L(".sb-style-panel .tab-item a").off("click",h)}}L(".sb-style-panel .tab-item a").off("click",h).on("click",h);if(L(".js-style-panel .js-admin-stylepanel-csseditor").is(":visible")){h()}},F=function(){if(J){return }L(".js-style-panel .tabs").tabs();var h;h=".js-style-panel #admin-stylepanel-themes .js-button--save";L(document).off("click",h).on("click",h,b);h=".js-style-panel #admin-stylepanel-themes .js-button--cancel";L(document).off("click",h).on("click",h,T);h=".js-style-panel #admin-stylepanel-csseditor .js-button--revert";L(document).off("click",h).on("click",h,I);h=".js-style-panel #admin-stylepanel-csseditor .js-button--cancel";L(document).off("click",h).on("click",h,N);h=".js-style-panel #admin-stylepanel-csseditor .js-button--close";L(document).off("click",h).on("click",h,P);h=".js-style-panel #admin-stylepanel-csseditor .js-button--save";L(document).off("click",h).on("click",h,f);h=".js-style-panel #admin-stylepanel-csseditor .js_stylepanel_selectStyle";L(document).off("change",h).on("change",h,R);if(L(".js-style-panel .js-theme-selector__container").length==1){W({liveUpdate:false,containerSelector:".js-style-panel"})}if(L(".js-style-panel .js-admin-stylepanel-csseditor").length==1){A()}J=true},O=function(){L(function(){L(document).off("click","#main-navbar .sb-menu:not(.h-disabled) #lnkStylePanel").on("click","#main-navbar .sb-menu:not(.h-disabled) #lnkStylePanel",Y);if(typeof (pageData.adminAction)!="undefined"&&pageData.adminAction=="stylePanel"){L("#lnkStylePanel").click()}})};window.vBulletin=window.vBulletin||{};window.vBulletin.sitebuilder=window.vBulletin.sitebuilder||{};window.vBulletin.sitebuilder.initThemesInQuickConfig=function(){W({liveUpdate:true,containerSelector:".js-config-site-panel"})};O()})(jQuery);