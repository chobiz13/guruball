<?php
set_time_limit(60*60); 
$link = mysql_connect("localhost", "dokterbola", "oleOLE777dokterbola");
mysql_select_db("dokterbola", $link);        
$sql = mysql_query("SELECT id_agent, nama, website FROM agent WHERE website != '' AND status = '1' ORDER BY nama ");
$rows = array(); 
while($r = mysql_fetch_assoc($sql)) {
    $rows[] = $r;
}

?>
<html>
  <head>
    <script language="javascript" type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript">
      var list_web = <?php  echo json_encode($rows); ?>;
      var list_web_status;


      function checkWeb( index ){
        $('#update-button').fadeOut("slow");
        if( typeof(index)!=='undefined' ){
          if( list_web.length<(index+1) ) alert('Finish');
          DATA = 'id=' + list_web[index]['id_agent'];
          $.ajax({
            type: 'GET',
            url: 'api.php',

            data: DATA,
            dataType: 'json',
           
            success: function(data){
              var id = data[0]["nama"];            
              var vname = data[0]["website"];
              var statusvar = data["statusweb"]; 
              var statusweb;

              if (statusvar == "1"){
                statusweb = "Up and Running!"
                var item = $("<div class='listprogress' style='background:#b4e391;'><div class='nama'>"+id+"</div><div class='web'><a href="+vname+" target='_blank'>"+vname+"</a></div><div class='status'><span style='font-size:1em;color:#000;font-weight:bold'>"+statusweb+"</div><div class='clear'></div></div>").hide().fadeIn(1500); 
              
              }else{
                statusweb = "Website tidak bisa diakses atau terlalu lama";
                var item = $("<div class='listprogress' style='background:#fe9090;'><div class='nama'>"+id+"</div><div class='web'><a href="+vname+" target='_blank'>"+vname+"</a></div><div class='status'><span style='font-size:1em;color:#000;font-weight:bold'>"+statusweb+"</div><div class='clear'></div></div>").hide().fadeIn(1500); 
              
              }

              $('#output').append(item);

              index = index + 1;
              checkWeb(index);
            }
          });
        }else return false;
      }; 
    </script>

    <style>
      body{
        font-family: Arial;
        background: url(witewall_3.png) repeat;
      }
   
      .listprogress{
        display: block;
        padding:5px;
        border-top:1px solid #999;
      }
      .clear{
        clear:both;
      }
      .listprogress .nama, .listprogress .web, .listprogress .status{
        padding:5px;
        float: left;
      }
      .listprogress .nama{
        width: 125px;
      }
      .listprogress .web{
        width: 250px;
        color:#888;
        word-wrap:break-word;
      }
      #wrapper{
        
        background:#f5f5f5;
        padding:10px;
        font-size: 0.9em;
        min-height: 200px;
       -webkit-transition: all 500ms ease-out 1s;
        -moz-transition: all 500ms ease-out 1s;
        -o-transition: all 500ms ease-out 1s;
        transition: all 500ms ease-out 1s;

      }
      #outer-wrapper{
        width:600px;
        margin:10px auto;
        padding:10px;
        background: #E5E5E5;
        border:1px solid #CCC;
        box-shadow: 1px 0px 15px rgba(0,0,0,0.1);
      }
      #desc{
        color: #555;
        font-size: 0.95em;
        line-height: 1.2em;
        margin-bottom:10px;
      }
      #update-button{
        margin: 10px 0;
        padding:10px 20px;
        font-size: 1em;
        text-transform: uppercase;
        background: #606c88; /* Old browsers */
        background: -moz-linear-gradient(top,  #606c88 0%, #3f4c6b 100%); /* FF3.6+ */
        background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#606c88), color-stop(100%,#3f4c6b)); /* Chrome,Safari4+ */
        background: -webkit-linear-gradient(top,  #606c88 0%,#3f4c6b 100%); /* Chrome10+,Safari5.1+ */
        background: -o-linear-gradient(top,  #606c88 0%,#3f4c6b 100%); /* Opera 11.10+ */
        background: -ms-linear-gradient(top,  #606c88 0%,#3f4c6b 100%); /* IE10+ */
        background: linear-gradient(to bottom,  #606c88 0%,#3f4c6b 100%); /* W3C */
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#606c88', endColorstr='#3f4c6b',GradientType=0 ); /* IE6-9 */
        border:1px solid #293B66;
        color:#EEE;
        border-radius:5px;
        box-shadow:1px 0px 5px rgba(0,0,0,0.5);
        cursor: pointer;
        text-shadow:1px 0px 5px rgba(0,0,0,0.5);

      }
      #update-button:hover{
        background: #606c88; /* Old browsers */
        background: -moz-linear-gradient(top,  #606c88 0%, #515e82 100%); /* FF3.6+ */
        background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#606c88), color-stop(100%,#515e82)); /* Chrome,Safari4+ */
        background: -webkit-linear-gradient(top,  #606c88 0%,#515e82 100%); /* Chrome10+,Safari5.1+ */
        background: -o-linear-gradient(top,  #606c88 0%,#515e82 100%); /* Opera 11.10+ */
        background: -ms-linear-gradient(top,  #606c88 0%,#515e82 100%); /* IE10+ */
        background: linear-gradient(to bottom,  #606c88 0%,#515e82 100%); /* W3C */
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#606c88', endColorstr='#515e82',GradientType=0 ); /* IE6-9 */


      }

    </style>

  </head>
  <body>
    <div id="outer-wrapper">
    <div id="wrapper">
  <h2>Dokterbola Website Checker</h2>
  <div id="desc">
    Dokterbola website checker adalah halaman untuk meng-update database dokterbola module agents. Semua agen yang mempunya website akan dicek vailability-nya dan secara otomatis meng-update database agents.
  </div>
  <div id="output"></div>
    <input type="button" id="update-button" onclick="checkWeb(0)" value="update" />

  </div>
</div>



  </body>
</html>  
