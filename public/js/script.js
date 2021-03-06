var sentencePos = 0;
var disallow_keystroke_register = false;
var translation_save_clicked = false;
var inspector_accept = true;
var en_abstract_default_font_szie = parseInt($("#en-abstract").css("font-size")) + "px";

var prior_ku_trans = '';
if(document.getElementById("ku_trans_abstract")){
	var prior_ku_trans = $('textarea#ku_trans_abstract').val().trim();
}

$(function() {
    $('#search-form').submit(function() {
        suggestions();
        return false;
    });

    $(document).click(hideSuggestions);

    $('.toggle-down-sibling').click(function() {
        var $sibling = $(this).parent().siblings();
        $sibling.toggleClass('hidden');
    });

    $('.toggle-password')
        .mousedown(showPassword)
        .mouseup(hidePassword)
        .mouseleave(hidePassword);

    $('a.delete-user').click(function() {
        $('.delete-user-modal').modal('show');
        var user_id = $(this).children('input[name=user_id]').val();
        $('.delete-user-modal input[name=user_id]').val(user_id);
    });

    $("input#ku_trans_title").change(updateTranslationScore).keyup(updateTranslationScore);
	$('textarea#ku_trans_abstract').on('summernote.keyup', function(we, e) {
		updateTranslationScore();
	});
	$('textarea#ku_trans_abstract').on('summernote.change', function(we, contents, $editable) {
		updateTranslationScore();
	});
    updateTranslationScore();

    setTimeout(function() {
        $('.action-result').fadeOut(1000);
    }, 5000);


    $('.beg-sentence').click(function() {
        sentencePos = 0;
        highlightEnAbstract();
    });
    $('.rewind-sentence').click(function() {
        sentencePos = (sentencePos > 0) ? sentencePos - 1 : sentencePos;
        highlightEnAbstract();
    });
    $('.forward-sentence').click(function() {
        sentencePos++;
        highlightEnAbstract();
    });
    highlightEnAbstract();

    $('.register-keystroke').keyup(function() {
        registerKeystroke();
    });

    setTimeout(registerActivity, 1000);
    setTimeout(refreshStatuses, 1000);
	
	$(window).on('beforeunload', function() {
		
		if($('#have_draft').val() && !translation_save_clicked){
			return 'You have draft. To save it stay on this page and after retrieve draft click on save changes.';
		}
		if (prior_ku_trans.localeCompare($('textarea#ku_trans_abstract').val()) != 0 && checkForUnsavedChanges() != 0 && checkForUnsavedChanges() != null &&
            !translation_save_clicked) {
            return 'You have unsaved changes!';
        }
    });
	
    $('#inspection').click(function() {
		var ku_trans_title = $("#ku_trans_title").val();
        var ku_trans_abstract = $("#ku_trans_abstract").val();
        var en_abstract = $("#hidden-en-abstract").val();
		if(ku_trans_abstract.length / en_abstract.length > 0.79){
			return confirm('Do you want to submit this article for proofreading? You can still make changes whilst it is waiting for proof read.');
		}else{
			return confirm('This looks like shorter than English text, are you sure you want to send it for proofreading?');
		}
    });
	
    $('#unreserve').click(function() {
		return confirm('Are you sure you want to unreserve topic?');
    });
	
	$("button[name = 'save']").click(function() {
		translation_save_clicked = true;
	});

    $('.peek-link').click(peek);

    refreshCsrf();

    $('.delete-topic-form').submit(function() {
        return confirm("Are you sure?");
    });
});

function htmlToPlaintext(text) {
  return text ? String(text).replace(/<[^>]+>/gm, '') : '';
}

function suggestions()
{
    var url = $('#search-form').prop('action');
    var token = $('input[name=_token]').val();
    var title = $('#topic-peek-search').val();
    $('div.suggestions').removeClass('hidden');
    var listGroup = $('.suggestions .list-group');
    listGroup.html('<span class="fa fa-cog fa-spin"></span>');
    $.ajax({
        type: "POST",
        url: url,
        data: {topic: title, _token: token},
        success: function(data) {
            var topics = data.topics;

            if(!topics) {
                listGroup.html('<p class="text-danger text-center">Error!</div>');
            }
            else if(!topics.length) {
                listGroup.html('<p class="text-danger text-center">No Hits Found</div>');
            }
            else {
                var items = "";
                for(var i = 0; i < topics.length; i++) {
                    items += "<a href='javascript:;' class='list-group-item peek-link'>" + topics[i] + "</a>";
                }
                listGroup.html(items);
                $('.peek-link').click(peek);
            }
        }
    });
}

