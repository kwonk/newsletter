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

require dirname(__FILE__).'/_widgets.php';

# loading librairies
require_once dirname(__FILE__).'/inc/class.captcha.php';
require_once dirname(__FILE__).'/inc/class.newsletter.settings.php';
require_once dirname(__FILE__).'/inc/class.newsletter.tools.php';
require_once dirname(__FILE__).'/inc/class.newsletter.plugin.php';
require_once dirname(__FILE__).'/inc/class.newsletter.core.php';
require_once dirname(__FILE__).'/inc/class.newsletter.letter.php';
require_once dirname(__FILE__).'/inc/class.newsletter.behaviors.public.php';

# adding templates
$core->tpl->addValue('Newsletter', array('tplNewsletter', 'Newsletter'));
$core->tpl->addValue('NewsletterPageTitle', array('tplNewsletter', 'NewsletterPageTitle'));
$core->tpl->addValue('NewsletterTemplateNotSet', array('tplNewsletter', 'NewsletterTemplateNotSet'));
$core->tpl->addBlock('NewsletterBlock', array('tplNewsletter', 'NewsletterBlock'));
$core->tpl->addBlock('NewsletterMessageBlock', array('tplNewsletter', 'NewsletterMessageBlock'));
$core->tpl->addBlock('NewsletterFormBlock', array('tplNewsletter', 'NewsletterFormBlock'));
$core->tpl->addValue('NewsletterFormSubmit', array('tplNewsletter', 'NewsletterFormSubmit'));
$core->tpl->addValue('NewsletterFormRandom', array('tplNewsletter', 'NewsletterFormRandom'));
$core->tpl->addValue('NewsletterFormCaptchaImg', array('tplNewsletter', 'NewsletterFormCaptchaImg'));
$core->tpl->addValue('NewsletterFormCaptchaInput', array('tplNewsletter', 'NewsletterFormCaptchaInput'));
$core->tpl->addValue('NewsletterFormLabel', array('tplNewsletter', 'NewsletterFormLabel'));
$core->tpl->addValue('NewsletterMsgPresentationForm', array('tplNewsletter', 'NewsletterMsgPresentationForm'));
$core->tpl->addBlock('NewsletterIfUseDefaultFormat',array('tplNewsletter','NewsletterIfUseDefaultFormat'));
$core->tpl->addValue('NewsletterFormFormatSelect', array('tplNewsletter', 'NewsletterFormFormatSelect'));
$core->tpl->addBlock('NewsletterIfUseDefaultAction',array('tplNewsletter','NewsletterIfUseDefaultAction'));
$core->tpl->addValue('NewsletterFormActionSelect', array('tplNewsletter', 'NewsletterFormActionSelect'));
$core->tpl->addBlock('NewsletterIfUseCaptcha',array('tplNewsletter','NewsletterIfUseCaptcha'));
$core->tpl->addBlock('NewsletterEntries',array('tplNewsletter','NewsletterEntries'));
/*
$core->tpl->addBlock('NewsletterEntryNext',array('tplNewsletter','NewsletterEntryNext'));
$core->tpl->addBlock('NewsletterEntryPrevious',array('tplNewsletter','NewsletterEntryPrevious'));
//*/

# adding behaviors
$core->addBehavior('publicBeforeContentFilter', array('dcBehaviorsNewsletterPublic', 'translateKeywords'));
$core->addBehavior('publicHeadContent', array('dcBehaviorsNewsletterPublic', 'publicHeadContent'));
$core->addBehavior('publicAfterUserCreate', array('dcBehaviorsNewsletterPublic', 'newsletterUserCreate'));

/**
 * tplNewsletter
 * define template
 */
