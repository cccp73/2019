//
console.log('comnews v1.0');
if (jQuery.cookie("force-full-version") == "1"){  
  jQuery('#mobile_css').remove();
  console.log(jQuery('body').css('display'));
  jQuery('#force-mobile, .mobile-icon-black').css('display','block');
  console.log(jQuery('body').css('display'));
} 
jQuery('body').css('display','block');

(function ($) {
    Drupal.behaviors.comnewsBehavior = {
      attach: function (context, settings) {
        // картинки к обложкам стандарта
        $('.issue-cover .cover').each(function(){ $(this).css('background-image',"url('"+$(this).attr('data-cover')+"')");});
        
        // внешние ссылки
        $.extend($.expr[':'],{
            external: function(a,i,m) {
            if(!a.href) {return false;}
            return a.hostname && a.hostname !== window.location.hostname;
            }
        });
        $("a:external").each(function(){this.target='_blank'});
      
        // mailto
        jQuery('a[href^="mailto:"]').each(function(){
          try{
          var h = jQuery(this).attr("href").split(':')[1];
          var d = h.split("@")[1].split('.');
          if(d.length ==1) {
            jQuery('<span>'+h+'</span>').insertBefore(jQuery(this));
            jQuery(this).remove();
          }
        } catch (err) {}
          
        });
        
        

      }
    };
  })(jQuery);

function isMobile(){
  let res = jQuery('.h-caption.mobile').css('display') == 'block';
  return res;
}

