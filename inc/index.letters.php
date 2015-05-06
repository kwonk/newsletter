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

# Loading librairies
require_once dirname(__FILE__).'/class.newsletter.settings.php';
require_once dirname(__FILE__).'/class.newsletter.tools.php';
require_once dirname(__FILE__).'/class.newsletter.core.php';
require_once dirname(__FILE__).'/class.newsletter.plugin.php';
require_once dirname(__FILE__).'/class.newsletter.admin.php';
require_once dirname(__FILE__).'/lib.newsletter.subscribers.list.php';

try {
	$blog_settings =& $core->blog->settings->newsletter;
	$system_settings =& $core->blog->settings->system;
	$blog = &$core->blog;
	$auth = &$core->auth;
	$settings = &$blog->settings;
		
	# Url
	$url = &$core->url;
	$blog_url = html::stripHostURL($core->blog->url);
	$urlBase = newsletterTools::concatURL($blog_url, $url->getBase('newsletter'));
	$p_url = 'plugin.php?p=newsletter';
	$msg = '';
	
	# setting variables
	$plugin_name = 'Newsletter';
	$id = null;
	$page_title = 'Newsletter';
	$newsletter_settings = new newsletterSettings($core);

	# retrieve module
	$m = (!empty($_REQUEST['m'])) ? (string) rawurldecode($_REQUEST['m']) : 'subscribers';
	
	# retrieve tab
	$plugin_tab = 'tab_'.$m;
	
	if (isset($_REQUEST['tab'])) {
		$plugin_tab = 'tab_'.$_REQUEST['tab'];
	}
	
	# retrieve operation
	$plugin_op = (!empty($_POST['op'])) ? (string)$_POST['op'] : 'none';
	
	# retrieve action on letters
	$action =  (!empty($_POST['action'])) ? (string) $_POST['action'] : 'none';
		
	# retrieve message to print
	if (!empty($_GET['msg']))
		$msg = (string) rawurldecode($_GET['msg']);
	else if (!empty($_POST['msg']))
		$msg = (string) rawurldecode($_POST['msg']);
	
	###############################################
	# operations
	###############################################
	switch ($plugin_op)
	{
		###############################################
		# MAILING
		###############################################
		
		# send newsletter
		case 'send':
		{
			if(!empty($_POST['subscriber'])) {
				$subscribers_id = array();
				$subscribers_id = $_POST['subscriber'];
		
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					# check if users are enabled
					if ($subscriber = newsletterCore::get((integer) $v)){
						if ($subscriber->state == 'enabled') {
							$ids[$k] = (integer) $v;
						}
					}
				}
				$subscribers_id = $ids;
			} else {
				throw new Exception(__('no user selected'));
				//newsletterTools::redirection($m,$msg);
			}
		}
		break;
		
		case 'none':
		default:
		break;
	} # end switch

	###############################################
	# actions on letters
	###############################################
	switch ($action)
	{
		case 'publish':
		case 'unpublish':
		case 'pending':
		case 'delete':
			{
				$letters_id = array();
				if(!empty($_POST['letters_id'])) $letters_id = $_POST['letters_id'];
				newsletterLettersList::lettersActions($letters_id);
			}
			break;
	
		case 'send_old':
			{
				$subscribers_id = array();
				$letters_id = array();
				$newsletter_mailing = new newsletterMailing($core);
				$newsletter_settings = new newsletterSettings($core);
				$letters_id[] = newsletterCore::insertMessageNewsletter($newsletter_mailing,$newsletter_settings);
					
				if($letters_id[0]==0) {
					$t_msg='';
					$t_msg.=date('Y-m-j H:i',time() + dt::getTimeOffset($system_settings->blog_timezone)).': ';
					$t_msg.=__('not enough posts for sending');
					throw new Exception($t_msg);
				}
	
				if(!empty($_POST['subscribers_id'])) {
					$subscribers_id = $_POST['subscribers_id'];
				}
			}
			break;
	
		case 'send':
		case 'author':
			{
				$subscribers_id = array();
				if(!empty($_POST['letters_id']))
					$letters_id = $_POST['letters_id'];
				else
					throw new Exception(__('no letter selected'));
					
				if(!empty($_POST['subscribers_id'])) {
					$subscribers_id = $_POST['subscribers_id'];
				}
			}
			break;
	
		case 'associate':
		case 'unlink':
			{
				$newsletterLetter = new newsletterLetter($core);
				$newsletterLetter->letterActions();
			}
			break;
	
		case 'none':
		default:
			break;
	} # end switch	

	} catch (Exception $e) {
		if(isset($core->blog->dcNewsletter)) {
			$core->blog->dcNewsletter->addError($e->getMessage());
			$core->blog->dcNewsletter->save();
			newsletterTools::redirection($m,$msg);
		}
	}	
	