class tplNewsletter
{
	/**
	 * Actions on newsletters
	 *
 	 * @return:	string	msg
	 */
	public static function Newsletter()
	{
		global $core;
		
		if (isset($GLOBALS['newsletter']['cmd'])) 
			$cmd = (string) html::clean($GLOBALS['newsletter']['cmd']);
		else 
			$cmd = 'about';
      
		if (isset($GLOBALS['newsletter']['email'])) 
			$email = (string) html::clean($GLOBALS['newsletter']['email']);
		else 
			$email = null;
      
		if (isset($GLOBALS['newsletter']['code'])) 
			$code = (string) html::clean($GLOBALS['newsletter']['code']);
		else 
			$code = null;

		if (isset($GLOBALS['newsletter']['modesend'])) 
			$modesend = (string) html::clean($GLOBALS['newsletter']['modesend']);
		else 
			$modesend = null;

		try {
			switch ($cmd) {
				case 'test':
					$msg = __('Test display template');
					break;
	
				case 'about':
					$msg = '<ul><strong>'.__('About Newsletter').' ...</strong>';
					$msg .= '<li>'.__('Version').' : ' . newsletterPlugin::dcVersion().'</li>';
					$msg .= '<li>'.__('Author').' : ' . newsletterPlugin::dcAuthor().'</li>';
					$msg .= '<li>'.__('Description').' : ' . newsletterPlugin::dcDesc().'</li>';
					$msg .= '</ul>';
					
					$msg = html::escapeHTML($msg);
					break;
	
				case 'confirm':
					if ($email == null || $code == null)
						$msg = __('Missing informations');
					else {
						$rs = newsletterCore::getemail($email);
						if ($rs == null || $rs->regcode != $code) 
							$msg = __('Your subscription code is invalid');
						else if ($rs->state == 'enabled') 
							$msg = __('Account already confirmed');
						else {
							newsletterCore::send($rs->subscriber_id,'enable');
							$msg = __('Your subscription is confirmed').'<br />'.__('You will soon receive an email');
						}
					}
					break;
	
				case 'enable':
					if ($email == null)
						$msg = __('Missing informations');
					else {
						$rs = newsletterCore::getemail($email);
						if ($rs == null) 
							$msg = __('Unable to find your account informations');
						else if ($rs->state == 'enabled') 
							$msg = __('Account already enabled');
						else {
							newsletterCore::send($rs->subscriber_id,'enable');
							$msg = __('Your account is enabled').'<br />'.__('You will soon receive an email');
						}
					}
					break;
	
				case 'disable':
					if ($email == null)
						$msg = __('Missing informations');
					else {
						$rs = newsletterCore::getemail($email);
						if ($rs == null) 
							$msg = __('Unable to find your account informations');
						else if ($rs->state == 'disabled') 
							$msg = __('Account already disabled');
						else {
							newsletterCore::send($rs->subscriber_id,'disable');
							$msg = __('Your account is disabled').'<br />'.__('You will soon receive an email');
						}
					}
					break;
	
				case 'suspend':
					if ($email == null)
						$msg = __('Missing informations');
					else {
						$rs = newsletterCore::getemail($email);
						if ($rs == null) 
							$msg = __('Unable to find you account informations');
						else if ($rs->state == 'suspended') 
							$msg = __('Account already suspended');
						else {
							newsletterCore::send($rs->subscriber_id,'suspend');
							$msg = __('Your account is suspended').'<br />'.__('You will soon receive an email');
						}
					}
					break;
	
				case 'changemode':
					if ($email == null)
						$msg = __('Missing informations');
					else {
						$rs = newsletterCore::getemail($email);
						if ($rs == null) 
							$msg = __('Unable to find you account informations');
						else {
							newsletterCore::send($rs->subscriber_id,'changemode');
							$msg = __('Your sending format is').$modesend.'<br />'.__('You will soon receive an email');
						}
					}
					break;
	
				case 'submit':
					
					if (!isset($_POST['nl_email']) || $_POST['nl_email'] == '')
						throw new Exception (__('No email specified'));
					elseif (!text::isEmail($_POST['nl_email']))
						throw new Exception(__('The given email is invalid'));
					else
						$email = (string)html::clean($_POST['nl_email']);
					
					if (!isset($_POST['nl_option']) || $_POST['nl_option'] == '')
						throw new Exception (__('No option specified'));
					else
						$option = (string)html::clean($_POST['nl_option']);
					
					$modesend = isset($_POST['nl_modesend']) ? (string)html::clean($_POST['nl_modesend']) : null;
					
					$newsletter_settings = new newsletterSettings($core);
					if ($newsletter_settings->getCaptcha()) {
						if (!isset($_POST['nl_captcha']) || $_POST['nl_captcha'] == '')
							throw new Exception (__('No captcha specified'));
						else
							$captcha = (string)html::clean($_POST['nl_captcha']);
					
						if (!isset($_POST['nl_checkid']) || $_POST['nl_checkid'] == '')
							throw new Exception (__('Error in captcha function'));
						else
							$checkid = (string)html::clean($_POST['nl_checkid']);
							
						if ($captcha != Captcha::readCodeFile($checkid)) {
							Captcha::deleteCodeFile($checkid);
							throw new Exception (__('Bad captcha code'));
						} else {
							Captcha::deleteCodeFile($checkid);
						}
					}
					
					switch ($option) {
						case 'subscribe':
							$msg = newsletterCore::accountCreate($email,null,$modesend);
							break;
						case 'unsubscribe':
							$msg = newsletterCore::accountDelete($email);
							break;
						case 'suspend':
							$msg = newsletterCore::accountSuspend($email);
							break;
						case 'resume':
							$msg = newsletterCore::accountResume($email);
							break;
						case 'changemode':
							$msg = newsletterCore::accountChangeMode($email,$modesend);
							break;
						default:
							throw new Exception (__('Error in formular').' option = '.$option);
							break;
					}
					
					break;
	
				default:
					$msg = '';
					break;
			}

		} catch (Exception $e) {
			$msg = $e->getMessage();
			return $msg;
		}		
		return $msg;
	}