(function ($) {$(function(){ 

    
    /**/
    if($('p').text().indexOf('%countdown-')!=-1){
      $('p').each(function(){$(this).html($(this).html().replace(/%countdown-([^%]*)%/i,'<span class="countdown">$1</span>'));});
    }
    if($('.countdown').length){
      $('.countdown').each(function(){
        let ts = new Date($(this).text());
        $(this).text('');
        $(this).countdown({
		      timestamp	: ts,
		      callback	: function(days, hours, minutes, seconds){}
        });
      });
    }
    /**/
    if($('.level2-megafon-cloud .video a').length) {
      if(!$('#ytifapi').length) $('head').append('<script id="ytifapi" src="https://www.youtube.com/iframe_api"></script>');
      $('.level2-megafon-cloud .video a').on('click',function(evt){
        
        let parent = $(this).parent();
        if( !/Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
          
          if($('#youtube',parent).length == 0){
            parent.append('<div id="ytbg"></div><div id="youtube"><div id="player"></div></div>');
            $('#ytbg',parent).on('click',function(){ $('#youtube, #ytbg',parent).remove();});
            
            let obj = $(this);
            let id = '';
            if (obj.attr('href').split('/')[3] != 'channel'){
                id = obj.attr('href');
                if (id.split('?').length > 1){
                  id = id.split('?')[1];
                  var ids = id.split('&');
                  for (var i in ids){ var n = ids[i].split('='); if (n[0] == 'v') {id = n[1]; break;}}
                } else {
                  id = id.split('/')[3];
                }
            }
            let player;
              player = new YT.Player('player', {
              height: '540',
              width: '960',
              videoId: id,
              events: {
                'onReady': function(event) {
                  $('#youtube #player').css('width','100%')
                  event.target.playVideo();
                  let player = $('#player')[0];
                  let requestFullScreen = player.requestFullScreen || player.mozRequestFullScreen || player.webkitRequestFullScreen;
                  if (requestFullScreen) {
                    requestFullScreen.bind(player)();
                  }
                }
              }});
        
          }
        } else {
          window.open('https://youtu.be/'+id,'_blank')
        }	
        (evt.preventDefault) ? evt.preventDefault() : evt.returnValue = false;
        return false;
      });
    }
    $('.level2-megafon-cloud .main-column').hyphenate();
    /**/
    console.log('======');
    $('.v-table').each(function(){
      
      let table = $(this);
      initVTable(table);
			
    });
    function initVTable(table){

      if(table.hasClass('inited')) return;

      let pW = table.parent().width();
			let tW = table.width();
			let xx = 0;
			
			let header = $('thead tr',table).html();
			$('tr td:first-child,tr th:first-child',table).each(function(){
				if($(this).text() == '%header%'){
					$(this).parent().html(header);
				}
      });
      //console.log(pW,tW);
			let r1Width = $($('th',table)[0]).width()+10;
			if(tW > pW){
        //$('td',table).css('min-width','150px');
        
				$('th,td',table).each(function(){
					$(this).width($(this).width());
					$(this).height($(this).height());
        });
        $('tbody th',table).each(function(){
					$(this).height($(this).height()+1);
        });
        $('thead th',table).each(function(){
					$(this).css('height','auto');
        });
        //$('tr:first-child th:first-child',table).height($('tr:first-child th:first-child',table).height()+1);
        
        $('tbody tr',table).each(function(){
          let tr = $(this);
          for(let i = 0; i < $('td',tr).length;i++){
            $($('td',tr)[i]).attr('title',$($('thead tr th')[i+1]).text());
            
          }
        });
        $('tr',table).each(function(){
          let tr = $(this);
          for(let i = 0; i < $('td,th',tr).length;i++){
            $($('td,th',tr)[i]).addClass('c-'+i);
          }
        });
        let ul = '';
        for(let i = 0; i < $('thead th',table).length;i++){
          if(i>0){
            ul = ul + '<li><label><input type="checkbox" value="c-'+i+'" checked/> '+$($('thead th',table)[i]).html()+'</label></li>';
          }
        }
        table.parent().parent().prepend('<ul>'+ul+'</ul>');
        $('ul li input',table.parent().parent()).on('click',function(){
           
          if($(this).is(':checked')) 
              $('.'+$(this).val(),table).show(); 
          else {
            if($('input:checked',$(this).parent().parent().parent()).length > 0) $('.'+$(this).val(),table).hide(); 
            else $(this).prop('checked', true);
          }
        });

				$('tr th:first-child',table).addClass('fixed');
				table.attr('unselectable', 'on').css('user-select', 'none').on('selectstart', false).css('margin-left',r1Width+'px')
        let d = 1;
        /*
				let cid = setInterval(function(){
					let x = table.parent().scrollLeft();
					if(x + d > 100) d = -d;
					if(x + d < 0) d = -d;

					table.parent().scrollLeft(x+d);    
					//setTimeout(function(){table.parent().scrollLeft(x+100);},10);
					//setTimeout(function(){table.parent().scrollLeft(x-100);},500);
					//setTimeout(function(){table.parent().scrollLeft(x);},900);
				},50);
        */
        
				table.parent().on('mousedown',function(e){
					if(tW > pW){
						let parentOffset = $(this).parent().offset(); 
						xx = e.pageX - parentOffset.left;
						$(this).css('cursor','grabbing');
						//clearInterval(cid);
					}
				}).css('cursor','grab').on('mouseup',function(e){
						$(this).css('cursor','grab');
				}).on('mouseleave',function(e){
					$(this).css('cursor','grab');
				});
				table.parent().on('mousemove',function(e){
					
					if(tW > pW && $(this).css('cursor') == 'grabbing'){
						let parentOffset = $(this).parent().offset(); 
						let relX = e.pageX - parentOffset.left;
	
						let x1 = table.parent().scrollLeft();
						let x2 = x1 + (xx - relX);
						table.parent().scrollLeft(x2);
						
						xx = relX;
					}
        });
        table.addClass('inited');
			}
      
    }

    /**/
    if($('.level2-208312').length){
      $('.level2-208312 #tab-header li').on('click',function(){
        $('.level2-208312 .tab').hide();
        $('.level2-208312 #p'+$(this).attr('id')).show();
        if($('.level2-208312 #p'+$(this).attr('id')+' .v-table').length){
          let table = $('.level2-208312 #p'+$(this).attr('id')+' .v-table');
          console.log(table);
          initVTable(table);
        }
      });
      let ul = '';
        for(let i = 0; i < $('.map-container p').length;i++){
          if(i>0){
            ul = ul + '<li><label><input type="checkbox" value="m'+i+'" checked/> '+$('img',$('.map-container p')[i]).attr('title')+'</label></li>';
            $('img',$('.map-container p')[i]).attr('title','');
          }
        }
        $('.map-container').prepend('<ul>'+ul+'</ul>');
        $('ul li input',$('.map-container')).on('click',function(){
          console.log($(this).val());
          if($(this).is(':checked')) 
              $('#'+$(this).val(),$('.map-container')).show(); 
          else 
              $('#'+$(this).val(),$('.map-container')).hide(); 
        });
    }

    /* */

    /* digital chelyabinsk  */ 
    if($('.level2-207904').length){
      let i = 0;
      $('table.dch-hidden tr').each(function(){
        let tr = $(this);
        //let gParams = $($('td:nth-child(1)',tr.parent())[0]).text().trim().split(',');
        
        if($('td:nth-child(1)',tr).text().trim() != ''){
          
          const params = $('td:nth-child(1)',tr).text().trim().split(',');
          let color = ' color:#fff; ';
          let weight1 = ' font-weight:500; ';
          let weight2 = ' font-weight:300; ';
          if(params[1]> 1000) { color = ' color:#000; '; weight1 = ' font-weight:600; '; weight2 = ' font-weight:500; ';}
          $('#dch-map').append('<div class="dch-col1" style="right:'+params[0]+'px;top:'+params[1]+'px;width:'+params[2]+'px;'+color+weight1+'">'+$('td:nth-child(4)',tr).text()+'</div>');
          $('#dch-map').append('<div id="i-'+i+'" class="dch-col2" style="left:'+(parseInt(params[3],10)-50)+'px;top:'+params[4]+'px;width:'+(parseInt(params[5],10)+50)+'px;'+color+weight2+'">'+$('td:nth-child(3)',tr).text()+'</div>');
          $('#i-'+i).attr('data-row1',$('td:nth-child(2)',tr).text());
          $('#i-'+i).attr('data-row2',$('td:nth-child(5)',tr).text());
          i++;
        }
      });
      $('.dch-col2').on('mouseenter', function(){
        
        $('#dch-dscr').remove();
        let td = $(this);
        $('#dch-map').append('<div id="dch-dscr"><div id="row1">'+td.attr('data-row1')+'</div><div id="row2">'+td.attr('data-row2')+'</div></div>');
        $('#dch-dscr').css('top',(parseInt(td.css('top'),10)+13)+'px').css('right',(1200 - parseInt(td.css('left'),10)+30)+'px').show();
         
      });
      $('.dch-col2').on('mouseleave', function(){
        $('#dch-dscr').remove();
      });
    }
    /* 5 star chelybinsk*/
    if($('.level2-207906').length){
      $('#dch5s-desktop div').on('mouseenter',function(){
        let c = $(this).attr('class');
        $('#dch5s-desktop img').css('opacity','0');
        $('#dch5s-desktop img.'+c).css('opacity','1');
      });
      $('#dch5s-desktop div').on('mouseleave',function(){
        let c = $(this).attr('class');
        $('#dch5s-desktop img.'+c).css('opacity','0');
        $('#dch5s-desktop img.i0').css('opacity','1');
      });
      let i = 1;
      $('.dch-hidden tr').each(function(){
        let tr = $(this);
        $('#dch5s-mobile').append('<div class="est e'+i+'">'+$('td:nth-child(1)',tr).text()+'</div>');
        $('#dch5s-mobile').append('<div class="star s'+i+'" rel="'+i+'">'+$('td:nth-child(2)',tr).text()+'</div>');
        $('#dch5s-mobile #dch-tab').append('<div class="txt p'+i+'">'+$('td:nth-child(3)',tr).html()+'</div>');
        i++;
      });
      $('#dch5s-mobile .star').on('click',function(){
        let i = $(this).attr('rel');
        $('#dch-tab').addClass('opened')
        $('#dch-tab .txt').hide();
        $('#dch-tab .p'+i).show();    

      });
      $('#dch-tab .dch-close').on('click',function(){
        $('#dch-tab').removeClass('opened');
      });
    }



    if($('#remote-work').length){
      $('#remote-work blockquote>h2,#remote-work blockquote>h3').on('click',function(){
        //$('#remote-work blockquote>h2,#remote-work blockquote>h3').removeClass('opened');
        $(this).toggleClass('opened');
        if($(this).hasClass('opened')){
            $([document.documentElement, document.body]).animate({
              scrollTop: $(this).offset().top - 100
            }, 1000);  
        }
      });
      $('#remote-work blockquote>h3').each(function(){
        $(this).parent().addClass('inner');
        if($('h2:first-child',$(this).parent().next()).length || $(this).parent().next()[0].tagName == 'H2') $(this).parent().addClass('last');
      });
      $('#remote-work table table tr:nth-child(2)').each(function(){
        let td = $(this);
        td.parent().addClass('adv');
        $('tr:nth-child(1)',$(this).parent()).on('click',function(){
          td.toggle();
          td.parent().toggleClass('opened');
        });
      });
    }  
    
    if($('#hp-review-04-2020').length){
      let m = '#m1';
      
      $("#hp-review-04-2020 .section-map div").on("mouseenter",function(){
        $(m).hide();
        console.log($(this).attr("rel"));
        $("#"+$(this).attr("rel")).show();
      });
      $("#hp-review-04-2020 .section-map div").on("mouseleave",function(){
        $("#"+$(this).attr("rel")).hide();
        $(m).show();
      });
      
      $("#hp-review-04-2020 .section-map div").on("click",function(){
        $("#hp-review-04-2020 .section-map img").hide();
        m = "#" + $(this).attr("rel");
        $(m).show();
      });
    }
    if($('#phpbb').length > 0){
        let id = $('#phpbb').attr('data-node-nid');
        let type = $('#phpbb').attr('data-node-type');
        let date = $('#phpbb').attr('data-node-date');
        let comments = $('#phpbb').attr('data-node-comments');
        let mid = $('#phpbb').attr('data-node-mid');
        let topic = $('#phpbb').attr('data-node-topic');
        $('#phpbb').load('/comments/'+id+'?type='+type+'&date='+date+'&comments='+comments+'&mid='+mid+'&topic='+topic,'',function(){ 
            console.log(id + ' - loaded');
            renderVotes();
        });
        
    }
    if(!$('#node-207044').length){
      $('.node-txt a[href^="https://www.facebook.com"], .node-txt a[href^="http://www.facebook.com"], .node-txt a[href^="https://facebook.com"], .node-txt a[href^="http://facebook.com"]').each(function(){
        let obj = $(this);
        let href = obj.attr('href');
        let tmp = href.split('/');
        if(tmp[4] == 'videos'){
          obj.parent().html('<div class="cc-yt-container"><iframe class="cc-youtube" width="98%" height="'+340+'" src="https://www.facebook.com/plugins/video.php?href='+href+'" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe></div>');
        }
        
      });

      $('.node-txt a[href^="https://www.youtube.com"],.node-txt a[href^="http://www.youtube.com"],.node-txt a[href^="https://youtube.com"],.node-txt a[href^="http://youtube.com"],.node-txt a[href^="http://youtu.be"],.node-txt a[href^="https://youtu.be"]').each(function(){

          
            var obj = $(this);
            if (obj.attr('href').split('/')[3] != 'channel'){
              let href = obj.attr('href');
              
              if(href.indexOf('noplayer') == -1){
              
                obj.attr('rel',obj.attr('href'));

                var title = obj.attr('title');

                var id = obj.attr('rel');
                if (id.split('?').length > 1){
                  id = id.split('?')[1];
                  var ids = id.split('&');
                  for (var i in ids){ var n = ids[i].split('='); if (n[0] == 'v') {id = n[1]; break;}}
                } else {
                  id = id.split('/')[3];
                }
                //obj.attr('href','javascript:void(0);');
                //obj.css('display', 'block');
                //obj.css('margin','0px auto');
                //obj.css('width','98%');

                obj.parent().html('<div class="cc-yt-container"><iframe class="cc-youtube" width="98%" height="'+340+'" src="//www.youtube.com/embed/'+id+'" frameborder="0" allowfullscreen></iframe></div>');
              }

            }
            
      });
      $('iframe.cc-youtube').each(function(){
        let w = parseInt($(this).width(),10);
        $(this).height(w/1.77);
      });
    }
    /******/
    if($('#remote-work').length){
      $('#remote-work a[href^="https://www.facebook.com"], #remote-work a[href^="http://www.facebook.com"], #remote-work a[href^="https://facebook.com"], #remote-work a[href^="http://facebook.com"]').each(function(){
        let obj = $(this);
        let href = obj.attr('href');
        let tmp = href.split('/');
        if(tmp[4] == 'videos'){
          obj.parent().html('<div class="cc-yt-container"><iframe class="cc-youtube" width="370" height="400" src="https://www.facebook.com/plugins/video.php?href='+href+'" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe></div>');
        }
        
      });

      $('#remote-work a[href^="https://www.youtube.com"],#remote-work a[href^="http://www.youtube.com"],#remote-work a[href^="https://youtube.com"],#remote-work a[href^="http://youtube.com"],#remote-work a[href^="http://youtu.be"],#remote-work a[href^="https://youtu.be"]').each(function(){
            var obj = $(this);
            if (obj.attr('href').split('/')[3] != 'channel'){
              let href = obj.attr('href');
              if(href.indexOf('noplayer') == -1){
                obj.attr('rel',obj.attr('href'));
                var title = obj.attr('title');
                var id = obj.attr('rel');
                if (id.split('?').length > 1){
                  id = id.split('?')[1];
                  var ids = id.split('&');
                  for (var i in ids){ var n = ids[i].split('='); if (n[0] == 'v') {id = n[1]; break;}}
                } else {
                  id = id.split('/')[3];
                }
                obj.parent().html('<div class="cc-yt-container"><iframe class="cc-youtube" width="370" height="400" src="//www.youtube.com/embed/'+id+'" frameborder="0" allowfullscreen></iframe></div>');
              }
            }
      });
      //setTimeout(function(){
      $('iframe.cc-youtube').each(function(){
        let w = parseInt($(this).width(),10);
        console.log(w);
        $(this).height(w/1.77);
      });
      //}, 1000);
    }
    /*****/
    $('#srch-text').on('blur',function(){ setTimeout(function(){$('.srch-panel').removeClass('opened');},800);});
    $('.srch-icon-black').on('click',function(){ 
      if(!$('.srch-panel').hasClass('opened')){
        $('.srch-panel').addClass('opened'); 
        $('#srch-text').focus();
      } else {
        if($('#srch-text').val().length == 0){
          $('.srch-panel').removeClass('opened'); 
        } else {
          $('.srch-panel form')[0].submit();
        }
      }
    });  
    $('.logo-calendar .c-header').bind('mouseenter',function(){ $('.logo-calendar .c-body').show();});
    $('.logo-calendar').bind('mouseleave',function(){ $('.logo-calendar .c-body').hide();});
    //jQuery.get('/c.php',{},assignCalendarCntrls);
    if (document.getElementById('big-calendar')){
      jQuery.get('/c.php',{n:'1',d:jQuery('#big-calendar').attr('rel')},assignBigCalendarCntrls);
    }


    // last commented
    if($('#last-comments').length > 0){
      let qnt = $('#last-comments .hp-block-body div').attr('data-qnt');
      $('#last-comments .hp-block-body').load('/comments/last/'+qnt,'',function(){ 
          console.log('last'+ qnt + ' - loaded');  
      });
      
    }

    // de image caption
    $('.node-de.node-interview .field-name-image .image-caption').each(function(){
      $(this).appendTo('.node-de .node-header');
    });
    $('.node.node-article .field-name-image .image-caption').each(function(){
      //$(this).css('padding-left','380px').css('font-size','18px').css('padding-top','10px').css('font-weight','400');
      //$(this).insertBefore('.node .node-header .clear');
    });


    $('#page-standart td[bgcolor="#04024E"]').each(function(){
      let papa = $(this).parent().parent().parent();
      $('td,th',papa).css('border','none');
      papa.css('width','100%');
      $('.line',papa).css('padding','0px');
    });


    let tagmenuId1 = null;
    let tagmenuId2 = null;
    $('header.navbar #tags-menu .ham').on('mouseenter',function(){
      tagmenuId1 = setTimeout(function(){$('header.navbar #tags-menu').addClass('opened'); },300);
      clearTimeout(tagmenuId2);
    });
    $('header.navbar #tags-menu .ham').on('mouseleave',function(){
      clearTimeout(tagmenuId1);
      tagmenuId2 = setTimeout(function(){$('header.navbar #tags-menu').removeClass('opened');},300);
    });

    if($('.node-old .node-txt').length > 0){
      $('.node-old .field-name-field-bigimg').prependTo('.node-old .node-txt .field-name-body');
      $('.field-name-field-bigimg .imglabel').hyphenate();
      $('.content-img span').hyphenate();
    }
    $('.image-caption').hyphenate();
    $('.field-name-image-text').hyphenate();
    $('.hp-block .node-person, .person').hyphenate();
    $('.hp-block .node-text').hyphenate();
    $('#author-header span').hyphenate();
    $('.br-person .job').hyphenate();
    if($('.node-old .node-txt table.left').length > 0){
      //$('.node-old .node-txt table.left').prependTo('.node-old .node-txt');
      $('.node-old.node--oldarticle .node-txt>table.left tr:nth-child(2) td').hyphenate();
    }
    $('#node-205825 table td a').hyphenateURL();

    /******************************** */
    if($('.node-wrapper .sharebuttons').length > 0){
      $('#sharebuttons-up').prependTo('.node-txt .field-name-body');
      $('.node-txt .field-name-body').append('<div id="sharebuttons-dn" class="sharebuttons">'+$('#sharebuttons-up').html()+'</div>');

      if($('.node--oldarticle').length > 0 && $('.node--oldarticle .field-name-body .right, .node--oldarticle .field-name-field-date').length == 0){
        $('#sharebuttons-up').hide();
      }
      $('body').append('<script src="https://yastatic.net/es5-shims/0.0.2/es5-shims.min.js"></script><script src="https://yastatic.net/share2/share.js"></script>');
    }
    if($('.level2-207352').length){
      $('#sharebuttons-up').insertBefore($('h2.228bf8a64b8551e1'));
      
      let start = new Date('2020-06-04T13:00:00+0300');
      let end = new Date('2020-06-04T14:30:00+0300');
      let interval = setInterval(function(){
          let now = new Date();
          if( now.getTime() > start.getTime() /*&& now.getTime() < end.getTime()*/){
            console.log(now, '=====++++++=====');
            $('#countdown-container').html('<iframe allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="" frameborder="0" height="439" src="https://www.youtube.com/embed/62pv3W0XydI" width="780"></iframe>');
            clearInterval(interval);
          }
      },2000);
    }
    /******************************** */

    //about 
    if($('.path-about').length){
      $('.page .field--name-body p#b1').html('');
      $('#hidden-pool .bn#bnAbout1').appendTo('.page .field--name-body p#b1');
      $('.page .field--name-body p#b2').html('');
      $('#hidden-pool .bn#bnAbout2').appendTo('.page .field--name-body p#b2');
      $('.page .field--name-body p#b3').html('');
      $('#hidden-pool .bn#bnAbout3').appendTo('.page .field--name-body p#b3');
      $('.page .field--name-body p#b4').html('');
      $('#hidden-pool .bn#bnAbout4').appendTo('.page .field--name-body p#b4');
      $('.page .field--name-body p#maps').html('').insertAfter('.page .field--name-body');
      $('#hidden-pool #maps').appendTo('.page p#maps');
      
    }
    if($('.path-node-205929').length){
      $('#hidden-pool #logos-rw').appendTo('.page-header');
    }

    if($('.node-txt .field-name-body p').length > 4){
      $('#hidden-pool .bn#bn0038').insertAfter('.node-txt .field-name-body p:nth-child(3)');
      setTimeout(function(){if($('.bn#bn0038').height() > 10) $('.bn#bn0038').css('margin', '20px 0px') },2000);
    }
    //=================================

    // проекты DE
    $('.view-de-companies .views-field-title, .view-de-companies .views-field-body').click(function(){
      //console.log('-======-');
      let pp = $(this).parent();
      if ($('.views-field-body-1',pp).hasClass('opened')) {
        $('.views-field-body-1',pp).removeClass('opened');
        $('.views-field-body',pp).show();
      } else {
        $('.views-field-body',pp).hide();
        $('.views-field-body-1',pp).addClass('opened');
      }
    });
    // awards 2018
    $('.projects .project .title').click(function(){
			$('.projects .visible').removeClass('visible');
			$('.text',$(this).parent()).addClass('visible');
			document.location.href='#'+$(this).attr('rel');
    });
    if($('.page-digital-economy-opinions .view-rubrika .views-row').length > 0){
      let rows = $('.page-digital-economy-opinions .view-rubrika .views-row');
      eqRowHeight(rows);  
    }
    if($('.view-ce-case-study .views-row').length > 0){
      let rows = $('.view-ce-case-study .views-row');
      eqRowHeight(rows);  
    }
    if($('.page-node-type-casestudy h1.page-header').length){
      let s = $('.page-node-type-casestudy h1.page-header').text();
      $('.page-node-type-casestudy h1.page-header').html('<div class="s1"><div>Проекты</div></div><div class="s2">'+s+'</div>');
      $('.field--name-field-url a').html('');
      $('.field--name-field-image img').appendTo('.field--name-field-url a');
      $('.field--name-field-url').appendTo('.page-node-type-casestudy h1.page-header .s2')
    }
    //график для вопросов
    $('.v-graph.2019').each(function(){
      let target = $(this);
      let nid = target.attr('rel');
      
      $(this).load('/v.php?n='+nid,'',function(rText){
        let done = getCookie('v'+nid);
         
        if (rText == 'graph' || done == '1'){
          target.html('<img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/>');
          let jsonData = jQuery.ajax({
              url: "/v.php",
              data:{n:nid,g:1},
              async: false
              }).responseText;
          target.html(jsonData);
        }
        
      });
    });
    if($('.exhibitions-form').length){
      $('.exhibitions-form #form-btn').on('click',function(){
        let m = $('#e-month').val();
        let y = $('#e-year').val();
        let c1 = $('#e-country').val();
        let c2 = $('#e-city').val();
        document.location.href = '/exhibitions/'+y+'-'+m+'?country='+c1+'&city='+c2;
      });
    }
    $('#birthdays .hp-block-body a img').each(function(){
      if($(this).attr('data-src').search('jpg') > -1) $(this).attr('src',$(this).attr('data-src'));
    });
    $('#exhibitions .hp-block-body,#birthdays .hp-block-body>div').slick({
      infinite : true,
      dots :true,
      arrows: false,
      autoplay: true,
      autoplaySpeed: 4000,
      slidesToShow: 1,
      slidesToScroll: 1,
      touchMove: true,
      vertical: true
    });
    let randomSlideId1 = Math.random() * $('.page-top2019 .desktop #editorials .hp-block-body .block-node').length | 0

    $('.page-top2019 #editorials .hp-block-body').slick({
      infinite : true,
      dots :true,
      arrows: false,
      autoplay: true,
      autoplaySpeed: 4000,
      slidesToShow: 1,
      slidesToScroll: 1,
      touchMove: true,
      initialSlide: +randomSlideId1,
      vertical: false
    });
    let randomSlideId2 = Math.random() * $('.page-top2019 .desktop #pointofview .hp-block-body .block-node').length | 0
    $('.page-top2019 #pointofview .hp-block-body').slick({
      infinite : true,
      dots :true,
      arrows: false,
      autoplay: true,
      autoplaySpeed: 4000,
      slidesToShow: 1,
      slidesToScroll: 1,
      touchMove: true,
      initialSlide: +randomSlideId2,
      vertical: false
    });
    let randomSlideId3 = Math.random() * $('.page-top2019 .desktop #de_opinions .hp-block-body .block-node').length | 0
    $('.page-top2019 #de_opinions .hp-block-body').slick({
      infinite : true,
      dots :true,
      arrows: false,
      autoplay: true,
      autoplaySpeed: 4000,
      slidesToShow: 1,
      slidesToScroll: 1,
      touchMove: true,
      initialSlide: +randomSlideId3,
      vertical: false
    });
    $('.v-graph.2019').on('click','.send2019',function (){
      let target = $(this).parent().parent();
      let nid = target.attr('rel');
      let res = 0;
      
      $('input',target).each(function(){ 
        //console.log($(this));
        if(this.checked) res = $(this).val();
      });
      //console.log(res);
      if (res > 0){
        target.html('<img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/>');
        let jsonData = jQuery.ajax({
          url: "/v.php",
          data:{n:nid,r:res},
          dataType:"json",
          async: false
          }).responseText;
        let tmp = jQuery.parseJSON(jsonData);
        if(tmp['res'] > 0){
          setCookie('v'+nid,1);
          jsonData = jQuery.ajax({
              url: "/v.php",
              data:{n:nid,g:1},
              async: false
              }).responseText;
          target.html(jsonData);
        }
      } else alert('Пожалуйста, выберите вариант ответа...');
    });
    
    $('.page-digital-economy-news .page-header .s1>div').text('Новости');

    $('.hp-block.old-news').each(function(){
      let titles = $('.node-title',this);
      if(titles.length>1){
        let h1 = $(titles[0]).height();
        let h2 = $(titles[1]).height();
        if(h1 > h2) $(titles[1]).height($(titles[0]).height());
        else $(titles[0]).height($(titles[1]).height());
      }
    });
    jQuery(window).scroll(function(){
      if (jQuery(window).scrollTop() > screen.height) jQuery('#go-top').show(); else jQuery('#go-top').hide();
    });


    if($('.download-pdf').length){
      function fillVariants(){
        let selected = $('#issue').find(":selected");
        let vars = selected.attr('data-variants').split(',');
        let cover = selected.attr('data-cover');
        $('#cover').attr('src',cover);
        let opts = '';
        for (let i=0; i < vars.length; i++) {
          let v = vars[i].split('|');
          opts = opts + '<option value="'+v[1]+'">'+v[0]+'</option>';
        }
        $('#variant').html(opts);
      }
      $('#issue').on('change',function(){ fillVariants(); });
      fillVariants();
      $('#btn').on('click',function(){
        if($('#pswd').val() == '') {
          $('#msg-box').text('Укажите пароль!');
        } else{
          $('#msg-box').html('<img src="/img/1/loader.gif" style="margin: 60px auto 0px; display:block;" class="loader"/>');
          $.get('/getlink.php',{i:$('#issue').val(), v:$('#variant').val(), p:$('#pswd').val()},function(data,status){
            $('#msg-box').html(data);
          });
        }
      });
    }
    if($('.buy-pdf').length){
      function fillVariants(){
        let selected = $('#ssissue').find(":selected");
        let vars = selected.attr('data-variants').split(',');
        let cover = selected.attr('data-cover');
        $('#cover').attr('src',cover);
        let opts = '';
        for (let i=0; i < vars.length; i++) {
          let v = vars[i].split('|');
          opts = opts + '<option value="'+v[0]+'" data-price="'+v[1]+'">'+v[0]+'</option>';
        }
        $('#ssvariant').html(opts);
        fillPrice();
      }
      function fillPrice(){
        let selected = $('#ssvariant').find(":selected");
        let price = selected.attr('data-price');
        $('#sstxtprice').text(price+'руб.');
        $('#ssprice').val(price);
        fillDelivery();
				
      }
      function fillDelivery(){
        $('#ssdeliverycost').text($('#ssdelivery').val());
				if($('#ssdelivery').val()!='0') $('#ssadr').show(); else $('#ssadr').hide();
				if(document.getElementById('sstotal')) $('#sstotal').text((parseInt($('#ssprice').val(),10)+parseInt($('#ssdelivery').val(),10))+' руб.');

      }
      $('#ssissue').on('change',function(){ fillVariants(); });
      $('#ssvariant').on('change',function(){ fillPrice(); });
      $('#ssdelivery').on('change',function(){ fillDelivery(); });
      fillVariants();
      
    }

    if($('.path-frontpage').length && !isMobile()){
      console.log('444');
      setTimeout(function(){
        let r1 = $('.section-1 .r1');
        let r2 = $('.section-1 .r2');
        console.log(r2.height() - r1.height() > 300);

        if((r2.height() - r1.height()) > 200 ) {
          $('#bn0037').appendTo('.section-1 .r1');
        }
      },1000);
    }
    
    setInterval(function(){
      if($('.page-top2019 .vv-grid').length){
        $('.page-top2019 .desktop #vopros').height($('.page-top2019 .desktop .vv-grid').height()+100);
        $('.page-top2019 .mobile #vopros').height($('.page-top2019 .mobile .vv-grid').height()+300);
      } else {
        $('.v-form').each(function(){
          $(this).height($('.vv-graph',this).height()+50);
          $('.vv-grid',this).height($('.vv-graph',this).height()+70);
        });  
      }
    },2000);
    
    $('#force-full').on('click',function(){
      if (jQuery.cookie("force-full-version") != "1"){
        let ddd = new Date();   
        let hhh = 1;
        ddd.setTime(ddd.getTime() + (hhh * 60 * 60 * 1000));
        jQuery.cookie("force-full-version", "1", { expires: ddd, path: "/" }); 
        document.location.reload();
        
      }
    });
    $('#force-mobile, .mobile-icon-black').on('click',function(){
      if (jQuery.cookie("force-full-version") == "1"){
        let ddd = new Date();   
        let hhh = 1;
        ddd.setTime(ddd.getTime() + (hhh * 60 * 60 * 1000));
        jQuery.cookie("force-full-version", "0", { expires: ddd, path: "/" }); 
        document.location.reload();
        console.log('=======');
      }
    });

    //   MOBILE =======================================================
    if(isMobile()){

       
      if (jQuery.cookie("show-mv-fullscreen") != "12" || force_show_mv_fullscreen){
          if(jQuery('#bnFullScreen *').length > 0){
            jQuery("#bn-fullscreen").attr('style','display: block!important');
            jQuery("#bn-fullscreen #bn-close").click(function(){jQuery("#bn-fullscreen").attr('style','display: none!important');});	
            setTimeout(function(){jQuery("#bn-fullscreen #bn-close").css("opacity","1");},1000);
          }
          let ddd = new Date();   
          let hhh = 12;
          ddd.setTime(ddd.getTime() + (hhh * 60 * 60 * 1000));
          
          jQuery.cookie("show-mv-fullscreen", "12", { expires: ddd, path: "/" });      
          
      }
      
      if($('.path-content .node-txt').length){
        $('#hidden-pool #bnM0005').insertAfter('.path-content .node-txt .field-name-body .field-item p:nth-child(2)');
      }

      $('#ham').on('click',function(){ $(this).toggleClass('opened')});

      if($('#hp-mobile').length){
        $('#hp-mobile').html('');
        $('#hp-mobile')
              .append($('#hp-desktop #mainnews'))
              .append($('#hidden-pool #bnM0004'))
              .append($('#hp-desktop #news'))
              .append($('#reviews'))
              .append($('#today-short-news'))
              //.append($('#spc'))
              .append($('#hp-desktop #pointofview'))
              .append($('#hp-desktop #editorials'))
              .append($('#hp-desktop #covid-19'))
              .append($('#hp-desktop #solutions'))
              .append($('#hp-desktop #de-news'))
              .append($('#hp-desktop #de-opinions'))
              .append($('#hp-desktop #pressreleases'))
              .append($('#hp-desktop #regionalnews'))
              .append($('#hidden-pool #bnM0003'))
              .append($('#hp-desktop #vopros'))
              
              .append($('#hp-desktop #quotes'))
              .append($('#hp-desktop #last-comments'))
              //.append($('#hp-desktop #exhibitions'))
              //.append($('#hp-desktop #birthdays'))
              ;
      }

      $('.path-about .page #y-m-msk').insertAfter('.path-about .page .a.a1');
      
      if($('.v-graph').length){
        $('.vv-container, .vv-grid .t1').each(function(){
          $(this).height($(this).attr('rel'));
        });
      }

      

      $('.field-name-field-bigimg img').each(function(){
        let src = $(this).attr('src');
        $(this).attr('src',src.replace('/styles/article_bigimg/public',''));
      });
      $('.page-editorials .views-row .node .title,.page-point-of-view .views-row .node .title, .path-taxonomy-term-1179 .views-row .node .title').each(function(){
        let papa = $(this).parent();
        $(this).insertAfter(papa);
      });
      $('.page-videointerviews .views-row .node .title').each(function(){
        let papa = $(this).parent();
        $(this).insertBefore(papa);
      });
      $('#author-header .quote').insertAfter('#author-header');

      $('.page-digital-economy #de-opinions,.page-digital-economy #de-quote').insertAfter('.page-digital-economy #news');


    }    
    let screenWidth = screen.width;
     
    window.addEventListener("orientationchange", function(e) {
        if((screen.width >= 750 && screenWidth < 750) || (screen.width < 750 && screenWidth >= 750)) document.location.reload();
    }, false);

});}(jQuery));

