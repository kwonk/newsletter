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

if (!defined('DC_CONTEXT_ADMIN')) { return; }

define("POST_TYPE","newsletter");

class newsletterLetter
{
	protected $core;
	protected $blog;
	protected $meta;
	protected $letter_id;
	
	protected $letter_subject;
	protected $letter_header;
	protected $letter_body;
	protected $letter_body_text;
	protected $letter_footer;
	
	protected $post_id;
	protected $cat_id;
	protected $post_dt;
	protected $post_format;
	protected $post_editor;
	protected $post_password;
	protected $post_url;
	protected $post_lang;
	protected $post_title;
	protected $post_excerpt;
	protected $post_excerpt_xhtml;
	protected $post_content;
	protected $post_content_xhtml;
	protected $post_notes;
	protected $post_status;
	protected $post_selected;
	protected $post_open_comment;
	protected $post_open_tb;
	protected $post_meta;
	
	private static $post_type = 'newsletter';

	/**
	 * Class constructor. Sets new letter object
	 * @param dcCore $core
	 * @param $letter_id
	 */
	public function __construct(dcCore $core,$letter_id=null)
	{
		$this->core = $core;
		$this->blog = $core->blog;
		$this->meta = $core->meta;
		$this->system_settings = $core->blog->settings->system;
		$this->init();
		$this->setLetterId($letter_id);
		$this->letter_subject = '';
		$this->letter_header = '';
		$this->letter_body = '';
		$this->letter_footer = '';
		$this->letter_body_text = '';
	}

	private function init()
	{
		$this->post_id = '';
		$this->cat_id = '';
		$this->post_dt = '';
		$this->post_format = $this->core->auth->getOption('post_format');
		$this->post_editor = $this->core->auth->getOption('editor');
		$this->post_password = '';
		$this->post_url = '';
		$this->post_lang = $this->core->auth->getInfo('user_lang');
		$this->post_title = '';
		$this->post_excerpt = '';
		$this->post_excerpt_xhtml = '';
		$this->post_content = '';
		$this->post_content_xhtml = '';
		$this->post_notes = '';
		$this->post_status = $this->core->auth->getInfo('user_post_status');
		$this->post_selected = false;
		$this->post_open_comment = false;
		$this->post_open_tb = false;
		$this->post_media = array();
		$this->post_meta = array();
	}
	
	/**
	 * Set id of the letter
	 * @param $letter_id
	 */
	public function setLetterId($letter_id=null)
	{
		if ($letter_id) {
			$this->letter_id = $letter_id;
		}
	}

	/**
	 * Get id of the letter
	 * @return integer
	 */
	public function getLetterId()
	{
		return (integer) $this->letter_id;
	}

	/**
	 * Get the ressource mysql result for the current letter
	 * @return mysql result
	 */
	private function getRSLetter()
	{
		$params['post_type'] = $this->post_type;
		$params['post_id'] = $this->letter_id;
			
		$rs_letter = $this->core->blog->getPosts($params);
			
		if ($rs_letter->isEmpty()) {
			$this->core->error->add(__('This letter does not exist'));
		} else {
			return $rs_letter;
		}
	}

	/**
	 * Get the url of the letter
	 * @return string
	 */
	public static function getURL($letter_id)
	{
		global $core;
		
		$params['post_type'] = 'newsletter';
		$params['post_id'] = $letter_id;
			
		$rs = $core->blog->getPosts($params);
		
		if ($rs->isEmpty())	{
			return ' ';
		} else {
			$rs->fetch();
			return $rs->getURL();
		}
	}
	
