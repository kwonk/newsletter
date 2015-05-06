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

# Rights management
if (!defined('DC_CONTEXT_ADMIN')) { return; }

if ($core->auth->check('newsletter,contentadmin',$core->blog->id)) {
	# Adding behaviors
	$core->addBehavior('pluginsBeforeDelete', array('newsletterBehaviors', 'pluginsBeforeDelete'));
	$core->addBehavior('adminAfterPostCreate', array('newsletterBehaviors', 'adminAutosend'));
	$core->addBehavior('adminAfterPostUpdate', array('newsletterBehaviors', 'adminAutosendUpdate'));

	$core->addBehavior('adminDashboardFavorites',array('newsletterDashboard','newsletterDashboardFavs'));
	
	# Adding import/export behavior
	$core->addBehavior('exportFull',array('newsletterBehaviors','exportFull'));
	$core->addBehavior('exportSingle',array('newsletterBehaviors','exportSingle'));
	$core->addBehavior('importInit',array('newsletterBehaviors','importInit'));
	$core->addBehavior('importFull',array('newsletterBehaviors','importFull'));
	$core->addBehavior('importSingle',array('newsletterBehaviors','importSingle'));
}

class newsletterDashboard
{
	public static function newsletterDashboardFavs($core,$favs)
	{
		$favs->register('newsletter', array(
				'title' => 'Newsletter',
				'url' => 'plugin.php?p=newsletter',
				'small-icon' => 'index.php?pf=newsletter/icon.png',
				'large-icon' => 'index.php?pf=newsletter/icon-big.png',
				'permissions' => 'contentadmin,newsletter',
				'dashboard_cb' => array('newsletterDashboard','newsletterDashboardCB'),
				'active_cb' => array('newsletterDashboard','newsletterActiveCB')
		));
		$favs->register('newNewsletter', array(
				'title' => __('New').' newsletter',
				'url' => 'plugin.php?p=newsletter&amp;m=letter',
				'small-icon' => 'index.php?pf=newsletter/icon-np.png',
				'large-icon' => 'index.php?pf=newsletter/icon-np-big.png',
				'permissions' => 'contentadmin,newsletter',
				'active_cb' => array('newsletterDashboard','newNewsletterActiveCB')
		));
	}

	public static function newsletterDashboardCB($core,$v)
	{
		$params = new ArrayObject();
		$params['post_type'] = 'newsletter';
		$newsletter_count = newsletterLettersList::countLetters('1');
		$subscriber_count = newsletterSubscribersList::countSubscribers('enabled');
		
		$v['title'] = sprintf(__('%d newsletter','%d newsletters',$newsletter_count),$newsletter_count);
		$v['title'] .= '<br />';
		$v['title'] .= sprintf(__('%d subscriber','%d subscribers',$subscriber_count),$subscriber_count);
	}
	
	public static function newsletterActiveCB($request,$params)
	{
		return ($request == "plugin.php") &&
		isset($params['p']) && $params['p'] == 'newsletter'
				&& !(isset($params['m']) && $params['m']=='letter');
	}
	
	public static function newNewsletterActiveCB($request,$params)
	{
		return ($request == "plugin.php") &&
		isset($params['p']) && $params['p'] == 'newsletter'
				&& isset($params['m']) && $params['m']=='letter';
	}	
}

# Admin menu integration
$_menu['Blog']->addItem('Newsletter','plugin.php?p=newsletter','index.php?pf=newsletter/icon.png',
		preg_match('/plugin.php(.*)$/',$_SERVER['REQUEST_URI']) && !empty($_REQUEST['p']) && $_REQUEST['p']=='newsletter',
		$core->auth->check('newsletter,contentadmin', $core->blog->id)
);

# Adding permission
$core->auth->setPermissionType('newsletter',__('manage newsletters'));

require dirname(__FILE__).'/_widgets.php';

?>