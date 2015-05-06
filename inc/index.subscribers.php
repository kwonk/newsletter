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
		
		# initialize field lastsent
		case 'lastsent':
		{
			$msg = __('No account changed.');
			if (is_array($_POST['subscriber'])) {
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
		
				if (newsletterCore::lastsent($ids, 'clear'))
					$msg = __('Account(s) successfully changed.');
				else
					throw new Exception(__('Error in modification of field last sent'));
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
		
		# send confirmation mail
		case 'sendconfirm':
		{
			if (is_array($_POST['subscriber'])) {
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
				$msg = newsletterCore::send($ids,'confirm');
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
		
		# send disable mail
		case 'senddisable':
		{
			if (is_array($_POST['subscriber'])) {
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
				$msg = newsletterCore::send($ids,'disable');
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
		
		# send enable mail
		case 'sendenable':
		{
			if (is_array($_POST['subscriber'])) {
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
				$msg = newsletterCore::send($ids,'enable');
			}
			newsletterTools::redirection($m,$msg);
		}
		break;		
		
		###############################################
		# SUBSCRIBERS
		###############################################
	
		# add subscriber
		case 'add':
		{
			$m = 'add_subscriber';
			
			$email = !empty($_POST['femail']) ? $_POST['femail'] : null;
	
			if (newsletterCore::add($email)) {
				$msg = __('Subscriber added.');
			} else {
				throw new Exception(__('Error adding subscriber'));
			}
	
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# Modify subscriber
		case 'edit':
		{
			$id = (!empty($_POST['id']) ? $_POST['id'] : null);
			$email = (!empty($_POST['femail']) ? $_POST['femail'] : null);
			$subscribed = (!empty($_POST['fsubscribed']) ? $_POST['fsubscribed'] : null);
			$lastsent = (!empty($_POST['flastsent']) ? $_POST['flastsent'] : null);
			$modesend = (!empty($_POST['fmodesend']) ? $_POST['fmodesend'] : null);
			$state = (!empty($_POST['fstate']) ? $_POST['fstate'] : null);
	
			if ($email == null) {
				if ($id == null) {
					throw new Exception(__('Missing informations'));
				} else {
					$plugin_tab = 'tab_edit_subscriber';
				}
			} else {
				$regcode = null;
				if (!newsletterCore::update($id, $email, $state, $regcode, $subscribed, $lastsent, $modesend)) {
					throw new Exception(__('Error to modify a subscriber'));
				} else {
					$msg = __('Subscriber updated.');
				}
			}
			newsletterTools::redirection($m,$msg);			
		}
		break;
	
		# remove subscribers
		case 'remove':
		{
			$msg = __('No account removed.');
			if (is_array($_POST['subscriber'])) {
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
	
				if (newsletterCore::delete($ids)) {
					$msg = __('Account(s) successfully removed');
				} else {
					throw new Exception(__('Error to remove account(s)'));
				}
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# suspend subscribers
		case 'suspend':
		{
			$msg = __('No account suspended.');
			if (is_array($_POST['subscriber'])) {
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
	
				if (newsletterCore::suspend($ids)) {
					$msg = __('Account(s) successfully suspended');
				} else {
					throw new Exception(__('Error to suspend account(s)'));
				}			
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# activate subscribers
		case 'enable':
		{
			$msg = __('No account enabled.');
			if (is_array($_POST['subscriber'])) {
				$ids = array();
	
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
				if (newsletterCore::enable($ids)) 
					$msg = __('Account(s) successfully enabled');
				else
					throw new Exception(__('Error to enable account(s)'));
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# disable subscribers
		case 'disable':
		{
			$msg = __('No account disabled.');
			if (is_array($_POST['subscriber'])) {
				$ids = array();
	
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
	
				if (newsletterCore::disable($ids)) 
					$msg = __('Account(s) successfully disabled');
				else
					throw new Exception(__('Error to disable account(s)'));
			}
			newsletterTools::redirection($m,$msg);	
		}
		break;
	
		# set mail format to html
		case 'changemodehtml':
		{
			$msg = __('No account updated.');
			if (is_array($_POST['subscriber'])) {
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
	
				if (newsletterCore::changemodehtml($ids)) 
					$msg = __('Format sending for account(s) successfully updated to html');
				else
					throw new Exception(__('Error in modification format'));
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# set mail format to text
		case 'changemodetext':
		{
			$msg = __('No account updated.');
			if (is_array($_POST['subscriber'])) {
				$ids = array();
				foreach ($_POST['subscriber'] as $k => $v) {
					$ids[$k] = (integer) $v;
				}
	
				if (newsletterCore::changemodetext($ids)) 
					$msg = __('Format sending for account(s) successfully updated to text');
				else
					throw new Exception(__('Error in modification format'));
			}
			newsletterTools::redirection($m,$msg);
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
	# --- Variables for page EditSubscriber ---
	$allowed = true;
	$mode_combo = array(__('text') => 'text',
			__('html') => 'html');
	
	$state_combo = array(__('pending') => 'pending',
			__('enabled') => 'enabled',
			__('suspended') => 'suspended',
			__('disabled') => 'disabled');

	# --- Variables for page Subscribers ---
	# Creating filter combo boxes
	$sortby_combo = array(
	__('Email') => 'email',
	__('Subscribed') => 'subscribed',
	__('Last sent') => 'lastsent',
	__('State') => 'state'
			);
	
	$order_combo = array(
			__('Descending') => 'desc',
			__('Ascending') => 'asc'
	);
	
	# Actions combo box
	$combo_action = array();
		
	if ($core->auth->check('publish,contentadmin',$core->blog->id))
	{
		if ($newsletter_settings->getCheckUseSuspend()) {
			$combo_action[__('Email to send')]=array(
					__('Newsletter') => 'send',
					__('Activation') => 'sendenable',
					__('Confirmation') => 'sendconfirm',
					__('Suspension') => 'sendsuspend',
					__('Desactivation') => 'senddisable'
			);
				
			$combo_action[__('Changing state')] = array(
					__('Enable') => 'enable',
					__('Suspend') => 'suspend',
					__('Disable') => 'disable',
					__('Delete') => 'remove'
			);
		} else {
			$combo_action[__('Email to send')]=array(
					__('Newsletter') => 'send',
					__('Activation') => 'sendenable',
					__('Confirmation') => 'sendconfirm',
					__('Desactivation') => 'senddisable'
			);
				
			$combo_action[__('Changing state')] = array(
					__('Enable') => 'enable',
					__('Disable') => 'disable',
					__('Delete') => 'remove'
			);
		}
	
		$combo_action[__('Changing format')] = array(
				__('html') => 'changemodehtml',
				__('text') => 'changemodetext'
		);
	
		$combo_action[__('Raz last sent')] = array(
				__('Last sent') => 'lastsent'
		);
	}
	
	$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'subscribed';
	$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';
	$page = !empty($_GET['page']) ? max(1,(integer) $_GET['page']) : 1;
	//$nb_per_page =  30;
	$nb_per_page = $newsletter_settings->getNbSubscribersPerpage();
	$show_filters = false;
	
	if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
		if ($nb_per_page != $_GET['nb']) {
			$show_filters = true;
		}
		$nb_per_page = (integer) $_GET['nb'];
	}
	
	$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);

	# - Sortby and order filter
	if ($sortby !== '' && in_array($sortby,$sortby_combo)) {
		if ($order !== '' && in_array($order,$order_combo)) {
			$params['order'] = $sortby.' '.$order;
		} else {
			$order='desc';
		}
	
		if ($sortby != 'subscribed' || $order != 'desc') {
			$show_filters = true;
		}
	} else {
		$sortby='subscribed';
		$order='desc';
	}
	
	// Request the subscribers list
	$rs = newsletterCore::getSubscribers($params);
	$counter = newsletterCore::getSubscribers($params,true);
	$subscribers_list = new newsletterSubscribersList($core,$rs,$counter->f(0));	
		
	$show_filters = false;
	$form_filter_title = __('Show filters and display options');
	# --- end Variables for page Subscribers ---
	
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

	if ($plugin_tab == 'tab_edit_subscribers') {
		echo dcPage::jsPageTabs($plugin_tab);
	} elseif ($plugin_tab == 'tab_subscribers'
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
			'<li><a href="'.$p_url.'&amp;m=letters">'.__('Letters').'</a></li>'.
			'<li><a href="'.$p_url.'&amp;m=subscribers" class="active">'.__('Subscribers').'</a></li>'.
			'<li><a href="'.$p_url.'&amp;m=resume">'.__('Properties').'</a></li>'.
		'</ul>';
}	
try {

	if($plugin_tab == 'tab_edit_subscriber') {

		if (!empty($_GET['id']))
		{
			$id = (integer)$_GET['id'];
			$datas = newsletterCore::get($id);
			if ($datas == null) {
				$allowed = false;
			} else {
				$email = $datas->f('email');
				$subscribed = $datas->f('subscribed');
				$lastsent = $datas->f('lastsent');
				$modesend = $datas->f('modesend');
				$regcode = $datas->f('regcode');
				$state = $datas->f('state');
		
				if ($subscribed != null)
					$subscribed = dt::dt2str($settings->date_format, $subscribed).' @'.dt::dt2str($settings->time_format, $subscribed);
				else
					$subscribed = __('Never');
		
				if ($lastsent != null)
					$lastsent = dt::dt2str($settings->date_format, $lastsent).' @'.dt::dt2str($settings->time_format, $lastsent);
				else
					$lastsent = __('Never');
			} 
			# --- end Variables for page AddSubscriber ---
		}

		// Print page Edit
		if (!$allowed) {
			echo __('Not allowed.');
		} else {
			echo
			'<form action="plugin.php" method="post" class="fieldset">'.
			'<h4>'.__('Edit a subscriber').'</h4>'.
		
			'<p class="field"><label for="femail">'.__('Email').'</label>'.
			form::field(array('femail','femail'),50,255,$email).
			'</p>'.
			
			'<p class="field"><label for="fsubscribed">'.__('Subscribed').'</label>'.
			form::field('fsubscribed',50,255,$subscribed,'','',true).
			'</p>'.
				
			'<p class="field"><label for="flastsent">'.__('Last sent').'</label>'.
			form::field('flastsent',50,255,$lastsent,'','',true).
			'</p>'.

			'<p class="field"><label for="fmodesend">'.__('Mode send').'</label>'.
			form::combo('fmodesend',$mode_combo,$modesend).
			'</p>'.
			
			'<p class="field"><label for="fregcode">'.__('Registration code').'</label>'.
			form::field('fregcode',50,255,$regcode,'','',true).
			'</p>'.
				
			'<p class="field"><label for="fstate">'.__('Status').'</label>'.
			form::combo('fstate',$state_combo,$state).
			'</p>'.
		
			'<p><input type="submit" value="'.__('Update').'" />'.
			'<input type="reset" name="reset" value="'.__('Cancel').'" /> '.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'subscribers').
			form::hidden(array('op'),'edit').
			form::hidden(array('id'),$id).
			$core->formNonce().'</p>'.
		
			'</form>';
			'';
			
			echo '<p><a class="back" href="'.$p_url.'&amp;m=subscribers">'.__('back').'</a></p>';
		}
	} elseif ($plugin_tab == 'tab_add_subscriber') {
		// Print page Add
		if ($allowed) {
			echo '<h3>'.__('Add a subscriber').'</h3>';
		
			echo
			'<form action="plugin.php" method="post" class="fieldset">'.
		
			'<p class="field"><label for="femail">'.__('Email:').'</label>'.
			form::field(array('femail','femail'),50,255,'').
			'</p>'.
		
			'<p><input type="submit" value="'.__('Add').'" />'.
			'<input type="reset" name="reset" value="'.__('Cancel').'" /> '.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'add_subscribers').
			form::hidden(array('op'),'add').
			$core->formNonce().'</p>'.
		
			'</form>'.
			'';
			
			echo '<p><a class="back" href="'.$p_url.'&amp;m=subscribers">'.__('back').'</a></p>';
		}
	} else {

		// Print page Subscribers	
		if($plugin_op == 'send') {
			newsletterSubscribersList::subcribersActions();
		} else {
			if (!$core->error->flag()) {
				echo '<p class="top-add"><a class="button add" href="plugin.php?p=newsletter&amp;m=add_subscriber">'.__('Add a subscriber').'</a></p>';
				
				echo 
				'<form action="plugin.php" method="get" id="filters-form">'.
				'<h3 class="out-of-screen-if-js">'.$form_filter_title.'</h3>'.
	
				'<div class="table">'.
				'<div class="cell">'.
				'<h4>'.__('Filters').'</h4>'.
				'<p><span class="label ib">'.__('Show').'</span> <label for="nb" class="classic">'.
				form::field('nb',3,3,$nb_per_page).' '.
				__('Subscribers per page').'</label></p>'.
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
				form::hidden(array('m'),'subscribers').
				'<input type="submit" value="'.__('Apply filters and display options').'" /></p>'.
				'</p>'.
					
				'<br class="clear" /></p>'.
				'</form>';			
			}
	
			// Show subscribers
			$subscribers_list->display($page,$nb_per_page,
					'<form action="plugin.php?p=newsletter&amp;m=subscribers" method="post" id="subscribers_list">'.
					'<p>' .
	
					'%s'.
	
					'<div class="two-cols">'.
					'<p class="col checkboxes-helpers"></p>'.
					
					'<p class="col right">'.__('Selected subscribers action:').
					form::combo('op',$combo_action).
					'<input type="submit" value="'.__('ok').'" />'.
					'</p>'.
					form::hidden(array('p'),newsletterPlugin::pname()).
					form::hidden(array('sortby'),$sortby).
					form::hidden(array('order'),$order).
					form::hidden(array('page'),$page).
					form::hidden(array('nb'),$nb_per_page).
					form::hidden(array('m'),'subscribers').
					$core->formNonce().
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