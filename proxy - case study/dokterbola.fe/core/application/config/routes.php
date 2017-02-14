<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'home';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;




$route['wags/:num'] = "/wags/detail/$1";
$route['wags/:num/:any'] = "/wags/detail/$1";

$route['berita'] = "/berita/index";
$route['berita/:num'] = "/berita/detail/$1";
$route['berita/:num/:any'] = "/berita/detail/$1";

$route['berita/:num/amp'] = "/berita/amp_detail/$1";
$route['berita/:num/:any/amp'] = "/berita/amp_detail/$1";

$route['berita/:num/:any/:any'] = "/berita/detail/$1";
$route['berita/:num/:any/:any/amp'] = "/berita/amp_detail/$1";
$route['berita/:any'] = "/berita/index/$1";
$route['berita/:any/:any/index'] = "/berita/index/$1";
$route['berita/:any/index'] = "/berita/index/$1";
$route['berita/:any/index/:num'] = "/berita/index/$1";


$route['(agen-bola|agents)'] = "/agen_bola";
$route['(agen-bola|agents)/voteme'] = "/agen_bola/voteme";
$route['(agen-bola|agents)/get_comments'] = "/agen_bola/get_comments";
$route['(agen-bola|agents)/like_comment'] = "/agen_bola/like_comment";


$route['(agen-bola|agents)/index/:num'] = "/agen_bola/index/$1";
$route['(agen-bola|agents)/:num'] = "/agen_bola/detail/$1";
$route['(agen-bola|agents)/:num/:any'] = "/agen_bola/detail/$1";

$route['(agen-bola|agents)/search'] = "/agen_bola/search";
$route['(agen-bola|agents)/search/:any'] = "/agen_bola/search/$1";
$route['(agen-bola|agents)/search/:any/index'] = "/agen_bola/search/$1";
$route['(agen-bola|agents)/search/:any/index/:num'] = "/agen_bola/search/$1";
$route['(agen-bola|agents)/search/:any/index/:num/:any'] = "/agen_bola/search/$1";

$route['(agen-bola|agents)/amp'] = "/agen_bola/amp";
$route['(agen-bola|agents)/index/:num/amp'] = "/agen_bola/amp/$1";
$route['(agen-bola|agents)/:num/amp'] = "/agen_bola/amp_detail/$1";
$route['(agen-bola|agents)/:num/:any/amp'] = "/agen_bola/amp_detail/$1";

$route['(agen-bola|agents)/([a-zA-Z]+)'] = "/agen_bola/index/$1";
$route['(agen-bola|agents)/([a-zA-Z]+)/index'] = "/agen_bola/index/$1";
$route['(agen-bola|agents)/([a-zA-Z]+)/index/:num'] = "/agen_bola/index/$1";

$route['statistik'] = "/statistik/index";
$route['statistik/:any'] = "/statistik/detail/$1";
$route['statistik/:any/:any'] = "/statistik/detail/$1";


$route['web'] = "/agen_bola/index";
$route['web/agen-bola'] = "/web/agen_bola";


