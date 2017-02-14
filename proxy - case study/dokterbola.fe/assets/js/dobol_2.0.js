//base_url = 'http://192.169.203.131/';
window.__lc = window.__lc || {};
window.__lc.license = 8104921;
(function() {
  var lc = document.createElement('script'); lc.type = 'text/javascript'; lc.async = true;
  lc.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'cdn.livechatinc.com/tracking.js';
  var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(lc, s);
})();

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length);
        }
    }
    return "";
}

function show_custom_modal(text, style='default'){
    $("#custom_modal .modal-body").removeClass (function (index, css) {
      return (css.match (/(^|\s)alert-\S+/g) || []).join(' ');
    });
    $("#custom_modal .modal-body").addClass('alert-'+style);
    $("#custom_modal .modal-body").html(text);
    $("#custom_modal").modal('show');       
}

function get_uri(uri){
    var URL = window.location.host + window.location.pathname;
    var pathArray = URL.split('/');
    return pathArray[uri];
}


$("#recomend-wrapper").on('click', '.voteMe', function() {

  var voteId = this.id;
  var upOrDown = voteId.split('_'); 
  var cct = getCookie('csname');
  $.ajax({
    type: "post",
    url: window.location.protocol + "//"+get_uri(0)+"/"+get_uri(1)+"/voteme",
    dataType: "JSON",
    cache: false,       
    data:'idagent='+upOrDown[0] + '&upOrDown=' +upOrDown[1] +'&cstoken='+cct,
    beforeSend: function(){
      $('.voteMe').button('loading');
    },
    success: function(response){
      $('.voteMe').button('reset');        
      try{
        switch (response.status) {
            case "true":
                if(upOrDown[1] == "up"){
                  var newValue = parseInt($("#overall_score").text()) + 1;            
                  $("#overall_score").html(newValue);
                  $('#recomend-wrapper').html("<button id='"+upOrDown[0]+"_down' data-loading-text='Proses Validasi...' class='btn btn-block btn-lg btn-warning btn-recommend voteMe' >Batal Rekomendasi</button>");
                }else{
                  var newValue = parseInt($("#overall_score").text()) - 1;            
                  $("#overall_score").html(newValue);
                  $('#recomend-wrapper').html("<button id='"+upOrDown[0]+"_up' data-loading-text='Proses Validasi...' class='btn btn-block btn-lg btn-success btn-recommend voteMe' >Rekomendasikan</button>");
                }
                show_custom_modal(response.message, response.type);
                break;
            case "login":
                $("#login-required").modal('show');
                break;
            default:
                show_custom_modal(response.message, response.type);
        }

     

      }catch(e) {  
        show_custom_modal(response.message, response.type);
      }   
    },
    error: function(){            
        show_custom_modal(response.message, response.type);
    }
   });
});


$(".review-list").on('click', '.like_comment', function() {
  var id_review = $(this).parents().data("id");
  var cct = getCookie('csname');
  var like = $(this).data("like");
  var $t = $(this);
  $.ajax({
    type: "post",
    url: window.location.protocol + "//"+get_uri(0)+"/"+get_uri(1)+"/like_comment",
    dataType: "JSON",
    cache: false,       
    data:'id_review='+id_review + '&like=' +like +'&cstoken='+cct,
    success: function(response){
      try{
        switch (response.status) {
            case "true":   
                  var newValue = parseInt($t.find('.count_like').text()) + 1;            
                  $t.find('.count_like').html(newValue);
                 
                  show_custom_modal(response.message, response.type);
                break;
            case "login":
                $("#login-required").modal('show');
                break;
            default:
                show_custom_modal(response.message, response.type);
        }
      }catch(e) {  
        show_custom_modal(response.message, response.type);
      }  
    },
  });
});


function cycleImages(){
  var $active = $('#cycler .active');
  var $next = ($active.next().length > 0) ? $active.next() : $('#cycler img:first');
  $next.css('z-index',2);//move the next image up the pile
  $active.fadeOut(1500,function(){//fade out the top image
  $active.css('z-index',1).show().removeClass('active');//reset the z-index and unhide the image
    $next.css('z-index',3).addClass('active');//make the next image the top one
  });
}

var getComment = function(page){
    $("#loader").show();
    $.ajax({
        url:window.location.protocol + "//"+get_uri(0)+"/"+get_uri(1)+"/get_comments",
        type:'GET',
        data: {page:page,id_agent:get_uri(2)}
    }).done(function(response){
        $("#ajax_comments_wrap").append(response);
        $("#loader").hide();
        var num = $('#loadmore_comment').data("val") + 1;   
        $('#loadmore_comment').data('val', num);
      
        //scroll();
    });
};

var scroll  = function(){
    $('html, body').animate({
        scrollTop: $('#loadmore_comment').offset().top
    }, 1000);
};