	/**
	* title page
	*/
	public static function NewsletterPageTitle()
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		return $newsletter_settings->getFormTitlePage();
	}	

	/**
	* indicate to the user that the page newsletter has not been initialized
	*/
	public static function NewsletterTemplateNotSet()
	{
		return newsletterCore::TemplateNotSet();
	}

	public static function NewsletterBlock($attr, $content)
	{
		return $content;
	}

	public static function NewsletterMessageBlock($attr, $content)
	{
		$text = 
			'<form action="'.newsletterCore::url('form').'" method="post" id="comment-form" class="newsletter">'.
			'<p class="newsletter-form">'.
			html::decodeEntities($content).
			'</p>'.
			'<input type="submit" name="nl_back" id="nl_back" value="'.__('Back').'" class="submit" />'.			
			'</form>';
			
		return (!empty($GLOBALS['newsletter']['msg']) ? $text : '');
	}

	public static function NewsletterFormBlock($attr, $content)
	{
		return (!empty($GLOBALS['newsletter']['form']) ? $content : '');
	}

	public static function NewsletterFormSubmit()
	{
		return newsletterCore::url('submit');
	}

	public static function NewsletterFormRandom()
	{
		return newsletterTools::getRandom();
	}

	public static function NewsletterFormLabel($attr, $content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		
		switch ($attr['id'])
		{
			case 'ok':
				$res = ($newsletter_settings->getUseDefaultAction() ? __('Subscribe') : __('Ok'));
				break;
			case 'subscribe':
				$res = __('Subscribe');
				break;
			case 'unsubscribe':
				$res = __('Unsubscribe');
				break;
			case 'suspend':
				$res = __('Suspend');
				break;
			case 'resume':
				$res = __('Resume');
				break;
			case 'nl_email':
				$res = __('Email');
				break;
			case 'nl_option':
				$res = __('Action');
				break;
			case 'nl_captcha':
				$res = '<label for="nl_captcha">'.__('Captcha').'</label>';
				break;
			case 'nl_submit':
				$res = '';
				break;
			case 'html':
				$res = __('html');
				break;
			case 'text':
				$res = __('text');
				break; 
			case 'nl_modesend':
				$res = __('Format');
				break;
			case 'changemode':
				$res = __('Change format');
				break;
			case 'back':
				$res = __('Back');
				break;
			default:
				$res = '';
				break;
		}
		return $res;
	}

	public static function NewsletterMsgPresentationForm($attr,$content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		$res = '';
		
		if($newsletter_settings->getMsgPresentationForm()) {
			$res = '<p id="newsletter_form-presentation">'.$newsletter_settings->getMsgPresentationForm().'</p>';
		}
		return $res;
	}

	public static function NewsletterIfUseDefaultFormat($attr,$content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		return (!$newsletter_settings->getUseDefaultFormat()? $content : '');
	}

	public static function NewsletterFormFormatSelect($attr,$content)
	{
		$res = 
			'<label for="nl_modesend">'.__('Format').'</label>'.
			'<select name="nl_modesend" id="nl_modesend" size="1">'.
				'<option value="html" selected="selected">'.__('html').'</option>'.
				'<option value="text">'.__('text').'</option>'.
			'</select>';
		return $res;
	}

	public static function NewsletterIfUseDefaultAction($attr,$content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		$res = '';
		
		if($newsletter_settings->getUseDefaultAction()) {
			$res = form::hidden(array('nl_option','nl_option'),'subscribe');
		}

		return ($newsletter_settings->getUseDefaultAction()? $res : $content);
	}	
	
	public static function NewsletterFormActionSelect($attr,$content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		
		$res = 
			'<label for="nl_option">'.__('Actions').'</label>'.
				'<select name="nl_option" id="nl_option" size="1">'.
				'<option value="subscribe" selected="selected">'.__('Subscribe').'</option>';
		
		if(!$newsletter_settings->getUseDefaultFormat()) {
			$res .= '<option value="changemode">'.__('Change format').'</option>';
		}
		
		if($newsletter_settings->getCheckUseSuspend()) {
			$res .= '<option value="suspend">'.__('Suspend').'</option>';
		}
		
		$res .= 
				'<option value="resume">'.__('Resume').'</option>'.
				'<option value="">---</option>'.
				'<option value="unsubscribe">'.__('Unsubscribe').'</option>'.
			'</select>';
		return $res;
	}	

	public static function NewsletterIfUseCaptcha($attr,$content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		return ($newsletter_settings->getCaptcha()? $content : '');
	}	
	
	public static function NewsletterFormCaptchaImg($attr,$content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		$res = '';
	
		if (!empty($GLOBALS['newsletter']['form']) && $newsletter_settings->getCaptcha()) {
			$as = new Captcha(80, 35, 5);
			$as->generate();

			$res =
			'<img id="nl_captcha_img" src="'.Captcha::newsletter_public_url().'/'.$as->getImgFileName().'" alt="'.__('Captcha').'" />'.
			'<img id="nl_reload_captcha" src="?pf=newsletter/reload.png" alt="'.__('Reload captcha').'" title="'.__('Reload captcha').'" style="cursor:pointer;position:relative;top:-7px;" />'.
			form::hidden(array('nl_checkid','nl_checkid'),$as->getCodeFileName()).
			form::hidden(array('nl_captcha_imgname','nl_captcha_imgname'),$as->getImgFileName());
		}		
		return $res;
	}
	
	public static function NewsletterFormCaptchaInput($attr,$content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		$res = '';
		
		if ($newsletter_settings->getCaptcha()) {
			$res = '<input id="nl_captcha" name="nl_captcha" type="text" placeholder="Saisissez le texte " autocomplete="off">';
		}
		return $res;
	}
	
	/* NewslettersEntries -------------------------------------------- */
	/*dtd
	<!ELEMENT tpl:NewslettersEntries - - -- Blog NewslettersEntries loop -->
	<!ATTLIST tpl:NewslettersEntries
	lastn	CDATA	#IMPLIED	-- limit number of results to specified value
	disabled -- author	CDATA	#IMPLIED	-- get entries for a given user id
	disabled -- category	CDATA	#IMPLIED	-- get entries for specific categories only (multiple comma-separated categories can be specified. Use "!" as prefix to exclude a category)
	disabled -- no_category	CDATA	#IMPLIED	-- get entries without category
	no_context (1|0)	#IMPLIED  -- Override context information
	sortby	(title|selected|author|date|id)	#IMPLIED	-- specify entries sort criteria (default : date) (multiple comma-separated sortby can be specified. Use "?asc" or "?desc" as suffix to provide an order for each sorby)
	order	(desc|asc)	#IMPLIED	-- specify entries order (default : desc)
	disabled -- no_content	(0|1)	#IMPLIED	-- do not retrieve entries content
	selected	(0|1)	#IMPLIED	-- retrieve posts marked as selected only (value: 1) or not selected only (value: 0)
	url		CDATA	#IMPLIED	-- retrieve post by its url
	disabled -- type		CDATA	#IMPLIED	-- retrieve post with given post_type (there can be many ones separated by comma)
	disabled -- age		CDATA	#IMPLIED	-- retrieve posts by maximum age (ex: -2 days, last month, last week)
	ignore_pagination	(0|1)	#IMPLIED	-- ignore page number provided in URL (useful when using multiple tpl:Entries on the same page)
	>
	*/	
	public static function NewsletterEntries($attr,$content)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		
		$lastn = 0;
		if (isset($attr['lastn'])) {
			$lastn = abs((integer) $attr['lastn'])+0;
		}
		
		$p = 'if (!isset($_page_number)) { $_page_number = 1; }'."\n";

		if ($lastn > 0) {
			$p .= "\$params['limit'] = ".$lastn.";\n";
		} else {
			$p .= "\$params['limit'] = ".$newsletter_settings->getNbNewslettersPerPublicPage().";\n";
		}
		
		if (!isset($attr['ignore_pagination']) || $attr['ignore_pagination'] == "0") {
			$p .= "\$params['limit'] = array(((\$_page_number-1)*\$params['limit']),\$params['limit']);\n";
		} else {
			$p .= "\$params['limit'] = array(0, \$params['limit']);\n";
		}

		$p .= "\$params['post_type'] = 'newsletter';\n";
		$p .= "\$params['post_selected'] = false;\n";

		if (isset($attr['sortby'])) {
			switch ($attr['sortby']) {
				case 'title': $sortby = 'post_title'; break;
				case 'selected' : $sortby = 'post_selected'; break;
				case 'author' : $sortby = 'user_id'; break;
				case 'date' : $sortby = 'post_dt'; break;
			}
		} else {
			$sortby = $newsletter_settings->getNewslettersPublicPageSort();
		}
		
		if (isset($attr['order']) && preg_match('/^(desc|asc)$/i',$attr['order'])) {
			$order = $attr['order'];
		} else {
			$order = $newsletter_settings->getNewslettersPublicPageOrder();
		}

		$p .= "\$params['order'] = '".$sortby." ".$order."';\n";

		if (!empty($attr['url'])) {
			$p .= "\$params['post_url'] = '".addslashes($attr['url'])."';\n";
		}
		
		if (isset($attr['no_content']) && $attr['no_content']) {
			$p .= "\$params['no_content'] = true;\n";
		}
		/*
		if (isset($attr['selected'])) {
			$p .= "\$params['post_selected'] = ".(integer) (boolean) $attr['selected'].";";
		}
		*/

		if (empty($attr['no_context']))
		{
			$p .=
			'if ($_ctx->exists("categories")) { '.
				"\$params['cat_id'] = \$_ctx->categories->cat_id; ".
			"}\n";
			
			$p .=
			'if ($_ctx->exists("langs")) { '.
				"\$params['sql'] = \"AND P.post_lang = '\".\$core->blog->con->escape(\$_ctx->langs->post_lang).\"' \"; ".
			"}\n";
		}

		$res = "<?php\n";
		$res .= $p;
		$res .= '$_ctx->post_params = $params;'."\n";
		$res .= '$_ctx->posts = $core->blog->dcNewsletter->getNewsletters($params); unset($params);'."\n";
		$res .= "?>\n";
		
		$res .=
		'<?php while ($_ctx->posts->fetch()) : ?>'.$content.'<?php endwhile; '.
		'$_ctx->posts = null; $_ctx->post_params = null; ?>';
		
		return $res;
	}
	
}

