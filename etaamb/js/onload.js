// Everything copyright Etaamb 2010, for the moment
// Application still in early beta phase, code 
// not optimized and still quite rough around the edges.

// Etaamb 2010 Work in progress

var em = ['team'];
var maxLinkedDocsShowed = 5;
var minLinkedDocsShowed = 3;

var dict = function()
	{
	var d = {
		fr:{
			'show_more':'montrer plus (%s)',
			'show_less':'montrer moins',
			'terms_empty'  :'Vous n\'avez spécifié aucun terme à retirer',
			'priv_success' :'Demande envoyée avec succes.',
			'priv_failed'  :'Echec de l\'envoi, veuillez réessayer plus tard.',
            'using_cookies' : 'Etaamb.be utilise des cookies',
            'cookies_text' : 'Etaamb.be utilise les cookies pour retenir votre préférence linguistique et pour mieux comprendre comment etaamb.be est utilisé.',
            'cookies_continue' : 'Continuer',
            'cookies_learnmore' : 'Plus de details'
		   },
		nl:{
			'show_more':'toon meer (%s)',
			'show_less':'toon minder',
			'terms_empty':'U heeft geen te verwijderen woorden opgegeven',
			'priv_success' :'Aanvraag doorgestuurd.',
			'priv_failed'  :'Operatie mislukt. Gelieve het later opnieuw te proberen.',
            'using_cookies': 'Etaamb.be maakt gebruik van cookies',
            'cookies_text' : 'Etaamb.be gebruikt cookies om uw taalvoorkeur te onthouden en om beter te begrijpen hoe etaamb.be gebruikt wordt.',
            'cookies_continue' : 'Doorgaan',
            'cookies_learnmore' : 'Meer details'
		   }
		 };
	var l = '';

	return { set:set,get:get}

	function set(newl)
		{
		l = newl;
		return this;
		}

	function get(Term)
		{
		return d[l][Term];
		}
	}();

$(document).ready(function()
    {
	em.push('openjustice'+'.'+'be');
    $('body').click(clickhandler);
	$('a.contact').attr("href",'mailto:'+em.join('@'));
	$('.email').html(em.join('@'));

	$('div.more_linkeddocs').each(function()
		{
		var c = $(this).find('a').length;
		if (c + minLinkedDocsShowed > maxLinkedDocsShowed)
			{
			$(this).css('display','none')
			   .parent('div')
			   .append('<a href="#" class="show_docs linkeddocs_op" alt="'+c+'">'
			   		  +dict.get('show_more').replace('%s',c)
					  +'</a>');
			}
		});


	$('.linktitle').hover(function() {	
				$(this).addClass('hover');},
			function() {
				$(this).removeClass('hover');});

	if (Words.length > 0)
		{
		highlight.element($('div.document_text'));
		highlight.words(Words);
		setTimeout(function() { highlight.execute(); },300);
		}

	$('#debug').dblclick(function()
		{
		var el = $(this);
		if (el.hasClass('line'))
			{
			el.removeClass('line')
			  .addClass('third');
			 }
		else if (el.hasClass('third'))
			{
			el.removeClass('third')
			  .addClass('twothirds');
			}
		else if (el.hasClass('twothirds'))
			{
			el.removeClass('twothirds')
			  .addClass('full');
			}
		else if (el.hasClass('full'))
			{
			el.removeClass('full')
			  .addClass('line');
			}
		});

	if (typeof window.scrollTo == "function")
		{
		window.scrollTo(0,1);
		$('div#up_arrow').delegate('a','click',function(e)
			{
			window.scrollTo(0,1);
			e.preventDefault();
			});
		}

    // EUPOP, if found (not available if disabled)
    if ($(".eupopup").length > 0) {
		$(document).euCookieLawPopup().init({
			'info' : 'etaamb.be cookie usage consent',
			'popupTitle' : dict.get('using_cookies'),
			'popupText' : dict.get('cookies_text'),
            'buttonContinueTitle' : dict.get('cookies_continue'),
            'buttonLearnmoreTitle': dict.get('cookies_learnmore'),
            'cookiePolicyUrl' : '/cookie-policy',
            'agreementExpiresInDays' : 120
		});
	}

    });

