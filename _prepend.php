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

$core->blog->settings->addNamespace('newsletter');
$blog_settings =& $core->blog->settings->newsletter;

# autoload classes
$__autoload['dcNewsletter'] = dirname(__FILE__).'/inc/class.dc.newsletter.php';
$__autoload['newsletterSettings'] = dirname(__FILE__).'/inc/class.newsletter.settings.php';
$__autoload['newsletterPlugin'] = dirname(__FILE__).'/inc/class.newsletter.plugin.php';
$__autoload['newsletterTools'] = dirname(__FILE__).'/inc/class.newsletter.tools.php';
$__autoload['newsletterCore'] = dirname(__FILE__).'/inc/class.newsletter.core.php';
$__autoload['newsletterSubscribersList'] = dirname(__FILE__).'/inc/lib.newsletter.subscribers.list.php';
$__autoload['newsletterLetter'] = dirname(__FILE__).'/inc/class.newsletter.letter.php';
$__autoload['newsletterLettersList'] = dirname(__FILE__).'/inc/lib.newsletter.letters.list.php';
$__autoload['newsletterLinkedPostList'] = dirname(__FILE__).'/inc/lib.newsletter.linked.post.list.php';
$__autoload['newsletterCSS'] = dirname(__FILE__).'/inc/class.newsletter.css.php';
$__autoload['newsletterBehaviors'] = dirname(__FILE__).'/inc/class.newsletter.behaviors.php';

if ($blog_settings->newsletter_flag) {
	
	require dirname(__FILE__).'/_services.php';
	
	$core->url->register('newsletter','newsletter','^newsletter/(.+)$',array('urlNewsletter','newsletter'));
	$core->url->register('letterpreview','letterpreview','^letterpreview/(.+)$',array('urlNewsletter','letterpreview'));
	$core->url->register('letter','letter','^letter/(.+)$',array('urlNewsletter','letter'));
	
	$core->blog->dcNewsletter = new dcNewsletter($core);
	$core->setPostType('newsletter','plugin.php?p=newsletter&m=letter&id=%d',$core->url->getBase('newsletter').'/%s');

	$core->url->register('newsletters','newsletters','^newsletters(.*)$',array('urlNewsletter','newsletters'));
	
	$core->url->register('newsletterRest','newsletterRest','^newsletterRest',array('urlNewsletter','newsletterRestService'));
	
	# Dynamic methods
	$core->rest->addFunction('prepareALetter', array('newsletterRest','prepareALetter'));
	$core->rest->addFunction('sendLetterBySubscriber', array('newsletterRest','sendLetterBySubscriber'));
}

?>