	public function displayTabLetter() 
	{
		global $core; 
		
		$p_url = 'plugin.php?p=newsletter';
		$redir_url = $p_url.'&m=letter';

		$post_id = '';
		$cat_id = '';
		$post_dt = '';
		$post_format = $core->auth->getOption('post_format');
		$post_editor = $core->auth->getOption('editor');
		$post_password = '';
		$post_url = '';
		$post_lang = $core->auth->getInfo('user_lang');
		$post_title = '';
		$post_excerpt = '';
		$post_excerpt_xhtml = '';
		$post_content = '';
		$post_content_xhtml = '';
		$post_notes = '';
		$post_status = $core->auth->getInfo('user_post_status');
		$post_selected = false;
		$post_open_comment = false;
		$post_open_tb = false;
		
		$post_media = array();
		$post_meta = array();
		
		$page_title = __('New letter');
		
		$can_view_letter = true;
		
		// check perms
		$can_edit_letter = $core->auth->check('usage,contentadmin',$core->blog->id);
		$can_publish = $core->auth->check('publish,contentadmin',$core->blog->id);
		$can_delete = false;
		
		$post_headlink = '<link rel="%s" title="%s" href="'.html::escapeURL($redir_url).'&id=%s" />';
		$post_link = '<a href="'.html::escapeURL($redir_url).'&amp;id=%s" title="%s">%s</a>';
		
		$next_link = $prev_link = $next_headlink = $prev_headlink = null;
		
		# Default value
		$post_status = -2;

		# Status combo
		$status_combo = dcAdminCombos::getPostStatusesCombo();

		$img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';
		
		# Formaters combo
		$core_formaters = $core->getFormaters();
		$available_formats = array('' => '');
		foreach ($core_formaters as $editor => $formats) {
			foreach ($formats as $format) {
				$available_formats[$format] = $format;
			}
		}

		# Languages combo
		$rs = $core->blog->getLangs(array('order'=>'asc'));
		$lang_combo = dcAdminCombos::getLangsCombo($rs,true);

		# Validation flag
		$bad_dt = false;
				
		# Get letter informations
		if (!empty($_REQUEST['id']))
		{
			$params['post_type'] = 'newsletter';
			$params['post_id'] = $_REQUEST['id'];
			
			$post = $core->blog->getPosts($params);
			
			if ($post->isEmpty())
			{
				$core->error->add(__('This letter does not exist.'));
				$can_view_letter = false;
			}
			else
			{
				$post_id = $post->post_id;
				$post_dt = date('Y-m-d H:i',strtotime($post->post_dt));
				$post_format = $post->post_format;
				$post_password = $post->post_password;
				$post_url = $post->post_url;
				$post_lang = $post->post_lang;
				$post_title = $post->post_title;
				$post_excerpt = $post->post_excerpt;
				$post_excerpt_xhtml = $post->post_excerpt_xhtml;
				$post_content = $post->post_content;
				$post_content_xhtml = $post->post_content_xhtml;
				$post_notes = $post->post_notes;
				$post_status = $post->post_status;
				$post_open_comment = (boolean) $post->post_open_comment;
				$post_open_tb = (boolean) $post->post_open_tb;
				$post_selected = (boolean) $post->post_selected;
				
				$page_title = __('Edit letter');
				
				$can_edit_letter = $post->isEditable();
				$can_delete= $post->isDeletable();
				
				$next_rs = $core->blog->getNextPost($post,1);
				$prev_rs = $core->blog->getNextPost($post,-1);
				
				if ($next_rs !== null) {
					$next_link = sprintf($post_link,$next_rs->post_id,
						html::escapeHTML($next_rs->post_title),__('Next letter').'&nbsp;&#187;');
					$next_headlink = sprintf($post_headlink,'next',
						html::escapeHTML($next_rs->post_title),$next_rs->post_id);
				}
				
				if ($prev_rs !== null) {
					$prev_link = sprintf($post_link,$prev_rs->post_id,
						html::escapeHTML($prev_rs->post_title),'&#171;&nbsp;'.__('Previous letter'));
					$prev_headlink = sprintf($post_headlink,'previous',
						html::escapeHTML($prev_rs->post_title),$prev_rs->post_id);
				}
				
				$post_meta = $post->post_meta;
				/*try {
					$core->meta = new dcMeta($core);
					$post_meta = self::getPostsLetter($post_id);
					
				} catch (Exception $e) {}				*/

				try {
					$core->media = new dcMedia($core);
					$post_media = $core->media->getPostMedia($post_id);
				} catch (Exception $e) {
					$core->error->add($e->getMessage());
				}
				
			}
		}

		# Format content
		if (!empty($_POST) && $can_edit_letter)
		{
			$post_format = $_POST['post_format'];
			$post_excerpt = $_POST['post_excerpt'];
			$post_content = $_POST['post_content'];
			
			$post_title = $_POST['post_title'];
			
			if (isset($_POST['post_status'])) {
				$post_status = (integer) $_POST['post_status'];
			}
			
			if (empty($_POST['post_dt'])) {
				$post_dt = '';
			} else {
				try
				{
					$post_dt = strtotime($_POST['post_dt']);
					if ($post_dt == false || $post_dt == -1) {
						$bad_dt = true;
						throw new Exception(__('Invalid publication date'));
					}
					$post_dt = date('Y-m-d H:i',$post_dt);
				}
				catch (Exception $e)
				{
					$core->error->add($e->getMessage());
				}				
			}
			
			/*
			$post_open_comment = !empty($_POST['post_open_comment']);
			$post_open_tb = !empty($_POST['post_open_tb']);
			//*/
			$post_selected = !empty($_POST['post_selected']);
			$post_lang = $_POST['post_lang'];
			$post_password = !empty($_POST['post_password']) ? $_POST['post_password'] : null;
			//$post_notes = $_POST['post_notes'];
			
			if (isset($_POST['post_url'])) {
				$post_url = $_POST['post_url'];
			}
			
			$core->blog->setPostContent(
				$post_id,$post_format,$post_lang,
				$post_excerpt,$post_excerpt_xhtml,$post_content,$post_content_xhtml
			);
		}

		# Delete letter
		if (!empty($_POST['delete']) && $can_delete)
		{
			try {
				# --BEHAVIOR-- adminBeforeLetterDelete
				$core->callBehavior('adminBeforeLetterDelete',$post_id);
				$core->blog->delPost($post_id);
				http::redirect($p_url.'&m=letters');
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}		
		
		# Create or update post
		if (!empty($_POST) && !empty($_POST['save']) && $can_edit_letter  && !$bad_dt)
		{
			$cur = $core->con->openCursor($core->prefix.'post');
			
			$cur->post_type = 'newsletter';
			$cur->post_title = $post_title;
			$cur->post_dt = $post_dt ? date('Y-m-d H:i:00',strtotime($post_dt)) : '';
			$cur->post_format = $post_format;
			$cur->post_password = $post_password;
			$cur->post_lang = $post_lang;
			$cur->post_title = $post_title;
			$cur->post_excerpt = $post_excerpt;
			$cur->post_excerpt_xhtml = $post_excerpt_xhtml;
			$cur->post_content = $post_content;
			$cur->post_content_xhtml = $post_content_xhtml;
			//$cur->post_notes = $post_notes;
			$cur->post_status = $post_status;
			$cur->post_selected = (integer) $post_selected;
			//$cur->post_open_comment = (integer) $post_open_comment;
			//$cur->post_open_tb = (integer) $post_open_tb;
			
			if (isset($_POST['post_url'])) {
				$cur->post_url = $post_url;
			} 
		
			# Update post
			if ($post_id)
			{
				try
				{
					# --BEHAVIOR-- adminBeforeLetterUpdate
					$core->callBehavior('adminBeforeLetterUpdate',$cur,$post_id);

					$core->blog->updPost($post_id,$cur);
					
					# --BEHAVIOR-- adminAfterLetterUpdate
					$core->callBehavior('adminAfterLetterUpdate',$cur,$post_id);
					
					http::redirect($redir_url.'&id='.$post_id.'&upd=1');
				}
				catch (Exception $e)
				{
					$core->error->add($e->getMessage());
				}
			}
			else
			{
				$cur->user_id = $core->auth->userID();
				
				try
				{
					# --BEHAVIOR-- adminBeforeLetterCreate
					$core->callBehavior('adminBeforeLetterCreate',$cur);
					
					$return_id = $core->blog->addPost($cur);
					
					# --BEHAVIOR-- adminAfterLetterCreate
					$core->callBehavior('adminAfterLetterCreate',$cur,$return_id);
					
					http::redirect($redir_url.'&id='.$return_id.'&crea=1');
				}
				catch (Exception $e)
				{
					$core->error->add($e->getMessage());
				}
			}
		}

		/* DISPLAY
		-------------------------------------------------------- */
		/*
		$default_tab = 'edit-entry';
		if (!$can_edit_letter) {
			$default_tab = '';
		}
		
		if (!empty($_GET['co'])) {
			$default_tab = 'comments';
		}
		*/
		
		if ($post_id) {
			switch ($post_status) {
				case 1:
					$img_status = sprintf($img_status_pattern,__('Published'),'check-on.png');
					break;
				case 0:
					$img_status = sprintf($img_status_pattern,__('Unpublished'),'check-off.png');
					break;
				case -1:
					$img_status = sprintf($img_status_pattern,__('Scheduled'),'scheduled.png');
					break;
				case -2:
					$img_status = sprintf($img_status_pattern,__('Pending'),'check-wrn.png');
					break;
				default:
					$img_status = '';
			}
			$edit_entry_title = '&ldquo;'.$post_title.'&rdquo;'.' '.$img_status;
		} else {
			$edit_entry_title = $page_title;
		}
		
		$admin_post_behavior = '';
		if ($this->post_editor) {
			$p_edit = $c_edit = '';
			if (!empty($this->post_editor[$post_format])) {
				$p_edit = $this->post_editor[$post_format];
			}
			if (!empty($this->post_editor['xhtml'])) {
				$c_edit = $this->post_editor['xhtml'];
			}
			if ($p_edit == $c_edit) {
				$admin_post_behavior .= $this->core->callBehavior('adminPostEditor',
						$p_edit,'letter',array('#post_excerpt','#post_content','#comment_content'));
				
			} else {
				$admin_post_behavior .= $this->core->callBehavior('adminPostEditor',
						$p_edit,'letter',array('#post_excerpt','#post_content'));
				$admin_post_behavior .= $this->core->callBehavior('adminPostEditor',
						$c_edit,'comment',array('#comment_content'));
			}
		}
		
		echo
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		dcPage::jsVar('dotclear.msg.confirm_delete_post', __('Are you sure you want to delete this letter?')).
		"\n//]]>\n".
		"</script>\n";
		
		echo
		dcPage::jsDatePicker().
		dcPage::jsModal().
		dcPage::jsMetaEditor().
		$admin_post_behavior.
		dcPage::jsLoad('js/_post.js').
		dcPage::jsConfirmClose('entry-form').
		# --BEHAVIOR-- adminLetterHeaders
		$core->callBehavior('adminLetterHeaders').
		dcPage::jsPageTabs('edit-entry');

		//$next_headlink."\n".$prev_headlink;
		/*
		echo dcPage::breadcrumb(
				array(
						html::escapeHTML($core->blog->name) => '',
						__('Pages') => $p_url,
						$edit_entry_title => ''
				));		
		*/
		
		if (!empty($_GET['upd'])) {
			dcPage::success(__('Letter has been successfully updated.'));
		}
		elseif (!empty($_GET['crea'])) {
			dcPage::success(__('Letter has been successfully created.'));
		}
		elseif (!empty($_GET['attached'])) {
			dcPage::success(__('File has been successfully attached.'));
		}
		elseif (!empty($_GET['rmattach'])) {
			dcPage::success(__('Attachment has been successfully removed.'));
		}		
		
		# XHTML conversion
		if (!empty($_GET['xconv']))
		{
			$post_excerpt = $post_excerpt_xhtml;
			$post_content = $post_content_xhtml;
			$post_format = 'xhtml';
		
			dcPage::message(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
		}
		
		if ($post_id && $post->post_status == 1) {
			echo '<p><a class="onblog_link outgoing" href="'.$post->getURL().'" title="'.$post_title.'">'.__('Go to this letter on the site').' <img src="images/outgoing-blue.png" alt="" /></a></p>';
		}		
		
		echo '';
		
		
		if ($post_id)
		{
			echo '<p class="nav_prevnext">';
			if ($prev_link) { echo $prev_link; }
			if ($next_link && $prev_link) { echo ' | '; }
			if ($next_link) { echo $next_link; }
		
			# --BEHAVIOR-- adminPageNavLinks
			$core->callBehavior('adminPageNavLinks',isset($post) ? $post : null);
		
			echo '</p>';
		}
		
		# Exit if we cannot view letter
		if (!$can_view_letter) {
			exit;
		}
		
		# Preview page
		if ($post_id && $post->post_status == 1) {
			echo '<p><a id="post-preview" href="'.$post->getURL().'" class="button">'.__('View letter').'</a></p>';
		} elseif ($post_id) {
			$preview_url =
			$core->blog->url.$core->url->getBase('letterpreview').'/'.
			$core->auth->userID().'/'.
			http::browserUID(DC_MASTER_KEY.$core->auth->userID().$core->auth->getInfo('user_pwd')).
			'/'.$post->post_url;
			echo '<a id="post-preview" href="'.$preview_url.'" class="button modal" accesskey="p">'.__('Preview letter').' (p)'.'</a> ';
		}
		
		/* Post form if we can edit letter
		-------------------------------------------------------- */
		if ($can_edit_letter)
		{
			$sidebar_items = new ArrayObject(array(
					'status-box' => array(
							'title' => __('Status'),
							'items' => array(
									'post_status' =>
									'<p><label for="post_status">'.__('Letter status').'</label> '.
									form::combo('post_status',$status_combo,$post_status,'','',!$can_publish).
									'</p>',
									'post_dt' =>
									'<p><label for="post_dt">'.__('Publication date and hour').'</label>'.
									form::field('post_dt',16,16,$post_dt,($bad_dt ? 'invalid' : '')).
									'</p>',
									'post_lang' =>
									'<p><label for="post_lang">'.__('Letter language').'</label>'.
									form::combo('post_lang',$lang_combo,$post_lang).
									'</p>',
									'post_format' =>
									'<div>'.
									'<h5 id="label_format"><label for="post_format" class="classic">'.__('Text formatting').'</label></h5>'.
									'<p>'.form::combo('post_format',$available_formats,$post_format,'maximal').'</p>'.
									'<p class="format_control control_no_xhtml">'.
									'<a id="convert-xhtml" class="button'.($post_id && $post_format != 'wiki' ? ' hide' : '').'"
                  href="'.html::escapeURL($redir_url).'&amp;id='.$post_id.'&amp;xconv=1">'.
									__('Convert to XHTML').'</a></p></div>')),
						'options-box' => array(
							'title' => __('Options'),
							'items' => array(
									/*
									'post_open_comment_tb' =>
									'<div>'.
									'<h5 id="label_comment_tb">'.__('Comments and trackbacks list').'</h5>'.
									'<p><label for="post_open_comment" class="classic">'.
									form::checkbox('post_open_comment',1,$post_open_comment).' '.
									__('Accept comments').'</label></p>'.
									($core->blog->settings->system->allow_comments ?
											(isContributionAllowed($post_id,strtotime($post_dt),true) ?
													'' :
													'<p class="form-note warn">'.
													__('Warning: Comments are not more accepted for this entry.').'</p>') :
											'<p class="form-note warn">'.
											__('Comments are not accepted on this blog so far.').'</p>').
									'<p><label for="post_open_tb" class="classic">'.
									form::checkbox('post_open_tb',1,$post_open_tb).' '.
									__('Accept trackbacks').'</label></p>'.
									($core->blog->settings->system->allow_trackbacks ?
											(isContributionAllowed($post_id,strtotime($post_dt),false) ?
													'' :
													'<p class="form-note warn">'.
													__('Warning: Trackbacks are not more accepted for this entry.').'</p>') :
											'<p class="form-note warn">'.__('Trackbacks are not accepted on this blog so far.').'</p>').
									'</div>',
									//*/
									'post_hide' =>
									'<p><label for="post_selected" class="classic">'.form::checkbox('post_selected',1,$post_selected).' '.
									__('Hide in widget Newsletters').'</label>'.
									'</p>',
									'post_password' =>
									'<p><label for="post_password">'.__('Password').'</label>'.
									form::field('post_password',10,32,html::escapeHTML($post_password),'maximal').
									'</p>',
									'post_url' =>
									'<div class="lockable">'.
									'<p><label for="post_url">'.__('Edit basename').'</label>'.
									form::field('post_url',10,255,html::escapeHTML($post_url),'maximal').
									'</p>'.
									'<p class="form-note warn">'.
									__('Warning: If you set the URL manually, it may conflict with another letter.').
									'</p></div>'
							))));								
			$main_items = new ArrayObject(array(
					"post_title" =>
					'<p class="col">'.
					'<label class="required no-margin bold"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').'</label>'.
					form::field('post_title',20,255,html::escapeHTML($post_title),'maximal').
					'</p>',
			
					"post_excerpt" =>
					'<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">'.__('Excerpt:').' <span class="form-note">'.
					__('Introduction to the letter.').'</span></label> '.
					form::textarea('post_excerpt',50,5,html::escapeHTML($post_excerpt)).
					'</p>',
			
					"post_content" =>
					'<p class="area" id="content-area"><label class="required bold" '.
					'for="post_content"><abbr title="'.__('Required field').'">*</abbr> '.__('Content:').'</label> '.
					form::textarea('post_content',50,$core->auth->getOption('edit_size'),html::escapeHTML($post_content)).
					'</p>'
					
					/*
					"post_notes" =>
					'<p class="area" id="notes-area"><label for="post_notes" class="bold">'.__('Personal notes:').' <span class="form-note">'.
					__('Unpublished notes.').'</span></label>'.
					form::textarea('post_notes',50,5,html::escapeHTML($post_notes)).
					'</p>'
					*/
			)
			);
				
			# --BEHAVIOR-- adminPostFormItems
			$core->callBehavior('adminLetterFormItems',$main_items,$sidebar_items, isset($post) ? $post : null);
			
			echo '<div class="multi-part" title="'.($post_id ? __('Edit letter') : __('New letter')).'" id="edit-entry">';
				
			echo '<form action="'.html::escapeURL($redir_url).'&amp;m=letter" method="post" id="entry-form">';
			
			echo '<div id="entry-wrapper">';
			echo '<div id="entry-content"><div class="constrained">';
			echo '<h3 class="out-of-screen-if-js">'.__('Edit letter').'</h3>';
				
			foreach ($main_items as $id => $item) {
				echo $item;
			}

			# --BEHAVIOR-- adminLetterForm
			$core->callBehavior('adminLetterForm',isset($post) ? $post : null);			
	
			echo
			'<p class="border-top">'.
			($post_id ? form::hidden('id',$post_id) : '').
			'<input type="submit" value="'.__('Save').' (s)" '.
			'accesskey="s" name="save" /> ';


			if ($post_id) {
				$preview_url = $core->blog->url.
				$core->url->getURLFor('letterpreview',
						$core->auth->userID().'/'.
						http::browserUID(DC_MASTER_KEY.$core->auth->userID().$core->auth->getInfo('user_pwd')).
						'/'.$post->post_url);
				//echo '<a id="post-preview" href="'.$preview_url.'" class="button" accesskey="p">'.__('Preview').' (p)'.'</a>';
			} else {
				echo
				'<a id="post-cancel" href="index.php" class="button" accesskey="c">'.__('Cancel').' (c)</a>';
			}
			
			echo
			($can_delete ? '<input type="submit" class="delete" value="'.__('Delete').'" name="delete" />' : '').
			$core->formNonce().
			'</p>';
							
			echo '</div></div>';		// End #entry-content
			echo '</div>';		// End #entry-wrapper
			
		
			echo '<div id="entry-sidebar" role="complementary">';	
			
			foreach ($sidebar_items as $id => $c) {
				echo '<div id="'.$id.'" class="sb-box">'.
						'<h4>'.$c['title'].'</h4>';
				foreach ($c['items'] as $e_name=>$e_content) {
					echo $e_content;
				}
				echo '</div>';
			}
			
			# --BEHAVIOR-- adminLetterFormSidebar
			$core->callBehavior('adminLetterFormSidebar',isset($post) ? $post : null);

			echo '</div>';		// End #entry-sidebar

			echo '</form>';
			
			echo '</div>';		// End
			
			# attach posts
			if ($post_id)
			{
				echo '<h3 class="clear">'.__('Entries linked').'</h3>';
				echo '<div id="link_posts" class="clear fieldset">';
					
				$meta = $core->meta;
					
				$params=array();
				$params['no_content'] = true;
					
				$params['meta_id'] = $post_id;
				$params['meta_type'] = 'letter_post';
				$params['post_type'] = '';
					
				# Get posts
				try {
				$posts = $meta->getPostsByMeta($params);
				$counter = $meta->getPostsByMeta($params,true);
				$post_list = new newsletterLinkedPostList($core,$posts,$counter->f(0));
				} catch (Exception $e) {
				$core->error->add($e->getMessage());
				}
				//print($counter->f(0));
				$page = 1;
				$nb_per_page = 10;
					
				if (!$core->error->flag())
				{
				if (!$posts->isEmpty())
				{
				;
			}
				
			# Show posts
			$post_list->display($page,$nb_per_page,
			'%s'
			,$post_id);
				
			}
			echo '<p><a href="plugin.php?p=newsletter&amp;m=letter_associate&amp;post_id='.$post_id.'">'.__('Add many posts to this letter').'</a></p>';
				echo '</div>';
			}
			
			self::printKeywords();			

			/*
			if ($post_id && !empty($post_media))
			{
				echo
				'<form action="post_media.php" id="attachment-remove-hide" method="post">'.
				'<div>'.form::hidden(array('post_id'),$post_id).
				form::hidden(array('media_id'),'').
				form::hidden(array('remove'),1).
				$core->formNonce().'</div></form>';
			}
			*/			
			

			
		}
	}

	/**
	 * print the list of keywords
	 */	
	protected static function printKeywords() 
	{
		$tab_keywords = array('LISTPOSTS' => __('displays a list of posts attached'),
						'LINK_VISU_ONLINE' => __('displays the link to the newsletter up on your blog'),
						'USER_DELETE' => __('displays the delete link of the user subscription'),
						'USER_SUSPEND' => __('displays the link suspension of the user subscription'));		


		echo '<h3 class="clear">'.__('Informations').'</h3>';
		echo '<div class="fieldset">';
		echo '<div class="col">';
		echo '<h4>'.__('List of keywords').'</h4>';
		echo '<ul>';
		foreach ($tab_keywords as $k => $v) {
			echo '<li>'.html::escapeHTML($k.' = '.$v).'</li>';
		}			
		echo '</ul>';
		echo '</div>';
		echo '</div>';
	}
			
	
	public function getPostsLetter() 
	{
		$meta = $this->meta;
		$newsletter_settings = new newsletterSettings($this->core);

		$params=array();
		$params['no_content'] = true;

		$params['meta_id'] = (integer) $this->letter_id;
		$params['meta_type'] = 'letter_post';
		$params['post_type'] = '';
		
		$rs = $meta->getPostsByMeta($params);
		unset($params);
		
		if($rs->isEmpty())
			return null;		
		
		// paramétrage de la récupération des billets
		$params = array();

		while ($rs->fetch()) {
			$params['post_id'][] = $rs->post_id;
		}

		// sélection du contenu
		//$params['no_content'] = ($newsletter_settings->getViewContentPost() ? false : true); 
		$params['no_content'] = (false);
		// sélection des billets
		$params['post_type'] = 'post';
		// uniquement les billets publiés, sans mot de passe
		$params['post_status'] = 1;
		// sans mot de passe
		$params['sql'] = ' AND P.post_password IS NULL';
			
		// définition du tris des enregistrements et filtrage dans le temps
		$params['order'] = ' P.'.$newsletter_settings->getOrderDate().' DESC';
			
		// récupération des billets
		$rs = $this->blog->getPosts($params, false);

		//throw new Exception('value is ='.$rs->count());
				
		return($rs->isEmpty()?null:$rs);
	}

	/**
	 * link a post to a letter
	 */
	public function linkPost($letter_id,$link_id)
	{
		$this->meta->delPostMeta($link_id,'letter_post',$letter_id);
		$this->meta->setPostMeta($link_id,'letter_post',$letter_id);
	}
	
	/**
	 */
	public function unlinkPost($letter_id,$link_id)
	{
		$this->meta->delPostMeta($link_id,'letter_post',$letter_id);

		$this->core->error->add($link_id.',letter_post,'.$letter_id);
	}



	###############################################
	# ACTIONS
	###############################################

	/**
	 * actions on letter
	 */
	public function letterActions()
	{
		global $core;
		
		$redir_url = 'plugin.php?p=newsletter&m=letter';
		$params = array();
		$entries = array();

		if (!empty($_POST['entries'])) {
			$entries = $_POST['entries'];
		} else if (!empty($_REQUEST['link_id'])) {
			$entries = array('post_id'=>$_REQUEST['link_id']);
		}

		/* Actions
		-------------------------------------------------------- */
		if (!empty($_POST['id']) && !empty($_POST['action']) && !empty($entries))
		{
			$action = $_POST['action'];
			$letter_id = $_POST['id'];
			
			foreach ($entries as $k => $v) {
				$entries[$k] = (integer) $v;
			}
			
			$params['sql'] = 'AND P.post_id IN('.implode(',',$entries).') ';
			$params['no_content'] = true;

			if (isset($_POST['post_type'])) {
				$params['post_type'] = $_POST['post_type'];
			}
			
			$posts = $this->core->blog->getPosts($params);
			
			# --BEHAVIOR-- adminPostsActions
			$this->core->callBehavior('adminLetterActions',$core,$posts,$action,$redir_url);

			if ($action == 'associate') {

				try {
					while ($posts->fetch()) {
						self::linkPost($letter_id,$posts->post_id);
					}
					unset($posts);
					http::redirect($redir_url.'&id='.$letter_id);
				} catch (Exception $e) {
					$this->core->error->add($e->getMessage());
				}
			} else if ($action == 'unlink') {
				
				try {
					while ($posts->fetch()) {
						self::unlinkPost($letter_id,$posts->post_id);
					}
					unset($posts);
					http::redirect($redir_url.'&id='.$letter_id);
				} catch (Exception $e) {
					$this->core->error->add($e->getMessage());
				}
			}
		} else {
			http::redirect('plugin.php?p=newsletter&m=letters');
		}
	}

	###############################################
	# FORMATTING LETTER FOR MAILING
	###############################################

	/**
	 * Define the links content for a subscriber
	 *
	 * @param	string	scontent
	 * @param	string	sub_email
	 * @return String
	 */	
	public static function renderingSubscriber($scontent, $sub_email = '')
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		
		/* replace tags to the current user */
		$patterns[0] = '/USER_DELETE/';
		$patterns[1] = '/USER_SUSPEND/';
		
		if('' == $sub_email) {
			$replacements[0] = '';
			$replacements[1] = '';
		} else {
			$style_link_disable = $newsletter_settings->getStyleLinkDisable();
			$style_link_suspend = $newsletter_settings->getStyleLinkSuspend();
			$replacements[0] = '<a href='.newsletterCore::url('disable/'.newsletterTools::base64_url_encode($sub_email)).'" style="'.$style_link_disable.'">';
			$replacements[0] .= html::escapeHTML($newsletter_settings->getTxtDisable()).'</a>';
			$replacements[1] = '<a href='.newsletterCore::url('suspend/'.newsletterTools::base64_url_encode($sub_email)).'" style="'.$style_link_suspend.'">';
			$replacements[1] .= html::escapeHTML($newsletter_settings->getTxtSuspend()).'</a>';
		}
		
		/* chaine initiale */
		$count = 0;
		$scontent = preg_replace($patterns, $replacements, $scontent, 1, $count);
		return $scontent;		
	}
	
