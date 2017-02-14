<?php if (!defined('VB_ENTRY')) die('Access denied.');
/*========================================================================*\
|| ###################################################################### ||
|| # vBulletin 5.2.3 - Licence Number LC451E80E8
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2016 vBulletin Solutions Inc. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/

if (!isset($GLOBALS['vbulletin']->db))
{
	exit;
}

// display the credits table for use in admin/mod control panels

//not sure why this is a form -- we don't have any way to submit it
//however removing the form header destroys the formatting (which
//may be why) so we'll "fix" it and leave it.  Its not a problem
//for the modcp since the link doesn't go anywhere anyway.
print_form_header('admincp/index', 'home');
print_table_header($vbphrase['vbulletin_developers_and_contributors']);
print_column_style_code(array('white-space: nowrap', ''));

print_label_row('<b>' . $vbphrase['software_developed_by'] . '</b>', '
	<a href="http://www.vbulletin.com/" target="vbulletin">vBulletin Solutions, Inc.</a>,
	<a href="http://www.internetbrands.com/" target="vbulletin">Internet Brands, Inc.</a>
', '', 'top', NULL, false);

print_label_row('<b>' . $vbphrase['project_management'] . '</b>', '
	Marjo Mercado,
	Thong Nguyen
', '', 'top', NULL, false);

print_label_row('<b>' . $vbphrase['development_lead'] . '</b>', '
	Kevin Sours
', '', 'top', NULL, false);

print_label_row('<b>' . $vbphrase['development'] . '</b>', '
	Abel Lawal,
	David Grove,
	Francisco Aceves,
	Jin-Soo Jo,
	Nicolas Acerenza,
	Paul Marsden
', '', 'top', NULL, false);

print_label_row('<b>' . $vbphrase['product_management_user_experience_visual_design'] . '</b>', '
	Neal Sainani,
	Olga Mandrosov,
	Joe Rosenblum,
	John McGanty
	', '', 'top', NULL, false);

print_label_row('<b>' . $vbphrase['qa'] . '</b>', '
	Allen H. Lin,
	Meghan Sensenbach,
	Yves Rigaud,
	Sebastiano Vassellatti
', '', 'top', NULL, false);

print_label_row('<b>' . $vbphrase['documentation'] . '</b>', '
	Wayne Luke
', '', 'top', NULL, false);

print_label_row('<b>' . $vbphrase['bussines_operations_management_and_customer_support'] . '</b>', '
	John McGanty,
	Christine Tran,
	Wayne Luke,
	Lynne Sands,
	Joe Dibiasi,
	George Liu,
	Yves Rigaud,
	Dominic Schlatter,
	Aakif Nazir,
	Mark Bowland,
	Trevor Hannant,
	Joshua Gonzales
', '', 'top', NULL, false);

print_label_row('<b>' . $vbphrase['special_thanks_and_contributions'] . '</b>', '
	Abraham Miranda,
	Ace Shattock,
	Adam Bloch,
	Adrian Sacchi,
	Ahmed Al-Shobaty,
	Alan Ordu&ntilde;o,
	Alexander Mueller,
	Allen Smith,
	Aman Singh,
	Anders Pettersson,
	Andew Simmons,
	Andrew Clarke,
	Anthony Falcone,
	Art Andrews Jr,
	Barry Chertov,
	Blake Bowden,
	Brad Amos,
	Brad Szopinski,
	Brandon Sheley,
	Brian Gunter,
	Campos Santos,
	Carl David Birch,
	Chad Billmyer,
	Chase Hausman,
	Chase Webb,
	Chen Xu,
	Chris Dildy,
	Chris Riley,
	Chris Van Dyke,
	Christian Hoffmann,
	Christos Teriakis,
	Danco Dimovski,
	Daniel Fatkic,
	Daniel Lee,
	David Gerard Hopwood,
	Darren Gordon,
	Domien Brandsma,
	Dominic Schlatter,
	Drew Pomerleau,
	Dylan Wheeler,
	Edwin Brown,
	Emon Khan,
	Eric Sizemore,
	Fabian Schonholz,
	Fernando Varesi,
	Fillip Hannisdal,
	Freddie Bingham,
	Gavin Clarke,
	Geoff Carew,
	George Boone,
	Glenn Vergara,
	Gorgi Gichevski,
	Gregg Hartling,
	Hani Saad Alazmi,
	Hartmut Voss,
	Iain Kidd,
	Ivan Anfimov,
	Janusz Mocek,
	Jarvis Ka,
	Jaume L&oacute;pez,
	Jim Dudek,
	John Sandells,
	John Waltz,
	Jon Dickinson,
	Jorge Tiznado,
	Joseph DeTomaso,
	Juan Carlos Muriente,
	Kamal Saleh,
	Kareem Ashur,
	Kevin Hynes,
	Kevin Kivlehan,
	Kevin Wilkinson,
	Kira Lerner,
	Kostas Skiadas,
	Kyle Furlong,
	Kym Farnik,
	Les Hill,
	Lionel Martelly,
	Lisa Swift,
	Marc Stridgen,
	Marco Mamdouh Fahem,
	Marcus Kielmann,
	Mark Bowland,
	Mark Hennyey,
	Mark Jean,
	Mark Stroman,
	Matthew Sealey,
	Mattia Sparacino,
	Maurice De Stefano,
	Michael Biddle,
	Michael Lavaveshkul,
	Michael Matthews,
	Mike Fara,
	Mike Ford,
	Milad Kaleh,
	Miner,
	Neal Parry,
	Nicolas Boileau,
	Nuno Santos,
	Pam Ellars,
	Paul Holbrook,
	Pawel Grzesiecki,
	Pieter Verhaeghe,
	Rafael Reyes Jr,
	Ranga Basuru Thenuwara,
	Refael Iliaguyev,
	Rick Frerichs,
	Rishi Basu,
	Rob Collyer,
	Robert G Plank,
	Robert White,
	Ryan Smith,
	Sal Colascione,
	Steven Burke,
	Steven Lawrence,
	Sven Keller,
	Tadeo Valencia,
	Teascu Dorin,
	Ted Sendinski,
	Theodore Phillips,
	Todd A. Hoff,
	Vincent Scatigna,
	Vladimir Metelitsa,
	William Golighty,
	Xiaoyu Huang,
	Zachery Woods,
	Zafer Bahadir,
	Zoltan Szalay
', '', 'top', NULL, false);

print_table_footer();

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 22:13, Thu Sep 8th 2016
|| # CVS: $RCSfile$ - $Revision: 88204 $
|| #######################################################################
\*=========================================================================*/