function hideSuggestions()
{
    $('.suggestions .list-group').html("");
    $('.suggestions').addClass("hidden");
}

function peek()
{
	$('#peek-no-ku-trans').addClass('hidden');
	var url = $('#peek-form').prop('action');
    var token = $('input[name=_token]').val();
    var title = $(this).find(".peek_topic_title_box").html();
	if(typeof title == 'undefined'){
		title = $(this).text();
	}
    $('.topic-peek-modal').modal('show');
    $('.topic-peek-modal div.loader').removeClass('hidden');
    $('.topic-peek-modal .topic-not-found').addClass('hidden');
    $('.topic-peek-modal .translation-group.en').addClass('hidden');
    $('.topic-peek-modal .translation-group.ku').addClass('hidden');
    $.ajax({
        type: "POST",
        url: url,
        data: {topic: title, _token: token},
        success: function(data) {
			$('.topic-peek-modal div.loader').addClass('hidden');
            if(data.error) {
                $('.topic-peek-modal .topic-not-found').removeClass('hidden');
            }
            else {
                $('.topic-peek-modal .translation-group.en').removeClass('hidden');
                $('#peek-en-title').html(data.topic);
                $('#peek-en-abstract').html(data.abstract);
				
                if(data.ku_topic) {
                    $('#peek-no-ku-trans').addClass('hidden');
					$('.topic-peek-modal .translation-group.ku').removeClass('hidden');
                    $('#peek-ku-title').html(data.ku_topic);
                    $('#peek-ku-abstract').html(data.ku_abstract);
                }
                else {
                    $('#peek-no-ku-trans').removeClass('hidden');
                    $('a.btn.translate-now').prop('href', data.translate_url);
					$('#deletion-rec-box').html(data.delete_recomend);
                }
				if(data.user_type == 'translator'){
					$('.refering-to-topic').removeClass('hidden');
					$('.refering-to-topic').attr('href', data.translate_url);
					$('a.btn.translate-now').removeClass('hidden');
				}
            }
        }
    });
}

function showPassword()
{
    $(this).children('span.fa').removeClass('fa-eye-slash').addClass('fa-eye');
    var $input = $(this).siblings('input');
    var rep = $("<input type='text' />")
        .attr("id", $input.attr("id"))
        .attr("name", $input.attr("name"))
        .attr('class', $input.attr('class'))
        .val($input.val())
        .insertBefore($input);
    $input.remove();
}

function hidePassword()
{
    $(this).children('span.fa').removeClass('fa-eye').addClass('fa-eye-slash');
    var $input = $(this).siblings('input');
    var rep = $("<input type='password' />")
        .attr("id", $input.attr("id"))
        .attr("name", $input.attr("name"))
        .attr('class', $input.attr('class'))
        .val($input.val())
        .insertBefore($input);
    $input.remove();
}

function highlightEnAbstract()
{
    if(!$("#hidden-en-abstract").length) {
        return;
    }

    var abstract = $("#hidden-en-abstract").val().trim().replace(/\.(?!\d)/g,'.|').split("|");
    if(sentencePos >= abstract.length) {
        sentencePos = abstract.length;
    }
    var done = "";
    var current = "";
    var next = "";


    for(var i = 0; i < sentencePos; i++) {
        if(abstract[i])
            done += abstract[i];
    }
    if(done.length) {
        done = "<span class='text-success'>" + done + "</span>";
    }

    if(abstract[sentencePos])
        current += abstract[sentencePos];
    if(current.length) {
        current = "<span class='text-primary'>" + current + "</span>";
    }

    for(var i = sentencePos + 1; i < abstract.length; i++) {
        if(abstract[i])
            next += abstract[i];
    }
    if(next.length) {
        next = "<span class='next-sentences'>" + next + "</span>";
    }

    $("p#en-abstract").html(done + current + next);
}
/*
function updateTranslationScore()
{
    var abstract = $('textarea#ku_trans_abstract');
    if(!abstract.length) {
        return;
    }
    var plaintext = abstract.val().trim();
    var words = plaintext.split(" ");
    var wordcount = 0;
    for(var i = 0; i < words.length; i++) {
        if(words[i].trim().length > 1) {
            wordcount++;
        }
    }

    wordcount -= $('#current_score').val();

    $('button[name=save] span.badge').html(wordcount > 0 ? "+" + wordcount : wordcount);
}
*/

