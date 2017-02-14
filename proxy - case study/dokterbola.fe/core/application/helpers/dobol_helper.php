<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');
 
 
  function tendency_percentage($val) {
  	$total = 12;
  	$p = ($val/$total)*100;
  	return floor($p).'%';
  }

  function tendency_total($home_win = 0,$home_lose = 0,$home_draw = 0,$away_win = 0,$away_lose = 0,$away_draw = 0) {
  	$total = 12;
  	$win_home = $home_win*2;
  	$lose_home = $home_lose*0;
  	$draw_home = $home_draw*1;

  	$win_away = $away_win*2;
  	$lose_away = $away_lose*0;
  	$draw_away = $away_draw*1;

  	$home_total = $win_home+$lose_home+$draw_home;
  	$away_total = $win_away+$lose_away+$draw_away;
  	if($home_total+$away_total){
  		$data['home'] = round($home_total / ($home_total+$away_total) * 100);
  		$data['away'] = round($away_total / ($home_total+$away_total) * 100);
  	}else{
  		$data['home'] = 0;
  		$data['away'] = 0;
  	}
  	return $data;
  }
  
 function encode_url($string, $url_safe=TRUE)
	{
	   
	    $CI =& get_instance();
	    $ret = $CI->encryption->encrypt($string);

	    if ($url_safe)
	    {
	        $ret = strtr(
	                $ret,
	                array(
	                    '+' => '.',
	                    '=' => '-',
	                    '/' => '~'
	                )
	            );
	    }

	    return $ret;
	}
  function decode_url($string)
	{
	  
	    $CI =& get_instance();
	    $string = strtr(
	            $string,
	            array(
	                '.' => '+',
	                '-' => '=',
	                '~' => '/'
	            )
	        );

	    return $CI->encryption->decrypt($string);
	}



