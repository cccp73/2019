//
const editorial_authors_nid = 201640;
const interview_authors_nid = 202214;
const article_authors_nid = 202213;
let editorial_authors = [];
let interview_authors = [];
let article_authors = [];
console.log('comnews adm v1.0');
(function ($) {
    Drupal.behaviors.comnewsBehavior = {
      attach: function (context, settings) {
        //===============
        if($('.node-editorial-edit-form, .node-editorial-form').length){
            addAuthorBtns(editorial_authors);
            /*
            $('#edit-field-authors-wrapper .form-text').each(function(){
                let papa = $(this).parent();
                $('.a-menu',papa).remove();
                let str = '<div class="list">';
                for (const row of editorial_authors) {
                    let tmp = row.split('|');
                    let name = tmp[0];
                    if(tmp.length > 1) name = tmp[1];
                    str = str + '<div class="list-item" rel="'+tmp[0]+'">'+name+'</div>';
                }
                str = str + '</div>';
                papa.append('<div class="a-menu btn" ="">...'+str+'</div>');
            });
            $('.a-menu .list .list-item').on('click',function(){
                let input = $('input',$(this).parent().parent().parent());
                input.val($(this).attr('rel'));
            });
            */       
        }
        if($('.node-interview-edit-form, .node-interview-form').length){
            addAuthorBtns(interview_authors);
        }
        if($('.node-article-edit-form, .node-article-form').length){
            addAuthorBtns(article_authors);
        }

        console.log('comnews adm - refreshed');
      }
    };
  })(jQuery);

(function ($) {$(function(){ 
    // open folder tree 
    $('.field--type-entity-reference.field--name-field-folders.field--widget-term-reference-fancytree .fancytree-expander').click();
    $('.field--type-entity-reference.field--name-field-tags.field--widget-term-reference-fancytree .fancytree-expander').click();
    $('#edit-title-0-value').focus();

    if($('.node-editorial-edit-form, .node-editorial-form').length){
        $.get('/authors.php?nid='+editorial_authors_nid,function(data){
            editorial_authors = data.split('\n');
            addAuthorBtns(editorial_authors);
            /*
            $('#edit-field-authors-wrapper .form-text').each(function(){
                let papa = $(this).parent();
                $('.a-menu',papa).remove();
                let str = '<div class="list">';
                for (const row of editorial_authors) {
                    let tmp = row.split('|');
                    let name = tmp[0];
                    if(tmp.length > 1) name = tmp[1];
                    str = str + '<div class="list-item" rel="'+tmp[0]+'">'+name+'</div>';
                }
                str = str + '</div>';
                papa.append('<div class="a-menu btn" ="">...'+str+'</div>');
            });
            $('.a-menu .list .list-item').on('click',function(){
                let input = $('input',$(this).parent().parent().parent());
                input.val($(this).attr('rel'));
            });
            */
        });
    }

    if($('.node-interview-edit-form, .node-interview-form').length){
        $.get('/authors.php?nid='+interview_authors_nid,function(data){
            interview_authors = data.split('\n');
            addAuthorBtns(interview_authors);
        });
    }
    if($('.node-article-edit-form, .node-article-form').length){
        $.get('/authors.php?nid='+article_authors_nid,function(data){
            article_authors = data.split('\n');
            addAuthorBtns(article_authors);
        });
    }

    console.log('comnews adm - initialized');

    
    


});}(jQuery));


function addAuthorBtns(listval){
    const list = listval;
    jQuery('#edit-field-authors-wrapper .form-text').each(function(){
        let papa = jQuery(this).parent();
        jQuery('.a-menu',papa).remove();
        let str = '<div class="list">';
        for (const row of list) {
            let tmp = row.split('|');
            let name = tmp[0];
            if(tmp.length > 1) name = tmp[1];
            str = str + '<div class="list-item" rel="'+tmp[0]+'">'+name+'</div>';
        }
        str = str + '</div>';
        jQuery('input',papa).css('width','80%');
        papa.append('<div class="a-menu btn" ="">...'+str+'</div>');
    });
    jQuery('.a-menu .list .list-item').on('click',function(){
        let input = jQuery('input',jQuery(this).parent().parent().parent());
        input.val(jQuery(this).attr('rel'));
    });
}