# Newsletter URL handler
class urlNewsletter extends dcUrlHandlers
{
    public static function newsletter($args)
    {
		$core = $GLOBALS['core'];
		$_ctx = $GLOBALS['_ctx'];

		if($args == '') {
			# The specified Preview URL is malformed.
	      		self::p404();
	    }

		# initialisation des variables
		$flag = 0;
		$cmd = null;
		$GLOBALS['newsletter']['cmd'] = null;
		$GLOBALS['newsletter']['msg'] = false;
		$GLOBALS['newsletter']['form'] = false;
		$GLOBALS['newsletter']['email'] = null;
		$GLOBALS['newsletter']['code'] = null;
		$GLOBALS['newsletter']['modesend'] = null;

		# décomposition des arguments et aiguillage
		$params = explode('/', $args);
		if (isset($params[0]) && !empty($params[0])) 
			$cmd = (string)html::clean($params[0]);
		else 
			$cmd = null;
					      
		if (isset($params[1]) && !empty($params[1])) {
			$email = newsletterTools::base64_url_decode((string)html::clean($params[1]));
		} else
	    	$email = null;
	      
		if (isset($params[2]) && !empty($params[2])) 
			$regcode = (string)html::clean($params[2]);
		else 
			$regcode = null;			

		if (isset($params[3]) && !empty($params[3])) 
			$modesend = newsletterTools::base64_url_decode((string)html::clean($params[3]));
		else 
			$modesend = null;
		
		switch ($cmd) {
			case 'test':
			case 'about':
				$GLOBALS['newsletter']['msg'] = true;
			break;

			case 'form':
				$GLOBALS['newsletter']['form'] = true;
			break;
                
			case 'submit':
				$GLOBALS['newsletter']['msg'] = true;
			break;
					
			case 'confirm':
			case 'enable':
			case 'disable':
			case 'suspend':
			case 'changemode':
			case 'resume':
			{
				if ($email == null) {
					self::p404();
				}
				$GLOBALS['newsletter']['msg'] = true;
				break;
			}
				
			default:
			{
				$flag = 1;
				self::letter($args);
				break;
			}
		}

		if (!$flag) {

			$GLOBALS['newsletter']['cmd'] = $cmd;
			$GLOBALS['newsletter']['email'] = $email;
			$GLOBALS['newsletter']['code'] = $regcode;
			$GLOBALS['newsletter']['modesend'] = $modesend;
	
			# Affichage du formulaire
			$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
			$file = $core->tpl->getFilePath('subscribe.newsletter.html');
			files::touch($file);
			self::serveDocument('subscribe.newsletter.html','text/html',false,false);
		}
    }