	/**
	 * define the style
	 * @return String
	 */ 
	public static function letter_style() {
		global $core;

		$css_style = '<style type="text/css">';
		$letter_css = new newsletterCSS($core);
		$css_style .= $letter_css->getLetterCSS();
		$css_style .= '</style>';
		
		return $css_style; 
	}

	/**
	 * add the header
	 * @param $title	title of the newsletter
	 * @return String
	 */ 
	public function letter_header($title)
	{
		$res  = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"' . "\r\n"; 
		$res .= '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">' . "\r\n";
		$res .= '<html>' . "\r\n";
		$res .= '<head>' . "\r\n";
		$res .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\r\n";
		$res .= '<meta name="MSSmartTagsPreventParsing" content="TRUE" />' . "\r\n";
		$res .= '<title>'.$title.'</title>' . "\r\n";
		$res .= '</head>' . "\r\n";
		$res .= '<body class="dc-letter">' . "\r\n";
		$res .= $this->letter_style() . "\r\n";		
		return $res;

		/*
		$res .= $this->letter_style() . "\r\n";
		$res .= '</head>' . "\r\n";
		$res .= '<body class="dc-letter">' . "\r\n";
		//*/
	}

	/**
	 * add the footer
	 * @return String
	 */
	public function letter_footer()
	{
		$res  = '</body> ' . "\r\n";
		$res .= '</html> ' . "\r\n";
		return $res;
	}