/*************************** */
function showCalendar(){
	var d = jQuery(this).attr('rel');
	jQuery.get('/c.php',{d:d},assignCalendarCntrls);
}

function assignCalendarCntrls(data,status){
	d = data.split('~');
	jQuery('.logo-calendar .c-body').html(d[1]);
	jQuery('.logo-calendar #calendarPrev').click(showCalendar);
	jQuery('.logo-calendar #calendarNext').click(showCalendar);
}

function showBigCalendar(){
	let d = jQuery(this).attr('rel');
	jQuery.get('/c.php',{d:d,n:'1'},assignBigCalendarCntrls);
}

function assignBigCalendarCntrls(data,status){
	d = data.split('~');
	jQuery('.a-c-body').html(d[1]);
	jQuery('.a-c-body #calendarPrev').click(showBigCalendar);
  jQuery('.a-c-body #calendarNext').click(showBigCalendar);
  jQuery('.a-c-body #calendar-month, .a-c-body #calendar-year').on('change',function(){jQuery.get('/c.php',{t:jQuery('.a-c-body #calendar-year').val()+'-'+jQuery('.a-c-body #calendar-month').val()+'-01',n:'1'},assignBigCalendarCntrls);})
  let currdate = jQuery('#big-calendar').attr('data-d');
  if(currdate!=undefined) jQuery('.a-c-body div[rel="'+currdate+'"]').addClass('current-date');
	//jQuery('#big-calendar .week a').attr('target','_blank');
}
/************************************** */
function animate(obj_id){
    //if(!isMobile()){  
				var i = jQuery('#cross');
				i.hide();
        var pos = jQuery('#'+obj_id).offset();
         
				var x = pos.left - 1000;// - $(document).scrollLeft();
        var y = pos.top -1000;// - $(document).scrollTop();
        console.log(x,y);
				i.css('left', x+'px');
        i.css('top', y+'px');
        dx = dy = 1000;
				i.width(2*dx);
				i.height(2*dy);
				i.show();
				function aa(){
					dx=dx-50;
          dy=dy-50;
          x = x + 50;
          y = y + 50;
           
					i.css('left', x+'px');
					i.css('top', y+'px');
					i.width(2*dx);
					i.height(2*dy);
					if (dx>0 && dy>0) setTimeout(aa,10); else i.hide();
				}
        setTimeout(aa,10);
        
    //}	   
}
function sendLike(){
		function showResult(data){
			var _data = data.split(',');
			if (_data[0] == 'ok'){
				if (v == 1)
					document.getElementById(objId+'_span').innerHTML = '+'+_data[1];	
				else 
					document.getElementById(objId+'_span').innerHTML = '-'+_data[2];
				animate(objId);	
			} else if(_data[0] == 'voted'){
				alert('Вы уже оценили этот комментарий!')
			} else if(_data[0] == 'denied'){
				alert('Ставить оценки коментариям могут только зарегистрировнные пользователи!')
			} else if(_data[0] == '404'){
				alert('Комментарий не найден! возможно он был удален модератором.')
			}
		}
		var objId = this.id;
		var id = objId.split('-')[1];
		var v = 1;
		if (objId.split('-')[0]=='dislike') v = -1;
		jQuery.get('https://comments.comnews.ru/cs/like.php',{p: id,v: v},showResult);
}

