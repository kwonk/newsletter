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
require_once dirname(__FILE__).'/class.newsletter.cron.php';
require_once dirname(__FILE__).'/class.newsletter.plugin.php';
require_once dirname(__FILE__).'/class.newsletter.admin.php';

try {
	$blog_settings =& $core->blog->settings->newsletter;
	$system_settings =& $core->blog->settings->system;
	
	$blog = &$core->blog;
	$auth = &$core->auth;
	
	# Url
	$url = &$core->url;
	$blog_url = html::stripHostURL($core->blog->url);
	$urlBase = newsletterTools::concatURL($blog_url, $url->getBase('newsletter'));
	$p_url = 'plugin.php?p=newsletter';
	$msg = '';
	
	# setting variables
	$plugin_name = 'Newsletter';
	$page_title = 'Newsletter';
	$newsletter_settings = new newsletterSettings($core);
	
	# retrieve module
	$m = (!empty($_REQUEST['m'])) ? (string) rawurldecode($_REQUEST['m']) : 'subscribers';
	
	# retrieve tab
	$plugin_tab = 'tab_'.$m;
	
	$edit_subscriber = ('addedit'==$m && !empty($_GET['id'])) ? __('Edit') : __('Add');

	if (isset($_REQUEST['tab'])) {
		$plugin_tab = 'tab_'.$_REQUEST['tab'];
	}
	
	# retrieve operation
	$plugin_op = (!empty($_POST['op'])) ? (string)$_POST['op'] : 'none';
	
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
		# SETTINGS
		###############################################
	
		# Modify the activation state
		case 'state':
		{
			$m = 'maintenance';
	
			if (empty($_POST['newsletter_flag']) !== null) {
				$newsletter_flag = (empty($_POST['newsletter_flag']))?false:true;
				$blog_settings->put('newsletter_flag',$newsletter_flag,'boolean','Plugin status newsletter');
				$msg = __('Plugin status updated.');
			}
		
			# notify the change to the blog
			newsletterPlugin::triggerBlog();
	
			//$msg = __('Plugin status updated.');
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# Modify settings
		case 'settings':
		{
			$m = 'settings';
	
			if (!empty($_POST['feditoremail']) && !empty($_POST['feditorname'])) {
				$newsletter_settings->setEditorName($_POST['feditorname']);
				$newsletter_settings->setEditorEmail($_POST['feditoremail']);
										
				(!empty($_POST['fcaptcha']) ? $newsletter_settings->setCaptcha($_POST['fcaptcha']) : $newsletter_settings->clearCaptcha());
				(!empty($_POST['f_auto_confirm_subscription']) ? $newsletter_settings->setAutoConfirmSubscription($_POST['f_auto_confirm_subscription']) : $newsletter_settings->clearAutoConfirmSubscription());
				(!empty($_POST['fmode']) ? $newsletter_settings->setSendMode($_POST['fmode']) : $newsletter_settings->clearSendMode());
				(!empty($_POST['f_use_default_format']) ? $newsletter_settings->setUseDefaultFormat($_POST['f_use_default_format']) : $newsletter_settings->clearUseDefaultFormat());
				(!empty($_POST['f_use_default_action']) ? $newsletter_settings->setUseDefaultAction($_POST['f_use_default_action']) : $newsletter_settings->clearUseDefaultAction());
				(!empty($_POST['fautosend']) ? $newsletter_settings->setAutosend($_POST['fautosend']) : $newsletter_settings->clearAutosend());
				(!empty($_POST['f_send_update_post']) ? $newsletter_settings->setSendUpdatePost($_POST['f_send_update_post']) : $newsletter_settings->clearSendUpdatePost());
				(!empty($_POST['fminposts']) ? $newsletter_settings->setMinPosts($_POST['fminposts']) : $newsletter_settings->clearMinPosts());
				(!empty($_POST['fmaxposts']) ? $newsletter_settings->setMaxPosts($_POST['fmaxposts']) : $newsletter_settings->clearMaxPosts());
	
				if (!empty($_POST['f_excerpt_restriction'])) {
					$newsletter_settings->setExcerptRestriction($_POST['f_excerpt_restriction']);
					# dependency
					$newsletter_settings->clearViewContentPost();
				} else {
					$newsletter_settings->clearExcerptRestriction();
					(!empty($_POST['f_view_content_post']) ? $newsletter_settings->setViewContentPost($_POST['f_view_content_post']) : $newsletter_settings->clearViewContentPost());
				}
	
				(!empty($_POST['f_view_content_in_text_format']) ? $newsletter_settings->setViewContentInTextFormat($_POST['f_view_content_in_text_format']) : $newsletter_settings->clearViewContentInTextFormat());
				(!empty($_POST['f_view_thumbnails']) ? $newsletter_settings->setViewThumbnails($_POST['f_view_thumbnails']) : $newsletter_settings->clearViewThumbnails());
				(!empty($_POST['f_size_content_post']) ? $newsletter_settings->setSizeContentPost($_POST['f_size_content_post']) : $newsletter_settings->clearSizeContentPost());
				(!empty($_POST['f_size_thumbnails']) ? $newsletter_settings->setSizeThumbnails($_POST['f_size_thumbnails']) : $newsletter_settings->clearSizeThumbnails());
				(!empty($_POST['f_category']) ? $newsletter_settings->setCategory($_POST['f_category']) : $newsletter_settings->clearCategory());
				(!empty($_POST['f_check_subcategories']) ? $newsletter_settings->setCheckSubCategories($_POST['f_check_subcategories']) : $newsletter_settings->clearCheckSubCategories());
				(!empty($_POST['f_check_notification']) ? $newsletter_settings->setCheckNotification($_POST['f_check_notification']) : $newsletter_settings->clearCheckNotification());
				(!empty($_POST['f_check_use_suspend']) ? $newsletter_settings->setCheckUseSuspend($_POST['f_check_use_suspend']) : $newsletter_settings->clearCheckUseSuspend());
				(!empty($_POST['f_order_date']) ? $newsletter_settings->setOrderDate($_POST['f_order_date']) : $newsletter_settings->clearOrderDate());
				(!empty($_POST['f_date_previous_send']) ? $newsletter_settings->setDatePreviousSend($_POST['f_date_previous_send']) : $newsletter_settings->setDatePreviousSend());
				(!empty($_POST['f_check_agora_link']) ? $newsletter_settings->setCheckAgoraLink($_POST['f_check_agora_link']) : $newsletter_settings->clearCheckAgoraLink());
				(!empty($_POST['f_check_subject_with_date']) ? $newsletter_settings->setCheckSubjectWithDate($_POST['f_check_subject_with_date']) : $newsletter_settings->clearCheckSubjectWithDate());
				(!empty($_POST['f_date_format_post_info']) ? $newsletter_settings->setDateFormatPostInfo($_POST['f_date_format_post_info']) : $newsletter_settings->clearDateFormatPostInfo());
				(!empty($_POST['f_nb_newsletters_per_public_page']) ? $newsletter_settings->setNbNewslettersPerPublicPage($_POST['f_nb_newsletters_per_public_page']) : $newsletter_settings->clearNbNewslettersPerPublicPage());
				(!empty($_POST['f_newsletters_public_page_order']) ? $newsletter_settings->setNewslettersPublicPageOrder($_POST['f_newsletters_public_page_order']) : $newsletter_settings->clearNewslettersPublicPageOrder());
				(!empty($_POST['f_newsletters_public_page_sort']) ? $newsletter_settings->setNewslettersPublicPageSort($_POST['f_newsletters_public_page_sort']) : $newsletter_settings->clearNewslettersPublicPageSort());
				(!empty($_POST['f_use_CSSForms']) ? $newsletter_settings->setUseCSSForms($_POST['f_use_CSSForms']) : $newsletter_settings->clearUseCSSForms());
				(!empty($_POST['f_nb_subscribers_per_page']) ? $newsletter_settings->setNbSubscribersPerpage($_POST['f_nb_subscribers_per_page']) : $newsletter_settings->clearNbSubscribersPerpage());
				
				# notify the change to the blog
				$newsletter_settings->save();
				newsletterTools::redirection($m,rawurldecode(__('Settings updated')));
			} else {
				if (empty($_POST['feditoremail']))
					throw new Exception(__('You must input a valid email'));
							
				if (empty($_POST['feditorname']))
					throw new Exception(__('You must input an editor'));
				}
		}
		break;
	
		# Modify messages
		case 'messages':
		{
			$m = 'messages';
			//$newsletter_settings = new newsletterSettings($core);
				
			# en vrac
			(!empty($_POST['f_txt_link_visu_online']) ? $newsletter_settings->setTxtLinkVisuOnline($_POST['f_txt_link_visu_online']) : $newsletter_settings->clearTxtLinkVisuOnline());
			(!empty($_POST['f_style_link_visu_online']) ? $newsletter_settings->setStyleLinkVisuOnline($_POST['f_style_link_visu_online']) : $newsletter_settings->clearStyleLinkVisuOnline());
			(!empty($_POST['f_style_link_read_it']) ? $newsletter_settings->setStyleLinkReadIt($_POST['f_style_link_read_it']) : $newsletter_settings->clearStyleLinkReadIt());
						
			# newsletter
			(!empty($_POST['f_introductory_msg']) ? $newsletter_settings->setIntroductoryMsg($_POST['f_introductory_msg']) : $newsletter_settings->clearIntroductoryMsg());
			(!empty($_POST['f_concluding_msg']) ? $newsletter_settings->setConcludingMsg($_POST['f_concluding_msg']) : $newsletter_settings->clearConcludingMsg());
			(!empty($_POST['f_presentation_msg']) ? $newsletter_settings->setPresentationMsg($_POST['f_presentation_msg']) : $newsletter_settings->clearPresentationMsg());
			(!empty($_POST['f_presentation_posts_msg']) ? $newsletter_settings->setPresentationPostsMsg($_POST['f_presentation_posts_msg']) : $newsletter_settings->clearPresentationPostsMsg());
			(!empty($_POST['f_newsletter_subject']) ? $newsletter_settings->setNewsletterSubject($_POST['f_newsletter_subject']) : $newsletter_settings->clearNewsletterSubject());
	
			# confirm
			(!empty($_POST['f_txt_intro_confirm']) ? $newsletter_settings->setTxtIntroConfirm($_POST['f_txt_intro_confirm']) : $newsletter_settings->clearTxtIntroConfirm());
			(!empty($_POST['f_txtConfirm']) ? $newsletter_settings->setTxtConfirm($_POST['f_txtConfirm']) : $newsletter_settings->clearTxtConfirm());
			(!empty($_POST['f_style_link_confirm']) ? $newsletter_settings->setStyleLinkConfirm($_POST['f_style_link_confirm']) : $newsletter_settings->clearStyleLinkConfirm());
			(!empty($_POST['f_confirm_subject']) ? $newsletter_settings->setConfirmSubject($_POST['f_confirm_subject']) : $newsletter_settings->clearConfirmSubject());
			(!empty($_POST['f_confirm_msg']) ? $newsletter_settings->setConfirmMsg($_POST['f_confirm_msg']) : $newsletter_settings->clearConfirmMsg());
			(!empty($_POST['f_concluding_confirm_msg']) ? $newsletter_settings->setConcludingConfirmMsg($_POST['f_concluding_confirm_msg']) : $newsletter_settings->clearConcludingConfirmMsg());
	
			# disable
			(!empty($_POST['f_txt_intro_disable']) ? $newsletter_settings->setTxtIntroDisable($_POST['f_txt_intro_disable']) : $newsletter_settings->clearTxtIntroDisable());
			(!empty($_POST['f_txtDisable']) ? $newsletter_settings->setTxtDisable($_POST['f_txtDisable']) : $newsletter_settings->clearTxtDisable());
			(!empty($_POST['f_style_link_disable']) ? $newsletter_settings->setStyleLinkDisable($_POST['f_style_link_disable']) : $newsletter_settings->clearStyleLinkDisable());
			(!empty($_POST['f_disable_subject']) ? $newsletter_settings->setDisableSubject($_POST['f_disable_subject']) : $newsletter_settings->clearDisableSubject());
			(!empty($_POST['f_disable_msg']) ? $newsletter_settings->setDisableMsg($_POST['f_disable_msg']) : $newsletter_settings->clearDisableMsg());
			(!empty($_POST['f_concluding_disable_msg']) ? $newsletter_settings->setConcludingDisableMsg($_POST['f_concluding_disable_msg']) : $newsletter_settings->clearConcludingDisableMsg());
			(!empty($_POST['f_txt_disabled_msg']) ? $newsletter_settings->setTxtDisabledMsg($_POST['f_txt_disabled_msg']) : $newsletter_settings->clearTxtDisabledMsg());
	
			# enable
			(!empty($_POST['f_txt_intro_enable']) ?	$newsletter_settings->setTxtIntroEnable($_POST['f_txt_intro_enable']) : $newsletter_settings->clearTxtIntroEnable());
			(!empty($_POST['f_txtEnable']) ? $newsletter_settings->setTxtEnable($_POST['f_txtEnable']) : $newsletter_settings->clearTxtEnable());
			(!empty($_POST['f_style_link_enable']) ? $newsletter_settings->setStyleLinkEnable($_POST['f_style_link_enable']) : $newsletter_settings->clearStyleLinkEnable());
			(!empty($_POST['f_enable_subject']) ? $newsletter_settings->setEnableSubject($_POST['f_enable_subject']) : $newsletter_settings->clearEnableSubject());
			(!empty($_POST['f_enable_msg']) ? $newsletter_settings->setEnableMsg($_POST['f_enable_msg']) : $newsletter_settings->clearEnableMsg());
			(!empty($_POST['f_concluding_enable_msg']) ? $newsletter_settings->setConcludingEnableMsg($_POST['f_concluding_enable_msg']) : $newsletter_settings->clearConcludingEnableMsg());
			(!empty($_POST['f_txt_enabled_msg']) ? $newsletter_settings->setTxtEnabledMsg($_POST['f_txt_enabled_msg']) : $newsletter_settings->clearTxtEnabledMsg());
	
			# suspend
			(!empty($_POST['f_txt_intro_suspend']) ? $newsletter_settings->setTxtIntroSuspend($_POST['f_txt_intro_suspend']) : $newsletter_settings->clearTxtIntroSuspend());
			(!empty($_POST['f_txtSuspend']) ? $newsletter_settings->setTxtSuspend($_POST['f_txtSuspend']) : $newsletter_settings->clearTxtSuspend());
			(!empty($_POST['f_style_link_suspend']) ? $newsletter_settings->setStyleLinkSuspend($_POST['f_style_link_suspend']) : $newsletter_settings->clearStyleLinkSuspend());
			(!empty($_POST['f_suspend_subject']) ? $newsletter_settings->setSuspendSubject($_POST['f_suspend_subject']) : $newsletter_settings->clearSuspendSubject());
			(!empty($_POST['f_suspend_msg']) ? $newsletter_settings->setSuspendMsg($_POST['f_suspend_msg']) : $newsletter_settings->clearSuspendMsg());
			(!empty($_POST['f_concluding_suspend_msg']) ? $newsletter_settings->setConcludingSuspendMsg($_POST['f_concluding_suspend_msg']) : $newsletter_settings->clearConcludingSuspendMsg());
			(!empty($_POST['f_txt_suspended_msg']) ? $newsletter_settings->setTxtSuspendedMsg($_POST['f_txt_suspended_msg']) : $newsletter_settings->clearTxtSuspendedMsg());
	
			# changemode
			(!empty($_POST['f_change_mode_subject']) ? $newsletter_settings->setChangeModeSubject($_POST['f_change_mode_subject']) : $newsletter_settings->clearChangeModeSubject());
			(!empty($_POST['f_header_changemode_msg']) ? $newsletter_settings->setHeaderChangeModeMsg($_POST['f_header_changemode_msg']) : $newsletter_settings->clearHeaderChangeModeMsg());
			(!empty($_POST['f_footer_changemode_msg']) ? $newsletter_settings->setFooterChangeModeMsg($_POST['f_footer_changemode_msg']) : $newsletter_settings->clearFooterChangeModeMsg());
			(!empty($_POST['f_changemode_msg']) ? $newsletter_settings->setChangeModeMsg($_POST['f_changemode_msg']) : $newsletter_settings->clearChangeModeMsg());
	
			# resume
			(!empty($_POST['f_resume_subject']) ? $newsletter_settings->setResumeSubject($_POST['f_resume_subject']) : $newsletter_settings->clearResumeSubject());
			(!empty($_POST['f_header_resume_msg']) ? $newsletter_settings->setHeaderResumeMsg($_POST['f_header_resume_msg']) : $newsletter_settings->clearHeaderResumeMsg());
			(!empty($_POST['f_footer_resume_msg']) ? $newsletter_settings->setFooterResumeMsg($_POST['f_footer_resume_msg']) : $newsletter_settings->clearFooterResumeMsg());
					
			# subscribe
			(!empty($_POST['f_msg_presentation_form']) ? $newsletter_settings->setMsgPresentationForm($_POST['f_msg_presentation_form']) : $newsletter_settings->clearMsgPresentationForm());
			(!empty($_POST['f_form_title_page']) ? $newsletter_settings->setFormTitlePage($_POST['f_form_title_page']) : $newsletter_settings->clearFormTitlePage());
			(!empty($_POST['f_txt_subscribed_msg']) ? $newsletter_settings->setTxtSubscribedMsg($_POST['f_txt_subscribed_msg']) : $newsletter_settings->clearTxtSubscribedMsg());
	
			# notify the change to the blog
			$newsletter_settings->save();
		
			$msg = __('Messages updated.');
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		###############################################
		# CSS
		###############################################
	
		# write the new CSS
		case 'write_css':
		{
			if (!empty($_POST['write']) && !empty($_POST['filecss'])) 
			{
				$letter_css = new newsletterCSS($core,$_POST['filecss']);
				if($_POST['filecss'] == 'style_pub_newsletter.css') {
					$msg=$letter_css->setLetterCSS($_POST['f_css_forms_content']);
				} else { 
					$msg=$letter_css->setLetterCSS($_POST['f_content']);
				}
			}
			newsletterTools::redirection($m,$msg);
		}
		break;

		# write the new CSS
		case 'copy_css':
		{
			if (!empty($_POST['fthemes']) && !empty($_POST['filecss']))
			{ 
				if (newsletterCSS::copyFileCSSToTheme($_POST['fthemes'],$_POST['filecss'])) {
					$msg = __('CSS successfully copied.');
				} else {
					throw new Exception(__('Error to copy CSS to your template'));
				}
			} else {
				$msg = __('No template selected');
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
		
		###############################################
		# PLANNING
		###############################################
					
		# schedule newsletter
		case 'schedule':
		{
			$m = 'planning';
	
			if ($core->blog->dcCron instanceof dcCron) {
				$newsletter_cron = new newsletterCron($core);
	
				# adding scheduled task
				$interval = (($_POST['f_interval']) ? $_POST['f_interval'] : 604800);
				$f_first_run = (($_POST['f_first_run']) ? strtotime(html::escapeHTML($_POST['f_first_run'])) : time() + dt::getTimeOffset($system_settings->blog_timezone));
	
				if ($newsletter_cron->add($interval, $f_first_run)) {
					$newsletter_settings->setCheckSchedule(true);
					$newsletter_settings->save();
					$msg = __('Planning updated');
				} else {
					throw new Exception(__('Error during create planning task'));
				}
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# unschedule newsletter
		case 'unschedule':
		{
			$m = 'planning';
	
			if ($core->blog->dcCron instanceof dcCron) {
				$newsletter_cron=new newsletterCron($core);
				$newsletter_settings->setCheckSchedule(false);
	
				# delete scheduled task
				$newsletter_cron->del();
				$newsletter_settings->save();
				$msg = __('Planning updated');
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# enable schedule task
		case 'enabletask':
		{
			$m = 'planning';
	
			if ($core->blog->dcCron instanceof dcCron) {
				$newsletter_cron=new newsletterCron($core);

				# enable scheduled task
				$newsletter_cron->enable();
				$newsletter_settings->save();
				$msg = __('Planning updated');
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# disable schedule task
		case 'disabletask':
		{
			$m = 'planning';
	
			if ($core->blog->dcCron instanceof dcCron) {
				$newsletter_cron=new newsletterCron($core);
					
				# disable scheduled task
				$newsletter_cron->disable();
				$newsletter_settings->save();
				$msg = __('Planning updated');
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		###############################################
		# MAINTENANCE
		###############################################
	
		# backup
		case 'export_blog':
		case 'export_all':
			{
				$m = 'maintenance';
		
				if (empty($_POST['f_export_file_name'])) {
					throw new Exception(__('Export file name was not defined'));
				}
					
				$file_name = $_POST['f_export_file_name'];
				$export_format = $_POST['f_export_format'];
				$file_zip = !empty($_POST['f_export_file_zip']);
				//$onlyblog = true;
				$onlyblog = (($plugin_op == 'export_blog') ? true : false);
					
				$retour = newsletterAdmin::exportToBackupFile($onlyblog,$export_format,$file_zip,$file_name);
				if($retour) {
					$msg = __('Datas exported to').' '.$file_name.' : '.$retour;
				}
			}
			break;		
	
		# importing list of subscribers
		case 'import':
		{
			$m = 'maintenance';
			//$blogid = (string)$core->blog->id;

			if (empty($_FILES['f_import_file'])) {
				throw new Exception(__('Import file name was not defined'));
			}
			
			$import_format = $_POST['f_import_format'];
				
			$retour = newsletterAdmin::importFromBackupFile($_FILES['f_import_file'], $import_format);
			if($retour) {
				$msg = __('Datas imported from').' '.$_FILES['f_import_file']['name'].' : '.$retour;
			}
			//newsletterTools::redirection($m,$msg);
		}
		break;
	
		# import email addresses from file
		case 'reprise':
		{
			$m = 'maintenance';
	
			if (empty($_POST['your_pwd']) || !$core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY,$_POST['your_pwd']))) {
				throw new Exception(__('Password verification failed'));
			}
			$retour = newsletterAdmin::importFromTextFile($_FILES['file_reprise']);
			
			if($retour) {
				$msg = __('Datas imported from').' '.$_FILES['file_reprise']['name'].' : '.$retour;
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# adapt template
		case 'adapt_theme':
		{
			$m = 'maintenance';
				
			if (!empty($_POST['fthemes'])) {
				if (newsletterAdmin::adaptTheme($_POST['fthemes'])) {
					$msg = __('Template successfully adapted.');
				} else {
					throw new Exception(__('Error to adapt template'));
				}
			} else {
				$msg = __('No template adapted');
			}
			newsletterTools::redirection($m,$msg);
		}
		break;
	
		# uninstall
		case 'erasingnewsletter':
		{
			$m = 'maintenance';
	
			# delete scheduled task
			if (isset($core->blog->dcCron)) {
				$newsletter_cron=new newsletterCron($core);
				$newsletter_settings->setCheckSchedule(false);
	
				$newsletter_cron->del();
				$newsletter_settings->save();
			}
			newsletterAdmin::uninstall();
			
			$redir = 'plugin.php?p=aboutConfig';
			http::redirect($redir);
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
	# --- Variables for page maintenance ---
	$format_combo = array(__('text file') => 'txt',
		__('data file') => 'dat');
	$f_export_format = 'txt';
	$f_import_format = 'txt';
	
	$core->themes = new dcThemes($core);
	$core->themes->loadModules($core->blog->themes_path,null);
	$bthemes = array();
	foreach ($core->themes->getModules() as $k => $v)
	{
		if (file_exists($blog->themes_path . '/' . $k . '/tpl/home.html'))
			$bthemes[html::escapeHTML($v['name'])] = $k;
	}
	$theme = $system_settings->theme;
	
	$sadmin = (($auth->isSuperAdmin()) ? true : false);
	# --- end Variables for page maintenance ---
	
	# CSS common
	$default_folder = path::real(newsletterPlugin::folder().'..');
	
	# --- Variables for page EditCSS ---
	$file_style_letter = 'style_letter.css';
	$letter_css = new newsletterCSS($core, $file_style_letter);
	$f_name = $letter_css->getFilenameCSS();
	$f_content = $letter_css->getLetterCSS();
	$f_editable = $letter_css->isEditable();
	if ($default_folder == $letter_css->getPathCSS())
		$f_editable = false;
	# --- end Variables for page EditCSS ---
	
	# --- Variables for page EditCSSforms ---
	$file_style_newsletter_forms = 'style_pub_newsletter.css';
	$newsletter_css_forms = new newsletterCSS($core, $file_style_newsletter_forms);
	$f_css_forms_name = $newsletter_css_forms->getFilenameCSS();
	$f_css_forms_content = $newsletter_css_forms->getLetterCSS();
	$f_css_forms_editable = $newsletter_css_forms->isEditable();
	if ($default_folder == $newsletter_css_forms->getPathCSS())
		$f_css_forms_editable = false;
	# --- end Variables for page EditCSSforms ---	
	
	# --- Variables for page Planning ---
	if($core->plugins->moduleExists('dcCron') && !isset($core->plugins->getDisabledModules['dcCron'])) {
		$newsletter_cron=new newsletterCron($core);
		$f_check_schedule = $newsletter_settings->getCheckSchedule();
		$f_interval = ($newsletter_cron->getTaskInterval() ? $newsletter_cron->getTaskInterval() : 604800);
		
		$default_run = date('Y-m-j H:i',(time() + dt::getTimeOffset($system_settings->blog_timezone) + 3 * 3600));
		$f_first_run = ($newsletter_cron->getFirstRun() ? $newsletter_cron->getFirstRun() : $default_run);
	}
	# --- end Variables for page Planning ---
	
	# --- Variables for page Messages ---
	# en vrac
	$f_txt_link_visu_online = $newsletter_settings->getTxtLinkVisuOnline();
	$f_style_link_visu_online = $newsletter_settings->getStyleLinkVisuOnline();
	$f_style_link_read_it = $newsletter_settings->getStyleLinkReadIt();
		
	# newsletter
	$f_newsletter_subject = $newsletter_settings->getNewsletterSubject();
	$f_introductory_msg = $newsletter_settings->getIntroductoryMsg();
	$f_concluding_msg = $newsletter_settings->getConcludingMsg();
	$f_msg_presentation_form = $newsletter_settings->getMsgPresentationForm();
	$f_presentation_msg = $newsletter_settings->getPresentationMsg();
	$f_presentation_posts_msg = $newsletter_settings->getPresentationPostsMsg();
	
	# confirm
	$f_confirm_subject = $newsletter_settings->getConfirmSubject();
	$f_txt_intro_confirm = $newsletter_settings->getTxtIntroConfirm();
	$f_txtConfirm = $newsletter_settings->getTxtConfirm();
	$f_style_link_confirm = $newsletter_settings->getStyleLinkConfirm();
	$f_confirm_msg = $newsletter_settings->getConfirmMsg();
	$f_concluding_confirm_msg = $newsletter_settings->getConcludingConfirmMsg();
	
	# disable
	$f_disable_subject = $newsletter_settings->getDisableSubject();
	$f_txt_intro_disable = $newsletter_settings->getTxtIntroDisable();
	$f_txtDisable = $newsletter_settings->getTxtDisable();
	$f_style_link_disable = $newsletter_settings->getStyleLinkDisable();
	$f_disable_msg = $newsletter_settings->getDisableMsg();
	$f_concluding_disable_msg = $newsletter_settings->getConcludingDisableMsg();
	$f_txt_disabled_msg = $newsletter_settings->getTxtDisabledMsg();
	
	# enable
	$f_txt_intro_enable = $newsletter_settings->getTxtIntroEnable();
	$f_txtEnable = $newsletter_settings->getTxtEnable();
	$f_style_link_enable = $newsletter_settings->getStyleLinkEnable();
	$f_enable_subject = $newsletter_settings->getEnableSubject();
	$f_enable_msg = $newsletter_settings->getEnableMsg();
	$f_concluding_enable_msg = $newsletter_settings->getConcludingEnableMsg();
	$f_txt_enabled_msg = $newsletter_settings->getTxtEnabledMsg();
	
	# suspend
	$f_suspend_subject = $newsletter_settings->getSuspendSubject();
	$f_suspend_msg = $newsletter_settings->getSuspendMsg();
	$f_txt_suspended_msg = $newsletter_settings->getTxtSuspendedMsg();
	$f_concluding_suspend_msg = $newsletter_settings->getConcludingSuspendMsg();
	$f_txt_intro_suspend = $newsletter_settings->getTxtIntroSuspend();
	$f_txtSuspend = $newsletter_settings->getTxtSuspend();
	$f_style_link_suspend = $newsletter_settings->getStyleLinkSuspend();
	
	# changemode
	$f_change_mode_subject = $newsletter_settings->getChangeModeSubject();
	$f_header_changemode_msg = $newsletter_settings->getHeaderChangeModeMsg();
	$f_footer_changemode_msg = $newsletter_settings->getFooterChangeModeMsg();
	$f_changemode_msg = $newsletter_settings->getChangeModeMsg();
	
	# resume
	$f_resume_subject = $newsletter_settings->getResumeSubject();
	$f_header_resume_msg = $newsletter_settings->getHeaderResumeMsg();
	$f_footer_resume_msg = $newsletter_settings->getFooterResumeMsg();
	
	# subscribe
	$f_form_title_page = $newsletter_settings->getFormTitlePage();
	$f_txt_subscribed_msg = $newsletter_settings->getTxtSubscribedMsg();	
	# --- end Variables for page Messages ---
	
	# --- Variables for page Settings ---
	$mode_combo = array(__('text') => 'text',
			__('html') => 'html');
		
	$date_combo = array(__('creation date') => 'post_creadt',
			__('update date') => 'post_upddt',
			__('publication date') => 'post_dt');
		
	$size_thumbnails_combo = array(__('medium') => 'm',
			__('small') => 's',
			__('thumbnail') => 't',
			__('square') => 'sq',
			__('original') => 'o');
	
	$sortby_combo = array(
			__('Date') => 'post_dt',
			__('Title') => 'post_title',
			__('Author') => 'user_id',
			__('Selected') => 'post_selected'
	);
	
	$order_combo = array(__('Ascending') => 'asc',
			__('Descending') => 'desc' );
	
	// initialisation des variables
	$feditorname = $newsletter_settings->getEditorName();
	$feditoremail = $newsletter_settings->getEditorEmail();
	$fcaptcha = $newsletter_settings->getCaptcha();
	$fmode = $newsletter_settings->getSendMode();
	$f_use_default_format = $newsletter_settings->getUseDefaultFormat();
	$f_use_default_action = $newsletter_settings->getUseDefaultAction();
	$fmaxposts = $newsletter_settings->getMaxPosts();
	$fminposts = $newsletter_settings->getMinPosts();
	$f_excerpt_restriction = $newsletter_settings->getExcerptRestriction();
	$f_view_content_post = $newsletter_settings->getViewContentPost();
	$f_size_content_post = $newsletter_settings->getSizeContentPost();
	$f_view_content_in_text_format = $newsletter_settings->getViewContentInTextFormat();
	$f_view_thumbnails = $newsletter_settings->getViewThumbnails();
	$f_size_thumbnails = $newsletter_settings->getSizeThumbnails();
	$fautosend = $newsletter_settings->getAutosend();
	$f_check_notification = $newsletter_settings->getCheckNotification();
	$f_check_use_suspend = $newsletter_settings->getCheckUseSuspend();
	$f_order_date = $newsletter_settings->getOrderDate();
	$f_send_update_post = $newsletter_settings->getSendUpdatePost();
	$f_check_agora_link = $newsletter_settings->getCheckAgoraLink();
	$f_check_subject_with_date = $newsletter_settings->getCheckSubjectWithDate();
	$f_date_format_post_info = $newsletter_settings->getDateFormatPostInfo();
	$f_auto_confirm_subscription = $newsletter_settings->getAutoConfirmSubscription();
	$f_nb_newsletters_per_public_page = $newsletter_settings->getNbNewslettersPerPublicPage();
	$f_newsletters_public_page_sort = $newsletter_settings->getNewslettersPublicPageSort();
	$f_newsletters_public_page_order = $newsletter_settings->getNewslettersPublicPageOrder();
	$f_use_CSSForms = $newsletter_settings->getUseCSSForms();
	$f_nb_subscribers_per_page = $newsletter_settings->getNbSubscribersPerpage();
	
	$rs = $core->blog->getCategories(array('post_type'=>'post'));
	$categories = array('' => '', __('Uncategorized') => 'null');
	while ($rs->fetch()) {
		$categories[str_repeat('&nbsp;&nbsp;',$rs->level-1).'&bull; '.html::escapeHTML($rs->cat_title)] = $rs->cat_id;
	}
	$f_category = $newsletter_settings->getCategory();
	$f_check_subcategories = $newsletter_settings->getCheckSubCategories();
	
	if ($newsletter_settings->getDatePreviousSend()) {
		$f_date_previous_send = date('Y-m-j H:i',$newsletter_settings->getDatePreviousSend()+ dt::getTimeOffset($system_settings->blog_timezone));
	} else {
		$f_date_previous_send = 'not sent';
	}	
	# --- end Variables for page Settings ---
	
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
	echo
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		dcPage::jsVar('dotclear.msg.confirm_erasing_datas', __('Are you sure you want to delete all informations about newsletter in database?')).
		dcPage::jsVar('dotclear.msg.confirm_import_backup', __('Are you sure you want to import a backup file?')).
		"\n//]]>\n".
		"</script>\n";
		
		if (isset($core->blog->dcCron)) {
			echo
			dcPage::jsDatePicker().
			dcPage::jsLoad('index.php?pf=newsletter/js/_newsletter.cron.js');
		}
		echo
		dcPage::jsLoad('index.php?pf=newsletter/js/_newsletter.js');
		echo dcPage::jsPageTabs($plugin_tab);		
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
			'<li><a href="'.$p_url.'&amp;m=subscribers">'.__('Subscribers').'</a></li>'.
			'<li><a href="'.$p_url.'&amp;m=resume" class="active">'.__('Properties').'</a></li>'.
		'</ul>';
}	
try {

	if ($newsletter_flag == 0) {
		echo '<h3>'.__('Maintenance').'</h3>';

		echo
		'<form action="plugin.php" method="post" class="fieldset" id="state">'.
		'<h4>'.__('Plugin state').'</h4>'.
			
		'<p class="field"><label for="newsletter_flag">'.__('Enable plugin').'</label>'.
		form::checkbox('newsletter_flag', 1, $blog_settings->newsletter_flag).
		'</p>'.
			
		'<p><input type="submit" value="'.__('Save').'" /> '.
		'<input type="reset" value="'.__('Cancel').'" /> '.
		form::hidden(array('p'),newsletterPlugin::pname()).
		form::hidden(array('m'),'maintenance').
		form::hidden(array('op'),'state').
		$core->formNonce().'</p>'.
		
		'</form>';
	} else { 
	
	// Print page Resume
	echo '<div class="multi-part" id="tab_resume" title="'.__('Resume').'">';
		echo newsletterSubscribersList::fieldsetResumeSubscribers();
		echo newsletterLettersList::fieldsetResumeLetters();
	echo '</div>';

	// Print page Settings
	echo '<div class="multi-part" id="tab_settings" title="'.__('Settings').'">';
		// gestion des paramètres du plugin
		echo '<form action="plugin.php" method="post" id="settings">';
			
		echo
		'<div class="fieldset" id="advanced">'.
		'<h4>'.__('Advanced Settings').'</h4>'.
			
		'<p class="field">'.
		'<label for="feditorname" class="classic required" title="'.__('Required field').'">'.__('Editor name').'</label>'.
		form::field('feditorname',50,255,html::escapeHTML($feditorname)).
		'</p>'.
		'<p class="field">'.
		'<label for="feditoremail" class="classic required" title="'.__('Required field').'">'.__('Editor email').'</label>'.
		form::field('feditoremail',50,255,html::escapeHTML($feditoremail)).
		'</p>'.
		'<p class="field">'.
		'<label for="fcaptcha" class="classic">'.__('Captcha').'</label>'.
		form::checkbox('fcaptcha',1,$fcaptcha).
		'</p>'.
		'<p class="field">'.
		'<label for="f_auto_confirm_subscription" class="classic">'.__('Automatically activate the account').'</label>'.
		form::checkbox('f_auto_confirm_subscription',1,$f_auto_confirm_subscription).
		'</p>'.
		'<p class="field">'.
		'<label for="f_use_default_action" class="classic">'.__('Use default action subscribe in form').'</label>'.
		form::checkbox('f_use_default_action',1,$f_use_default_action).
		'</p>'.
		'<p class="field">'.
		'<label for="f_use_default_format" class="classic">'.__('Use default format for sending').'</label>'.
		form::checkbox('f_use_default_format',1,$f_use_default_format).
		'</p>'.
		'<p class="field">'.
		'<label for="fmode" class="classic">'.__('Default format for sending').'</label>'.
		form::combo('fmode',$mode_combo,$fmode).
		'</p>'.
		'<p class="field">'.
		'<label for="f_check_notification" class="classic">'.__('Notification sending').'</label>'.
		form::checkbox('f_check_notification',1,$f_check_notification).
		'</p>'.
		'<p class="field">'.
		'<label for="f_excerpt_restriction" class="classic">'.__('Restrict the preview only to excerpt of posts').'</label>'.
		form::checkbox('f_excerpt_restriction',1,$f_excerpt_restriction).
		'</p>'.
		'<p class="field">'.
		'<label for="f_view_content_post" class="classic">'.__('View contents posts').'</label>'.
		form::checkbox('f_view_content_post',1,$f_view_content_post).
		'</p>'.
		'<p class="field">'.
		'<label for="f_size_content_post" class="classic">'.__('Size contents posts').'</label>'.
		form::field('f_size_content_post',4,4,$f_size_content_post).
		'</p>'.
		'<p class="field">'.
		'<label for="f_view_content_in_text_format" class="classic">'.__('View content in text format').'</label>'.
		form::checkbox('f_view_content_in_text_format',1,$f_view_content_in_text_format).
		'</p>'.
		'<p class="field">'.
		'<label for="f_view_thumbnails" class="classic">'.__('View thumbnails').'</label>'.
		form::checkbox('f_view_thumbnails',1,$f_view_thumbnails).
		'</p>'.
		'<p class="field">'.
		'<label for="f_size_thumbnails" class="classic">'.__('Size of thumbnails').'</label>'.
		form::combo('f_size_thumbnails',$size_thumbnails_combo,$f_size_thumbnails).
		'</p>'.
		'<p class="field">'.
		'<label for="f_check_use_suspend" class="classic">'.__('Use suspend option').'</label>'.
		form::checkbox('f_check_use_suspend',1,$f_check_use_suspend).
		'</p>'.
		'<p class="field">'.
		'<label for="f_order_date" class="classic">'.__('Date selection for sorting posts').'</label>'.
		form::combo('f_order_date',$date_combo,$f_order_date).
		'</p>'.
		'<p class="field">'.
		'<label for="f_check_agora_link" class="classic">'.__('Automaticly create a subscriber when an user is added in the plugin Agora').'</label>'.
		form::checkbox('f_check_agora_link',1,$f_check_agora_link).
		'</p>'.
		'<p class="field">'.
		'<label for="f_date_format_post_info" class="classic">'.__('Date format for post info').'</label>'.
		form::field('f_date_format_post_info',20,20,$f_date_format_post_info).
		'</p>'.
		'<p class="field">'.
		'<label for="f_use_CSSForms" class="classic">'.sprintf(__('Use CSS %s in forms'),'style_pub_newsletter.css').'</label>'.
		form::checkbox('f_use_CSSForms',1,$f_use_CSSForms).
		'</p>'.
		'<p class="field">'.
		'<label for="f_nb_subscribers_per_page" class="classic">'.__('Default value for number of subscribers per page').'</label>'.
		form::field('f_nb_subscribers_per_page',4,4,$f_nb_subscribers_per_page).
		'</p>'.
		'</div>'.
		
		'<div class="fieldset" id="advanced">'.
		'<h4>'.__('Settings for auto letter').'</h4>'.
		
		'<p class="field">'.
		'<label for="f_date_previous_send" class="classic">'.__('Date for the previous sent').'</label>'.
		form::field('f_date_previous_send',20,20,$f_date_previous_send).
		'</p>'.
		'<p class="field">'.
		'<label for="fautosend" class="classic">'.__('Automatic send when create post').'</label>'.
		form::checkbox('fautosend',1,$fautosend).
		'</p>'.
		'<p class="field">'.
		'<label for="f_send_update_post" class="classic">'.__('Automatic send when update post').'</label>'.
		form::checkbox('f_send_update_post',1,$f_send_update_post).
		'</p>'.
		'<p class="field">'.
		'<label for="fminposts" class="classic">'.__('Minimum posts').'</label>'.
		form::field('fminposts',4,4,$fminposts).
		'</p>'.
		'<p class="field">'.
		'<label for="fmaxposts" class="classic">'.__('Maximum posts').'</label>'.
		form::field('fmaxposts',4,4,$fmaxposts).
		'</p>'.
		'<p class="field">'.
		'<label for="f_category" class="classic">'.__('Category').'</label>'.
		form::combo('f_category',$categories,$f_category).
		'</p>'.
		'<p class="field">'.
		'<label for="f_check_subcategories" class="classic">'.__('Include sub-categories').'</label>'.
		form::checkbox('f_check_subcategories',1,$f_check_subcategories).
		'</p>'.
		'<p class="field">'.
		'<label for="f_check_subject_with_date" class="classic">'.__('Add the date in the title of the letter').'</label>'.
		form::checkbox('f_check_subject_with_date',1,$f_check_subject_with_date).
		'</p>'.
		'</div>'.
		
		'<div class="fieldset" id="newsletters_public_page">'.
		'<h4>'.__('Settings for the public page for a list of newsletters').'</h4>'.
		
		'<p class="field">'.
		'<label for="f_nb_newsletters_per_public_page" class="classic">'.__('Number per page').'</label>'.
		form::field('f_nb_newsletters_per_public_page',4,4,$f_nb_newsletters_per_public_page).
		'</p>'.
		'<p class="field">'.
		'<label for="f_newsletters_public_page_sort" class="classic">'.__('Sort by').'</label>'.
		form::combo('f_newsletters_public_page_sort',$sortby_combo,$f_newsletters_public_page_sort).
		'</p>'.
		'<p class="field">'.
		'<label for="f_newsletters_public_page_order" class="classic">'.__('Order').'</label>'.
		form::combo('f_newsletters_public_page_order',$order_combo,$f_newsletters_public_page_order).
		'</p>'.
		'</div>'.
		// boutons du formulaire
		'<p>'.
		'<input type="submit" name="save" value="'.__('Save').'" /> '.
		'<input type="reset" name="reset" value="'.__('Cancel').'" /> '.
		'</p>'.
		'<p>'.
		form::hidden(array('p'),newsletterPlugin::pname()).
		form::hidden(array('m'),'settings').
		form::hidden(array('op'),'settings').
		$core->formNonce().
		'</p>'.
		'</form>'.
		'';
	echo '</div>';
	
	# Print page Planning
	echo '<div class="multi-part" id="tab_planning" title="'.__('Planning').'">';
	
	# Utilisation de dcCron
	if($core->plugins->moduleExists('dcCron') && !isset($core->plugins->getDisabledModules['dcCron'])) {
	
		echo
		'<form action="plugin.php" method="post" name="planning" class="fieldset">'.
			'<h4>'.__('Planning newsletter').'</h4>'.
			'<p class="field">'.
				'<label class="classic" for="f_interval">'.__('Interval time in seconds between 2 runs').'</label>'.
				form::field('f_interval',20,20,$f_interval).
			'</p>'.
			'<p class="comments">'.
			__('samples').' : ( 1 '.__('day').' = 86400s / 1 '.__('week').' = 604800s / 28 '.__('days').' =  2420000s )'.
			'</p>'.
			'<p>'.
			'<label id="f_first_run_label" for="f_first_run">'.__('Date for the first run').'</label>'.
			form::field('f_first_run',20,20,$f_first_run).
			'</p>'.
	
			# form buttons
			'<p>'.
				'<input type="submit" name="submit" value="'.(($f_check_schedule)?__('Unschedule'):__('Schedule')).'" /> '.
				'<input type="reset" name="reset" value="'.__('Cancel').'" /> '.
			'</p>'.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'planning').
			form::hidden(array('op'),(($f_check_schedule)?'unschedule':'schedule')).
			$core->formNonce().
		
		'</form>';

		if ($f_check_schedule) {
			$f_task_state = $newsletter_cron->getState();
								
			echo
			'<div class="fieldset">'.
				'<h4>'.__('Scheduled task').' : '.html::escapeHTML($newsletter_cron->getTaskName()).'</h4>'.
				'<table summary="resume" class="minimal">'.
					'<thead>'.
					'<tr>'.
						'<th>'.__('Name').'</th>'.
						'<th>'.__('Value').'</th>'.
					'</tr>'.
					'</thead>'.
					'<tbody id="classes-list">'.
					'<tr class="line">'.
						'<td>'.__('State').'</td>'.
						'<td>'.html::escapeHTML(__($newsletter_cron->printState())).'</td>'.
					'</tr>'.
					'<tr class="line">'.
						'<td>'.__('Interval').'</td>'.
						'<td>'.html::escapeHTML($newsletter_cron->printTaskInterval()).'</td>'.
					'</tr>'.
					'<tr class="line">'.
						'<td>'.__('Last run').'</td>'.
						'<td>'.html::escapeHTML($newsletter_cron->printLastRunDate()).'</td>'.
					'</tr>'.
					'<tr class="line">'.
						'<td>'.__('Next run').'</td>'.
						'<td>'.html::escapeHTML($newsletter_cron->printNextRunDate()).'</td>'.
					'</tr>'.
					'<tr class="line">'.
						'<td>'.__('Remaining Time').'</td>'.
						'<td>'.html::escapeHTML($newsletter_cron->printRemainingTime()).'</td>'.
					'</tr>'.
					'</tbody>'.
				'</table>'.
	
				'<form action="plugin.php" method="post" name="taskplanning">'.
	
					# form buttons
					'<p>'.
						'<input type="submit" name="submit" value="'.(($f_task_state)?__('Disable'):__('Enable')).'" /> '.
					'</p>'.
					form::hidden(array('p'),newsletterPlugin::pname()).
					form::hidden(array('m'),'planning').
					form::hidden(array('op'),(($f_task_state)?'disabletask':'enabletask')).
					$core->formNonce().
				'</form>'.
								
			'</div>'.
			'';
		}
	} else {
		echo
		'<div class="fieldset">'.
			'<h4>'.__('Planning newsletter').'</h4>'.
			'<p>'.__('Install the plugin dcCron for using planning').'</p>'.
		'</div>';
	}
	echo '</div>';
		
	# Print page Messages
	echo '<div class="multi-part" id="tab_messages" title="'.__('Messages').'">';
		echo '<form action="plugin.php" method="post" id="messages">';
		
		# form buttons
		echo 
		
		'<h3>'.__('Update messages').'</h3>'.
		'<p>'.
		'<input type="submit" name="save" value="'.__('Update').'" /> '.
		'<input type="reset" name="reset" value="'.__('Cancel').'" /> '.
		'</p>'.
		
		'<div class="fieldset" id="define_newsletter">'.
			'<h4>'.__('Define message content Newsletter').'</h4>'.
			'<p>'.
				'<label for="f_newsletter_subject">'.__('Subject of the Newsletter').'</label>'.
				form::field('f_newsletter_subject',50,255,html::escapeHTML($f_newsletter_subject)).
			'</p>'.
			'<p>'.
				'<label for="f_presentation_msg">'.__('Message presentation').'</label>'.
				form::field('f_presentation_msg',50,255,html::escapeHTML($f_presentation_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_introductory_msg">'.__('Introductory message').' : </label>'.
				form::textarea('f_introductory_msg',30,4,html::escapeHTML($f_introductory_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_presentation_posts_msg">'.__('Presentation message for posts').'</label>'.
				form::field('f_presentation_posts_msg',50,255,html::escapeHTML($f_presentation_posts_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_concluding_msg">'.__('Concluding message').' : </label>'.
				form::textarea('f_concluding_msg',30,4, html::escapeHTML($f_concluding_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_txt_link_visu_online">'.__('Set the link text viewing online').'</label>'.
				form::field('f_txt_link_visu_online',50,255,html::escapeHTML($f_txt_link_visu_online)).
			'</p>'.
			'<p>'.
				'<label for="f_style_link_visu_online">'.__('Set the link style viewing online').'</label>'.
				form::field('f_style_link_visu_online',50,255,html::escapeHTML($f_style_link_visu_online)).
			'</p>'.
			'<p>'.
				'<label for="f_style_link_read_it">'.__('Set the link style read it').'</label>'.
				form::field('f_style_link_read_it',50,255,html::escapeHTML($f_style_link_read_it)).
			'</p>'.
			'<p>'.
				'<label for="f_style_link_confirm">'.__('Set the link style confirm').'</label>'.
				form::field('f_style_link_confirm',50,255,html::escapeHTML($f_style_link_confirm)).
			'</p>'.
			'<p>'.
				'<label for="f_style_link_disable">'.__('Set the link style disable').'</label>'.
				form::field('f_style_link_disable',50,255,html::escapeHTML($f_style_link_disable)).
			'</p>'.
			'<p>'.
				'<label for="f_style_link_enable">'.__('Set the link style enable').'</label>'.
				form::field('f_style_link_enable',50,255,html::escapeHTML($f_style_link_enable)).
			'</p>'.
			'<p>'.
				'<label for="f_style_link_suspend">'.__('Set the link style suspend').'</label>'.
				form::field('f_style_link_suspend',50,255,html::escapeHTML($f_style_link_suspend)).
			'</p>'.
		'</div>'.
		'<div class="fieldset" id="define_subscribe">'.
			'<h4>'.__('Define formulary content Subscribe').'</h4>'.
			'<p>'.
				'<label for="f_form_title_page">'.__('Title page of the subscribe form').'</label>'.
				form::field('f_form_title_page',50,255,html::escapeHTML($f_form_title_page)).
			'</p>'.
			'<p>'.
				'<label for="f_msg_presentation_form">'.__('Message presentation form').' : </label>'.
				form::textarea('f_msg_presentation_form',30,4,html::escapeHTML($f_msg_presentation_form)).
			'</p>'.
			'<p>'.
				'<label for="f_txt_subscribed_msg">'.__('Subcribed message').'</label>'.
				form::field('f_txt_subscribed_msg',50,255,html::escapeHTML($f_txt_subscribed_msg)).
			'</p>'.
		'</div>'.
		'<div class="fieldset" id="define_confirm">'.
			'<h4>'.__('Define message content Confirm').'</h4>'.
			'<p>'.
				'<label for="f_confirm_subject">'.__('Subject of the mail Confirm').'</label>'.
				form::field('f_confirm_subject',50,255,html::escapeHTML($f_confirm_subject)).
			'</p>'.
			'<p>'.
				'<label for="f_confirm_msg">'.__('Confirm message').'</label>'.
				form::field('f_confirm_msg',50,255,html::escapeHTML($f_confirm_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_txt_intro_confirm">'.__('Introductory confirm message').'</label>'.
				form::field('f_txt_intro_confirm',50,255,html::escapeHTML($f_txt_intro_confirm)).
			'</p>'.
			'<p>'.
				'<label for="f_txtConfirm">'.__('Title confirmation link').'</label>'.
				form::field('f_txtConfirm',50,255,html::escapeHTML($f_txtConfirm)).
			'</p>'.
			'<p>'.
				'<label for="f_concluding_confirm_msg">'.__('Concluding confirm message').'</label>'.
				form::field('f_concluding_confirm_msg',50,255,html::escapeHTML($f_concluding_confirm_msg)).
			'</p>'.
		'</div>'.
		'<div class="fieldset" id="define_disable">'.
			'<h4>'.__('Define message content Disable').'</h4>'.
			'<p>'.
				'<label for="f_txt_disabled_msg">'.__('Txt disabled msg').
				form::field('f_txt_disabled_msg',50,255,html::escapeHTML($f_txt_disabled_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_disable_subject">'.__('Subject of the mail Disable').'</label>'.
				form::field('f_disable_subject',50,255,html::escapeHTML($f_disable_subject)).
			'</p>'.
			'<p>'.
				'<label for="f_disable_msg">'.__('Disable message').'</label>'.
				form::field('f_disable_msg',50,255,html::escapeHTML($f_disable_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_txt_intro_disable">'.__('Introductory disable message').'</label>'.
				form::field('f_txt_intro_disable',50,255,html::escapeHTML($f_txt_intro_disable)).
			'</p>'.
			'<p>'.
				'<label for="f_txtDisable">'.__('Title disable link').'</label>'.
				form::field('f_txtDisable',50,255,html::escapeHTML($f_txtDisable)).
			'</p>'.
			'<p>'.
				'<label for="f_concluding_disable_msg">'.__('Concluding disable msg').'</label>'.
				form::field('f_concluding_disable_msg',50,255,html::escapeHTML($f_concluding_disable_msg)).
			'</p>'.
		'</div>'.
		'<div class="fieldset" id="define_enable">'.
			'<h4>'.__('Define message content Enable').'</h4>'.
			'<p>'.
				'<label for="f_txt_enabled_msg">'.__('Texte enabled message').'</label>'.
				form::field('f_txt_enabled_msg',50,255,html::escapeHTML($f_txt_enabled_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_enable_subject">'.__('Subject of the mail Enable').'</label>'.
				form::field('f_enable_subject',50,255,html::escapeHTML($f_enable_subject)).
			'</p>'.
			'<p>'.
				'<label for="f_enable_msg">'.__('Enable message').'</label>'.
				form::field('f_enable_msg',50,255,html::escapeHTML($f_enable_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_txt_intro_enable">'.__('Introductory enable message').'</label>'.
				form::field('f_txt_intro_enable',50,255,html::escapeHTML($f_txt_intro_enable)).
			'</p>'.
			'<p>'.
				'<label for="f_txtEnable">'.__('Title enable link').'</label>'.
				form::field('f_txtEnable',50,255,html::escapeHTML($f_txtEnable)).
			'</p>'.
			'<p>'.
				'<label for="f_concluding_enable_msg">'.__('Concluging enable message').'</label>'.
				form::field('f_concluding_enable_msg',50,255,html::escapeHTML($f_concluding_enable_msg)).
			'</p>'.
		'</div>'.
		'<div class="fieldset" id="define_suspend">'.
			'<h4>'.__('Define message content Suspend').'</h4>'.
			'<p>'.
				'<label for="f_txt_suspended_msg">'.__('Txt suspended msg').'</label>'.
				form::field('f_txt_suspended_msg',50,255,html::escapeHTML($f_txt_suspended_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_suspend_subject">'.__('Subject of the mail Suspend').'</label>'.
				form::field('f_suspend_subject',50,255,html::escapeHTML($f_suspend_subject)).
			'</p>'.
			'<p>'.
				'<label for="f_suspend_msg">'.__('Suspend message').'</label>'.
				form::field('f_suspend_msg',50,255,html::escapeHTML($f_suspend_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_txt_intro_suspend">'.__('Introductory suspend message').'</label>'.
				form::field('f_txt_intro_suspend',50,255,html::escapeHTML($f_txt_intro_suspend)).
			'</p>'.
			'<p>'.
				'<label for="f_txtSuspend">'.__('Title suspend link').'</label>'.
				form::field('f_txtSuspend',50,255,html::escapeHTML($f_txtSuspend)).
			'</p>'.
			'<p>'.
				'<label for="f_concluding_suspend_msg">'.__('Concluding suspend message').'</label>'.
				form::field('f_concluding_suspend_msg',50,255,html::escapeHTML($f_concluding_suspend_msg)).
			'</p>'.
		'</div>'.
		'<div class="fieldset" id="define_changemode">'.
			'<h4>'.__('Define message content Changemode').'</h4>'.
			'<p>'.
				'<label for="f_changemode_msg">'.__('Change mode message').'</label>'.
				form::field('f_changemode_msg',50,255,html::escapeHTML($f_changemode_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_change_mode_subject">'.__('Subject of the mail Changing mode').'</label>'.
				form::field('f_change_mode_subject',50,255,html::escapeHTML($f_change_mode_subject)).
			'</p>'.
			'<p>'.
				'<label for="f_header_changemode_msg">'.__('Introductory change mode message').'</label>'.
				form::field('f_header_changemode_msg',50,255,html::escapeHTML($f_header_changemode_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_footer_changemode_msg">'.__('Concludind change mode message').'</label>'.
				form::field('f_footer_changemode_msg',50,255,html::escapeHTML($f_footer_changemode_msg)).
			'</p>'.
		'</div>'.
		'<div class="fieldset" id="define_resume">'.
			'<h4>'.__('Define message content Resume').'</h4>'.
			'<p>'.
				'<label for="f_resume_subject">'.__('Subject of the mail Resume').'</label>'.
				form::field('f_resume_subject',50,255,html::escapeHTML($f_resume_subject)).
			'</p>'.
			'<p>'.
				'<label for="f_header_resume_msg">'.__('Introductory resume message').'</label>'.
				form::field('f_header_resume_msg',50,255,html::escapeHTML($f_header_resume_msg)).
			'</p>'.
			'<p>'.
				'<label for="f_footer_resume_msg">'.__('Concluding resume message').'</label>'.
				form::field('f_footer_resume_msg',50,255,html::escapeHTML($f_footer_resume_msg)).
			'</p>'.
		'</div>'.
		# boutons du formulaire
		'<h3>'.__('Update messages').'</h3>'.
		'<p>'.
		'<input type="submit" name="save" value="'.__('Update').'" /> '.
		'<input type="reset" name="reset" value="'.__('Cancel').'" /> '.
		'</p>'.
	
		'<p>'.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'maintenance').
			form::hidden(array('op'),'messages').
			$core->formNonce().
		'</p>'.
		'</form>'.
	'</div>';
			
	# Print page Maintenance
	echo '<div class="multi-part" id="tab_maintenance" title="'.__('Maintenance').'">';
		echo '<h3>'.__('Maintenance').'</h3>';
		
		echo
			'<div class="fieldset">'.
				'<h4>'.__('Plugin state').'</h4>'.
				'<form action="plugin.php" method="post" id="state">'.
					'<p class="field">'.
						'<label for="newsletter_flag" class="classic">'.__('Enable plugin').'</label>'.
						form::checkbox('newsletter_flag', 1, $blog_settings->newsletter_flag).
					'</p>'.
					'<p>'.
						'<input type="submit" value="'.__('Save').'" /> '.
						'<input type="reset" value="'.__('Cancel').'" /> '.
					'</p>'.
					form::hidden(array('p'),newsletterPlugin::pname()).
					form::hidden(array('m'),'maintenance').
					form::hidden(array('op'),'state').
					$core->formNonce().
				'</form>'.
			'</div>';
		
		if ($blog_settings->newsletter_flag) {
			# export
			echo '<h3>'.__('Export').'</h3>';
			
			echo
			'<form action="plugin.php" method="post" class="fieldset">'.
			'<h4>'.__('Single blog').'</h4>'.
			'<p>'.sprintf(__('This will create an export of subscribers of your current blog: <strong>%s</strong>'),html::escapeHTML($core->blog->name)).'</p>'.
			
			'<p><label for="f_export_file_name">'.__('File name:').'</label>'.
			form::field(array('f_export_file_name','f_export_file_name'),50,255,date('Ymd-Hi-').html::escapeHTML($core->blog->id.'-'.newsletterPlugin::pname().'-backup.txt')).
			'</p>'.
			
			'<p><label for="f_export_format">'.__('File format:').'</label>'.
			form::combo('f_export_format',$format_combo,$f_export_format).
			'</p>'.
			
			'<p><label for="f_export_file_zip" class="classic">'.
			form::checkbox(array('f_export_file_zip','f_export_file_zip'),1).' '.
			__('Compress file').'</label>'.
			'</p>'.
			
			'<p><input type="submit" value="'.__('Export').'" />'.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'maintenance').
			form::hidden(array('op'),'export_blog').
			$core->formNonce().'</p>'.

			'</form>';

			if ($core->auth->isSuperAdmin())
			{
				echo
				'<form action="plugin.php" method="post" class="fieldset">'.
				'<h4>'.__('Multiple blogs').'</h4>'.
				'<p>'.__('This will create an export of subscribers of all blog').'</p>'.
				
				'<p><label for="f_export_file_name">'.__('File name:').'</label>'.
				form::field(array('f_export_file_name','f_export_file_name'),50,255,date('Ymd-Hi-').html::escapeHTML(newsletterPlugin::pname().'-backup.txt')).
				'</p>'.
				
				'<p><label for="f_export_format">'.__('File format:').'</label>'.
				form::combo('f_export_format',$format_combo,$f_export_format).
				'</p>'.
				
				'<p><label for="f_export_file_zip" class="classic">'.
				form::checkbox(array('f_export_file_zip','f_export_file_zip'),1).' '.
				__('Compress file').'</label>'.
				'</p>'.
				
				'<p><input type="submit" value="'.__('Export').'" />'.
				form::hidden(array('p'),newsletterPlugin::pname()).
				form::hidden(array('m'),'maintenance').
				form::hidden(array('op'),'export_all').
				$core->formNonce().'</p>'.
				
				'</form>';
			}
			
			# Import
			echo '<h3>'.__('Import').'</h3>';
			echo
			'<form action="plugin.php" method="post" class="fieldset" enctype="multipart/form-data">'.
			'<h4>'.__('Import subscribers list').'</h4>'.
			'<p>'.sprintf(__('This will import a backup of subscribers list on your current blog: <strong>%s</strong>.'),html::escapeHTML($core->blog->name)).'</p>'.

			'<p><label for="f_import_file">'.__('Upload a backup file').
			' ('.sprintf(__('maximum size %s'),files::size(DC_MAX_UPLOAD_SIZE)).')'.' </label>'.
			' <input type="file" id="f_import_file" name="f_import_file" size="20" />'.
			'</p>'.
			
			'<p><label for="f_import_format">'.__('File format:').'</label>'.
			form::combo('f_import_format',$format_combo,$f_import_format).
			'</p>'.
				
			'<p><input type="submit" value="'.__('Import').'" />'.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'maintenance').
			form::hidden(array('op'),'import').
			form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).
			$core->formNonce().'</p>'.
			
			'</form>';
			
			echo
				'<form action="plugin.php" method="post" id="reprise" name="reprise" enctype="multipart/form-data" class="fieldset">'.
					'<h4>'.__('Importing a list of emails includes in a text file on the current blog').'</h4>'.
					'<p><label class="classic required" title="'.__('Required field').'">'.__('File to import :').' '.'</label>'.
						'<input type="file" name="file_reprise" />'.
					'</p>'.
					'<p><label class="classic required" title="'.__('Required field').'">'.__('Your password:').' '.'</label>'.
						form::password(array('your_pwd'),20,255).
					'</p>'.
					'<p>'.
						'<input type="submit" value="'.__('Launch reprise').'" />'.
					'</p>'.
					form::hidden(array('p'),newsletterPlugin::pname()).
					form::hidden(array('m'),'maintenance').
					form::hidden(array('op'),'reprise').
					$core->formNonce().
				'</form>';
			
			if ($sadmin) {
				echo '<h3>'.__('Adapt').'</h3>';
				# adapt template
				echo
					'<form action="plugin.php" method="post" id="adapt" class="fieldset">'.
						'<h4>'.__('Adapt the template for the theme').'</h4>'.
						'<p>'.__('<strong>Caution :</strong> use the theme adapter only if you experience some layouts problems when viewing newsletter form on your blog.')."</p>".
						'<p><label class="classic" for="fthemes">'.__('Theme name').' : '.
						form::combo('fthemes',$bthemes,$theme).
						'</label></p>'.
						'<p>'.
							'<input type="submit" value="'.__('Adapt').'" />'.
						'</p>'.
						'<p>'.
							'<a href="'.$urlBase.'/test'.'" target="_blank">'.__('Clic here to test the template.').'</a>'.
						'</p>'.
						form::hidden(array('p'),newsletterPlugin::pname()).
						form::hidden(array('m'),'maintenance').
						form::hidden(array('op'),'adapt_theme').
						$core->formNonce().
					'</form>';
			}
		
			if ($sadmin) {
				echo '<h3>'.__('Cleaning').'</h3>';
				echo
					'<form action="plugin.php" method="post" id="erasingnewsletter" class="fieldset">'.
					'<h4>'.__('Erasing all informations about newsletter in database').'</h4>'.
						'<p>'.__('<strong>Caution :</strong> please backup your database before use this option.')."</p>".
						
						'<p><input type="submit" value="'.__('Erasing').'" name="delete" class="delete"/>'.
						'</p>'.
						form::hidden(array('p'),newsletterPlugin::pname()).
						form::hidden(array('m'),'maintenance').
						form::hidden(array('op'),'erasingnewsletter').
						$core->formNonce().
					'</form>';
			}
		}	
	echo '</div>';
	
	# Print page EditCSS
	echo '<div class="multi-part" id="tab_editCSS" title="'.__('CSS for letters').'">';
	echo '<h3>'.__('File editor').'</h3>';
	echo	'<form action="plugin.php" method="post" id="file-form">';
	echo				
				'<p>'.sprintf(__('Editing file %s'),'<strong>'.$f_name).'</strong></p>'.
				'<p>'.
					form::textarea('f_content',72,25,html::escapeHTML($f_content),'maximal','',!$f_editable).
				'</p>';
		if(!$f_editable) {
			echo '<p>'.__('This file is not writable. Please check your theme files permissions.').'<p>'.
				'<p>NB: '.sprintf(__('If you want edit the file CSS, please copy the default file %s in your current theme folder.'),$file_style_letter).'</p>';
		} else {
			echo
				'<p>'.
					'<input type="submit" name="write" value="'.__('save').' (s)" accesskey="s" /> '.
					form::hidden(array('p'),newsletterPlugin::pname()).
					form::hidden(array('m'),'editCSS').
					form::hidden(array('op'),'write_css').
					form::hidden(array('filecss'),$file_style_letter).
					$core->formNonce().
				'</p>';
		}
		echo '</form>';
		
		if(!$f_editable) {
			echo '<form action="plugin.php" method="post" id="file-form-copy">';
			echo '<h4>'.__('Copy this CSS to your theme').'</h4>';
			echo
			'<p><label class="classic" for="fthemes">'.__('Theme name').' : '.
			form::combo('fthemes',$bthemes,$theme).
			'</label></p>';
			echo
			'<p>'.
			'<input type="submit" name="write" value="'.__('copy to theme').'" /> '.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'editCSS').
			form::hidden(array('op'),'copy_css').
			form::hidden(array('filecss'),$file_style_letter).
			$core->formNonce().
			'</p>';
			echo '</form>';
		}		
		echo '</div>';

		# Print page EditCSSforms
		echo '<div class="multi-part" id="tab_editFormsCSS" title="'.__('CSS for forms').'">';
		echo '<h3>'.__('File editor').'</h3>';
		echo	'<form action="plugin.php" method="post" id="file-form-pub">';
		echo
		'<p>'.sprintf(__('Editing file %s'),'<strong>'.$f_css_forms_name).'</strong></p>'.
		'<p>'.
		form::textarea('f_css_forms_content',72,25,html::escapeHTML($f_css_forms_content),'maximal','',!$f_css_forms_editable).
		'</p>';
		if(!$f_css_forms_editable) {
			echo '<p>'.__('This file is not writable. Please check your theme files permissions.').'<p>'.
				'<p>NB: '.sprintf(__('If you want edit the file CSS, please copy the default file %s in your current theme folder.'),$file_style_newsletter_forms).'</p>';
		} else {
			echo
			'<p>'.
			'<input type="submit" name="write" value="'.__('save').' (s)" accesskey="s" /> '.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'editFormsCSS').
			form::hidden(array('op'),'write_css').
			form::hidden(array('filecss'),$file_style_newsletter_forms).
			$core->formNonce().
			'</p>';
		}
		echo '</form>';
		
		if(!$f_css_forms_editable) {
			echo '<form action="plugin.php" method="post" id="file-form-pub-copy">';
			echo '<h4>'.__('Copy this CSS to your theme').'</h4>';
			echo
			'<p><label class="classic" for="fthemes">'.__('Theme name').' : '.
			form::combo('fthemes',$bthemes,$theme).
			'</label></p>';
			echo
			'<p>'.
			'<input type="submit" name="write" value="'.__('copy to theme').'" /> '.
			form::hidden(array('p'),newsletterPlugin::pname()).
			form::hidden(array('m'),'editFormsCSS').
			form::hidden(array('op'),'copy_css').
			form::hidden(array('filecss'),$file_style_newsletter_forms).
			$core->formNonce().
			'</p>';
			echo '</form>';
		}
		echo '</div>';		
		
	}
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}	
	
dcPage::helpBlock('newsletter');

?>

</body>
</html>