	/**
	 * copie de la fonction context::ContentFirstImageLookup
	 * @param $root
	 * @param $img
	 * @param $size
	 * @return String of false
	 */
	public static function ContentFirstImageLookup($root,$img,$size)
	{
		# Get base name and extension
		$info = path::info($img);
		$base = $info['base'];
		
		if (preg_match('/^\.(.+)_(sq|t|s|m)$/',$base,$m)) {
			$base = $m[1];
		}
		
		$res = false;
		if ($size != 'o' && file_exists($root.'/'.$info['dirname'].'/.'.$base.'_'.$size.'.jpg'))
		{
			$res = '.'.$base.'_'.$size.'.jpg';
		}
		else
		{
			$f = $root.'/'.$info['dirname'].'/'.$base;
			if (file_exists($f.'.'.$info['extension'])) {
				$res = $base.'.'.$info['extension'];
			} elseif (file_exists($f.'.jpg')) {
				$res = $base.'.jpg';
			} elseif (file_exists($f.'.png')) {
				$res = $base.'.png';
			} elseif (file_exists($f.'.gif')) {
				$res = $base.'.gif';
			}
		}
		
		if ($res) {
			return $res;
		}
		return false;
	}	
	
	/**
	 * Replace keywords
	 * @param String $scontent
	 * @return String
	 */
	public function rendering($scontent = null, $url_visu_online = null) 
	{
		$replacements = array();
		$patterns = array();
		
		$newsletter_settings = new newsletterSettings($this->core);
		
		/*
		$format = '';
		if (!empty($attr['format'])) {
			$format = addslashes($attr['format']);
		}
		//*/
		$format = $newsletter_settings->getDateFormatPostInfo();

		/* Preparation de la liste des billets associes */
		$rs_attach_posts = '';
		$rs_attach_posts = $this->getPostsLetter();
	
		if ('' != $rs_attach_posts)
		{
			$replacements[0]= '';
			while ($rs_attach_posts->fetch())
			{
				$replacements[0] .= '<div class="letter-post">';
				
				$replacements[0] .= '<h2 class="post-title">';
				$replacements[0] .= '<a href="'.$rs_attach_posts->getURL().'">'.$rs_attach_posts->post_title.'</a>';
				$replacements[0] .= '</h2>';

				$replacements[0] .= '<p class="post-info">';
				$replacements[0] .= '('.$rs_attach_posts->getDate($format).'&nbsp;'.__('by').'&nbsp;'.$rs_attach_posts->getAuthorCN().')';
				$replacements[0] .= '</p>';
			
				// Affiche les miniatures
				if ($newsletter_settings->getViewThumbnails()) {
					
					// reprise du code de context::EntryFirstImageHelper et adaptation
					$size=$newsletter_settings->getSizeThumbnails();
					if (!preg_match('/^sq|t|s|m|o$/',$size)) {
						$size = 's';
					}
					$class = !empty($attr['class']) ? $attr['class'] : '';
					
					$p_url = $this->system_settings->public_url;
					$p_site = preg_replace('#^(.+?//.+?)/(.*)$#','$1',$this->core->blog->url);
					$p_root = $this->core->blog->public_path;
				
					$pattern = '(?:'.preg_quote($p_site,'/').')?'.preg_quote($p_url,'/');
					$pattern = sprintf('/<img.+?src="%s(.*?\.(?:jpg|gif|png))"[^>]+/msu',$pattern);
				
					$src = '';
					$alt = '';
				
					# We first look in post content
					$subject = $rs_attach_posts->post_excerpt_xhtml.$rs_attach_posts->post_content_xhtml.$rs_attach_posts->cat_desc;
						
					if (preg_match_all($pattern,$subject,$m) > 0)
					{
						foreach ($m[1] as $i => $img) {
							if (($src = self::ContentFirstImageLookup($p_root,$img,$size)) !== false) {
								//$src = $p_url.(dirname($img) != '/' ? dirname($img) : '').'/'.$src;
								if (dirname($img) != '/' && dirname($img) != '\\') {
									$src = $p_url.dirname($img).'/'.$src;
								} else {
									$src = $p_url.'/'.$src;
								}
								
								if (preg_match('/alt="([^"]+)"/',$m[0][$i],$malt)) {
									$alt = $malt[1];
								}
								break;
							}
						}
					}

					# No src, look in category description if available
					if (!$src && $rs_attach_posts->cat_desc)
					{
						if (preg_match_all($pattern,$rs_attach_posts->cat_desc,$m) > 0)
						{
							foreach ($m[1] as $i => $img) {
								if (($src = self::ContentFirstImageLookup($p_root,$img,$size)) !== false) {
									//$src = $p_url.(dirname($img) != '/' ? dirname($img) : '').'/'.$src;
									if (dirname($img) != '/' && dirname($img) != '\\') {
										$src = $p_url.dirname($img).'/'.$src;
									} else {
										$src = $p_url.'/'.$src;
									}
										
									if (preg_match('/alt="([^"]+)"/',$m[0][$i],$malt)) {
										$alt = $malt[1];
									}
									break;
								}
							}
						};
					}

						
					if ($src) {
						$replacements[0] .= html::absoluteURLs('<img alt="'.$alt.'" src="'.$src.'" class="'.$class.'" />',$rs_attach_posts->getURL()); 
					}				
					// end reprise context::EntryFirstImageHelper
				}						

				// Contenu des billets
				$news_content = '';
				if ($newsletter_settings->getExcerptRestriction()) {
					// Get only Excerpt
					$news_content = $rs_attach_posts->getExcerpt($rs_attach_posts,true);
					$news_content = html::absoluteURLs($news_content,$rs_attach_posts->getURL());
				} else {
					if ($newsletter_settings->getViewContentPost()) {
						$news_content = $rs_attach_posts->getExcerpt($rs_attach_posts,true).' '.$rs_attach_posts->getContent($rs_attach_posts,true);
						$news_content = html::absoluteURLs($news_content,$rs_attach_posts->getURL());
					}
				}
				
				if(!empty($news_content)) {
					//*
					# supprimer le contenu script dans les extraits si plugin GalleryInsert activé
					if($this->core->plugins->moduleExists('GalleryInsert') && !isset($this->core->plugins->getDisabledModules['GalleryInsert'])) {
						$search = array('@<script[^>]*?>.*?</script>@si');
						$news_content = preg_replace($search, '', $news_content);
					}
					//*/
					
					if($newsletter_settings->getViewContentInTextFormat()) {
						$news_content = context::remove_html($news_content);
						$news_content = text::cutString($news_content,$newsletter_settings->getSizeContentPost());
						$news_content = html::escapeHTML($news_content);
						$news_content = $news_content.' ... ';
					} else {
						$news_content = newsletterTools::truncateHtmlString($news_content,$newsletter_settings->getSizeContentPost(),'',false,true);
						$news_content = html::decodeEntities($news_content);
						//$news_content = preg_replace('/<\/p>$/',"...</p>",$news_content);
					}

					// Affichage
					$replacements[0] .= '<p class="post-content">';
					$replacements[0] .= $news_content;
					$replacements[0] .= '</p>';
				}
				
				// Affiche le lien "read more"
				$style_link_read_it = $newsletter_settings->getStyleLinkReadIt();
				$replacements[0] .= '<p class="read-it">';
				$replacements[0] .= '<a href="'.$rs_attach_posts->getURL().'" style="'.$style_link_read_it.'">Read more - Lire la suite</a>';
				$replacements[0] .= '</p>';

				$replacements[0] .= '<br /><br />';
				$replacements[0] .= '</div>';
			}
		} else {
			$replacements[0]= '';
		}
		
		if (isset($url_visu_online)) {
			$text_visu_online = $newsletter_settings->getTxtLinkVisuOnline();
			$style_link_visu_online = $newsletter_settings->getStyleLinkVisuOnline();
			$replacements[1] = '';
			$replacements[1] .= '<p>';
			$replacements[1] .= '<span class="letter-visu"><a href="'.$url_visu_online.'" style="'.$style_link_visu_online.'">'.$text_visu_online.'</a></span>';
			$replacements[1] .= '</p>';
		}
		
		/* Liste des chaines a remplacer */
		$patterns[0] = '/LISTPOSTS/';
		$patterns[1] = '/LINK_VISU_ONLINE/';

		// Lancement du traitement
		$count = 0;
		$scontent = preg_replace($patterns, $replacements, $scontent, -1, $count);

		return $scontent;
	}

