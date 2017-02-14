<?php if(!defined('VB_ENTRY')) die('Access denied.');
/*
 * Forum Runner
 *
 * Copyright (c) 2013 to Internet Brands, Inc
 *
 * License: http://www.forumrunner.com/lgpl30.txt
 *
 * http://www.forumrunner.com
 */

$vbulletin = vB::get_registry();
if (!is_object($vbulletin->db)) {
    exit;
}


define('MCWD', DIR . '/packages/forumrunner');

require_once(MCWD . '/support/Snoopy.class.php');
require_once(MCWD . '/support/JSON.php');
if (file_exists(MCWD . '/sitekey.php')) {
    require_once(MCWD . '/sitekey.php');
} else if (file_exists(MCWD . '/vb_sitekey.php')) {
    require_once(MCWD . '/vb_sitekey.php');
}

// You must have your valid Forum Runner forum site key.  This can be
// obtained from http://www.forumrunner.com in the Forum Manager.
if (!$mykey || $mykey == '') {
    exit;
}

 // Check to see if our prompt is disabled.  If so, exit.
if (!$vbulletin->options['forumrunner_redirect_onoff']) {
    return;
}

// We know we have a prompt enabled at this point.  Phone home for status.
$snoopy = new snoopy();
$snoopy->submit('http://www.forumrunner.com/forumrunner/request.php',
    array(
        'cmd' => 'checkstatus',
        'sitekey' => $mykey,
    )
);

$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
$out = $json->decode($snoopy->results);

if (!$out['success']) {
    // If request failed for any reason, do not change anything.
    return;
}

if ($out['data']['pub']) {
    // We are published and fine.
    return;
}

// We are unpublished.  Disable prompt.
$vbulletin->db->query_write("
    UPDATE " . TABLE_PREFIX . "setting
    SET value = 0
    WHERE varname = 'forumrunner_redirect_onoff'
");