function sendTLike(){
		function showResult(data){
			var _data = data.split(',');
			if (_data[0] == 'ok'){
				if (v == 1){
					if (document.getElementById('up-like-'+id+'_span')) document.getElementById('up-like-'+id+'_span').innerHTML = '+'+_data[1];	
					if (document.getElementById('dn-like-'+id+'_span')) document.getElementById('dn-like-'+id+'_span').innerHTML = '+'+_data[1];	
				}
				else { 
					if (document.getElementById('up-dislike-'+id+'_span')) document.getElementById('up-dislike-'+id+'_span').innerHTML = '-'+_data[2];
					if (document.getElementById('dn-dislike-'+id+'_span')) document.getElementById('dn-dislike-'+id+'_span').innerHTML = '-'+_data[2];
				}
				animate(objId);	
			} else if(_data[0] == 'voted'){
				alert('Вы уже оценили эту запись в блоге!')
			} else if(_data[0] == 'denied'){
				alert('Ставить оценки  записям в блоге могут только зарегистрировнные пользователи!')
			} else if(_data[0] == '404'){
				alert('Комментарий не найден! возможно он был удален модератором.')
			}
		}
		
		var objId = this.id;
		var id = objId.split('-')[2];
		var v = 1;
		if (objId.split('-')[1]=='dislike') v = -1;
		jQuery.get('https://comments.comnews.ru/cs/like.php',{t: id,v: v},showResult);
}