	/**
	 * Replace keywords
	 * @param String $scontent
	 * @return String
	 */
	public function rendering_text($scontent = null, $url_visu_online = null) 
	{
		$replacements = array();
		$patterns = array();
		
		$newsletter_settings = new newsletterSettings($this->core);
		
		$format = '';
		if (!empty($attr['format'])) {
			$format = addslashes($attr['format']);
		}

		/* Preparation de la liste des billets associes */
		$rs_attach_posts = '';
		$rs_attach_posts = $this->getPostsLetter();
	
		if ('' != $rs_attach_posts)
		{
			$replacements[0]= '';
			
			while ($rs_attach_posts->fetch())
			{
				$replacements[0] .= $rs_attach_posts->post_title.'<br/>';
				$replacements[0] .= '('.$rs_attach_posts->getDate($format).' '.__('by').' '.$rs_attach_posts->getAuthorCN().')<br/>';
			
				// On n'affiche pas les miniatures en mode texte

				// Contenu des billets
				$news_content = '';
				if ($newsletter_settings->getExcerptRestriction()) {
					// Get only Excerpt
					$news_content = $rs_attach_posts->getExcerpt($rs_attach_posts,true);
					$news_content = html::absoluteURLs($news_content,$rs_attach_posts->getURL());
				} else {
					if ($newsletter_settings->getViewContentPost()) {
						$news_content = $rs_attach_posts->getExcerpt($rs_attach_posts,true).' '.$rs_attach_posts->getContent($rs_attach_posts,true);
						$news_content = html::absoluteURLs($news_content,$rs_attach_posts->getURL());
					}
				}
				
				if(!empty($news_content)) {
					$news_content = context::remove_html($news_content);
					$news_content = text::cutString($news_content,$newsletter_settings->getSizeContentPost());
					$news_content = html::escapeHTML($news_content);
					$news_content = $news_content.' ... ';

					// Affichage
					$replacements[0] .= $news_content;
				}
				
				// Affiche le lien "read more"
				$replacements[0] .= '<br/>Read more - Lire la suite<br/>';
				$replacements[0] .= '('.$rs_attach_posts->getURL().')<br/>';
			}
		} else {
			$replacements[0]= '';
		}

		if (isset($url_visu_online)) {
			$text_visu_online = $newsletter_settings->getTxtLinkVisuOnline();
			$replacements[1] = '';
			$replacements[1] = $text_visu_online;
			$replacements[1] .= '<br/>('.$url_visu_online.')<br/>';
		}
		
		/* Liste des chaines a remplacer */
		$patterns[0] = '/LISTPOSTS/';
		$patterns[1] = '/LINK_VISU_ONLINE/';

		// Lancement du traitement
		$count = 0;
		$scontent = preg_replace($patterns, $replacements, $scontent, -1, $count);
		
		$convertisseur = new html2text();
		$convertisseur->set_html($scontent);
		//$convertisseur->labelLinks = __('Links:');
		$scontent = $convertisseur->get_text();
		
		throw new Exception('content='.$scontent);
	
		return $scontent;
	}	
	
