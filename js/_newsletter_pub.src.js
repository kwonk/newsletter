/* -- BEGIN LICENSE BLOCK ----------------------------------
 *
 * This file is part of newsletter, a plugin for Dotclear 2.
 * 
 * Copyright (c) 2009-2015 Benoit de Marne and contributors
 * benoit.de.marne@gmail.com
 * Many thanks to Association Dotclear
 * 
 * Licensed under the GPL version 2.0 license.
 * A copy of this license is available in LICENSE file or at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * -- END LICENSE BLOCK ------------------------------------*/

$(document).ready(function($){

	function recharger(data){
		if ($(data).find('rsp').attr('status') == 'ok') {
			var item=$(data).find('item');
			var src=$(item).attr('src');
			var checkid=$(item).attr('checkid');
		
			$("#nl_captcha_img").attr('src',src);
			$("#nl_checkid").attr('value',checkid);
			$("#nl_captcha_imgname").attr('src',src);

			$.post(newsletter_rest_service_pub, {
				f: 'newsletterDeleteImgCaptcha',
				captcha_imgname: src		  	
			}, deleteImgCaptcha	
			);
		} else {
			$("#newsletter-pub-message").empty();
			$("#newsletter-pub-message").html(newsletter_msg_reload_failed);
		}		
	}
	
	$('newsletter-widget').on("click", '#nl_reload_captcha', function() {
		$.post(newsletter_rest_service_pub, {
			f: 'newsletterReloadCaptcha'
		}, recharger);
		return false;		
	});
	
	function afficher(data){
		$("#newsletter-pub-message").empty();
		$("#newsletter-pub-message").html(please_wait);
		
		if ($(data).find('rsp').attr('status') == 'ok') {
			var item=$(data).find('item');
			var email=$(item).attr('email');
			var result=$(item).attr('result');
			
			$("#nl_form").empty();
			$("#newsletter-pub-message").empty();
			//$("#newsletter-pub-message").html(email);
			//$("#newsletter-pub-message").append('<br />'+result);
			$("#newsletter-pub-message").html(result);
						
		} else {
			$("#newsletter-pub-message").empty();
			$("#newsletter-pub-message").html($(data).find('message').text());
			$.post(newsletter_rest_service_pub, {
				f: 'newsletterReloadCaptcha'
			}, recharger);			
		}
	}
	
	$('#nl_form').submit(function(){
		params = {
				f: 'newsletterSubmitFormSubscribe',
			  	email: $('#nl_email').val(),
			  	option: $('#nl_option').val(),
			  	captcha: $('#nl_captcha').val(),
			  	checkid: $('#nl_checkid').val(),
			  	modesend: $('#nl_modesend').val(),
			  	captcha_imgname: $('#nl_captcha_imgname').val()		
		}
		$.post(newsletter_rest_service_pub, params, afficher);
		return false;
	});

	/*
	function deleteImgCaptcha(data){
		if ($(data).find('rsp').attr('status') == 'ok') {
			var item=$(data).find('item');
			var result=$(item).attr('result');
			$("#newsletter-pub-message").html(result);
		} else {
			$("#newsletter-pub-message").empty();
			$("#newsletter-pub-message").html('unable to delete temporary file);
		}		
	}
	//*/		

	$(window).load(function(){
		$.post(newsletter_rest_service_pub, {
			f: 'newsletterDeleteImgCaptcha',
			captcha_imgname: $('#nl_captcha_imgname').val()		  	
		}, ''
		);
		return false;
	});

});