function sendMLike(){
	
		function showResult(data){
      var _data = data.split(',');
      
				if (_data[0] == 'ok'){
				if (v == 1){
					if (document.getElementById('up-like-'+id+'_span')) document.getElementById('up-like-'+id+'_span').innerHTML = '+'+_data[1];	
					if (document.getElementById('dn-like-'+id+'_span')) document.getElementById('dn-like-'+id+'_span').innerHTML = '+'+_data[1];	
				}
				else { 
					if (document.getElementById('up-dislike-'+id+'_span')) document.getElementById('up-dislike-'+id+'_span').innerHTML = '-'+_data[2];
					if (document.getElementById('dn-dislike-'+id+'_span')) document.getElementById('dn-dislike-'+id+'_span').innerHTML = '-'+_data[2];
				}
				animate(objId);	
			} else if(_data[0] == 'voted'){
				alert('Вы уже оценили эту статью!')
			} else if(_data[0] == 'denied'){
				alert('Ставить оценки статьям могут только зарегистрировнные пользователи!')
			} else if(_data[0] == '404'){
				//alert('Комментарий не найден! возможно он был удален модератором.')
			}	
		}
		
    var objId = this.id;
		var id = objId.split('-')[2];
		var v = 1;
		if (objId.split('-')[1]=='dislike') v = -1;
		jQuery.get('https://comments.comnews.ru/cs/like.php',{m: id,v: v},showResult);
	
}