	/**
	 * - define the letter's content
	 * - format the letter
	 * - create the XML tree corresponding to the newsletter
	 * @return xmlTag
	 */
	public function getXmlLetterById()
	{
		$subject='';
		$body='';
		$mode='html';
		
		// recupere le contenu de la letter
		$params = array();
		$params['post_type'] = 'newsletter';
		$params['post_id'] = (integer) $this->letter_id;
	
		$rs = $this->core->blog->getPosts($params);
		
		if ($rs->isEmpty()) {
			throw new Exception('No post for this ID');
		}
		
		// formatte les champs de la letter pour l'envoi
		$subject=text::toUTF8($rs->post_title);
		$header=$this->letter_header($rs->post_title);
		$footer=$this->letter_footer();
		
		// mode html
		$body=$this->rendering(html::absoluteURLs($rs->post_content_xhtml,$rs->getURL()), $rs->getURL());
		$body = text::toUTF8($body);
		$this->letter_body=$body;
		
		// mode texte		
		$body_text=$body;
		$this->letter_body_text = $body_text; 

		// creation de l'arbre xml correspondant
		$rsp = new xmlTag('letter');
		$rsp->letter_id = $rs->post_id;
		$rsp->letter_subject($subject);
		$rsp->letter_header($header);
		$rsp->letter_footer($footer);

		// Version html
		$rsp->letter_body($body);
		
		// Version text
		$rsp->letter_body_text($body_text);
		
		return $rsp;		
	}	