    public static function letterpreview($args)
    {
		$core = $GLOBALS['core'];
		$_ctx = $GLOBALS['_ctx'];
		
		if (!preg_match('#^(.+?)/([0-9a-z]{40})/(.+?)$#',$args,$m)) {
			# The specified Preview URL is malformed.
			self::p404();
		}
		else
		{
			$user_id = $m[1];
			$user_key = $m[2];
			$post_url = $m[3];
			if (!$core->auth->checkUser($user_id,null,$user_key)) {
				# The user has no access to the entry.
				self::p404();
			}
			else
			{
				$_ctx->preview = true;
				self::letter($post_url);
			}
		}
    }
    
	public static function letter($args)
	{
		if ($args == '') {
			# No page was specified.
			self::p404();
		}
		else
		{
			$_ctx =& $GLOBALS['_ctx'];
			$core =& $GLOBALS['core'];
			
			$core->blog->withoutPassword(false);
			
			$params = new ArrayObject();
			$params['post_type'] = 'newsletter';
			$params['post_url'] = $args;
			
			$_ctx->posts = $core->blog->getPosts($params);
			
			$_ctx->comment_preview = new ArrayObject();
			$_ctx->comment_preview['content'] = '';
			$_ctx->comment_preview['rawcontent'] = '';
			$_ctx->comment_preview['name'] = '';
			$_ctx->comment_preview['mail'] = '';
			$_ctx->comment_preview['site'] = '';
			$_ctx->comment_preview['preview'] = false;
			$_ctx->comment_preview['remember'] = false;
			
			$core->blog->withoutPassword(true);
			
			if ($_ctx->posts->isEmpty())
			{
				# The specified page does not exist.
				self::p404();
			}
			else
			{
				$post_id = $_ctx->posts->post_id;
				$post_password = $_ctx->posts->post_password;
				
				# Password protected entry
				if ($post_password != '' && !$_ctx->preview)
				{
					# Get passwords cookie
					if (isset($_COOKIE['dc_passwd'])) {
						$pwd_cookie = unserialize($_COOKIE['dc_passwd']);
					} else {
						$pwd_cookie = array();
					}
					
					# Check for match
					if ((!empty($_POST['password']) && $_POST['password'] == $post_password)
					|| (isset($pwd_cookie[$post_id]) && $pwd_cookie[$post_id] == $post_password))
					{
						$pwd_cookie[$post_id] = $post_password;
						setcookie('dc_passwd',serialize($pwd_cookie),0,'/');
					}
					else
					{
						self::serveDocument('password-form.html','text/html',false);
						return;
					}
				}
				
				# The entry
				$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
				self::serveDocument('letter.html');
			}
		}
	}
	