if(isset($core->blog->dcNewsletter)) {
	# Display errors
	foreach ($core->blog->dcNewsletter->getErrors() as $k => $v) {
		$core->error->add($v);
		$core->blog->dcNewsletter->delError($k);
	}
	
	# Get messages
	foreach ($core->blog->dcNewsletter->getMessages() as $k => $v) {
		$msg .= $v.'<br />';
		$core->blog->dcNewsletter->delMessage($k);
	}
	
	# Save
	$core->blog->dcNewsletter->save();
}
	
try {
	# --- Variables for page Letters ---
	# Creating filter combo boxes
	$sortby_combo = array(
	__('Mailing date') => 'post_dt',
	__('Title') => 'post_title',
	__('Author') => 'user_id',
	__('Status') => 'post_status'
			);
	
	$order_combo = array(
			__('Descending') => 'desc',
			__('Ascending') => 'asc'
	);
	
	# Actions combo box
	$combo_action = array();
		
	if ($core->auth->check('publish,contentadmin',$core->blog->id))
	{
	
		$combo_action[__('Newsletter')]=array(
				__('send') => 'send'
		);
			
		$combo_action[__('Changing state')] = array(
				__('publish') => 'publish',
				__('unpublish') => 'unpublish',
				__('mark as pending') => 'pending',
				__('delete') => 'delete'
		);
	
		if ($core->auth->check('admin',$core->blog->id)) {
			$combo_action[__('Changing state')][__('change author')]='author';
		}
	}
	
	$params = array(
		'post_type' => 'newsletter'
	);
	
	$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'post_dt';
	$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';
	$page = !empty($_GET['page']) ? max(1,(integer) $_GET['page']) : 1;
	$nb_per_page =  30;
	$show_filters = false;	
	
	if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
		if ($nb_per_page != $_GET['nb']) {
			$show_filters = true;
		}
		$nb_per_page = (integer) $_GET['nb'];
	}
	
	$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
	$params['no_content'] = true;
	
	# - Sortby and order filter
	if ($sortby !== '' && in_array($sortby,$sortby_combo)) {
		if ($order !== '' && in_array($order,$order_combo)) {
			$params['order'] = $sortby.' '.$order;
		} else {
			$order='desc';
		}
	
		if ($sortby != 'post_dt' || $order != 'desc') {
			$show_filters = true;
		}
	} else {
		$sortby='post_dt';
		$order='desc';
	}	
	
	# Request the letters list
	$rs = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	$letters_list = new newsletterLettersList($core,$rs,$counter->f(0));	
	
	$show_filters = false;
	$form_filter_title = __('Show filters and display options');
	# --- end Variables for page Letters ---
	
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}	


###############################################
# define content of the html page
###############################################
?>

<html>
<head>
	<title><?php echo $page_title ?></title>
	<link rel="stylesheet" type="text/css" href="index.php?pf=newsletter/style.css" />

<?php	

	if ($plugin_tab == 'tab_letters'
		&& ($action == 'send' || $action == 'send_old')) {
		echo
		dcPage::jsLoad('index.php?pf=newsletter/js/_sequential_ajax.js').
		dcPage::jsLoad('index.php?pf=newsletter/js/_letters_actions.js');
	
		echo
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"var letters = [".implode(',',$letters_id)."];\n".
		"var subscribers = [".implode(',',$subscribers_id)."];\n".
		dcPage::jsVar('dotclear.msg.search_subscribers_for_letter', __('Search subscribers for letter')).
		dcPage::jsVar('dotclear.msg.subject', __('Subject')).
		dcPage::jsVar('dotclear.msg.to_user', __('to user')).
		dcPage::jsVar('dotclear.msg.please_wait', __('Waiting...')).
		dcPage::jsVar('dotclear.msg.subscribers_found', __('%s subscriber(s) found')).
		dcPage::jsVar('dotclear.msg.confirm_delete_subscribers', __('Are you sure you want to delete selected subscribers')).
		"\n//]]>\n".
		"</script>\n";
		echo dcPage::jsPageTabs($plugin_tab);

	} else if ($plugin_tab == 'tab_letter') {
		// move in class.newsletter.letter.php
		;
	} else if ($plugin_tab == 'tab_letter_associate') {
		echo dcPage::jsLoad('index.php?pf=newsletter/js/_newsletter.js');
		echo
			'<script type="text/javascript">'."\n".
			"//<![CDATA[\n".
			dcPage::jsVar('dotclear.msg.show_filters', $show_filters ? 'true':'false')."\n".
			dcPage::jsVar('dotclear.msg.filter_subscribers_list',$form_filter_title)."\n".
			dcPage::jsVar('dotclear.msg.cancel_the_filter',__('Cancel filters and display options'))."\n".
			"\n//]]>\n".
			"</script>\n";
		echo dcPage::jsPageTabs('tab_letter');
	} else { 
		echo dcPage::jsLoad('index.php?pf=newsletter/js/_newsletter.js');
		echo
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		dcPage::jsVar('dotclear.msg.confirm_delete_letters', __('Are you sure you want to delete selected letters?')).
		dcPage::jsVar('dotclear.msg.confirm_delete_subscribers', __('Are you sure you want to delete selected subscribers?')).
		dcPage::jsVar('dotclear.msg.show_filters', $show_filters ? 'true':'false')."\n".
		dcPage::jsVar('dotclear.msg.filter_subscribers_list',$form_filter_title)."\n".
		dcPage::jsVar('dotclear.msg.cancel_the_filter',__('Cancel filters and display options'))."\n".
		"\n//]]>\n".
		"</script>\n";
		echo dcPage::jsPageTabs($plugin_tab);
	}
	
