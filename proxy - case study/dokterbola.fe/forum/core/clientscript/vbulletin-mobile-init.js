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
$(document).bind("mobileinit",function(){$("div.ui-page").live("pagecreate",function(B,D){var A=0;var C=$("#dummylist li span",this);for(i=0;i<C.length;i++){A+=parseInt($(C[i]).text())}$(".notifications_total_count",this).html(A);if(A>0){$(".notifications_total",this).removeClass("hidden")}})});