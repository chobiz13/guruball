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
window.vBulletin=window.vBulletin||{};window.vBulletin.phrase=window.vBulletin.phrase||{};window.vBulletin.phrase.precache=window.vBulletin.phrase.precache||[];window.vBulletin.phrase.precache=$.merge(window.vBulletin.phrase.precache,["january","february","march","april","may","june","july","august","september","october","november","december","sunday_min","monday_min","tuesday_min","wednesday_min","thursday_min","friday_min","saturday_min","time","now","done","hour","minute"]);window.vBulletin.options=window.vBulletin.options||{};window.vBulletin.options.precache=window.vBulletin.options.precache||[];window.vBulletin.options.precache=$.merge(window.vBulletin.options.precache,[]);(function(){$.datepicker.regional.vb={monthNames:[vBulletin.phrase.get("january"),vBulletin.phrase.get("february"),vBulletin.phrase.get("march"),vBulletin.phrase.get("april"),vBulletin.phrase.get("may"),vBulletin.phrase.get("june"),vBulletin.phrase.get("july"),vBulletin.phrase.get("august"),vBulletin.phrase.get("september"),vBulletin.phrase.get("october"),vBulletin.phrase.get("november"),vBulletin.phrase.get("december")],dayNamesMin:[vBulletin.phrase.get("sunday_min"),vBulletin.phrase.get("monday_min"),vBulletin.phrase.get("tuesday_min"),vBulletin.phrase.get("wednesday_min"),vBulletin.phrase.get("thursday_min"),vBulletin.phrase.get("friday_min"),vBulletin.phrase.get("saturday_min")]};$.datepicker.setDefaults($.datepicker.regional.vb);$.timepicker.regional.vb={timeText:vBulletin.phrase.get("time"),currentText:vBulletin.phrase.get("now"),closeText:vBulletin.phrase.get("done"),hourText:vBulletin.phrase.get("hour"),minuteText:vBulletin.phrase.get("minute")};$.timepicker.setDefaults($.timepicker.regional.vb);$(".js-datepicker").not(".hasDatePicker").datetimepicker({showOn:"both",buttonImage:pageData.baseurl+"/images/calendar-blue.png",buttonImageOnly:true,ampm:true})})();