$(document).ready(function() {
      function load_tipster() {
        var cct = getCookie('csname');
        $.ajax({
          type: "GET",
          url:window.location.protocol + "//"+get_uri(0)+"/tipster/ajax_list",
          cache: false      
          }).done(function(data){
                $('#data_tipster').html(data); 
                $('#load_tipster').hide();
          }).fail(function(xhr, status, error){
              console.log("Status: " + status + " Error: " + error);
              console.log(xhr);
          });
      };
      load_tipster();

      $('body').on('click', '.facebook-comment-bt', function() {
          $(this).next('.facebook-comments').slideToggle('fast');
          $(this).text(function(i, text){
                return text === "Show Comments" ? "Hide Comments" : "Show Comments";
            })
     
      });

      $('#reset_advance').on('click', function(e) {
          var forms = $(this).closest('form');
          forms.find('input[type=text], textarea').val("");
          forms.find('option').css("display","block");
          forms.find('select').prop('selectedIndex',0);
         
      });

      $('#search_product').on('change', function(e) {
          var selected = $(this).find('option:selected');
          var game = $('#search_game');
          var value = selected.data('game');
      
          if(value){
            $(game).find('option').css("display","none");
            $(game).find("option[value='"+value+"']").css("display","block");
          }else{
            $(game).find('option').css("display","block");
          }
      });

      $('#search_game').on('change', function(e) {
          var selected = $(this).find('option:selected');
          var product = $('#search_product');
          var value = selected.val();
          if(value){
            $(product).find('option').css("display","none");
            $(product).find("option[data-game='"+value+"']").css("display","block");
              
          }else{
            $(product).find('option').css("display","block");
          }
      
      });

      $('#search_promotion').on('change', function(e) {
          var selected = $(this).find('option:selected');
          var value = selected.val();
          var type = selected.data('type');
          var requirement = selected.data('requirement'); 

          switch(type){
            case 'persen' :
                          $(".note-search").css("display","none");
                          $(".note-persen").css("display","block");
                          $(".suffix-persen").css("display","table-cell");
                          $(".prefix-rupiah").css("display","none");
                          break;
            case 'currency' :
                          $(".note-search").css("display","none");
                          $(".note-currency").css("display","block");
                          $(".suffix-persen").css("display","none");
                          $(".prefix-rupiah").css("display","table-cell");
                          break;
            default :
                          $(".note-search").css("display","none");
                          $(".note-default").css("display","block");
                          $(".suffix-persen").css("display","none");
                          $(".prefix-rupiah").css("display","none");

          }

          if(type){
            $(".search-requirement").css("display","block");
          }else{
            $(".search-requirement").css("display","none");
          }    
      });

      $('.image-lightbox').on('click', function() {
        $('.image-preview').attr('src', $(this).data('src'));
        $('#image-modal').modal('show');
      });
      $('#textarea-register').summernote({
        height: 150,
        toolbar: [
          // [groupName, [list of button]]
          ['style', ['bold', 'italic', 'underline']],
     
        
          ['color', ['color']],
          ['para', ['ul', 'ol', 'paragraph']],
          ['insert', ['link', 'table', 'hr']]
       
        ],
        placeholder: 'Deskripsi atau promosi detail',
        callbacks: {
            onKeydown: function (e) { 
                var t = e.currentTarget.innerText; 
                if (t.trim().length >= 2000) {
                    //delete key
                    if (e.keyCode != 8)
                    e.preventDefault(); 
                } 
            },
            onKeyup: function (e) {
                var t = e.currentTarget.innerText;
                $('#max_description').text(2000 - t.trim().length);
            },
            onPaste: function (e) {
                var t = e.currentTarget.innerText;
                var bufferText = ((e.originalEvent || e).clipboardData || window.clipboardData).getData('Text');
                e.preventDefault();
                var all = t + bufferText;
                document.execCommand('insertText', false, all.trim().substring(0, 2000));
                $('#max_description').text(2000 - t.length);
            }
        }
      });
      $('#popup-prediction').popover();
      //detail agent page
      if(((get_uri(1) == "agents") || (get_uri(1) == "agen-bola")) && isFinite(get_uri(2)) ){
        getComment(0);
      }

      
      
      $("#submit_review").click(function(e){
        var logged = $(this).data('login');
        if(logged){
          $(this).parents('form').submit()
        }else{
          $("#login-required").modal('show');
        }
      });

      
      $("#loadmore_comment").click(function(e){
          e.preventDefault();
          var page = $(this).data('val');
          var max = $(this).data('max');
         
          if(((parseInt(page)+1)*5) >= parseInt(max) ){
            $(this).hide();          
          }  
          getComment(page);

      });

      $('.js-activated').dropdownHover().dropdown();
      //force set font-size in 'Ketentuan Promo'
      var element = $('#policyDiv').children();
      element.each(function (i) {
          if(parseInt($(this).css('font-size')) > 10){
              $(this).css('font-size','1.15em');  
          }
      });

      //define the image dimention wide or potrait
      $('#cycler').find('img').each(function(){
        var imgClass = (this.width/this.height >= 1) ? 'wide' : 'tall';
        $(this).addClass(imgClass);
       })

      setInterval('cycleImages()', 7000);

      //ads banner keep on screen
      var stickyNavTop = ($('#bannerL,#bannerR').offset().top + 177);
      var stickyNav = function(){
                          var scrollTop = $(window).scrollTop();
                          if (scrollTop > stickyNavTop) { 
                            $('#bannerL,#bannerR').addClass('sticky');
                          } else {
                            $('#bannerL,#bannerR').removeClass('sticky'); 
                          }
                      };
      stickyNav();
      $(window).scroll(function() {
        stickyNav();
      });
      //end ads banner
     

      //sticky navigation
      var stickyNavTop = $('nav').offset().top;
      var stickyNav = function(){
      var scrollTop = $(window).scrollTop();
            
      if (scrollTop > stickyNavTop) { 
          $('nav').addClass('sticky-nav');
      } else {
          $('nav').removeClass('sticky-nav'); 
      }
      };
       
      stickyNav();
       
      $(window).scroll(function() {
        stickyNav();
      });

});