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
(function(C){var B=window.History,A=B&&!!B.enabled;window.vBulletin=window.vBulletin||{};window.vBulletin.history=window.vBulletin.history||{};B.options.transformHash=false;vBulletin.history.instance=function(F){var E=true,D=!!F&&A;this.isEnabled=function(){return D};this.setDefaultState=function(I,J,G){if(D){E=false;var H=vBulletin.parseUrl(G);G=decodeURI(H.pathname)+decodeURIComponent(H.search);B.replaceState(I,J,G);E=true}};this.setStateChange=function(I,G){if(D){var H="statechange"+(G?"."+G:"");B.Adapter.bind(window,H,function(J){if(E){I.apply(this,C.makeArray(arguments))}})}};this.pushState=function(I,J,G){if(D){E=false;var H=vBulletin.parseUrl(G);G=decodeURI(H.pathname)+decodeURIComponent(H.search);B.pushState(I,J,G);E=true}};this.getState=function(){if(D){return B.getState()}};this.log=function(){if(D){B.log.call(window,arguments)}};if(D){if(!C(window).data("hashchange.history")){B.Adapter.bind(window,"hashchange.history",function(L){var H=location.hash,I,K;if(E&&H){K=H.match(/#post(\d+)/);if(K&&(Number(K[1])+"")===K[1]&&Number(K[1])>1&&C(H).length==0&&C(".conversation-content-widget").length){var J=vBulletin.parseUrl(location.href),G=[J.pathname,J.search,(J.search?"&":"?"),"p=",K[1],K[0]];location.replace(G.join(""))}else{setTimeout(function(){history.back()},0);I=vBulletin.scrollToAnchor(H);if(I){window.scrollTo(0,0)}}}})}C(window).data("hashchange.history",true)}return this}})(jQuery);