	/**
	 */
	public function getLetterBody($mode = 'html')
	{
		if ($mode == 'text')
			return $this->letter_body_text;
		else
			return $this->letter_body;
	}	
	
	/**
	 * Display tab to select associate posts with letter
	 */
	public function displayTabLetterAssociate() 
	{
		global $core;
		
		$redir_url = 'plugin.php?p=newsletter&m=letter';

		$letter_id = !empty($_GET['post_id']) ? (integer) $_GET['post_id'] : null;
		
		if ($letter_id) {
			$post = $core->blog->getPosts(array('post_id'=>$letter_id,'post_type'=>''));
			if ($post->isEmpty()) {
				$letter_id = null;
			}
			$post_title = $post->post_title;
			$post_type = $post->post_type;
			unset($post);

			echo '<h3>'.__('Associate posts for this letter').'</h3>'; 
			echo '<div class="fieldset">';
			echo '<h4>'.__('Title of letter :').' '.$post_title.'</h4>';
			self::displayPostsList($letter_id);
			echo '</div>';
			echo '<p><a class="back" href="'.html::escapeURL($redir_url).'&amp;id='.$letter_id.'">'.__('back').'</a></p>';	

		} else {
			echo '<h3>'.__('Associate posts for this letter').'</h3>';
			echo 
				'<div class="fieldset">'.
				'no letter active'.
				'</div>';
			echo '<p><a class="back" href="'.html::escapeURL('plugin.php?p=newsletter&m=letters').'">'.__('back').'</a></p>';
		}
	}