    public static function newsletters($args)
    {
    	$_ctx =& $GLOBALS['_ctx'];
    	$core =& $GLOBALS['core'];

		$n = self::getPageNumber($args);
		if ($n) {
			$GLOBALS['_page_number'] = $n;
			$GLOBALS['core']->url->type = $n > 1 ? 'newsletters-page' : 'newsletters';
		}
    	$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
    	self::serveDocument('newsletters.html');    	
    }
    
    public static function newsletterRestService($args)
    {
    	global $core;
    	$core->rest->addFunction('newsletterSubmitFormSubscribe',array('dcNewsletterPubRest','submitFormSubscribe'));
    	$core->rest->addFunction('newsletterReloadCaptcha',array('dcNewsletterPubRest','reloadCaptcha'));
    	$core->rest->addFunction('newsletterDeleteImgCaptcha',array('dcNewsletterPubRest','deleteImgCaptcha'));
    	$core->rest->serve();
    	exit;
    }    
}

class publicWidgetsNewsletter
{
	/**
	 * initialize widget
	 * @param $w
	 * @return String
	 */
	public static function initWidgets($w)
	{
		global $core;
		$blog_settings =& $core->blog->settings->newsletter;
		$system_settings =& $core->blog->settings->system;

		try {
			# get state of plugin
			$newsletter_flag = (boolean)$blog_settings->newsletter_flag;
			if (!$newsletter_flag)
				return;

			if (($w->homeonly == 1 && $core->url->type != 'default') ||
				($w->homeonly == 2 && $core->url->type == 'default')) {
				return;
			}

			$title = ($w->title) ? html::escapeHTML($w->title) : 'Newsletter';
			
			$res  = '<div class="newsletter-widget">';
			$res .= ($w->showtitle) ? '<h2>'.$title.'</h2>' : '';			

			# mise en place du contenu du widget
			if ($w->inwidget) {
				$newsletter_settings = new newsletterSettings($core);
				$link = newsletterCore::url('submit');
				
				$res .= 
				'<form id="nl_form" action="'.$link.'" method="post">';
				
				# texte de presentation
				if($newsletter_settings->getMsgPresentationForm()) {
					$res .= '<p id="newsletter-widget-presentation">'.$newsletter_settings->getMsgPresentationForm().'</p>';
				}
				
				# saisie de l'adresse email
				$res .= 
				'<p>'.
				'<label for="nl_email">'.__('Email').'</label>'.
				'<input id="nl_email" type="email" name="nl_email">'.
				'</p>';
				

				# selection du mode d'envoi
				if(!$newsletter_settings->getUseDefaultFormat()) {
					$res .= 
						'<p><label for="nl_modesend">'.__('Format').'</label>'.
							'<select name="nl_modesend" id="nl_modesend" size="1">'.
								'<option value="html" selected="selected">'.__('html').'</option>'.
								'<option value="text">'.__('text').'</option>'.
							'</select>'.
						'</p>';
				}
				
				# selection du type de message
				if($newsletter_settings->getUseDefaultAction()) {
					$res .= form::hidden(array('nl_option','nl_option'),'subscribe');
				} else {
					$res .= 
						'<p>'.
							'<label for="nl_option">'.__('Actions').'</label>'.
							'<select name="nl_option" id="nl_option" size="1">'.
								'<option value="subscribe" selected="selected">'.__('Subscribe').'</option>';
					
					if(!$newsletter_settings->getUseDefaultFormat()) {
						$res .= '<option value="changemode">'.__('Change format').'</option>';
					}
					if($newsletter_settings->getCheckUseSuspend()) {
						$res .= '<option value="suspend">'.__('Suspend').'</option>';
					}
	
						$res .=
								'<option value="resume">'.__('Resume').'</option>'.
								'<option value="">---</option>'.
								'<option value="unsubscribe">'.__('Unsubscribe').'</option>'.
							'</select>'.
						'</p>';
				}
				
				# utilisation du captcha
				if ($newsletter_settings->getCaptcha()) {
					require_once dirname(__FILE__).'/inc/class.captcha.php';
					$as = new Captcha(80, 35, 5);
					$as->generate();

					$res .=
					'<p>'.
						'<img id="nl_captcha_img" src="'.Captcha::newsletter_public_url().'/'.$as->getImgFileName().'" alt="'.__('Captcha').'" />'.
						'<img id="nl_reload_captcha" src="?pf=newsletter/reload.png" alt="'.__('Reload captcha').'" title="'.__('Reload captcha').'" style="cursor:pointer;position:relative;top:-7px;" />'.
						'<input id="nl_captcha" name="nl_captcha" type="text" placeholder="Saisissez le texte " autocomplete="off">'.
					'</p>';

					$res .=
					form::hidden(array('nl_checkid','nl_checkid'),$as->getCodeFileName()).
					form::hidden(array('nl_captcha_imgname','nl_captcha_imgname'),$as->getImgFileName());					
				}
					
				$res .=
				'<p><input class="submit" type="submit" name="nl_submit" id="nl_submit" value="'.
					($newsletter_settings->getUseDefaultAction() ? __('Subscribe') : __('Ok')).'" /></p>';
				
				$res .=	form::hidden(array('nl_random'),newsletterTools::getRandom()).
				$core->formNonce().
				'</form>';
			} else {
				# in sublink
				$link = newsletterCore::url('form');
				$subscription_link = ($w->subscription_link) ? html::escapeHTML($w->subscription_link) : __('Subscription link');
				$res .= '<ul><li><a href="'.$link.'">'.$subscription_link.'</a></li></ul>';
			}
			# affichage dynamique
			$res .= '<div id="newsletter-pub-message"></div>';
				
			$res .= '</div>';
			return $res;

		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}

	# List Newsletters Widget function
	public static function listnsltrWidget($w)
	{
		global $core,$_ctx;
	
		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}		
		
		$orderby = $w->orderby;
		$orderdir = $w->orderdir;
		$params = '';
		$order = '';
		$order .= ($orderby == 'date') ? 'P.post_dt' : 'P.post_title'; 
		$order .= ' ';
		$order .= ($orderdir == 'asc') ? 'asc' : 'desc';
				
		if (empty($core->blog->dcNewsletter)) { 
			$core->blog->dcNewsletter = new dcNewsletter($core);
		}

		if (((integer)$w->limit) != 0) {
			$params = array(
						'order' => $order, 
						'limit' => array(0,(integer)$w->limit), 
						'no_content' => true,
						'post_selected' => false
					);
		} else {
			$params = array(
						'order' => $order, 
						'no_content' => true,
						'post_selected' => false
					);
		}
		$rsnsltr = $core->blog->dcNewsletter->getNewsletters($params);

		$title = $w->title ? html::escapeHTML($w->title) : 'Newsletters';
		$res =
			'<div class="listnsltr"><h2>'.$title.'</h2>'.
			'<ul>';
		while ($rsnsltr->fetch()) {
			$nsltrLink = '<a href="'.$rsnsltr->getURL().'">'.html::escapeHTML($rsnsltr->post_title).'</a>';
			$res .= '<li class="linsltr">'.$nsltrLink.'</li>';
		}
		$res .= '</ul>';

		$res .= '<p class="allnsltr"><a href="'.$core->blog->url.$core->url->getBase("newsletters").'">'.
			__('All newsletters').'</a></p>';
				
		$res .= '</div>';

		return $res;
	}
}

