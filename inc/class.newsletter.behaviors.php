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

if (!defined('DC_CONTEXT_ADMIN')) { return; }

require dirname(__FILE__).'/class.newsletter.mail.php';
require_once dirname(__FILE__).'/class.html2text.php';

# Define behaviors
class newsletterBehaviors
{
	/**
	* Before delete plugin
	*/
	public static function pluginsBeforeDelete($plugin)
	{
		$name = (string) $plugin['name'];
		if (strcmp($name, newsletterPlugin::pname()) == 0) {
         	require dirname(__FILE__).'/inc/class.newsletter.admin.php';
			newsletterAdmin::uninstall();
		}
	}
    
	/**
	* Automatic send after create post
	*/
	public static function adminAutosend($cur, $post_id)
	{
		global $core;

		# recupere le contenu du billet
		$params = array();
		$params['post_id'] = (integer) $post_id;

		$rs = $core->blog->getPosts($params);

		if (!$rs->isEmpty() && $rs->post_status == 1) {
			newsletterCore::autosendNewsletter((integer)$post_id);
		}
	}

	/**
	* Automatic send after update post
	*/
	public static function adminAutosendUpdate($cur, $post_id)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		
		if($newsletter_settings->getSendUpdatePost()) {
			# recupere le contenu du billet
			$params = array();
			$params['post_id'] = (integer) $post_id;
	
			$rs = $core->blog->getPosts($params);
		
			if (!$rs->isEmpty() && $rs->post_status == 1) {
				newsletterCore::autosendNewsletter((integer)$post_id);
			}
		}
	}
	
	/**
	* Behaviors export
	*/
	public static function exportFull($core,$exp)
	{
		$exp->exportTable('newsletter');
	}

	public static function exportSingle($core,$exp,$blog_id)
	{
		$exp->export('newsletter',
	   		'SELECT subscriber_id, blog_id, email, regcode, state, subscribed, lastsent, modesend '.
	   		'FROM '.$core->prefix.'newsletter N '.
	   		"WHERE N.blog_id = '".$blog_id."'"
		);
	}

	/**
	* Behaviors import
	*/
	public static function importInit($bk,$core)
	{
		$bk->cur_newsletter = $core->con->openCursor($core->prefix.'newsletter');
	}

	public static function importSingle($line,$bk,$core)
	{
		if ($line->__name == 'newsletter') {
			
			$cur = $core->con->openCursor($core->prefix.'newsletter');
			$bk->cur_newsletter->subscriber_id	= (integer) $line->subscriber_id;
			$bk->cur_newsletter->blog_id 		= (string) $core->blog_id;
			$bk->cur_newsletter->email 		= (string) $line->email;
			$bk->cur_newsletter->regcode 		= (string) $line->regcode;
			$bk->cur_newsletter->state 		= (string) $line->state;
			$bk->cur_newsletter->subscribed 	= (string) $line->subscribed;
			$bk->cur_newsletter->lastsent 	= (string) $line->lastsent;
			$bk->cur_newsletter->modesend 	= (string) $line->modesend;
			
			newsletterCore::add($bk->cur_newsletter->email, (string) $core->blog_id, $bk->cur_newsletter->regcode, $bk->cur_newsletter->modesend);

			$subscriber = newsletterCore::getEmail($bk->cur_newsletter->email);
			if ($subscriber != null) {
				newsletterCore::update($subscriber->subscriber_id, 
					$bk->cur_newsletter->email, 
					$bk->cur_newsletter->state, 
					$bk->cur_newsletter->regcode, 
					$bk->cur_newsletter->subscribed, 
					$bk->cur_newsletter->lastsent, 
					$bk->cur_newsletter->modesend
				);
			}
		}
	}

	public static function importFull($line,$bk,$core)
	{
		if ($line->__name == 'newsletter') {
			
			$bk->cur_newsletter->clean();
			
			$bk->cur_newsletter->subscriber_id	= (integer) $line->subscriber_id;
			$bk->cur_newsletter->blog_id 		= (string) $line->blog_id;
			$bk->cur_newsletter->email 			= (string) $line->email;
			$bk->cur_newsletter->regcode 		= (string) $line->regcode;
			$bk->cur_newsletter->state 			= (string) $line->state;
			$bk->cur_newsletter->subscribed 	= (string) $line->subscribed;
			$bk->cur_newsletter->lastsent 		= (string) $line->lastsent;
			$bk->cur_newsletter->modesend 		= (string) $line->modesend;
			
			$bk->cur_newsletter->insert();
		}
	}
}

?>