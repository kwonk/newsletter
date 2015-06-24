<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of newsletter, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2015 Benoit de Marne and contributors
# benoit.de.marne@gmail.com
# Many thanks to Association Dotclear
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) { return; }

# Initialisation des widgets
$core->addBehavior('initWidgets', array('newsletterWidgets', 'initWidgets'));

class newsletterWidgets 
{
	public static function initWidgets($w)
	{
		global $core, $plugin_name;
      	try {
      		# widget newsletter
			$w->create('newsletter', __('Newsletter: subscription form'), array('publicWidgetsNewsletter', 'initWidgets'),
			null,
			__('Link or subscription form'));
			$w->newsletter->setting('title', __('Title').' : ', __('Newsletter'));
			$w->newsletter->setting('inwidget', __('Print subscription form in widget'), false, 'check');
									
			$w->newsletter->setting('subscription_link',__('Text of subscription link').' : ',__('Subscription link'));
			$w->newsletter->setting('homeonly', __('Display on:'),0,'combo',
				array(
					__('All pages') => 0,
					__('Home page only') => 1,
					__('Except on home page') => 2
				)
      );
      $w->newsletter->setting('content_only',__('Content only'),0,'check');
      $w->newsletter->setting('class',__('CSS class:'),'');
  		$w->newsletter->setting('offline',__('Offline'),0,'check');


			# widget newsletters
			$w->create('listnsltr', 'Newsletters', array('publicWidgetsNewsletter', 'listnsltrWidget'),
			null,
			__('List of newsletters'));
			$w->listnsltr->setting('title', __('Title').' : ', __('Newsletters'));
			$w->listnsltr->setting('limit',__('Limit (empty means no limit):'),'5');			
			$w->listnsltr->setting('orderby',__('Order by'),'name','combo',
			array(__('Newsletter name') => 'name', __('Newsletter date') => 'date'));
			$w->listnsltr->setting('orderdir',__('Sort:'),'desc','combo',
			array(__('Ascending') => 'asc', __('Descending') => 'desc'));			
			$w->listnsltr->setting('homeonly', __('Display on:'),0,'combo',
				array(
					__('All pages') => 0,
					__('Home page only') => 1,
					__('Except on home page') => 2
				)
			);
      $w->listnsltr->setting('content_only',__('Content only'),0,'check');
      $w->listnsltr->setting('class',__('CSS class:'),'');
  		$w->listnsltr->setting('offline',__('Offline'),0,'check');
			
	      
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}
}