<?php 
  set_time_limit(60*60); 
  $link = mysql_connect("localhost", "dokterbola", "oleOLE777dokterbola");
  mysql_select_db("dokterbola", $link);

  $id = $_GET['id'];
  $result = mysql_query("SELECT id_agent, nama, website FROM agent WHERE id_agent = $id ");    
  $rows = array();        //query
  while($r = mysql_fetch_array($result)) {
    $rows[] = $r;
  }
  $idagent = $rows[0]['id_agent'];
  $website = $rows[0]['website'];
  //$rows['statusweb'] = check($website);
/*
  $result = get_headers($website, 1);
  $status =  $result[0];
  $code = explode(" ", $status);
  $rows['statusweb'] = $status;
*/

  if (isDomainAvailible($website))
       {
               mysql_query("UPDATE agent SET nonaktif = 0 WHERE id_agent = $idagent ");
               $rows['statusweb'] = "1";
       }
       else
       {
               mysql_query("UPDATE agent SET nonaktif = 1 WHERE id_agent = $idagent ");
               $rows['statusweb'] = "0";
       }

       //returns true, if domain is availible, false if not
       function isDomainAvailible($domain)
       {
               //check, if a valid url is provided
               if(!filter_var($domain, FILTER_VALIDATE_URL))
               {
                       return false;
               }

               //initialize curl
               $curlInit = curl_init($domain);
               curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
               curl_setopt($curlInit,CURLOPT_HEADER,true);
               curl_setopt($curlInit,CURLOPT_NOBODY,true);
               curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

               //get answer
               $response = curl_exec($curlInit);

               curl_close($curlInit);

               if ($response) return true;

               return false;
       }

  
  echo json_encode($rows);

?>