function updateTranslationScore()
{
    var abstract = $('textarea#ku_trans_abstract');
    var topic = $('input#ku_trans_title');
    if(!abstract.length && !topic.length) {
        return;
    }
    var plaintext = htmlToPlaintext(abstract.val().trim().replace(new RegExp("<br>", "g"), ' ').replace(new RegExp("<p>", "g"), ' ').replace(new RegExp("<li>", "g"), ' ').replace(new RegExp("&nbsp;", "g"), ' '));
    var words = plaintext.split(" ");
    var abstractwordcount = 0;
    for(var i = 0; i < words.length; i++) {
		if(words[i].trim().length > 0) {
            abstractwordcount++;
        }
    }

    var plaintext = topic.val().trim();
    var words = plaintext.split(" ");
    var titlewordcount = 0;
    for(var i = 0; i < words.length; i++) {
        if(words[i].trim().length > 0) {
            titlewordcount++;
        }
    }

	wordcount = titlewordcount + abstractwordcount - $('#current_score').val();

    $('button[name=save] span.badge').html(wordcount > 0 ? "+" + wordcount : wordcount);
}

function checkForUnsavedChanges()
{
    var abstract = $('textarea#ku_trans_abstract');
	
    if(!abstract.length) {
        return null;
    }
    var plaintext = abstract.val().trim();
    var words = plaintext.split(" ");
    var wordcount = 0;
    for(var i = 0; i < words.length; i++) {
        if(words[i].trim().length > 1) {
            wordcount++;
        }
    }

    wordcount -= $('#current_score').val();
    return wordcount;
}

function registerActivity()
{
    var url = $('#reg-activity-url').val();
    $.get(url, function() {
        setTimeout(registerActivity, 1000);
    });
}

function registerKeystroke()
{
    if(disallow_keystroke_register) {
        return;
    }
    var url = $('#reg-keystroke-url').val();
    $.get(url);
    disallow_keystroke_register = true;
    setTimeout(function() {
        disallow_keystroke_register = false;
    }, 1000);
}

function refreshStatuses() {
    var statusUrl = $('#get-statuses-url').val();

    $.get(statusUrl, function(data) {
        for(var i = 0; i < data.length; i++) {
            if(data[i].typing == "typing") {
                $('#typing-status-' + data[i].id).removeClass('hidden');
            }
            else {
                $('#typing-status-' + data[i].id).addClass('hidden');
            }
            if(data[i].online == "online") {
                $('#online-status-' + data[i].id).removeClass('offline');
                $('#online-status-' + data[i].id).addClass('online');
            }
            else {
                $('#online-status-' + data[i].id).removeClass('online');
                $('#online-status-' + data[i].id).addClass('offline');
            }
        }
        setTimeout(refreshStatuses, 1000);
    });
}

function refreshCsrf()
{
    var url = '/csrf';
    $.get(url, function(data) {
        $('input[name=_token]').val(data.csrf);
        $('meta[name=csrf-token]').attr('content', data.csrf);
        setTimeout(refreshCsrf, 1000 * 60);
    });
}

