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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.CompMenuDropdown={};(function(D){var A=false;function B(){if(A){return }D(document).off("click",C).on("click",C);A=true}function C(I){var G=D(I.target),H=G.closest(".js-comp-menu-dropdown");if(!H.length){D(".js-comp-menu-dropdown").removeClass("b-comp-menu-dropdown--open")}}function F(I){var H=D(I.target).closest(".js-comp-menu-dropdown");if(H.is(".b-comp-menu-dropdown--open")){H.removeClass("b-comp-menu-dropdown--open")}else{H.addClass("b-comp-menu-dropdown--open")}var G=D(".js-comp-menu-dropdown").filter(function(){return(D(this).get(0)!=H.get(0))}).removeClass("b-comp-menu-dropdown--open");B();return false}function E(){var G=D(".js-comp-menu-dropdown__trigger").filter(function(){return(D(this).data("comp-menu-dropdown-initialized")!="1")});G.off("click",F).on("click",F).data("comp-menu-dropdown-initialized","1")}vBulletin.CompMenuDropdown.init=E;D(E)})(jQuery);