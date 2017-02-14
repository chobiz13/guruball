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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["gallery_slideshow","no_photos_found_or_post_deleted"]);(function(G){window.vBulletin=window.vBulletin||{};vBulletin.gallery=vBulletin.gallery||{};var H=5,N=G("#slideshow-dialog"),L=false,M=false,E=0,F={};function K(R,S,V,Q){var X=R.find(".caption"),U;X.html("").hide();if(S.links){X.append(G("<div />").addClass("links ellipsis").html(S.links))}if(S.title){X.append(G("<div />").addClass("title ellipsis").html(S.title))}if(S.links&&S.title){U=15}else{if(S.links||S.title){U=35}else{U=50}}var T=R.find(".slideshow-wrapper .jcarousel-item-"+V+" img");var W=T.outerWidth();X.css({marginBottom:U+"px",width:W+"px",marginLeft:"auto",marginRight:"auto"});T.one("load",function(){var Y=G(this).outerWidth();X.css("width",Y+"px")});if(Q){X.fadeIn("slow",function(){R.click()})}else{X.show()}}function D(Q){if(Q.which==37){N.find(".jcarousel-prev").click()}else{if(Q.which==39){N.find(".jcarousel-next").click()}}if(Q.which>=33&&Q.which<=40){return false}}function C(Q){return false}function A(){var g=G(this);M=this;if(g.closest("#preview-overlay").length==1){return false}var U=g.attr("data-channelid"),c=g.closest(".photoPreviewBox"),h=g.attr("src");if(typeof (h)==="undefined"){h=g.attr("href")}if(L===false){L=(c.length>0)?c.index():g.index()}G(document).off("click","#slideshow-dialog .close-btn").on("click","#slideshow-dialog .close-btn",function(){N.dialog("close")});G(document).off("click",".thumbnails .image-wrapper").on("click",".thumbnails .image-wrapper",function(n,m){if(typeof m=="undefined"){m=true}G(".thumbnails .image-wrapper",N).addClass("dimmed");G(this).removeClass("dimmed");var l=Number(G(this).parent().attr("jcarouselindex"));N.data("thumbclick",true);vBulletin.gallery.slideshow.jcarousel("scroll",l,m);N.removeData("thumbclick")});G("body").css("overflow","hidden");var k=G(window).innerWidth(),X=G(window).innerHeight(),e=(k>768);var R=e?20:0,i=10,d=parseInt(k-(R*2),10),S=parseInt(X-(R*2),10);if(!e){N.css({borderRadius:0,borderWidth:0})}else{N.css({borderRadius:"",borderWidth:""})}var W=parseInt(d-(i*2),10),f=parseInt(S-(i*2),10),T=60,V=90,b=50,j=W-10,Z=parseInt(f-T-b-(i*3),10),a=parseInt(((Z/2)-21),10);E=j;H=Math.max(1,Math.floor(E/V));N.dialog({autoOpen:false,resizable:false,closeOnEscape:true,showTitleBar:false,modal:true,width:d,height:S,dialogClass:"slideshow-dialog",open:function(){G(".ui-widget-overlay").bind("click",function(){N.dialog("close")});G(document).off("keydown",D).on("keydown",D);G(document).off("mousewheel",C).on("mousewheel",C)},close:function(m,l){if(vBulletin.gallery.thumbnails){vBulletin.gallery.thumbnails.jcarousel("destroy")}if(vBulletin.gallery.slideshow){vBulletin.gallery.slideshow.jcarousel("destroy")}G("body").css("overflow","auto");G(document).off("keydown",D);G(document).off("mousewheel",C);M=false;L=false}});var Q=N.find(".slideshow-wrapper .slideshow-list");if(Q.length==0){Q=N.find(".slideshow-wrapper").append(G("<ul />").addClass("slideshow-list"))}N.dialog("open");window.vBulletin.loadingIndicator.show();var Y=g.closest(".js-slideshow__gallery-node").attr("data-node-id")||g.closest(".js-slideshow__gallery-node").attr("data-nodeid")||g.closest(".js-post-sm").attr("data-nodeid");G('<style type="text/css">			.slideshow .slideshow-wrapper > .jcarousel-clip-horizontal {				width: '+j+"px;				height: "+Z+"px;			}			.slideshow .thumbnails > .jcarousel-clip-horizontal {				width: "+E+"px;			}			.slideshow .slideshow-wrapper > .jcarousel-clip .jcarousel-item {				width: "+j+"px;				height: "+Z+"px;			}			.slideshow .jcarousel-item .image {				max-width: "+j+"px;				max-height: "+Z+"px;			}			.slideshow .slideshow-wrapper > .jcarousel-prev-horizontal,			.slideshow .slideshow-wrapper > .jcarousel-next-horizontal {				top: "+a+"px;			}		</style>").appendTo("head");vBulletin.gallery.slideshow=Q.jcarousel({wrap:null,animation:"slow",easing:"swing",scroll:1,rtl:G("html").attr("dir")=="rtl",initCallback:function(u,o){if(o=="init"){var m=g.closest(".b-content-entry-panel__content--gallery").find(".js-photo-postdata input");if(m.length>0){var l={photos:[]};m.filter('[name="filedataid[]"]').each(function(){var v={title:JShtmlEncode(m.filter('[name="title_'+this.value+'"]').val()),url:pageData.baseurl+"/filedata/fetch?filedataid="+this.value,thumb:pageData.baseurl+"/filedata/fetch?thumb=1&filedataid="+this.value};l.photos.push(v)});setTimeout(function(){I(l,u)},10)}else{var q={nodeid:Y};if(vBulletin.media&&vBulletin.media.MEDIA_USERID){q.userid=vBulletin.media.MEDIA_USERID}if(U){q.channelid=U}var n=parseInt(G(".albumSlideShowPageNo").val());if(!isNaN(n)){q.pageno=n}var r=parseInt(G(".albumSlideShowPerPage").val());if(!isNaN(r)){q.perpage=r}var p=G(".media-tab .media-filter-overlay .filter-options input:checked").val();if(p){q.dateFilter=p}if(!isNaN(L)){q.startIndex=L}var t=q;if(Y>0){t.startIndex=""}var s=JSON.stringify(t);if(F[s]){I(F[s],u)}else{G.post(vBulletin.getAjaxBaseurl()+"/filedata/gallery",q,function(v){if(!v&&h){v={photos:[{title:"",url:h,thumb:""}]}}F[s]=v;I(v,u)},"json").complete(function(){window.vBulletin.loadingIndicator.hide()})}}}},itemVisibleInCallback:{onBeforeAnimation:function(q,o,n,p){if(p=="init"){return false}var s=N.data("image-data");if(s){var l=s.photos.length;if(n>0){while(n>l){n-=l}}else{while(n<=0){n+=l}}K(N,s.photos[n-1],n,true);var r=Math.floor(H/2);var m=n-r;m=Math.max(m,1);vBulletin.gallery.thumbnails.jcarousel("scroll",m,true);if(!N.data("thumbclick")){G("#slideshow-dialog .thumbnails .image-wrapper").addClass("dimmed");vBulletin.gallery.thumbnails.jcarousel("get",n).find(".image-wrapper").removeClass("dimmed")}L=n-1}}}});return false}function I(U,T){if(!U||!U.photos){openAlertDialog({title:vBulletin.phrase.get("gallery_slideshow"),message:vBulletin.phrase.get("no_photos_found_or_post_deleted"),iconType:"warning"});return }N.data("image-data",U);P(T,U,N);K(N,U.photos[0],1);if(U.photos.length>1){vBulletin.gallery.thumbnails.jcarousel("get",1).find(".image-wrapper").removeClass("dimmed")}var R=L;var Q=parseInt(G(".albumSlideShowPerPage").val());if(!isNaN(Q)){R=L%Q}if(R>0){var S=vBulletin.gallery.slideshow.jcarousel("scroll",R+1,false);if(typeof S!="undefined"){N.find(".slideshow-wrapper").css("visibility","hidden");window.setTimeout(function(){G(".thumbnails .image-wrapper",N).eq(R).trigger("click",[false]);N.find(".slideshow-wrapper").css("visibility","")},0)}}window.vBulletin.loadingIndicator.hide()}function P(R,T,Q){R.size(T.photos.length);if(T.photos.length>1){G("#slideshow-dialog .thumbnails").show();J(T)}else{G("#slideshow-dialog .thumbnails").hide();Q.dialog("option","height","auto")}var S=R.first||1;G.each(T.photos,function(V,U){R.add(S+V,'<div class="image-wrapper"><a href="{0}"><img src="{0}" alt="{1}" class="image" /></a></div>'.format(U.url,U.htmltitle))})}function J(R){var Q=G("#slideshow-dialog .thumbnails .thumbnail-list");if(Q.length==0){Q=G("#slideshow-dialog .thumbnails").append(G("<ul />").addClass("thumbnail-list"))}vBulletin.gallery.thumbnails=Q.jcarousel({wrap:null,animation:"fast",easing:"swing",scroll:H,rtl:G("html").attr("dir")=="rtl",size:R.photos.length,initCallback:function(W,U){if(U=="init"){W.size(R.photos.length);G.each(R.photos,function(Z,Y){var X=(W.first||1)+Z;if(W.has(X)){return true}if(X>R.photos.length){return false}var a=G("<img />").addClass("thumbnail").on("load",function(){}).attr({src:Y.thumb,alt:""}).appendTo(G("<div />").addClass("image-wrapper dimmed"));W.add(X,a.parent().get(0))});var S=G("#slideshow-dialog .thumbnails .jcarousel-clip");var V=Math.min(W.size(),H)*90;var T=Math.min(E,V);S.css("width",T+"px");if(W.size()<=H){W.buttonNext.hide();W.buttonPrev.hide()}}},itemVisibleInCallback:{onBeforeAnimation:function(V,T,S,U){G("#slideshow-dialog .thumbnails").data("visible-range",{first:V.first,last:V.last})}}}).disableSelection()}function O(){if(M){var R=M;var Q=L;N.dialog("close");L=Q;A.call(R)}}function B(){G(document).off("click",".b-gallery-thumbnail-list__item").on("click",".b-gallery-thumbnail-list__item",A);N.addClass("js-no-responsive-resize");G(window).on("resize",G.debounce(300,O))}G(document).ready(B)})(jQuery);