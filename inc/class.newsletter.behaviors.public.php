<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of newsletter, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2014 Benoit de Marne and contributors
# benoit.de.marne@gmail.com
# Many thanks to Association Dotclear
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) { return; }

class dcBehaviorsNewsletterPublic
{
	public static function publicHeadContent($core,$_ctx)
	{
		$blog_settings =& $core->blog->settings->newsletter;
		$system_settings =& $core->blog->settings->system;
		
		try {
			$newsletter_flag = (boolean)$blog_settings->newsletter_flag;
			
			# plugin status
			if (!$newsletter_flag) 
				return;		
		
			if($core->url->type == "newsletter") {
				$letter_css = new newsletterCSS($core, 'style_letter.css');
				$css_style = '<style type="text/css" media="screen">'.$letter_css->getLetterCSS().'</style>';
				echo $css_style;
			}
			//echo '<link rel="stylesheet" type="text/css" href="?pf=newsletter/style_pub_newsletter.css" />'."\n";
			$newsletter_settings = new newsletterSettings($core);

			if($newsletter_settings->getUseCSSForms()) {
				$letter_css = new newsletterCSS($core, 'style_pub_newsletter.css');
				$css_style = '<style type="text/css" media="screen">'.$letter_css->getLetterCSS().'</style>';
				echo $css_style;
			}

			echo
			'<script type="text/javascript">'."\n".
			"//<![CDATA[\n".
			"var please_wait = '".html::escapeJS(__('Waiting...'))."';\n".
			"var newsletter_rest_service_pub = '".html::escapeJS($core->blog->url.$core->url->getBase('newsletterRest'))."';\n".
			"var newsletter_img_src = '".html::escapeJS($core->blog->url.'pf='.$core->url->getBase('newsletter'))."';\n".
			"var newsletter_msg_reload_failed = '".html::escapeJS(__('unable to reload'))."';\n".
			"var newsletter_msg_register_success = '".html::escapeJS(__('has successfully registered'))."';\n".
			"//]]>\n".
			"</script>\n";

			$res_js = '<script type="text/javascript" src="';
			if($core->url->mode == 'path_info') {
				$res_js .= html::escapeURL($core->blog->url.'?pf=newsletter/js/_newsletter_pub.js');
			} else {
				$res_js .= html::escapeURL($core->blog->url.'pf=newsletter/js/_newsletter_pub.js');
			}
			$res_js .= '"></script>'."\n";
			echo $res_js;
			
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}

	public static function translateKeywords($core,$tag,$args)
	{
		global $_ctx;
		if($tag != 'EntryContent' # tpl value
		 || $args[0] == '' # content
		 || $args[2] # remove html
		 || $core->url->type != 'newsletter'
		) return;

		$nltr = new newsletterLetter($core,(integer)$_ctx->posts->post_id);
		$body = $args[0];
		
		$body = $nltr->rendering($body, $_ctx->posts->getURL());
		$args[0] = $nltr->renderingSubscriber($body, '');

		return;
	}
	
	/**
	 * Add entry in newsletter when an user is added in the plugin "Agora" 
	 * @param $cur
	 * @param $user_id
	 * @return unknown_type
	 */
	public static function newsletterUserCreate($cur,$user_id)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);

		if($newsletter_settings->getCheckAgoraLink()) {
			$email = $cur->user_email;
			try {
				if (!newsletterCore::accountCreate($email)) {
					throw new Exception(__('Error adding subscriber.').' '.$email);
				}
			} catch (Exception $e) {
				throw new Exception('Plugin Newsletter : '.$e->getMessage());
			}
		}
		return;
	}
}

?>