?>
</head>
<body>

<?php
echo dcPage::breadcrumb(
	array(
		html::escapeHTML($core->blog->name) => '',
		$plugin_name.' '.newsletterPlugin::dcVersion() => $p_url
	)).dcPage::notices();

# print information message
if (!empty($msg)) {
	dcPage::success($msg);
}

if ($newsletter_flag != 0) {
	echo
		'<ul class="pseudo-tabs">'.
			'<li><a href="'.$p_url.'&amp;m=letters" class="active">'.__('Letters').'</a></li>'.
			'<li><a href="'.$p_url.'&amp;m=subscribers">'.__('Subscribers').'</a></li>'.
			'<li><a href="'.$p_url.'&amp;m=resume">'.__('Properties').'</a></li>'.
		'</ul>';
}	
try {
	
	if($plugin_tab == 'tab_letter') {
		$nltr = new newsletterLetter($core);
		$nltr->displayTabLetter();
	} else if ($plugin_tab == 'tab_letter_associate') {
		$nltr = new newsletterLetter($core);
		$nltr->displayTabLetterAssociate();
	} else {
		// Print page Letters
		if($action == 'author' || $action == 'send' || $action == 'send_old') {
			newsletterLettersList::lettersActions($letters_id);
		} else {
			echo '<p class="top-add"><a class="button add" href="plugin.php?p=newsletter&amp;m=letter">'.__('New newsletter').'</a></p>';
		
			if (!$core->error->flag())
			{
				echo
				'<form action="plugin.php" method="get" id="filters-form">'.
				'<h3 class="out-of-screen-if-js">'.$form_filter_title.'</h3>'.
				'<div class="table">'.
				'<div class="cell">'.
				'<h4>'.__('Filters').'</h4>'.
				'<p><span class="label ib">'.__('Show').'</span> <label for="nb" class="classic">'.
				form::field('nb',3,3,$nb_per_page).' '.
				__('Letters per page').'</label></p>'.
				'</div>'.
				'<div class="cell filters-options">'.
				'<h4>'.__('Display options').'</h4>'.
				'<p><label for="sortby" class="ib">'.__('Order by:').'</label> '.
				form::combo('sortby',$sortby_combo,$sortby).'</p>'.
				'<p><label for="order" class="ib">'.__('Sort:').'</label> '.
				form::combo('order',$order_combo,$order).'</p>'.
				'</div>'.
				'</div>'.
		
				'<p>'.
				form::hidden(array('p'),newsletterPlugin::pname()).
				form::hidden(array('m'),"letters").
				'<input type="submit" value="'.__('Apply filters and display options').'" /></p>'.
				'</p>'.
					
				'<br class="clear" /></p>'. //Opera sucks
				'</form>';
			}
				
			// Show letters
			$letters_list->display($page,$nb_per_page,
					'<form action="plugin.php?p=newsletter&amp;m=letters" method="post" id="letters_list">'.
					'<p>' .
						
					'%s'.
		
					'<div class="two-cols">'.
					'<p class="col checkboxes-helpers"></p>'.
					'<p class="col right">'.__('Selected letters action:').
					form::combo('action',$combo_action).
					form::hidden(array('m'),'letters').
					form::hidden(array('p'),newsletterPlugin::pname()).
					form::hidden(array('sortby'),$sortby).
					form::hidden(array('order'),$order).
					form::hidden(array('page'),$page).
					form::hidden(array('nb'),$nb_per_page).
					form::hidden(array('post_type'),'newsletter').
					form::hidden(array('redir'),html::escapeHTML($_SERVER['REQUEST_URI'])).
					$core->formNonce().
					'<input type="submit" value="'.__('ok').'" />'.
					'</p>'.
					'</div>'.
					'</form>',
					$show_filters
			);
		}
	}
	
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}	

dcPage::helpBlock('newsletter');
?>

</body>
</html>