	/**
	 * Display list of posts for associate
	 */
	private static function displayPostsList($letter_id = null)
	{
		global $core;

		# Getting categories
		try {
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		
		# Getting authors
		try {
			$users = $core->blog->getPostsUsers();
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		
		# Getting dates
		try {
			$dates = $core->blog->getDates(array('type'=>'month'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		
		# Getting langs
		try {
			$langs = $core->blog->getLangs();
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		
		# Creating filter combo boxes
		if (!$core->error->flag())
		{
			# Filter form we'll put in html_block
			$users_combo = $categories_combo = array();
			$users_combo['-'] = $categories_combo['-'] = '';
			while ($users->fetch())
			{
				$user_cn = dcUtils::getUserCN($users->user_id,$users->user_name,
				$users->user_firstname,$users->user_displayname);
				
				if ($user_cn != $users->user_id) {
					$user_cn .= ' ('.$users->user_id.')';
				}
				
				$users_combo[$user_cn] = $users->user_id; 
			}
			
			while ($categories->fetch()) {
				$categories_combo[str_repeat('&nbsp;&nbsp;',$categories->level-1).'&bull; '.
					html::escapeHTML($categories->cat_title).
					' ('.$categories->nb_post.')'] = $categories->cat_id;
			}
			
			$status_combo = array(
			'-' => ''
			);
			foreach ($core->blog->getAllPostStatus() as $k => $v) {
				$status_combo[$v] = (string) $k;
			}
			
			$selected_combo = array(
			'-' => '',
			__('selected') => '1',
			__('not selected') => '0'
			);
			
			# Months array
			$dt_m_combo['-'] = '';
			while ($dates->fetch()) {
				$dt_m_combo[dt::str('%B %Y',$dates->ts())] = $dates->year().$dates->month();
			}
			
			$lang_combo['-'] = '';
			while ($langs->fetch()) {
				$lang_combo[$langs->post_lang] = $langs->post_lang;
			}
			
			$sortby_combo = array(
			__('Date') => 'post_dt',
			__('Title') => 'post_title',
			__('Category') => 'cat_title',
			__('Author') => 'user_id',
			__('Status') => 'post_status',
			__('Selected') => 'post_selected'
			);
			
			$order_combo = array(
			__('Descending') => 'desc',
			__('Ascending') => 'asc'
			);
		}
		
		# Actions combo box
		$combo_action = array();
		
		if ($core->auth->check('admin',$core->blog->id)) {
			$combo_action[__('associate')] = 'associate';
		}
		
		# --BEHAVIOR-- adminPostsActionsCombo
		$core->callBehavior('adminLetterActionsCombo',array(&$combo_action));
		
		/* Get posts
		-------------------------------------------------------- */
		$user_id = !empty($_GET['user_id']) ?	$_GET['user_id'] : '';
		$cat_id = !empty($_GET['cat_id']) ?	$_GET['cat_id'] : '';
		$status = isset($_GET['status']) ?	$_GET['status'] : '';
		$selected = isset($_GET['selected']) ?	$_GET['selected'] : '';
		$month = !empty($_GET['month']) ?		$_GET['month'] : '';
		$lang = !empty($_GET['lang']) ?		$_GET['lang'] : '';
		$sortby = !empty($_GET['sortby']) ?	$_GET['sortby'] : 'post_dt';
		$order = !empty($_GET['order']) ?		$_GET['order'] : 'desc';
		
		$show_filters = false;
		
		$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
		$nb_per_page =  30;
		
		if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
			if ($nb_per_page != $_GET['nb']) {
				$show_filters = true;
			}
			$nb_per_page = (integer) $_GET['nb'];
		}
		
		$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
		$params['no_content'] = true;
		
		# - User filter
		if ($user_id !== '' && in_array($user_id,$users_combo)) {
			$params['user_id'] = $user_id;
			$show_filters = true;
		}
		
		# - Categories filter
		if ($cat_id !== '' && in_array($cat_id,$categories_combo)) {
			$params['cat_id'] = $cat_id;
			$show_filters = true;
		}
		
		# - Status filter
		if ($status !== '' && in_array($status,$status_combo)) {
			$params['post_status'] = $status;
			$show_filters = true;
		}
		
		# - Selected filter
		if ($selected !== '' && in_array($selected,$selected_combo)) {
			$params['post_selected'] = $selected;
			$show_filters = true;
		}
		
		# - Month filter
		if ($month !== '' && in_array($month,$dt_m_combo)) {
			$params['post_month'] = substr($month,4,2);
			$params['post_year'] = substr($month,0,4);
			$show_filters = true;
		}
		
		# - Lang filter
		if ($lang !== '' && in_array($lang,$lang_combo)) {
			$params['post_lang'] = $lang;
			$show_filters = true;
		}
		
		# - Sortby and order filter
		if ($sortby !== '' && in_array($sortby,$sortby_combo)) {
			if ($order !== '' && in_array($order,$order_combo)) {
				$params['order'] = $sortby.' '.$order;
			}
			
			if ($sortby != 'post_dt' || $order != 'desc') {
				$show_filters = true;
			}
		}
		
		# Get posts
		try {
			$posts = $core->blog->getPosts($params);
			$counter = $core->blog->getPosts($params,true);
			$post_list = new adminPostList($core,$posts,$counter->f(0));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		
		if (!$core->error->flag())
		{
			if (!$show_filters) {
				echo '<p><a id="filter-control" class="form-control" href="#">'.
				__('Filters').'</a></p>';
			}
			
			echo
			'<form action="plugin.php" method="get" id="filters-form">'.
			
			'<fieldset><legend>'.__('Filters').'</legend>'.
			'<div class="three-cols">'.
			'<div class="col">'.
			'<label>'.__('Author:').
			form::combo('user_id',$users_combo,$user_id).'</label> '.
			'<label>'.__('Category:').
			form::combo('cat_id',$categories_combo,$cat_id).'</label> '.
			'<label>'.__('Status:').
			form::combo('status',$status_combo,$status).'</label> '.
			'</div>'.
			
			'<div class="col">'.
			'<label>'.__('Selected:').
			form::combo('selected',$selected_combo,$selected).'</label> '.
			'<label>'.__('Month:').
			form::combo('month',$dt_m_combo,$month).'</label> '.
			'<label>'.__('Lang:').
			form::combo('lang',$lang_combo,$lang).'</label> '.
			'</div>'.
			
			'<div class="col">'.
			'<p><label>'.__('Order by:').
			form::combo('sortby',$sortby_combo,$sortby).'</label> '.
			'<label>'.__('Sort:').
			form::combo('order',$order_combo,$order).'</label></p>'.
			'<p><label class="classic">'.	form::field('nb',3,3,$nb_per_page).' '.
			__('Entries per page').'</label> '.
			'<input type="hidden" name="p" value="'.newsletterPlugin::pname().'" />'.
			'<input type="hidden" name="m" value="letter_associate" />'.
			'<input type="hidden" name="post_id" value='.$letter_id.' />'.
			'<input type="submit" value="'.__('filter').'" /></p>'.
			'</div>'.
			'</div>'.
			'<br class="clear" />'. //Opera sucks
			'</fieldset>'.
			'</form>';
			
			# Show posts
			$post_list->display($page,$nb_per_page,
			'<form action="plugin.php?p=newsletter&amp;m=letter" method="post" id="letter_associate">'.
			
			'%s'.
			
			'<div class="two-cols">'.
			'<p class="col checkboxes-helpers"></p>'.
			
			'<p class="col right">'.__('Selected entries action:').' '.
			form::combo('action',$combo_action).
			'<input type="submit" value="'.__('ok').'" /></p>'.
			
			form::hidden(array('m'),'letter').
			form::hidden(array('p'),newsletterPlugin::pname()).	
			form::hidden(array('id'),$letter_id).
			$core->formNonce().
			'</div>'.
			'</form>'
			);
		}
	}

	public function insertOldLetter($subject,$body)
	{
		global $core;
		
		# Create or update post
		$cur = $core->con->openCursor($core->prefix.'post');
		$cur->post_type = 'newsletter';
		$cur->post_title = $subject;
		$cur->post_content = $body;
		$cur->post_status = 1;
		$cur->user_id = $core->auth->userID();
		
		try
		{
			# --BEHAVIOR-- adminBeforeLetterCreate
			$core->callBehavior('adminBeforeLetterCreate',$cur);
					
			$return_id = $core->blog->addPost($cur);
					
			# --BEHAVIOR-- adminAfterLetterCreate
			$core->callBehavior('adminAfterLetterCreate',$cur,$return_id);
					
			//http::redirect($redir_url.'&id='.$return_id.'&crea=1');
			$this->letter_id = $cur->post_id;
		} catch (Exception $e) {
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}
}