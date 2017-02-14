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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache.push("wrap_x_tags","insert_video","retrieve_remote_file_and_ref_local");window.vBulletin.options=window.vBulletin.options||{};window.vBulletin.options.precache=window.vBulletin.options.precache||[];window.vBulletin.options.precache=$.merge(window.vBulletin.options.precache,["threadpreview","minuserlength","maxuserlength"]);vBulletin.ckeditor=vBulletin.ckeditor||{};CKEDITOR.config.allowedContent=true;CKEDITOR.config.baseHref=pageData.baseurl+"/";if(typeof (pageData.ckeditorCss)!="undefined"){CKEDITOR.config.contentsCss=CKEDITOR.config.baseHref+pageData.ckeditorCss}CKEDITOR.config.toolbar_vBulletinFull=(function(){var D=[["EnhancedSource","Source","RemoveFormat","PasteText","PasteFromWord"],["Font","FontSize"],["TextColor","-","Undo","Redo"],["Bold","Italic","Underline"],["JustifyLeft","JustifyCenter","JustifyRight"],["NumberedList","BulletedList","Outdent","Indent"],["Link","Email","Unlink","Image","Video"],["Quote"],["Code","Html","Php"],["Table","vBTable"],["Subscript","Superscript","HorizontalRule"]];if(window.pageData&&window.pageData.channelType&&window.pageData.channelType=="article"){D.push(["PageBreak","PreviewBreak"])}if(jQuery.type(window.vBulletin.customBbcode)!=="undefined"){var B=[];for(var A in window.vBulletin.customBbcode){if(!A){continue}var C=window.vBulletin.customBbcode[A];B.push(C.title)}if(B.length>0){D[D.length]=B}}return D})();var CK2BB={Font:"font",FontSize:"size",TextColor:"color",Bold:"b",Italic:"i",Underline:"u",Subscript:"sub",Superscript:"sup",HorizontalRule:"hr",Table:"table",TableProperties:"table",DeleteTable:"table",InsertRowBefore:"table",InsertRowAfter:"table",DeleteRow:"table",InsertColumnBefore:"table",InsertColumnAfter:"table",DeleteColumn:"table",JustifyLeft:"left",JustifyCenter:"center",JustifyRight:"right",NumberedList:"list",BulletedList:"list",Outdent:"indent",Indent:"indent",Link:"url",Email:"email",Unlink:"url",Quote:"quote",Code:"code",Html:"html",Php:"php"};CKEDITOR.config.smiley_path=pageData.baseurl_core+"/";CKEDITOR.config.enterMode=CKEDITOR.ENTER_BR;CKEDITOR.config.entities=false;CKEDITOR.config.height=70;CKEDITOR.config.width="100%";CKEDITOR.config.autoGrow_minHeight=70;CKEDITOR.config.autoGrow_maxHeight=800;CKEDITOR.config.autoGrow_viewportLimit=true;CKEDITOR.config.contentsLangDirection=pageData.textdirection;CKEDITOR.config.language="en";CKEDITOR.config.defaultLanguage="en";CKEDITOR.config.language_list=["en"];vBulletin.ckeditor.config={toolbar:"vBulletinFull",customConfig:"",toolbarCanCollapse:false,resize_dir:"vertical",removePlugins:"about,contextmenu,elementspath,link,smiley,liststyle,sourcearea,scayt",extraPlugins:"vblink,vbwrapbuttons,vbsource,videotag,autosave,autogrow,vbtable,resize",pluginPath:pageData.baseurl+"/js/ckeplugins/",tabIndex:0,disableNativeSpellChecker:false,filebrowserImageUploadUrl:pageData.baseurl+"/uploader/CKEditorInsertImage"};(function(){var A=vBulletin.ckeditor.config.extraPlugins.split(",");for(var B=0;B<A.length;B++){try{if(!CKEDITOR.plugins.get(A[B])){console.log("Ckeditor: Plugin Loaded: "+pageData.baseurl+"/js/ckeplugins/"+A[B]+"/plugin.js");CKEDITOR.plugins.addExternal(A[B],vBulletin.ckeditor.config.pluginPath+A[B]+"/","plugin.js")}}catch(C){console.warn("Ckeditor: Plugin failed to load: "+pageData.baseurl+"/js/ckeplugins/"+A[B]+"/plugin.js")}}})();vBulletin.ckeditor.instances={};vBulletin.ckeditor.imageSettings=["imgAlignment","imgSize","imgTitle","imgDescription","imgStyles","imgLink","imgLinkURL","imgLinkTarget",];vBulletin.ckeditor.initFocus=function(B,A){if($("#"+B).is(".js-ckeditor-init-on-focus")||$("#"+B).is(".js-ckeditor-focus-editor-after-init")){A.focus();console.log("Ckeditor: Focus on editor for editor: {0}".format(B))}};vBulletin.ckeditor._isEmptyData=function(A){A=A+"";if(A.length>1000){return false}if(A.match(/<(table|ol|ul|li|img|hr)/)||A.match(/<(div|ol|ul|li|img) style="(margin-left|margin-right|text-align)/)){return false}if(A.match(/<h3\s+class="wysiwyg_pagebreak">/)){return false}A=$("<div />").append(A).text();A=$.trim(A).replace(/&nbsp;|\s|\n|\t/g,"");return !A};vBulletin.ckeditor.initPlaceholder=function(D,C){var E=$("#"+D),J=E.attr("placeholder");C=C||vBulletin.ckeditor.getEditor(D);console.log("Ckeditor: Initializing placeholder text");if(!J){console.warn("Ckeditor: Placeholder text: No placeholder text");return }var H=function(L){var M=E.closest(".b-editor").find(".cke_contents"),K=M.find(".b-editor__placeholder");if(K.length==0){K=$("<div />").text(J).addClass("b-editor__placeholder h-hide placeholder").appendTo(M)}return K};var B=function(K){return K.document?K.document.getBody():null};var F=function(K){if(K&&K.$&&!K.hasClass("js-vbulletin-has-placeholder-events")){console.log("Ckeditor: Placeholder text: attachBodyEventHandlers");K.on("keydown",I);K.on("drop",I);K.addClass("js-vbulletin-has-placeholder-events")}};var A=true;var I=function(K){window.setTimeout(function(){var L=B(C),O=H(D);if(L&&L.$){F(L);var Q=vBulletin.ckeditor._isEmptyData(L.getHtml());O.toggleClass("h-hide",!Q);if(A&&!Q){var M=E.closest(".js-content-entry"),P=M.find(".js-human-verification"),N=P.find(".imagereg");M.find(".js-contententry-buttons").removeClass("h-hide");P.removeClass("h-hide");if(N.height()!=N.attr("height")){P.find(".refresh_imagereg").click()}A=false}}},10)};C.on("saveSnapshot",I);C.on("afterCommandExec",I);C.on("afterSetData",I);var G=function(K){if(K.editor.mode!="source"){I()}};C.on("mode",G);I()};vBulletin.ckeditor.initToolbarCloseButton=function(B,A){var E=$("#"+B),D=E.closest(".b-editor").find(".cke_top");var C=$("<span />").addClass("b-icon b-icon__x-circle--dark h-right js-link js-contententry-panel-x h-margin-right-m h-margin-top-m").attr("tabindex","0").text("x").appendTo(D)};vBulletin.ckeditor.modifyDialogs=function(C,A){var B=vBulletin.ckeditor.getEditor(C);CKEDITOR.on("dialogDefinition",function(G){var H=G.data.name;var J=G.data.definition;switch(H){case"image":J.removeContents("advanced");J.removeContents("Link");var F=J.getContents("info");F.remove("remote");F.remove("txtWidth");F.remove("txtHeight");F.remove("txtBorder");F.remove("txtAlt");F.remove("ratioLock");F.remove("txtHSpace");F.remove("txtVSpace");F.remove("cmbAlign");for(var E=0;E<vBulletin.ckeditor.imageSettings.length;E++){F.remove(vBulletin.ckeditor.imageSettings[E])}F.add({type:"radio",id:"imgAlignment",label:B.lang.common.align,items:[[B.lang.vbulletin.none,"none"],[B.lang.common.alignLeft,"left"],[B.lang.common.alignCenter,"center"],[B.lang.common.alignRight,"right"]],"default":"none",});F.add({type:"radio",id:"imgSize",label:B.lang.vbulletin.size,items:[[B.lang.vbulletin.icon,"icon"],[B.lang.vbulletin.thumbnail,"thumb"],[B.lang.vbulletin.small,"small"],[B.lang.vbulletin.medium,"medium"],[B.lang.vbulletin.large,"large"],[B.lang.vbulletin.fullsize,"full"]],"default":"full",});F.add({type:"text",id:"imgTitle",label:B.lang.vbulletin.title,});F.add({type:"textarea",id:"imgDescription",label:B.lang.vbulletin.description,});F.add({type:"text",id:"imgStyles",label:B.lang.vbulletin.style,});F.add({type:"radio",id:"imgLink",label:B.lang.vbulletin.linktype,items:[[B.lang.vbulletin["default"],"0"],[B.lang.common.url,"1"],[B.lang.vbulletin.none,"2"]],"default":"0",onChange:function(){var K=this.getDialog();var L=K.getContentElement("info","imgLink");var N=K.getContentElement("info","imgLinkURL");var M=K.getContentElement("info","imgLinkTarget");if(L.isEnabled()&&L.getValue()==1){N.enable();M.enable()}else{N.disable();M.disable()}},});F.add({type:"text",id:"imgLinkURL",label:B.lang.vbulletin.linkurl,onShow:function(){var K=this.getDialog();var L=K.getContentElement("info","imgLink");if(L.isEnabled()&&L.getValue()==1){this.enable()}else{this.disable()}},});F.add({type:"select",id:"imgLinkTarget",label:B.lang.vbulletin.linktarget,items:[[B.lang.common.targetSelf,"0"],[B.lang.common.targetNew,"1"]],"default":"0",onShow:function(){var K=this.getDialog();var L=K.getContentElement("info","imgLink");if(L.isEnabled()&&L.getValue()==1){this.enable()}else{this.disable()}},});F.add({type:"checkbox",id:"remote",label:vBulletin.phrase.get("retrieve_remote_file_and_ref_local"),validate:function(){if(this.getValue()){var M=this.getDialog();var L=M.getContentElement("info","txtUrl");var K=L.getValue();if(!K){return true}M.disableButton("ok");M.disableButton("cancel");var O=false;var N=this;vBulletin.AJAX({async:false,url:vBulletin.getAjaxBaseurl()+"/uploader/url",data:{urlupload:K,attachment:1},skipdefaultsuccess:true,success:function(P){if(P.errors){var Q=P.errors;if($.isArray(Q)&&Q.length>0){Q=Q[0]}alert(vBulletin.phrase.get("error_x",vBulletin.phrase.get(Q)))}else{if(P.imageUrl){var R={url:P.imageUrl,filedataid:P.filedataid,name:P.filename,};vBulletin.ckeditor.closeFileDialog(C,new Array(R));O=true}}},fail:function(Q){var R=vBulletin.phrase.get("error_uploading_image");var P="error";if(Q&&Q.files.length>0){switch(Q.files[0].error){case"acceptFileTypes":R=vBulletin.phrase.get("invalid_image_allowed_filetypes_are");P="warning";break}}alert(R)},complete:function(){M.enableButton("ok");M.enableButton("cancel")}});return O}}});var I=J.onOk;J.onOk=function(M){var L=this.getContentElement("info","remote");if(L&&L.getValue()){}else{var K=this.getContentElement("info","txtUrl").getValue();vBulletin.ckeditor.autogrowEditorOnImageLoad(K,B);I.apply(this,M)}};break;default:}});function D(H){if(!H){return false}var G=pageData.baseurl+"/filedata/fetch?",E="filedata/fetch?",F="/filedata/fetch?";return(H.indexOf(E)==0||H.indexOf(F)==0||H.indexOf(G)!==-1)}B.on("dialogShow",function(V){var S=V.data;var L=S.getName();switch(L){case"image":var P=B.getSelection(),I=P&&P.getSelectedElement(),N=I&&B.elementPath(I).contains("a",1);S.getContentElement("info","htmlPreview").getElement().hide();var F=I&&I.getAttribute("src"),T=F&&D(F)||false;showDefaultImageBits=function(){S.showPage("Upload");for(var X=0;X<vBulletin.ckeditor.imageSettings.length;X++){imgSettingElement=S.getContentElement("info",vBulletin.ckeditor.imageSettings[X]);if(imgSettingElement){imgSettingElement.getElement().hide()}}S.getContentElement("info","remote").getElement().show();S.getContentElement("info","txtUrl").getElement().show()};if(I&&I.getName()=="img"&&T){var O=I.getAttribute("data-tempid"),H=I.getAttribute("data-attachmentid"),E=O||H,M=false;if(!E){var J=/(\?|&)filedataid=(\d+)/,G=J.exec(F),Q=G&&G[2];if(!Q){J=/(\?|&)([A-Za-z]*id)=(\d+)/;G=J.exec(F);if(G){vBulletin.AJAX({async:false,url:vBulletin.getAjaxBaseurl()+"/uploader/fetchfiledataid",data:{idname:G[2],id:G[3]},success:function(X){if(X.filedataid){Q=X.filedataid}},})}}E=Q&&vBulletin.ckeditor.generateTempAttachmentId(Q);M=Q&&true}if(E){S.hidePage("Upload");for(var R=0;R<vBulletin.ckeditor.imageSettings.length;R++){imgSettingElement=S.getContentElement("info",vBulletin.ckeditor.imageSettings[R]);if(imgSettingElement){imgSettingElement.getElement().show()}}S.getContentElement("info","remote").getElement().hide();S.getContentElement("info","txtUrl").getElement().hide();for(var R=0;R<vBulletin.ckeditor.imageSettings.length;R++){cleanId=vBulletin.ckeditor.imageSettings[R].substring(3).toLowerCase();attributeName="data-"+cleanId;fieldValue=I.getAttribute(attributeName);if(fieldValue){S.getContentElement("info",vBulletin.ckeditor.imageSettings[R]).setValue(fieldValue)}}var K=S.on("ok",function(){var X=$("#"+C).closest("form"),c={},Z=false;for(var Y=0;Y<vBulletin.ckeditor.imageSettings.length;Y++){fieldElement=S.getContentElement("info",vBulletin.ckeditor.imageSettings[Y]);fieldValue=fieldElement&&fieldElement.isEnabled()&&fieldElement.getValue();cleanId=vBulletin.ckeditor.imageSettings[Y].substring(3).toLowerCase();attributeName="data-"+cleanId;hiddenInputName="setting["+E+"]["+cleanId+"]";if(fieldValue){I.setAttribute(attributeName,fieldValue);if(H){c[cleanId]=fieldValue}else{$('input[type="hidden"][name="'+hiddenInputName+'"]').remove();$parent=X.find('input[name="filedataids['+E+']"]').parent();if(!$parent||$parent.length==0){$parent=X}$parent.append($("<input />").attr({type:"hidden",name:hiddenInputName}).val(fieldValue));Z=true}}else{I.removeAttribute(attributeName);if(H){}else{$('input[type="hidden"][name="'+hiddenInputName+'"]').remove()}}switch(cleanId){case"size":newImgSrc=F.replace(/&type=[A-Za-z]+/,"")+"&type="+fieldValue;I.setAttribute("src",newImgSrc);break;case"alignment":I.removeClass("align_left");I.removeClass("align_center");I.removeClass("align_right");if(fieldValue!=="none"){I.addClass("align_"+fieldValue)}break;case"title":break;default:break}}if(Z&&M){vBulletin.ckeditor.insertImageAttachment(C,Q,"",E);var a=/(\?|&)[A-Za-z]*id=\d+/,b=a.exec(F);I.setAttribute("src",I.getAttribute("src").replace(b[0],"?filedataid="+Q));if(I.getAttribute("data-cke-saved-src")){I.setAttribute("data-cke-saved-src",I.getAttribute("data-cke-saved-src").replace(b[0],"?filedataid="+Q))}I.setAttribute("data-tempid",E);I.setAttribute("data-tempid",E);I.addClass("bbcode-attachment")}if(H){c.attachmentid=H;vBulletin.AJAX({url:vBulletin.getAjaxBaseurl()+"/uploader/saveattachmentsetting",data:c,success:function(d){if(d.success){console.log("/uploader/saveattachmentsetting Success!")}else{}},})}});S.on("hide",function(){K.removeListener()})}else{showDefaultImageBits()}}else{showDefaultImageBits()}var U=($(window).height()-S.getSize().height)/2,W=($(window).width()-S.getSize().width)/2;if(U<0){U=0}S.move(W,(U-1));S.move(W,U,true);S.on("selectPage",function(Y){var X=Y.data.page;switch(X){case"Upload":var Z=$(S.getElement().$).find("iframe.cke_dialog_ui_input_file").contents().find("form");if(Z.find('input[name="securitytoken"]').length){Z.find('input[name="securitytoken"]').val(pageData.securitytoken)}else{Z.append('<input type="hidden" name="securitytoken" value="'+pageData.securitytoken+'" />')}break}});break;default:}})};vBulletin.ckeditor.killImageFloat=function(C,A){var B=vBulletin.ckeditor.getEditor(C);var D=B.getCommand("justifyright");$.each(D._.events.exec.listeners,function(E,F){D.removeListener("exec",F.fn)});$.each(D._.events.refresh.listeners,function(E,F){D.removeListener("refresh",F.fn)});var D=B.getCommand("justifyleft");$.each(D._.events.exec.listeners,function(E,F){D.removeListener("exec",F.fn)});$.each(D._.events.refresh.listeners,function(E,F){D.removeListener("refresh",F.fn)});var D=B.getCommand("justifycenter");$.each(D._.events.refresh.listeners,function(E,F){D.removeListener("refresh",F.fn)})};vBulletin.ckeditor.modifyMagicline=function(B,A){try{A.plugins.magicline.backdoor.that.triggers.h3=1;console.log("Ckeditor: Added h3 to ckeditor magicline triggers.")}catch(C){console.warn("Ckeditor: Unable to add h3 to ckeditor magicline triggers. Error: {0}".format(C))}};vBulletin.ckeditor.verifyEditorCss=function(C,B,F,G,H,J){var E=function(L){if(L&&L.document&&L.document.$&&L.document.$.defaultView&&L.document.$.defaultView.frameElement){return L.document.$.defaultView.frameElement.contentDocument||L.document.$.defaultView.frameElement.contentWindow.document}return false},K=function(S,O){if(!S){return -1}var N,M,R,Q=false,L=function(T){return(T+"").replace(/&amp;/g,"&")};for(N=0;N<S.styleSheets.length;++N){try{if(L(S.styleSheets[N].href)==L(CKEDITOR.config.contentsCss)){Q=true}R=S.styleSheets[N].cssRules;for(M=0;M<R.length;++M){if(R[M].selectorText==O){return 1}}}catch(P){}}return Q?0:-1},A=E(B),D=function(){++I;if(F>0&&K(A,H)==0){--F;window.setTimeout(D,G)}else{J.apply(B,[C,B]);console.log("Ckeditor: Init: verifyEditorCss: checkCss called {0} times".format(I))}},I=0;D()};vBulletin.ckeditor.initSmilies=function(B,A){A.on("mode",function(C){if(C.editor.mode=="source"){$("#"+B).closest(".js-content-entry").find(".js-smilie-container .js-smilie-button").addClass("b-smilie__button--disabled")}else{$("#"+B).closest(".js-content-entry").find(".js-smilie-container .js-smilie-button").removeClass("b-smilie__button--disabled")}})};vBulletin.ckeditor.initHandleEditorFocus=function(B,A){A.on("focus",function(G){var C=$("#"+B).closest(".js-content-entry"),E=C.find(".js-human-verification"),D=E.find(".imagereg");C.find(".js-contententry-buttons").removeClass("h-hide");E.removeClass("h-hide");if(D.height()!=D.attr("height")){E.find(".refresh_imagereg").click()}var F=$.cookie(pageData.cookie_prefix+"showcketoolbar");if(F!="null"){var H=F==1?true:false;if(H){vBulletin.ckeditor.toggleToolbar($("#"+B),H)}}})};vBulletin.ckeditor.initHandleEditorBlur=function(B,A){A.on("blur",function(C){(function(){var D=$("#"+B).closest(".js-content-entry"),E=vBulletin.ckeditor.getEditorContent(B);vBulletin.contentEntryBox.populateMetaDescription(E,D)})()})};vBulletin.ckeditor.isEditorEmpty=function(A){var B=vBulletin.ckeditor.getEditorContent(A);if(B===false||B===""){return true}return vBulletin.ckeditor._isEmptyData(B)};vBulletin.ckeditor.resetEditor=function(B,C){B=vBulletin.ckeditor.verifyEditorId(B);C=C||function(){};var A=vBulletin.ckeditor.getEditor(B);if(!A){return false}A.setData("",C)};vBulletin.ckeditor.maintainContent=function(B,A){};vBulletin.ckeditor.convertToAdvancedEditor=function(A){};vBulletin.ckeditor.convertToBasicEditor=function(A){};vBulletin.ckeditor.toggleAdvancedAndBasicEditor=function(B,A){};vBulletin.ckeditor.toggleToolbar=function(C,D,B){var E=$(C);if(typeof D=="undefined"){E.closest(".b-editor").toggleClass("b-editor--cke-toolbar-is-hidden")}else{E.closest(".b-editor").toggleClass("b-editor--cke-toolbar-is-hidden",!D)}var A=!E.closest(".b-editor").hasClass("b-editor--cke-toolbar-is-hidden");E.closest(".js-content-entry").find(".js-toolbar-secondary .b-toolbar__item").filter(function(){return($(this).data("panel")=="b-content-entry-panel__content--toggle-editor")}).toggleClass("b-toolbar__item--active",A);if(!B){$.cookie(pageData.cookie_prefix+"showcketoolbar",(A?1:0),{path:pageData.cookie_path,domain:pageData.cookie_domain,expires:365})}};vBulletin.ckeditor.doInitEditor=function(A,J){if(CKEDITOR.env.mobile&&!CKEDITOR.env.isCompatible){console.log("Ckeditor: Not supported on this mobile device");vBulletin.ckeditor.hideLoadingDialog(A);return }console.log("Ckeditor: Initializing editor: {0}".format(A));var F=$("#"+A);F.trigger("beforeInit",[A]);if(vBulletin.ckeditor.editorExists(A)){console.log("The editor ({0}) already exists.".format(A));return }if(F.is(":visible")&&F.val()==""){F.val(" ")}var D=F.data("disable-features");if(D){if(D=="all"){console.log("Ckeditor: Disabling features: All");vBulletin.ckeditor.config.toolbar=[]}else{D=D.split(",");var C=CKEDITOR.config["toolbar_"+vBulletin.ckeditor.config.toolbar];var I,E,B,G;for(E=0;E<C.length;E++){for(B=0;B<C[E].length;B++){I=C[E][B];if(I.length==0||I=="-"||!CK2BB[I]){continue}if(jQuery.inArray(CK2BB[I],D)>=0){console.log("Ckeditor: Disabling feature: "+I);delete CKEDITOR.config["toolbar_"+vBulletin.ckeditor.config.toolbar][E][B]}}}}}var H=(typeof J.showToolbar=="undefined"?false:J.showToolbar);vBulletin.ckeditor.toggleToolbar(F,H,true);F.ckeditor(function(){vBulletin.ckeditor.hideLoadingDialog(A);vBulletin.ckeditor.instances[A]=this;vBulletin.ckeditor.initPlaceholder(A,this);vBulletin.ckeditor.initHandleEditorFocus(A,this);vBulletin.ckeditor.initHandleEditorBlur(A,this);vBulletin.ckeditor.initFocus(A,this);vBulletin.ckeditor.initToolbarCloseButton(A,this);vBulletin.ckeditor.initSmilies(A,this);vBulletin.ckeditor.killImageFloat(A,this);vBulletin.ckeditor.modifyDialogs(A,this);vBulletin.ckeditor.modifyMagicline(A,this);console.log("Ckeditor: Editor initialized: {0}".format(A));if(F.closest("form").length>0){F.closest("form").attr("ck-editorid",A);console.log('Ckeditor: Added editorId "'+A+'" to form')}vBulletin.ckeditor.verifyEditorCss(A,this,10,100,"hr.previewbreak",function(M,L){F.trigger("afterInit",[M]);vBulletin.ckeditor.fireCallbacks(M,J,"success",this)});var K=F.data("disable-resize");if(K){vBulletin.ckeditor.disableResizeAndAutogrow(A,this)}},vBulletin.ckeditor.config)};vBulletin.ckeditor.editorExists=function(A){A=vBulletin.ckeditor.verifyEditorId(A);return(typeof vBulletin.ckeditor.instances[A]!="undefined"&&vBulletin.ckeditor.instances[A])};vBulletin.ckeditor.getEditor=function(A){A=vBulletin.ckeditor.verifyEditorId(A);return vBulletin.ckeditor.editorExists(A)?vBulletin.ckeditor.instances[A]:null};vBulletin.ckeditor.closeFileDialog=function(E,J){var G=vBulletin.ckeditor.getEditor(E);if(G){var H=CKEDITOR.dialog.getCurrent();if(H.getName()=="image"){for(var F=0;F<J.length;F++){var C=J[F]["url"],A=J[F]["filedataid"],B=J[F]["name"],I=vBulletin.ckeditor.generateTempAttachmentId(A);if(A&&C){vBulletin.ckeditor.autogrowEditorOnImageLoad(C,G);var D=CKEDITOR.dom.element.createFromHtml('<img class="bbcode-attachment" src="'+C+'" data-tempid="'+I+'" />');if(B){D.setAttribute("data-title",B)}G.insertElement(D);vBulletin.ckeditor.insertImageAttachment(E,A,B,I)}}H.hide()}}};vBulletin.ckeditor.insertImageAttachment=function(C,D,E,A){var B=$("#"+C).closest("form");E=E||"";A=A||"";filedataObj={filedataid:D,filename:E,imagesize:"full"};vBulletin.contentEntryBox.addAttachmentItemPanel(B,A,filedataObj)};vBulletin.ckeditor.destroyEditor=function(A){console.log("Ckeditor: Destroying editor: {0}".format(A));A=vBulletin.ckeditor.verifyEditorId(A);if(vBulletin.ckeditor.editorExists(A)){try{vBulletin.ckeditor.getEditor(A).destroy()}catch(B){}vBulletin.ckeditor.instances[A]=null;vBulletin.ckeditor.toggleToolbar($("#"+A),false);return true}else{console.warn("Ckeditor: Not destroyed, because editor {0} does not exist.".format(A))}return false};vBulletin.ckeditor.generateTempAttachmentId=function(C){var B=Math.floor(Math.random()*1000),A=(new Date()).getTime();return"temp_"+C+"_"+A+"_"+B};vBulletin.ckeditor.swapLanguage=function(A){A.editor.on("langLoaded",function(D){var C=($("#debug-information").length==1);CKEDITOR.vBulletin=CKEDITOR.vBulletin||{};if(typeof (CKEDITOR.vBulletin.langInit)=="undefined"&&typeof (window.vBulletin.ckeditorPhrases)!="undefined"){CKEDITOR.vBulletin.langInit=true;if(C){var E={};function B(G,F){F=F||"";$.each(G,function(H,I){if(typeof I=="object"){B(G[H],H+".")}else{G[H]="~~"+I+"~~ ("+F+H+")";E[F+H]=0}})}B(CKEDITOR.lang.en)}$.each(window.vBulletin.ckeditorPhrases,function(G,F){var I=CKEDITOR.lang.en;var H=G.split(".");for(var J=0;J<H.length;J++){if(J==(H.length-1)){if(C){if(typeof I[H[J]]!="undefined"){E[G]=1}else{E[G]=-1}}if(F===null){I[H[J]]="Undefined: "+G}else{I[H[J]]=F}}else{if(typeof (I[H[J]])!="object"||I[H[J]]===null){I[H[J]]={}}I=I[H[J]]}}});CKEDITOR.lang.en.dir=pageData.textdirection;if(C){}}else{if(typeof (window.vBulletin.ckeditorPhrases)=="undefined"&&C){console.warn("Ckeditor phrases not loaded.")}}});CKEDITOR.removeListener("instanceCreated",vBulletin.ckeditor.swapLanguage)};vBulletin.ckeditor.fixMobileDisplay=function(B){var A=B.editor;if(CKEDITOR.env.ie){try{document.execCommand("AutoUrlDetect",false,false)}catch(D){}}var C=$("#"+A.id+"_contents").children().filter(function(){return this.tagName=="IFRAME"?true:false});if(parseInt(A.config.width)<310){C.hide();C.show()}if("ontouchstart" in document.documentElement){C.get(0).contentWindow.scrollTo(0,10)}A.dataProcessor.dataFilter.addRules({elements:{img:function(H){if(H.attributes["class"]&&H.attributes["class"].indexOf("js-need-data-att-update")!==-1){var E=H.attributes["data-tempid"],F=$("#"+A.element.getId()).closest("form");if(E&&F){for(var G=0;G<vBulletin.ckeditor.imageSettings.length;G++){cleanId=vBulletin.ckeditor.imageSettings[G].substring(3).toLowerCase();inputName="setting["+E+"]["+cleanId+"]";$inputElement=F.find('input[name="'+inputName+'"]');inputValue=$inputElement&&$inputElement.is(":enabled")&&$inputElement.val();if(inputValue){H.attributes["data-"+cleanId]=inputValue;switch(cleanId){case"size":newImgSrc=H.attributes.src.replace(/&type=[A-Za-z]+/,"")+"&type="+inputValue;H.attributes.src=newImgSrc;break;case"alignment":H.attributes["class"]=H.attributes["class"].replace("align_left","").replace("align_center","").replace("align_right","");if(inputValue!=="none"){H.attributes["class"]=H.attributes["class"]+" align_"+inputValue}break;case"title":break;default:break}}}}H.attributes["class"]=H.attributes["class"].replace("js-need-data-att-update","")}}}})};vBulletin.ckeditor.fixIeEditorClick=function(B){var A=B.editor||false;if(A&&A.container&&A.container.$){$(".cke_wysiwyg_frame",A.container.$).contents().on("click",function(C){A.focus()})}};vBulletin.ckeditor.fixTableFunctionality=function(B,C){var A=C||B.editor;$("#"+A.id+"_top .cke_button__table").hide()};vBulletin.ckeditor.initUsernameAutosuggest=function(L){if(typeof vBulletin.contentEntryBox.bbcode_enabled.user=="undefined"){console.log("Warning, vBulletin.contentEntryBox.bbcode_enabled.user is undefined! Disabling usermention auto suggest.");vBulletin.contentEntryBox.bbcode_enabled.user=false}if(!vBulletin.contentEntryBox.bbcode_enabled.user){console.log("USER bbcode is disabled on this forum. Disabling usermention auto suggest.");return }var I=0;var P=300;var H={};var O=[];var A=false;function R(S){if(S&&S.data&&S.data.keyCode){switch(S.data.keyCode){case 27:case 13:case 38:case 40:if(I){window.clearTimeout(I);I=0}return E(S);break}}if(I){return }I=window.setTimeout(function(){E(S)},P)}function E(T){if(!T||!T.data||!T.data.keyCode){return true}var V=T.data.keyCode,W=([13,27,38,40].indexOf(V)!=-1),S=N(T.editor);var U=true;if(W){U=C(T,S)}if(A&&!S){A=false}I=0;return U}function C(U,S){var W=U.data.keyCode;var T=vBulletin.ckeditor.verifyEditorId(U.editor),V=$("#"+T).closest(".js-content-entry"),X=$(".js-contententry-username-autosuggest",V);$autocomplete=X.autocomplete("widget");if(!$autocomplete.is(":visible")){return true}switch(W){case 27:A=true;X.val("").autocomplete("close");break;case 13:$autocomplete.menu("select");break;case 38:$autocomplete.menu("previous");break;case 40:$autocomplete.menu("next");break}U.cancel();U.stop();return false}function N(T){if(T.mode&&T.mode=="source"){return false}var S=vBulletin.ckeditor.verifyEditorId(T),U=$("#"+S).closest(".js-content-entry"),W=$(".js-contententry-username-autosuggest",U);var V=K(T),X="";if(V){X=V.username}if(X!=""&&X.length>=3&&X.length<=vBulletin.options.get("maxuserlength")){console.log(X,W.val());if(X!=W.val()){F(T,X,V.startNode)}return true}else{W.val("").autocomplete("close");return false}}function K(V){var j=V.getSelection();if(!j){return false}var a=j.getRanges()[0];a.collapse(true);a.setStartAt(V.editable(),CKEDITOR.POSITION_AFTER_START);var g=a.endOffset;var f=new CKEDITOR.dom.walker(a);var U=0;var d=f.previous();--U;var i=d;if(!d){return false}var Y=d.getText();var b=Y.lastIndexOf("@");if(b==-1){while(true){var c=f.previous();--U;if(c&&c.type==CKEDITOR.NODE_TEXT){var X=c.getText();var Z=X.lastIndexOf("@");Y=X+Y;b=Z;g+=X.length;if(b!=-1){i=c;break}}else{break}}}if(b==-1){return false}if(b==Y.length-1){return false}if(b==0){var c=f.previous();--U;if(c){var e=c.getText();if(e!=""&&!(/\s$/.test(e))){return false}if(c&&c.$&&c.$.tagName&&c.$.tagName.toUpperCase){var W=c.$.tagName.toUpperCase();if(W!="BR"&&W!="HR"){return false}}}}if(b>0){if(!(/\s/.test(Y.substr(b-1,1)))){return }}while(U<0){f.next();++U}while(1){var S=f.next();if(S&&S.type==CKEDITOR.NODE_TEXT){var h=S.getText();if(h!=""){Y+=h;g+=h.length}}else{break}}var T=Y.substr(b+1);T=T.substr(0,g-b-1);T=$.trim(T);return{username:T,startNode:i}}function F(V,W,b){G("showUsernameAutosuggest()");var g=vBulletin.ckeditor.verifyEditorId(V);var c=$("#"+g).closest(".js-content-entry");var U=$(".js-contententry-username-autosuggest",c);if(A){U.val("").autocomplete("close");return }var S=c.find(".cke_wysiwyg_frame").offset();var a=c.offset();var Z=b.getParent();var d=Z.getDocumentPosition();var i=Z.getHtml();var T=b.getText();function X(k){return k.replace(/&nbsp;/g," ").replace((new RegExp("\u00A0","g"))," ")}i=X(i);T=X(T);var e=T.lastIndexOf("@");if(T.substr(e+1).length<W.length){T=T.substr(0,e+1)+W}var Y=T.substr(0,e)+'<span id="vb-get-pos">x</span>'+T.substr(e);var j=i.replace(T,Y);$("<div />").attr("id","vb-temp-position-el").css({position:"absolute",top:(S.top-a.top+d.y),left:(S.left-a.left+d.x),border:"1px solid red",background:"#CCC",zIndex:"999"}).html(j).appendTo(c);var h=$("#vb-get-pos",c).offset();var f=$("#vb-get-pos",c).outerHeight();$("#vb-temp-position-el",c).remove();U.css({top:(h.top-a.top+f),left:(h.left-a.left)});U.val(W);U.autocomplete("search",W);U.data("node",b)}function J(T){var S=vBulletin.ckeditor.verifyEditorId(T.editor.name);var U=$("#"+S).closest(".js-content-entry");var V=$(".js-contententry-username-autosuggest",U);V.val("").autocomplete("close")}function D(S,X){var T=false;$.each(O,function(Y,Z){if(S.term.indexOf(Z)==0){T=true;return false}});if(T){$(this.element).val("").autocomplete("close");return }var V=$(this.element);var U=V.closest(".js-content-entry");function W(Y){X(Y);window.setTimeout(function(){var Z=$(".ui-autocomplete",U);Z.css("zIndex","2");Z.find(".ui-menu-item").on("mouseout",function(){return false})},10)}if(typeof H[S.term]!="undefined"){W(H[S.term])}else{vBulletin.AJAX({call:"/ajax/api/user/getAutocomplete",data:({searchStr:S.term}),success:function(Z){if(Z&&$.isArray(Z.suggestions)){var Y=[];$.each(Z.suggestions,function(a,b){Y.push({label:b.title,value:b.value})});if(Y.length>0){W(Y);H[S.term]=Y}else{O.push(S.term);V.val("").autocomplete("close")}}},error_phrase:"error_getting_suggestions"})}}function B(a,b){var c=$(this);var d=c.closest(".js-content-entry");var U=d.find(".js-editor").attr("id");var Y=vBulletin.ckeditor.getEditor(U);var X=c.data("node").getParent();var f=X.getHtml();var W="@"+c.val();var S=f.lastIndexOf(W);var V=f.substr(0,S);var T=f.substr(S+W.length).replace(/<br>$/i,"");var Z=T.match(/(&nbsp;| )$/);if(Z){T=T.replace(/(&nbsp;| )$/,"")}X.setText("");Y.insertHtml(V);Y.insertHtml('<a href="#" class="b-bbcode-user js-bbcode-user">'+b.item.value+"</a>&nbsp;");Y.insertHtml(T);I=0;c.val("").autocomplete("close")}function Q(S){I=0}function G(T,S){console.log("Ckeditor: Username autosuggest: "+T);if(typeof S!="undefined"){console.dir(S)}}function M(T){G("init() start");if(!T){G("Editor instance in empty");return }T.on("key",R,null,null,1);T.on("contentDom",function(){this.document.on("click",function(W){N(T)})});T.on("blur",J);var S=vBulletin.ckeditor.verifyEditorId(T);var U=$("#"+S).closest(".js-content-entry").css("position","relative");var V=$('<input type="text" class="js-contententry-username-autosuggest" style="position:absolute;top:0px;left:0px;visibility:hidden;width:1px;height:1px;" />');V.appendTo(U).autocomplete({appendTo:U,autoFocus:true,delay:0,minLength:3,source:D,select:B,close:Q});G("init() finish")}M(L.editor)};vBulletin.ckeditor.modifyCkeditorDialogs=function(C){var D=C.data.name,B=C.data.definition;switch(D){case"table":case"tableProperties":var A=B.getContents("info");A.remove("txtCaption");A.remove("txtSummary");A.remove("selHeaders");A.remove("txtCellSpace");delete B.contents[0].elements[1];delete B.contents[0].elements[2];B.minHeight=200;delete B.contents[1];break;default:break}};vBulletin.ckeditor.autogrowEditorOnImageLoad=function(B,C){var A=new Image();A.onload=function(){C.execCommand("autogrow_ifallowed")};A.src=B};vBulletin.ckeditor.applyCustomPasteFilter=function(B){var A=B.editor;A.on("paste",function(F){var D=new CKEDITOR.htmlParser.filter({elements:{p:function(H){var J="<br>",K="<br>";if(H.previous&&H.previous.type==CKEDITOR.NODE_TEXT){J="<br><br>"}else{if(H.previous&&H.previous.type==CKEDITOR.NODE_ELEMENT&&H.previous.name=="p"){}else{if(H.previous===null){J=""}}}if(H.next&&H.next.type==CKEDITOR.NODE_TEXT){K="<br><br>"}else{if(H.next&&H.next.type==CKEDITOR.NODE_ELEMENT&&H.next.name=="p"){K=""}else{if(H.next===null){K=""}}}var G=J+H.getHtml()+K,I=new CKEDITOR.htmlParser.text(G);H.replaceWith(I)},div:function(H){var G="<br>"+H.getHtml()+"<br>",I=new CKEDITOR.htmlParser.text(G);H.replaceWith(I)}}});var C=CKEDITOR.htmlParser.fragment.fromHtml(F.data.dataValue),E=new CKEDITOR.htmlParser.basicWriter();D.applyTo(C);C.writeHtml(E);F.data.dataValue=E.getHtml()})};vBulletin.ckeditor.disableResizeAndAutogrow=function(B,A){var A=A||vBulletin.ckeditor.getEditor(B);A.fire("disableResize")};CKEDITOR.on("instanceCreated",vBulletin.ckeditor.swapLanguage);CKEDITOR.on("instanceReady",vBulletin.ckeditor.fixMobileDisplay);CKEDITOR.on("instanceReady",vBulletin.ckeditor.fixIeEditorClick);CKEDITOR.on("instanceReady",vBulletin.ckeditor.fixTableFunctionality);CKEDITOR.on("instanceReady",vBulletin.ckeditor.initUsernameAutosuggest);CKEDITOR.on("instanceReady",vBulletin.ckeditor.applyCustomPasteFilter);CKEDITOR.on("dialogDefinition",vBulletin.ckeditor.modifyCkeditorDialogs);