$(document).ready(function(){
	$("textarea#inspection_ku_trans_abstract").summernote({
		height: ($("#inspec-en-abstract").height())+"px",
		direction: 'rtl',
		toolbar: [
			['style', ['ul', 'ol', 'superscript', 'subscript']]
		],
	});
	$("textarea#ku_trans_abstract").summernote({
		height: ($("#en-abstract").height())+"px",
		direction: 'rtl',
		toolbar: [
			['style', ['ul', 'ol', 'superscript', 'subscript', 'codeview']]
		],
	});
	$('#inspection_ku_trans_abstract').summernote('justifyRight');
	$('#ku_trans_abstract').summernote('justifyRight');
	var ku_translate_default_font_szie = parseInt($('textarea#ku_trans_abstract').css("font-size")) + "px";
		
	$("[name='ku_trans_title']").keypress(function(event){
		if (event.keyCode == 10 || event.keyCode == 13){
			event.preventDefault();
			$("#ku_trans_abstract").focus();
		}
	});
	$("#inspection_edit_key").click(function(event){
		event.preventDefault();
		$("#inspec-en-abstract").css("margin-top", "25px");
		$(".inspection_viewtranslate").hide();
		$(".inspection_edittranslate").show();
	});
	$('#bulk_select_all').change(function(){
		if (this.checked) {
			$('.bulk_select').prop('checked', true);
		}else{
			$('.bulk_select').prop('checked', false);
		}
	});
	
	$('#editpage_reject_reason').keyup(function(){
		if($(this).val().length > 0){
			$('button#save_accept').text('Save Changes And Reject');
			$('button#save_accept').removeClass('btn-success');
			$('button#save_accept').addClass('btn-danger');
			inspector_accept = false;
		}else{
			$('button#save_accept').text('Save Changes And Accept');
			$('button#save_accept').removeClass('btn-danger');
			$('button#save_accept').addClass('btn-success');
			inspector_accept = true;
		}
	});
	
	$('#reject_reason').keyup(function(){
		if($(this).val().length > 0){
			$('button#accept').text('Reject');
			$('button#accept').removeClass('btn-success');
			$('button#accept').addClass('btn-danger');
			inspector_accept = false;
		}else{
			$('button#accept').text('Accept');
			$('button#accept').removeClass('btn-danger');
			$('button#accept').addClass('btn-success');
			inspector_accept = true;
		}
	});
	
	$('button#accept').click(function(event){
		if(inspector_accept){
			var msg = 'You have not provided any reason for rejection. This means accept. Is this correct?';
		}else{
			var msg = 'You have provided a reason for rejection. Do you want to reject this translation?';
		}
		return confirm(msg);
	});
	
	$('button#save_accept').click(function(event){
		if(inspector_accept){
			var msg = 'You have not provided any reason for rejection. This means accept. Is this correct?';
		}else{
			var msg = 'You have provided a reason for rejection. Do you want to reject this translation?';
		}
		return confirm(msg);
	});
	
	$('.admin_delete_recomm_action_confirm_restore_bulk').click(function(event){
		return confirm('Are you sure you want to restore these topics?');
	});
	
	$('.admin_delete_recomm_action_confirm_delete_bulk').click(function(event){
		return confirm('Are you sure you want to delete these topics?');
	});
	
	$('.admin_delete_recomm_action_confirm_restore').click(function(event){
		return confirm('Are you sure you want to restore this topic?');
	});
	
	$('.admin_delete_recomm_action_confirm_delete').click(function(event){
		return confirm('Are you sure you want to delete this topic?');
	});
	
	$("#cats_selected").chosen();
	
	$('button#inc_text_size').click(function(event){
		var fontSize = parseInt($('.note-editable').css("font-size")) + 1 + "px";
		if(parseInt(fontSize) < 40){
			$('.note-editable').css('font-size',fontSize);
			$('#en-abstract').css('font-size', fontSize);
		}
		$('.note-editable').css('height',($("#en-abstract").height())+"px");
	});
	
	$('button#dec_text_size').click(function(event){
		var fontSize = parseInt($('.note-editable').css("font-size")) - 1 + "px";
		if(parseInt(fontSize) > 10){
			$('.note-editable').css('font-size',fontSize);
			$('#en-abstract').css('font-size', fontSize);
		}
		$('.note-editable').css('height',($("#en-abstract").height())+"px");
	});
	
	$('button#reset_tex_size').click(function(event){
		$('#en-abstract').css('font-size', en_abstract_default_font_szie);
		$('.note-editable').css('font-size',ku_translate_default_font_szie);
		$('.note-editable').css('height',($("#en-abstract").height())+"px");
	});
	
	if(($("#inspec-ku-abstract").height()) > ($("#inspec-en-abstract").height())){
		$('#inspec-en-abstract').css('height',($("#inspec-ku-abstract").height())+"px");
	}else{
		$('#inspec-ku-abstract').css('height',($("#inspec-en-abstract").height()+32)+"px");
	}
	
	if(($("#ku-title").height()) > ($("#en-title").height())){
		$('#en-title').css('height',($("#ku-title").height()+22)+"px");
		$('#edit-ku-title').css('height',($("#ku-title").height()+13)+"px");
	}else if(($("#ku-title").height()) < ($("#en-title").height())){
		$('#ku-title').css('height',($("#en-title").height()+22)+"px");
		$('#edit-ku-title').css('height',($("#en-title").height()+13)+"px");
	}
	
	$('.remove-saved-topic').click(function(event){
		event.preventDefault();
		window.location.href = $(this).attr('rdurl');
	});
});