function renderVotes(){
	(function ($) {
		
		var out = $('#vote-src').html();
		$('#vote-src').html('');
		$('#vote-container').html(out);
		
		$('.vote').on('click','a.p-like',sendLike);	   
		$('.vote').on('click','a.p-dislike',sendLike);
		$('.vote').on('click','a.m-like',sendMLike);	   
		$('.vote').on('click','a.m-dislike',sendMLike);
		$('.vote').on('click','a.t-like',sendTLike);	   
		$('.vote').on('click','a.t-dislike',sendTLike);
  
    // старые закрытые комменты
    if($('#phpbb .buttons .locked-icon').length >0 && $('#phpbb .post').length <= 1 ){
      $('#phpbb .buttons h3, #phpbb .buttons .locked-icon').hide();
      
    }

	
	}(jQuery));
	
}

jQuery.fn.hyphenate = function() {
  var all = "[абвгдеёжзийклмнопрстуфхцчшщъыьэюя]",
    glas = "[аеёиоуыэю\я]",
    sogl = "[бвгджзклмнпрстфхцчшщ]",
    zn = "[йъь]",
    shy = "&shy;";//"\xAD",
    re = [];
   
  re[1] = new RegExp("("+zn+")("+all+all+")","ig");
  re[2] = new RegExp("("+sogl+glas+glas+")("+sogl+glas+")","ig");
  re[3] = new RegExp("("+glas+")("+glas+all+")","ig");
  re[4] = new RegExp("("+glas+sogl+")("+sogl+glas+")","ig");
  re[5] = new RegExp("("+sogl+glas+")("+sogl+glas+")","ig");
  re[6] = new RegExp("("+glas+sogl+")("+sogl+sogl+glas+")","ig");
  re[7] = new RegExp("("+glas+sogl+sogl+")("+sogl+sogl+glas+")","ig");
  re[8] = new RegExp("("+glas+sogl+sogl+")("+sogl+glas+")","ig"); 

  return this.each(function() {
    let text = jQuery(this).html();
    let title = jQuery('img',this).attr('title');
    let alt = jQuery('img',this).attr('alt');
    for (let i = 1; i < 9; ++i) {
      text = text.replace(re[i], "$1"+shy+"$2");
    }
    jQuery(this).html(text);
    jQuery('img',this).attr('title',title);
    jQuery('img',this).attr('alt',alt);
  });
};
jQuery.fn.hyphenateURL = function() {
  let shy = "&shy;";//"\xAD",
  let re = new RegExp("(/)","ig");
  
  return this.each(function() {
    var text = jQuery(this).html();
    text = text.replace(re, "$1"+shy);
    jQuery(this).html(text);
  });
};   