function clickhandler(e)
    {
	e = e || window.event;
	var t = e.target || e.srcElement;
    var jT = $(t); 
	var jTId = jT.attr('id');

    if (jT.parents('table.day_list').length > 0)
        {
		var link = jT.parents('a').attr('href');
        window.location.href = link;
		e.preventDefault();
        }

	else if (jTId == 'referer_deactivate')
		{
		$('div.referer_data').slideUp();
		//$('span.match').removeClass('match');
		$('span.match').fadeOut(function()
			{
			$(this).removeClass('match')
				   .fadeIn();
			
			});
		e.preventDefault();
		}

	else if (jT.hasClass('show_docs'))
		{
		jT.siblings('.more_linkeddocs').slideDown();
		jT.removeClass('show_docs').addClass('hide_docs')
								   .html(dict.get('show_less'));
		e.preventDefault();
		}

	else if (jT.hasClass('hide_docs'))
		{
		jT.siblings('.more_linkeddocs').slideUp();
		jT.removeClass('hide_docs').addClass('show_docs')
								   .html(dict.get('show_more').replace('%s',jT.attr('alt')));
		e.preventDefault();
		}

	else if (jT.hasClass('linktitle'))
		{
		jT.parent('.link').children('.list').fadeIn();
		jT.addClass('live');
		e.preventDefault();
		}

	else if (jT.parents('.int_list').length == 0 && jT.parents('.link').length > 0)
		{
		jT.parents('.link').children('.list').fadeOut();
		jT.parents('.link').children('.linktitle').removeClass('live');
		e.preventDefault();
		}
	else if (jTId == 'private_open')
		{
		$('div#private_life').fadeIn();
		$('div#hire_window').fadeOut();
		e.preventDefault();
		}
	else if (jTId == 'private_close' ||
	   		($('#private_life').length > 0
			 && $('div#private_life').css('display') != 'none'
			 && jT.parents('#private_life').length == 0
			 && jT.attr('id') != 'private_life'))
		{
		$('div#private_life').fadeOut();
		e.preventDefault();
		}
	else if (jTId == 'private_send')
		{
		send_priv_mail();
		e.preventDefault();
		}


	}

var highlight = function()
	{
	var words   = [];
	var element = '';

	return {
		words : words_set,
		element : element_set,
		execute : words_highlight
		}

	function words_set(Words)
		{
		words = Words;
		return this;
		}

	function words_highlight()
		{
		words_pointer();
		words_parser();
		return true;
		}

	function words_pointer()
		{
		element.find('p').contents().each(function()
			{
			if (this.nodeType != 3) return
			var text = this.nodeValue;
			for (var i=0,l=words.length;i<l;i++)
				{
				var Word = patternize(words[i]);
				var Reg = new RegExp('('+Word+')','ig');
				text = text.replace(Reg,'[[$1#'+i+']]');
				}
			this.nodeValue = text;
			});
		return true;
		}

	function words_parser()
		{
		element.children().each(function()
			{
			var text = $(this).html();
			for (var i=0,l=words.length;i<l;i++)
				{
				text = text.replace(/\[\[([^#]+)#([^\]]+)\]\]/g
								   ,'<span class="match keyw_$2">$1</span>');
				}
			$(this).html(text);
			});
		return true;
		}

	function patternize(Word)
		{
		var Letters  = ['[aàáâãäåæ]' ,'[cç]' ,'[eèéêë]'
					   ,'[ìiíîï]' ,'[òóoôõöø]' ,'[ùuúû]'];
		for (var i=0,l=Letters.length;i<l;i++)
			{
			var L = Letters[i];
			var R = new RegExp(L,'g');
			Word = Word.replace(R,L);
			}
		return Word;
		}

	function element_set(El)
		{
		element = El;
		return this;
		}
	}();

function send_priv_mail()
	{
	var terms = $('#priv_terms').val();
	var email = $('#priv_mail').val();
	var comm  = $('#priv_comment').val();
	var url   = $('#priv_url').html();
	if (terms == '')
		{
		alert(dict.get('terms_empty'));
		return;
		}
	$.ajax({
		type: 	'POST',
		url : 	'//'+Host+'/mail.php',
		data:	{terms:terms,email:email,comment:comm,url:url},
		success:send_priv_success
	});
	}

function send_priv_success(Resp,St,Xhttp)
	{
	if (Resp.indexOf('ok') >= 0)
		{
		alert(dict.get('priv_success'));
		$('div#private_life').fadeOut();
		return;
		}
	alert(dict.get('priv_failed'));
	return;
	}