class dcNewsletterPubRest 
{
	public static function deleteImgCaptcha($core,$get,$post)
	{
		require_once dirname(__FILE__).'/inc/class.captcha.php';
		$as = new Captcha(80, 35, 5);
		
		if (!isset($post['captcha_imgname']) || $post['captcha_imgname'] == '')
			throw new Exception (__('Cannot load imgname'));
		else {
			$captcha_imgname = basename($post['captcha_imgname']);
			$as->deleteImgCaptcha($captcha_imgname);
		}
		
		# reponse
		$rsp = new xmlTag();
		$captchaTag = new xmlTag('item');
		$captchaTag->result = $captcha_imgname;
		$rsp->insertNode($captchaTag);
					
		return $rsp;
	}
	
	public static function reloadCaptcha($core)
	{
		require_once dirname(__FILE__).'/inc/class.captcha.php';
		$as = new Captcha(80, 35, 5);
		$as->generate();
		
		# reponse
		$rsp = new xmlTag();
		$captchaTag = new xmlTag('item');
		$captchaTag->src = Captcha::newsletter_public_url().'/'.$as->getImgFileName();
		$captchaTag->checkid = $as->getCodeFileName();
		$rsp->insertNode($captchaTag);
			
		return $rsp;
	}
	
	public static function submitFormSubscribe($core,$get,$post)
	{
		if (!isset($post['email']) || $post['email'] == '')
			throw new Exception (__('No email specified'));
		elseif (!text::isEmail($post['email']))
			throw new Exception(__('The given email is invalid'));
		else
			$email = $post['email'];
	
		if (!isset($post['option']) || $post['option'] == '')
			throw new Exception (__('No option specified'));
		else
			$option = $post['option'];
		
		$modesend = isset($post['nl_modesend']) ? $post['nl_modesend'] : null;
		
		$newsletter_settings = new newsletterSettings($core);
		if ($newsletter_settings->getCaptcha()) {
			if (!isset($post['captcha']) || $post['captcha'] == '')
				throw new Exception (__('No captcha specified'));
			else 
				$captcha = $post['captcha'];

			if (!isset($post['checkid']) || $post['checkid'] == '')
				throw new Exception (__('Error in captcha function'));
			else
				$checkid = $post['checkid'];
			
			if ($captcha != Captcha::readCodeFile($checkid)) {
				Captcha::deleteCodeFile($checkid);
				throw new Exception (__('Bad captcha code'));
			} else {
				Captcha::deleteCodeFile($checkid);
			}
		}
		
		switch ($option) {
			case 'subscribe':
				$msg = newsletterCore::accountCreate($email,null,$modesend);
				break;
			case 'unsubscribe':
				$msg = newsletterCore::accountDelete($email);
				break;
			case 'suspend':
				$msg = newsletterCore::accountSuspend($email);
				break;
			case 'resume':
				$msg = newsletterCore::accountResume($email);
				break;
			case 'changemode':
				$msg = newsletterCore::accountChangeMode($email,$modesend);
				break;
			default:
				throw new Exception (__('Error in formular').' option = '.$option);
				break;
		}
			
		# version xml
		$rsp = new xmlTag();
		$subscriberTag = new xmlTag('item');
		$subscriberTag->email = $email;
		$subscriberTag->option = $option;
		$subscriberTag->result = $msg;
		$rsp->insertNode($subscriberTag);
			
		return $rsp;
	}		
}

?>