function setCookie (name, value, expires, path, domain, secure) {
  document.cookie = name + "=" + escape(value) +
    ((expires) ? "; expires=" + expires : "") +
    ((path) ? "; path=" + path : "; path=/") +
    ((domain) ? "; domain=" + domain : "") +
    ((secure) ? "; secure" : "");
}
function getCookie(name) {
	var cookie = " " + document.cookie;
	
	var search = " " + name + "=";
	var setStr = null;
	var offset = 0;
	var end = 0;
	if (cookie.length > 0) {
		offset = cookie.indexOf(search);
		if (offset != -1) {
			offset += search.length;
			end = cookie.indexOf(";", offset)
			if (end == -1) {
				end = cookie.length;
			}
			setStr = unescape(cookie.substring(offset, end));
		}
	}
	//alert(setStr);
	return(setStr);
}
function eqRowHeight(rows){
  for(let i = 0; i < rows.length; i=i+3){
    let h = jQuery(rows[i]).height();
    if((i+1) < rows.length && h < jQuery(rows[i+1]).height()) h = jQuery(rows[i+1]).height();
    if((i+2) < rows.length && h < jQuery(rows[i+2]).height()) h = jQuery(rows[i+2]).height();
    h = h +40;
    jQuery(rows[i]).height(h);
    if((i+1) < rows.length) jQuery(rows[i+1]).height(h);
    if((i+2) < rows.length) jQuery(rows[i+2]).height(h);
    //console.log(h);
  }
}
