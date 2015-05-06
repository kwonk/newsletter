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

dcPage::check('newsletter,contentadmin');

$blog_settings =& $core->blog->settings->newsletter;
$system_settings =& $core->blog->settings->system;

$newsletter_flag = (boolean)$blog_settings->newsletter_flag;

# retrieve module
$m = (!empty($_REQUEST['m'])) ? (string) rawurldecode($_REQUEST['m']) : 'subscribers';

if ( $newsletter_flag == 0) {
	require_once dirname(__FILE__).'/inc/index.properties.php';
} elseif (!empty($_REQUEST['m'])) {
	switch ($_REQUEST['m']) {
		case 'resume':
		case 'planning':
		case 'settings':
		case 'messages':
		case 'maintenance':
		case 'editCSS':
		case 'editFormsCSS':
			require_once dirname(__FILE__).'/inc/index.properties.php';
			break;
		case 'letters':
		case 'letter':
		case 'letter_associate':
			require_once dirname(__FILE__).'/inc/index.letters.php';
			break;
		case 'subscribers':
		case 'add_subcriber':
		case 'edit_subcriber':
		default:
			require_once dirname(__FILE__).'/inc/index.subscribers.php';
			break;
	}
} else {
	require_once dirname(__FILE__).'/inc/index.letters.php';
}

?>