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

class newsletterSettings
{
	protected $core;
	protected $blog;
	protected $blogname;
	protected $parameters;
	protected $blog_settings;
	protected $system_settings;
	
	/**
	 * Class constructor
	 *
	 * @param:	$core	dcCore
	 */
	public function __construct($core)
	{
		$this->core = &$core;
		$this->blog = &$core->blog;
		$this->blogname = $this->blog->name;
		$this->blog_settings =& $core->blog->settings->newsletter;
		$this->system_settings = $core->blog->settings->system;

		if($this->blog_settings->newsletter_parameters != '') {
			$this->parameters = unserialize($this->blog_settings->newsletter_parameters); 
		} 
		if(!is_array($this->parameters)) {
			$this->parameters = array();
		}
	}

	/**
	 * Retrieves all parameters
	 *
	 * @return:	array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Retrieves one parameter
	 *
	 * @return:	$parameter
	 */
	public function getParameter($parameter)
	{
		return array_key_exists($parameter,$this->parameters) ? $this->parameters[$parameter] : false;
	}

	/**
	 * Add one parameter
	 *
	 * @return:	$parameter
	 */
	public function setParameter($parameter,$value)
	{
		$this->parameters[$parameter] = $value;
	}

	/**
	 * Saves parameters array on blog settings
	 */
	public function save()
	{
		$this->blog_settings->put('newsletter_parameters',serialize($this->parameters),'string','Newsletter settings');
		$this->blog->triggerBlog();
	}

	/**
	* renvoi le nom de l'editeur du blog
	*/
	public function getEditorName() 
	{ 
		return (string)$this->getParameter('editorName'); 
	}

	/**
	* renseigne le nom de l'editeur
	*/
	public function setEditorName($value) 
	{ 
		$this->setParameter('editorName',(string)$value);
	}

	/**
	* efface/initialise le nom de l'editeur
	*/
	public function clearEditorName() 
	{ 
		$this->setEditorName(''); 
	}

	/**
	* renvoi l'email de l'editeur du blog
	*/
	public function getEditorEmail() 
	{ 
		return (string)$this->getParameter('editorEmail');
	}
	
	/**
	* renseigne l'email de l'editeur
	*/
	public function setEditorEmail($value) 
	{
		$this->setParameter('editorEmail',(string)$value);
	}
	
	/**
	* efface/initialise l'email de l'editeur
	*/
	public function clearEditorEmail() 
	{ 
		$this->setEditorEmail('');
	}

	/**
	* renvoi le mode d'envoi de la newsletter
	*/
	public function getSendMode() 
	{ 
		return (string)$this->getParameter('mode');
	}
	
	/**
	* renseigne le mode d'envoi de la newsletter
	*/
	public function setSendMode($value) 
	{ 
		$this->setParameter('mode',(string)$value);
	}
	
	/**
	* efface/initialise le mode d'envoi de la newsletter
	*/
	public function clearSendMode() 
	{ 
		$this->setSendMode('html');
	}

	/**
	* utilisation du format d'envoi par utilisateur ou defaut
	*/
	public function getUseDefaultFormat() 
	{ 
		return (boolean)$this->getParameter('use_default_format'); 
	}
	
	public function setUseDefaultFormat($value) 
	{
		$this->setParameter('use_default_format',(boolean)$value);
	}
	
	public function clearUseDefaultFormat() 
	{ 
		$this->setUseDefaultFormat(false); 
	}	
	
	/**
	 * utilisation de l'action par defaut
	 */
	public function getUseDefaultAction()
	{
		return (boolean)$this->getParameter('use_default_action');
	}
	
	public function setUseDefaultAction($value)
	{
		$this->setParameter('use_default_action',(boolean)$value);
	}
	
	public function clearUseDefaultAction()
	{
		$this->setUseDefaultAction(false);
	}
		
	/**
	* nombre maximal de billet retournes
	*/
	public function getMaxPosts() 
	{ 
		return (integer)$this->getParameter('maxposts'); 
	}
	
	/**
	* renseigne le nombre maximal de billet retournes
	*/
	public function setMaxPosts($value) 
	{ 
		$minPosts=(integer)$this->getParameter('minposts');
		if( $minPosts < (integer)$value) {
			$this->setParameter('maxposts',(integer)$value);
		} else {
			$this->setParameter('maxposts',(integer)$minPosts);
		}
	}
	
	/**
	* efface/initialise le nombre maximal de billet retournes
	*/
	public function clearMaxPosts() 
	{ 
		$this->setMaxPosts(1); 
	}
	
	/**
	* nombre minimum de billet retournes
	*/
	public function getMinPosts() 
	{ 
		return (integer)$this->getParameter('minposts'); 
	}
	
	/**
	* renseigne le nombre minimal de billet retournes
	*/
	public function setMinPosts($value) 
	{ 
		$this->setParameter('minposts',(integer)$value);
	}
	
	/**
	* efface/initialise le nombre maximal de billet retournes
	*/
	public function clearMinPosts() 
	{ 
		$this->setMinPosts(1); 
	}	

	/**
	* envoi automatique
	*/
	public function getAutosend() 
	{ 
		return (boolean)$this->getParameter('autosend'); 
	}
	
	/**
	* indique si on doit envoyer automatiquement
	*/
	public function setAutosend($value) 
	{ 
		$this->setParameter('autosend',(boolean)$value);
	}
	
	/**
	* reinitialise l'indicateur d'envoi automatique
	*/
	public function clearAutosend() 
	{ 
		$this->setAutosend(false); 
	}

	/**
	* utilisation d'un captcha
	*/
	public function getCaptcha() 
	{ 
		return (boolean)$this->getParameter('captcha'); 
	}
	
	/**
	* indique si on doit utiliser un captcha
	*/
	public function setCaptcha($value) 
	{
		$this->setParameter('captcha',(boolean)$value);
	}
	
	/**
	* reinitialise l'indicateur d'utilisation de captcha
	*/
	public function clearCaptcha() 
	{ 
		$this->setCaptcha(false); 
	}

	/**
	* retourne le flag Restreindre l'apercu uniquement a l'extrait des billets
	*/
	public function getExcerptRestriction() 
	{ 
		return (boolean)$this->getParameter('excerpt_restriction');
	}
	
	/**
	* positionne le flag Restreindre l'apercu uniquement a l'extrait des billets
	*/
	public function setExcerptRestriction($value) 
	{
		$this->setParameter('excerpt_restriction',(boolean)$value);
	}
	
	/**
	* initialise le flag Restreindre l'apercu uniquement a l'extrait des billets
	*/
	public function clearExcerptRestriction() 
	{ 
		$this->setExcerptRestriction(false);
	}
		
	/**
	* Affichage du contenu du post dans la newsletter
	*/
	public function getViewContentPost() 
	{ 
		return (boolean)$this->getParameter('view_content_post');
	}
	
	/**
	* indique si on doit afficher le contenu du post
	*/
	public function setViewContentPost($value) 
	{
		$this->setParameter('view_content_post',(boolean)$value);
	}
	
	/**
	* reinitialise l'indicateur d'affichage du contenu du post
	*/
	public function clearViewContentPost() 
	{ 
		$this->setViewContentPost(false);
	}

	/**
	* retourne la taille maximale du contenu du billet
	*/
	public function getSizeContentPost() 
	{ 
		return (integer)$this->getParameter('size_content_post'); 
	}
	
	/**
	* renseigne la taille maximale du contenu du billet
	*/
	public function setSizeContentPost($value) 
	{
		$this->setParameter('size_content_post',(integer)$value);
	}
	
    /**
	* efface/initialise la taille maximale du contenu du billet
	*/
	public function clearSizeContentPost() 
	{ 
		$this->setSizeContentPost(100); 
	}

	/**
	 * retourne le nombre d'abonnés par page par défaut
	 */
	public function getNbSubscribersPerpage()
	{
		return (integer)$this->getParameter('nb_subscribers_per_page');
	}
	
	public function setNbSubscribersPerpage($value)
	{
		$this->setParameter('nb_subscribers_per_page',(integer)$value);
	}
	
	public function clearNbSubscribersPerpage()
	{
		$this->setNbSubscribersPerpage(30);
	}
	
	/**
	* retourne le message d'introduction de la newsletter
	*/
	public function getIntroductoryMsg() 
	{ 
		return (string)$this->getParameter('introductory_msg'); 
	}
	
	/**
	* renseigne le message d'introduction de la newsletter
	*/
	public function setIntroductoryMsg($value) 
	{
		$this->setParameter('introductory_msg',(string)$value);
	}
	
	/**
	* efface/initialise le message d'introduction de la newsletter
	*/
	public function clearIntroductoryMsg() 
	{ 
		$this->setIntroductoryMsg(''); 
	}

	/**
	* retourne le message de conclusion
	*/
	public function getConcludingMsg()
	{ 
		return (string)$this->getParameter('concluding_msg');
	}
	
	/**
	* renseigne le message de conclusion
	*/
	public function setConcludingMsg($value) 
	{ 
		$this->setParameter('concluding_msg',(string)$value);
	}
	
	/**
	* efface/initialise le message de conclusion
	*/
	public function clearConcludingMsg()
	{ 
		$this->setConcludingMsg(__('Thanks you for reading.')); 
	}

	/**
	* retourne le message de presentation du formulaire d'inscription
	*/
	public function getMsgPresentationForm() 
	{ 
		return (string)$this->getParameter('msg_presentation_form'); 
	}
	
	/**
	* renseigne le message de presentation du formulaire d'inscription
	*/
	public function setMsgPresentationForm($value) 
	{ 
		$this->setParameter('msg_presentation_form',(string)$value);
	}
	
	/**
	* efface/initialise le message de presentation du formulaire d'inscription
	*/
	public function clearMsgPresentationForm() 
	{ 
		$this->setMsgPresentationForm(''); 
	}

	/**
	* retourne le message de presentation
	*/
	public function getPresentationMsg()
	{ 
		return (string)$this->getParameter('presentation_msg');
	}
	
	/**
	* renseigne le message de presentation
	*/
	public function setPresentationMsg($value) 
	{ 
		$this->setParameter('presentation_msg',(string)$value);
	}
	
	/**
	* efface/initialise le message de presentation
	*/
	public function clearPresentationMsg() 
	{ 
		$this->setPresentationMsg(__('This is the newsletter for')); 
	}

	/**
	* retourne le message de presentation des billets
	*/
	public function getPresentationPostsMsg()
	{ 
		return (string)$this->getParameter('presentation_posts_msg');
	}
	
	/**
	* renseigne le message de presentation des billets
	*/
	public function setPresentationPostsMsg($value) 
	{ 
		$this->setParameter('presentation_posts_msg',(string)$value);
	}
	
	/**
	* efface/initialise le message de presentation des billets
	*/
	public function clearPresentationPostsMsg() 
	{ 
		$this->setPresentationPostsMsg(__('Here are the last post:')); 
	}

	/**
	* retourne le message d'introduction pour la confirmation
	*/
	public function getTxtIntroConfirm()
	{ 
		return (string)$this->getParameter('txt_intro_confirm');
	}

	public function setTxtIntroConfirm($value) 
	{ 
		$this->setParameter('txt_intro_confirm',(string)$value);
	}

	public function clearTxtIntroConfirm() 
	{ 
		$this->setTxtIntroConfirm(__('To confirm your subscription')); 
	}	
	
	/**
	* retourne le titre du lien de confirmation
	*/
	public function getTxtConfirm()
	{ 
		return (string)$this->getParameter('txtConfirm');
	}
	
	public function setTxtConfirm($value) 
	{ 
		$this->setParameter('txtConfirm',(string)$value);
	}
	
	public function clearTxtConfirm() 
	{ 
		$this->setTxtConfirm(__('Click here')); 
	}	
	
	/**
	* retourne le message d'introduction pour la suppression
	*/
	public function getTxtIntroDisable()
	{ 
		return (string)$this->getParameter('txt_intro_disable');
	}
	
	public function setTxtIntroDisable($value) 
	{ 
		$this->setParameter('txt_intro_disable',(string)$value);
	}
	
	public function clearTxtIntroDisable() 
	{ 
		$this->setTxtIntroDisable(__('To cancel your account')); 
	}	
	
	/**
	* retourne le titre du lien de suppression
	*/
	public function getTxtDisable()
	{ 
		return (string)$this->getParameter('txtDisable');
	}
	
	public function setTxtDisable($value) 
	{ 
		$this->setParameter('txtDisable',(string)$value);
	}
	
	public function clearTxtDisable() 
	{ 
		$this->setTxtDisable(__('Click here')); 
	}	
	
	/**
	* retourne le message d'introduction pour l'activation
	*/
	public function getTxtIntroEnable()
	{ 
		return (string)$this->getParameter('txt_intro_enable');
	}
	
	public function setTxtIntroEnable($value) 
	{ 
		$this->setParameter('txt_intro_enable',(string)$value);
	}
	
	public function clearTxtIntroEnable() 
	{ 
		$this->setTxtIntroEnable(__('To enable your account')); 
	}	
	
	/**
	* retourne le titre du lien de confirmation
	*/
	public function getTxtEnable()
	{ 
		return (string)$this->getParameter('txtEnable');
	}
	
	public function setTxtEnable($value) 
	{ 
		$this->setParameter('txtEnable',(string)$value);
	}
	
	public function clearTxtEnable() 
	{ 
		$this->setTxtEnable(__('Click here')); 
	}	
	
	/**
	* retourne le message d'introduction pour la suspension
	*/
	public function getTxtIntroSuspend()
	{ 
		return (string)$this->getParameter('txt_intro_suspend');
	}
	
	public function setTxtIntroSuspend($value) 
	{ 
		$this->setParameter('txt_intro_suspend',(string)$value);
	}
	
	public function clearTxtIntroSuspend() 
	{ 
		$this->setTxtIntroSuspend(__('To suspend your account')); 
	}	
	
	/**
	* retourne le titre du lien de suspension
	*/
	public function getTxtSuspend()
	{ 
		return (string)$this->getParameter('txtSuspend');
	}
	
	public function setTxtSuspend($value) 
	{ 
		$this->setParameter('txtSuspend',(string)$value);
	}
	
	public function clearTxtSuspend() 
	{ 
		$this->setTxtSuspend(__('Click here')); 
	}

	/**
	* retourne le texte de la suspension
	*/
	public function getTxtSuspendedMsg()
	{
		return (string)$this->getParameter('txt_suspended_msg');
	}
	
	public function setTxtSuspendedMsg($value)
	{
		$this->setParameter('txt_suspended_msg',(string)$value);
	}
	
	public function clearTxtSuspendedMsg()
	{
		$this->setTxtSuspendedMsg(__('Newsletter account suspend for'));
	}

	/**
	* retourne le filtre sur la categorie
	*/
	public function getCategory() 
	{ 
		return (integer)$this->getParameter('category'); 
	}
	
	/**
	* renseigne le filtre sur la categorie
	*/
	public function setCategory($value) 
	{ 
		$this->setParameter('category',(integer)$value);
	}
	
	/**
	* efface/initialise le filtre sur la categorie
	*/
	public function clearCategory() 
	{ 
		$this->setCategory(null);
	}

	/**
	* retourne si on inclut les billets des sous categories
	*/
	public function getCheckSubCategories() 
	{ 
		return (boolean)$this->getParameter('check_subcategories');
	}
	
	/**
	* indique si on inclut les billets des sous categories
	*/
	public function setCheckSubCategories($value) 
	{ 
		$this->setParameter('check_subcategories',(boolean)$value);
	}
	
	/**
	* reinitialise l'indicateur des sous-categories
	*/
	public function clearCheckSubCategories() 
	{ 
		$this->setCheckSubCategories(false);
	}	
	

	/**
	* retourne l'etat de la planification
	*/
	public function getCheckSchedule() 
	{ 
		return (boolean)$this->getParameter('check_schedule');
	}
	
	/**
	* indique si on doit utiliser la planification
	*/
	public function setCheckSchedule($value) 
	{ 
		$this->setParameter('check_schedule',(boolean)$value);
	}
	
	/**
	* reinitialise l'indicateur de planification
	*/
	public function clearCheckSchedule() 
	{ 
		$this->setCheckSchedule(false);
	}

	/**
	* retourne l'etat de la notification
	*/
	public function getCheckNotification() 
	{ 
		return (boolean)$this->getParameter('check_notification');
	}
	
	/**
	* indique si on doit utiliser la notification
	*/
	public function setCheckNotification($value) 
	{ 
		$this->setParameter('check_notification',(boolean)$value);
	}
	
	/**
	* reinitialise l'indicateur de notification
	*/
	public function clearCheckNotification()
	{ 
		$this->setCheckNotification(false);
	}

	/**
	* retourne le titre de la newsletter
	*/
	public function getNewsletterSubjectWithDate()
	{
		$time = time() + dt::getTimeOffset($this->system_settings->blog_timezone);
		$format = $this->system_settings->date_format;
		$date_sent = dt::str(
			$format,
			$time
		);
		return (string)$this->getParameter('newsletter_subject').' du '.$date_sent;
	}
	
	/**
	* retourne le titre de la newsletter
	*/
	public function getNewsletterSubject()
	{
		return (string)$this->getParameter('newsletter_subject');
	}
	
	public function setNewsletterSubject($value) 
	{ 
		$this->setParameter('newsletter_subject',(string)$value);
	}
	
	public function clearNewsletterSubject() 
	{ 
		$this->setNewsletterSubject(__('Newsletter for').' '.$this->blogname); 
	}

	/**
	* retourne le titre du mail de confirmation
	*/
	public function getConfirmSubject()
	{ 
		return (string)$this->getParameter('confirm_subject');
	}
	
	public function setConfirmSubject($value) 
	{ 
		$this->setParameter('confirm_subject',(string)$value);
	}
	
	public function clearConfirmSubject() 
	{ 
		$this->setConfirmSubject(__('Newsletter subscription confirmation for').' '.$this->blogname); 
	}

	/**
	* retourne le titre du mail de suspension
	*/
	public function getSuspendSubject()
	{ 
		return (string)$this->getParameter('suspend_subject');
	}
	
	public function setSuspendSubject($value) 
	{ 
		$this->setParameter('suspend_subject',(string)$value);
	}
	
	public function clearSuspendSubject() 
	{ 
		$this->setSuspendSubject(__('Newsletter account suspend for').' '.$this->blogname); 
	}

	/**
	* retourne le titre du mail d'activation
	*/
	public function getEnableSubject()
	{ 
		return (string)$this->getParameter('enable_subject');
	}
	
	public function setEnableSubject($value) 
	{ 
		$this->setParameter('enable_subject',(string)$value);
	}
	
	public function clearEnableSubject() 
	{ 
		$this->setEnableSubject(__('Newsletter account activation for').' '.$this->blogname);
	}

	/**
	* retourne le titre du mail de desactivation
	*/
	public function getDisableSubject()
	{ 
		return (string)$this->getParameter('disable_subject');
	}
	
	public function setDisableSubject($value) 
	{ 
		$this->setParameter('disable_subject',(string)$value);
	}
	
	public function clearDisableSubject() 
	{ 
		$this->setDisableSubject(__('Newsletter account removal for').' '.$this->blogname);
	}

	/**
	* retourne le titre du mail de resume
	*/
	public function getResumeSubject()
	{ 
		return (string)$this->getParameter('resume_subject');
	}
	
	public function setResumeSubject($value) 
	{ 
		$this->setParameter('resume_subject',(string)$value);
	}
	
	public function clearResumeSubject() 
	{ 
		$this->setResumeSubject(__('Newsletter account resume for').' '.$this->blogname);
	}

	/**
	* retourne le titre du mail de changement de mode d'envoi
	*/
	public function getChangeModeSubject()
	{ 
		return (string)$this->getParameter('change_mode_subject');
	}
	
	public function setChangeModeSubject($value) 
	{ 
		$this->setParameter('change_mode_subject',(string)$value);
	}
	
	public function clearChangeModeSubject() 
	{ 
		$this->setChangeModeSubject(__('Newsletter account change format for').' '.$this->blogname);
	}

	/**
	* Utilisation de l'option Suspend
	*/
	public function getCheckUseSuspend() 
	{ 
		return (boolean)$this->getParameter('check_use_suspend');
	}
	
	/**
	* indique si on doit utiliser la notification
	*/
	public function setCheckUseSuspend($value) 
	{ 
		$this->setParameter('check_use_suspend',(boolean)$value);
	}
	
	/**
	* reinitialise l'indicateur de notification
	*/
	public function clearCheckUseSuspend()
	{ 
		$this->setCheckUseSuspend(false);
	}

	/**
	* retourne le titre de la page du formulaire
	*/
	public function getFormTitlePage()
	{
		return (string)$this->getParameter('form_title_page');
	}
	
	public function setFormTitlePage($value)
	{
		$this->setParameter('form_title_page',(string)$value);
	}
	
	public function clearFormTitlePage()
	{
		$this->setFormTitlePage(__('Newsletter'));
	}

	/**
	* retourne le message de confirmation
	*/
	public function getConfirmMsg()
	{
		return (string)$this->getParameter('confirm_msg');
	}
	
	public function setConfirmMsg($value)
	{
		$this->setParameter('confirm_msg',(string)$value);
	}
	
	public function clearConfirmMsg()
	{
		$this->setConfirmMsg(__('Newsletter subscription confirmation for'));
	}

 	/**
	* retourne le message de conclusion de la confirmation
	*/
	public function getConcludingConfirmMsg()
	{
		return (string)$this->getParameter('concluding_confirm_msg');
	}
	
	public function setConcludingConfirmMsg($value)
	{
		$this->setParameter('concluding_confirm_msg',(string)$value);
	}
	
	public function clearConcludingConfirmMsg()
	{
		$this->setConcludingConfirmMsg(__('Thank you for subscribing.'));
	}

	/**
	* retourne le message de suspension
	*/
	public function getSuspendMsg()
	{
		return (string)$this->getParameter('suspend_msg');
	}
	
	public function setSuspendMsg($value)
	{
		$this->setParameter('suspend_msg',(string)$value);
	}
	
	public function clearSuspendMsg()
	{
		$this->setSuspendMsg(__('Newsletter account suspend for'));
	}
	
	/**
	* retourne le titre de la page du formulaire
	*/
	public function getConcludingSuspendMsg()
	{
		return (string)$this->getParameter('concluding_suspend_msg');
	}
	
	public function setConcludingSuspendMsg($value)
	{
		$this->setParameter('concluding_suspend_msg',(string)$value);
	}
	
	public function clearConcludingSuspendMsg()
	{
		$this->setConcludingSuspendMsg(__('Have a nice day!'));
	}

	/**
	* retourne le message d'activation
	*/
	public function getEnableMsg()
	{
		return (string)$this->getParameter('enable_msg');
	}
	
	public function setEnableMsg($value)
	{
		$this->setParameter('enable_msg',(string)$value);
	}
	
	public function clearEnableMsg()
	{
		$this->setEnableMsg(__('Newsletter account activation for'));
	}

	/**
	* retourne la conclusion du message d'activation
	*/
	public function getConcludingEnableMsg()
	{
		return (string)$this->getParameter('concluding_enable_msg');
	}
	
	public function setConcludingEnableMsg($value)
	{
		$this->setParameter('concluding_enable_msg',(string)$value);
	}
	
	public function clearConcludingEnableMsg()
	{
		$this->setConcludingEnableMsg(__('Thank you for subscribing.'));
	}

	/**
	* retourne le message d'activation
	*/
	public function getTxtEnabledMsg()
	{
		return (string)$this->getParameter('txt_enabled_msg');
	}
	
	public function setTxtEnabledMsg($value)
	{
		$this->setParameter('txt_enabled_msg',(string)$value);
	}
	
	public function clearTxtEnabledMsg()
	{
		$this->setTxtEnabledMsg(__('Your account has been validated.'));
	}

	/**
	* retourne le message de desactivation
	*/
	public function getDisableMsg()
	{
		return (string)$this->getParameter('disable_msg');
	}
	
	public function setDisableMsg($value)
	{
		$this->setParameter('disable_msg',(string)$value);
	}
	
	public function clearDisableMsg()
	{
		$this->setDisableMsg(__('Newsletter account removal for'));
	}

	/**
	* retourne la conclusion du message de desactivation
	*/
	public function getConcludingDisableMsg()
	{
		return (string)$this->getParameter('concluding_disable_msg');
	}
	
	public function setConcludingDisableMsg($value)
	{
		$this->setParameter('concluding_disable_msg',(string)$value);
	}
	
	public function clearConcludingDisableMsg()
	{
		$this->setConcludingDisableMsg(__('Have a nice day!'));
	}

	/**
	* retourne le texte de la desactivation
	*/
	public function getTxtDisabledMsg()
	{
		return (string)$this->getParameter('txt_disabled_msg');
	}
	
	public function setTxtDisabledMsg($value)
	{
		$this->setParameter('txt_disabled_msg',(string)$value);
	}
	
	public function clearTxtDisabledMsg()
	{
		$this->setTxtDisabledMsg(__('Your account has been canceled.'));
	}
	
	/**
	* retourne le texte
	*/
	public function getHeaderChangeModeMsg()
	{
		return (string)$this->getParameter('header_changemode_msg');
	}
	
	public function setHeaderChangeModeMsg($value)
	{
		$this->setParameter('header_changemode_msg',(string)$value);
	}
	
	public function clearHeaderChangeModeMsg()
	{
		$this->setHeaderChangeModeMsg(__('Newsletter account change format for'));
	}

	/**
	* retourne le texte
	*/
	public function getFooterChangeModeMsg()
	{
		return (string)$this->getParameter('footer_changemode_msg');
	}
	
	public function setFooterChangeModeMsg($value)
	{
		$this->setParameter('footer_changemode_msg',(string)$value);
	}
	
	public function clearFooterChangeModeMsg()
	{
		$this->setFooterChangeModeMsg(__('Have a nice day!'));
	}
	
	/**
	* retourne le texte
	*/
	public function getChangeModeMsg()
	{
		return (string)$this->getParameter('changemode_msg');
	}
	
	public function setChangeModeMsg($value)
	{
		$this->setParameter('changemode_msg',(string)$value);
	}
	
	public function clearChangeModeMsg()
	{
		$this->setChangeModeMsg(__('Your sending format has been updated.'));
	}
	
	/**
	* retourne le texte
	*/
	public function getHeaderResumeMsg()
	{
		return (string)$this->getParameter('header_resume_msg');
	}
	
	public function setHeaderResumeMsg($value)
	{
		$this->setParameter('header_resume_msg',(string)$value);
	}
	
	public function clearHeaderResumeMsg()
	{
		$this->setHeaderResumeMsg(__('Newsletter account resume for'));
	}

	/**
	* retourne le texte
	*/
	public function getFooterResumeMsg()
	{
		return (string)$this->getParameter('footer_resume_msg');
	}
	
	public function setFooterResumeMsg($value)
	{
		$this->setParameter('footer_resume_msg',(string)$value);
	}
	
	public function clearFooterResumeMsg()
	{
		$this->setFooterResumeMsg(__('Have a nice day!'));
	}

	/**
	* retourne le texte
	*/
	public function getTxtSubscribedMsg()
	{
		return (string)$this->getParameter('txt_subscribed_msg');
	}
	
	public function setTxtSubscribedMsg($value)
	{
		$this->setParameter('txt_subscribed_msg',(string)$value);
	}
	
	public function clearTxtSubscribedMsg()
	{
		$this->setTxtSubscribedMsg(__('Thank you for your subscription.'));
	}
	
	/**
	* Selection du type de date pour le tri des billets
	*/
	public function getOrderDate() 
	{ 
		return (string)$this->getParameter('order_date');
	}
	
	/**
	* renseigne du type de date pour le tri des billets
	*/
	public function setOrderDate($value) 
	{ 
		$this->setParameter('order_date',(string)$value);
	}
	
	/**
	* initialise le type de date pour le tri des billets
	*/
	public function clearOrderDate() 
	{ 
		$this->setOrderDate('post_upddt');
	}

	/**
	* indique si on doit envoyer la newsletter lors d'un update
	*/
	public function getSendUpdatePost() 
	{ 
		return (boolean)$this->getParameter('send_update_post');
	}
	
	/**
	* indique si on doit envoyer la newsletter lors d'un update
	*/
	public function setSendUpdatePost($value) 
	{
		$this->setParameter('send_update_post',(boolean)$value);
	}
	
	/**
	* reinitialise l'indicateur si on doit envoyer la newsletter lors d'un update
	*/
	public function clearSendUpdatePost() 
	{ 
		$this->setSendUpdatePost(false);
	}

	/**
	* retourne le flag d'affichage du contenu des posts au format texte
	*/
	public function getViewContentInTextFormat() 
	{ 
		return (boolean)$this->getParameter('view_content_in_text_format');
	}
	
	/**
	* positionne le flag d'affichage du contenu des posts au format texte
	*/
	public function setViewContentInTextFormat($value) 
	{
		$this->setParameter('view_content_in_text_format',(boolean)$value);
	}
	
	/**
	* initialise le flag d'affichage du contenu des posts au format texte
	*/
	public function clearViewContentInTextFormat() 
	{ 
		$this->setViewContentInTextFormat(false);
	}	
	
	/**
	* retourne le flag d'affichage des miniatures dans la newsletter
	*/
	public function getViewThumbnails() 
	{ 
		return (boolean)$this->getParameter('view_thumbnails');
	}
	
	/**
	* positionne le flag d'affichage des miniatures dans la newsletter
	*/
	public function setViewThumbnails($value) 
	{
		$this->setParameter('view_thumbnails',(boolean)$value);
	}
	
	/**
	* initialise le flag d'affichage des miniatures dans la newsletter
	*/
	public function clearViewThumbnails() 
	{ 
		$this->setViewThumbnails(false);
	}
	
	/**
	* retourne la taille d'affichage des miniatures dans la newsletter
	*/
	public function getSizeThumbnails() 
	{ 
		return (string)$this->getParameter('size_thumbnails');
	}
	
	/**
	* positionne la taille d'affichage des miniatures dans la newsletter
	*/
	public function setSizeThumbnails($value) 
	{ 
		$this->setParameter('size_thumbnails',(string)$value);
	}
	
	/**
	* initialise la taille d'affichage des miniatures dans la newsletter
	*/
	public function clearSizeThumbnails() 
	{ 
		$this->setSizeThumbnails('m');
	}

	/**
	* retourne le texte du lien pour la visualisation online
	*/
	public function getTxtLinkVisuOnline()
	{
		return (string)$this->getParameter('txt_link_visu_online');
	}

	/**
	* positionne le texte du lien pour la visualisation online
	*/
	public function setTxtLinkVisuOnline($value)
	{
		$this->setParameter('txt_link_visu_online',(string)$value);
	}
	
	/**
	* initialise le texte du lien pour la visualisation online
	*/	
	public function clearTxtLinkVisuOnline()
	{
		$this->setTxtLinkVisuOnline(__('If you have problems viewing this message, go to the online version.'));
	}	

	/**
	* retourne la date de l'envoi precedent
	*/
	public function getDatePreviousSend() 
	{ 
		return (string)$this->getParameter('date_previous_send'); 
	}
	
	/**
	* positionne la date de l'envoi precedent
	*/
	public function setDatePreviousSend($value=null) 
	{ 
		if($value===null) {
			// on ajoute 5s pour eviter de recuperer 2 fois le meme post			
			$date_previous_send = time()+5;
		} else {
			$date_previous_send = strtotime(html::escapeHTML($value)) - dt::getTimeOffset($this->system_settings->blog_timezone) + 5;
		}
		
		$this->setParameter('date_previous_send',(string)$date_previous_send);
	}

	/**
	* Get the state of link with Agora
	*/
	public function getCheckAgoraLink() 
	{ 
		return (boolean)$this->getParameter('check_agora_link');
	}
	
	/**
	* indique si on doit utiliser la liaison avec Agora
	*/
	public function setCheckAgoraLink($value) 
	{ 
		$this->setParameter('check_agora_link',(boolean)$value);
	}
	
	/**
	* reinitialise si on doit utiliser la liaison avec Agora
	*/
	public function clearCheckAgoraLink()
	{ 
		$this->setCheckAgoraLink(false);
	}	

	/**
	* retourne le style du lien pour la visualisation online
	*/
	public function getStyleLinkVisuOnline()
	{
		return (string)$this->getParameter('style_link_visu_online');
	}

	/**
	* positionne le style du lien pour la visualisation online
	*/
	public function setStyleLinkVisuOnline($value)
	{
		$this->setParameter('style_link_visu_online',(string)$value);
	}
	
	/**
	* initialise le style du lien pour la visualisation online
	*/	
	public function clearStyleLinkVisuOnline()
	{
		$this->setStyleLinkVisuOnline('color: #0000cc; text-decoration: none;');
	}

	/**
	* retourne le style du lien Lire la suite
	*/
	public function getStyleLinkReadIt()
	{
		return (string)$this->getParameter('style_link_read_it');
	}

	/**
	* positionne le style du lien Lire la suite
	*/
	public function setStyleLinkReadIt($value)
	{
		$this->setParameter('style_link_read_it',(string)$value);
	}
	
	/**
	* initialise le style du lien Lire la suite
	*/	
	public function clearStyleLinkReadIt()
	{
		$this->setStyleLinkReadIt('color: #0000cc; text-decoration: none;');
	}

	/**
	* retourne le style du lien pour la confirmation 
	*/
	public function getStyleLinkConfirm()
	{
		return (string)$this->getParameter('style_link_confirm');
	}

	/**
	* positionne le style du lien pour la confirmation
	*/
	public function setStyleLinkConfirm($value)
	{
		$this->setParameter('style_link_confirm',(string)$value);
	}
	
	/**
	* initialise le style du lien pour la confirmation 
	*/	
	public function clearStyleLinkConfirm()
	{
		$this->setStyleLinkConfirm('color: #0000cc; text-decoration: none;');
	}

	/**
	* retourne le style du lien pour StyleLinkDisable
	*/
	public function getStyleLinkDisable()
	{
		return (string)$this->getParameter('style_link_disable');
	}

	/**
	* positionne le style du lien pour StyleLinkDisable
	*/
	public function setStyleLinkDisable($value)
	{
		$this->setParameter('style_link_disable',(string)$value);
	}
	
	/**
	* initialise le style du lien pour StyleLinkDisable
	*/	
	public function clearStyleLinkDisable()
	{
		$this->setStyleLinkDisable('color: #0000cc; text-decoration: none;');
	}

	/**
	* retourne le style du lien pour StyleLinkEnable
	*/
	public function getStyleLinkEnable()
	{
		return (string)$this->getParameter('style_link_enable');
	}

	/**
	* positionne le style du lien pour StyleLinkEnable
	*/
	public function setStyleLinkEnable($value)
	{
		$this->setParameter('style_link_enable',(string)$value);
	}
	
	/**
	* initialise le style du lien pour StyleLinkEnable
	*/	
	public function clearStyleLinkEnable()
	{
		$this->setStyleLinkEnable('color: #0000cc; text-decoration: none;');
	}

	/**
	* retourne le style du lien pour la StyleLinkSuspend
	*/
	public function getStyleLinkSuspend()
	{
		return (string)$this->getParameter('style_link_suspend');
	}

	/**
	* positionne le style du lien pour la StyleLinkSuspend
	*/
	public function setStyleLinkSuspend($value)
	{
		$this->setParameter('style_link_suspend',(string)$value);
	}
	
	/**
	* initialise le style du lien pour la StyleLinkSuspend
	*/	
	public function clearStyleLinkSuspend()
	{
		$this->setStyleLinkSuspend('color: #0000cc; text-decoration: none;');
	}
	
	/**
	* retourne le flag d'affichage des miniatures dans la newsletter
	*/
	public function getCheckSubjectWithDate() 
	{ 
		return (boolean)$this->getParameter('check_subject_with_date');
	}
	
	/**
	* positionne le flag d'affichage des miniatures dans la newsletter
	*/
	public function setCheckSubjectWithDate($value) 
	{
		$this->setParameter('check_subject_with_date',(boolean)$value);
	}
	
	/**
	* initialise le flag d'affichage des miniatures dans la newsletter
	*/
	public function clearCheckSubjectWithDate() 
	{ 
		$this->setCheckSubjectWithDate(false);
	}	

	/**
	* post info date format 
	*/
	public function getDateFormatPostInfo()
	{
		return (string)$this->getParameter('date_format_post_info');
	}

	/**
	* positionne le style du lien pour la StyleLinkSuspend
	*/
	public function setDateFormatPostInfo($value)
	{
		$this->setParameter('date_format_post_info',(string)$value);
	}
	
	/**
	* initialise le style du lien pour la StyleLinkSuspend
	*/	
	public function clearDateFormatPostInfo()
	{
		$this->setDateFormatPostInfo($this->system_settings->date_format.' - '.$this->system_settings->time_format);
	}	
	
	/**
	* retourne le flag d'auto confirmation de l'inscription
	*/
	public function getAutoConfirmSubscription() 
	{ 
		return (boolean)$this->getParameter('auto_confirm_subscription');
	}
	
	public function setAutoConfirmSubscription($value) 
	{
		$this->setParameter('auto_confirm_subscription',(boolean)$value);
	}
	
	public function clearAutoConfirmSubscription() 
	{ 
		$this->setAutoConfirmSubscription(false);
	}

	/*
	* number of newsletter per public page 
	*/
	public function getNbNewslettersPerPublicPage() 
	{ 
		return (integer)$this->getParameter('nb_newsletters_per_public_page'); 
	}
	
	public function setNbNewslettersPerPublicPage($value) 
	{ 
		$this->setParameter('nb_newsletters_per_public_page',(integer)$value);
	}
	
	public function clearNbNewslettersPerPublicPage() 
	{ 
		$this->setNbNewslettersPerPublicPage(10); 
	}
	
	/**
	* --
	*/
	public function getNewslettersPublicPageOrder()
	{
		return (string)$this->getParameter('newsletters_public_page_order');
	}

	public function setNewslettersPublicPageOrder($value)
	{
		$this->setParameter('newsletters_public_page_order',(string)$value);
	}
	
	public function clearNewslettersPublicPageOrder()
	{
		$this->setNewslettersPublicPageOrder('desc');
	}	
	
	/**
	* --
	*/
	public function getNewslettersPublicPageSort()
	{
		return (string)$this->getParameter('newsletters_public_page_sort');
	}

	public function setNewslettersPublicPageSort($value)
	{
		$this->setParameter('newsletters_public_page_sort',(string)$value);
	}
	
	public function clearNewslettersPublicPageSort()
	{
		$this->setNewslettersPublicPageSort('date');
	}		
	
	/**
	 * utilisation du CSS pour les formulaires
	 */
	public function getUseCSSForms()
	{
		return (boolean)$this->getParameter('use_CSSForms');
	}
	
	public function setUseCSSForms($value)
	{
		$this->setParameter('use_CSSForms',(boolean)$value);
	}
	
	public function clearUseCSSForms()
	{
		$this->setUseCSSForms(false);
	}
	
	/**
	* initialize settings
	*/
	public function defaultsSettings()
	{
		# settings
		if(!$this->getEditorName()) $this->clearEditorName();
		if(!$this->getEditorEmail()) $this->clearEditorEmail();
		if(!$this->getSendMode()) $this->clearSendMode();
		if(!$this->getUseDefaultFormat()) $this->setUseDefaultFormat(true);
		if(!$this->getUseDefaultAction()) $this->setUseDefaultAction(true);
		if(!$this->getMaxPosts()) $this->clearMaxPosts();
		if(!$this->getMinPosts()) $this->clearMinPosts();
		if(!$this->getAutosend()) $this->clearAutosend();
		if(!$this->getCaptcha()) $this->clearCaptcha();
		if(!$this->getExcerptRestriction()) $this->clearExcerptRestriction();
		if(!$this->getViewContentPost()) $this->setViewContentPost(true);
		if(!$this->getSizeContentPost()) $this->clearSizeContentPost();
		if(!$this->getViewContentInTextFormat()) $this->setViewContentInTextFormat(true);
		if(!$this->getViewThumbnails()) $this->clearViewThumbnails();
		if(!$this->getSizeThumbnails()) $this->clearSizeThumbnails();
		if(!$this->getCategory()) $this->clearCategory();
		if(!$this->getCheckSubCategories()) $this->clearCheckSubCategories();
		if(!$this->getCheckSchedule()) $this->clearCheckSchedule();
		if(!$this->getCheckNotification()) $this->clearCheckNotification();
		if(!$this->getCheckUseSuspend()) $this->clearCheckUseSuspend();
		if(!$this->getOrderDate()) $this->clearOrderDate();
		if(!$this->getSendUpdatePost()) $this->clearSendUpdatePost();
		if(!$this->getDatePreviousSend()) $this->setDatePreviousSend();
		if(!$this->getCheckAgoraLink()) $this->setCheckAgoraLink(true);
		if(!$this->getCheckSubjectWithDate()) $this->setCheckSubjectWithDate(true);
		if(!$this->getDateFormatPostInfo()) $this->clearDateFormatPostInfo();
		if(!$this->getAutoConfirmSubscription()) $this->clearAutoConfirmSubscription();
		if(!$this->getUseCSSForms()) $this->setUseCSSForms(true);
		if(!$this->getNbSubscribersPerpage()) $this->clearNbSubscribersPerpage();
		
		# en vrac
		if(!$this->getTxtLinkVisuOnline()) $this->clearTxtLinkVisuOnline();
		if(!$this->getStyleLinkVisuOnline()) $this->clearStyleLinkVisuOnline();
		if(!$this->getStyleLinkReadIt()) $this->clearStyleLinkReadIt();
		
		# newsletter
		if(!$this->getNewsletterSubject()) $this->clearNewsletterSubject();
		if(!$this->getIntroductoryMsg()) $this->clearIntroductoryMsg();
		if(!$this->getConcludingMsg()) $this->clearConcludingMsg();
		if(!$this->getMsgPresentationForm()) $this->clearMsgPresentationForm();
		if(!$this->getPresentationMsg()) $this->clearPresentationMsg();
		if(!$this->getPresentationPostsMsg()) $this->clearPresentationPostsMsg();
				
		# confirm
		if(!$this->getConfirmSubject()) $this->clearConfirmSubject();
		if(!$this->getTxtIntroConfirm()) $this->clearTxtIntroConfirm();
		if(!$this->getTxtConfirm()) $this->clearTxtConfirm();
		if(!$this->getStyleLinkConfirm()) $this->clearStyleLinkConfirm();
		if(!$this->getConfirmMsg()) $this->clearConfirmMsg();
		if(!$this->getConcludingConfirmMsg()) $this->clearConcludingConfirmMsg();

		# disable
		if(!$this->getDisableSubject()) $this->clearDisableSubject();
		if(!$this->getTxtIntroDisable()) $this->clearTxtIntroDisable();
		if(!$this->getTxtDisable()) $this->clearTxtDisable();
		if(!$this->getStyleLinkDisable()) $this->clearStyleLinkDisable();
		if(!$this->getDisableMsg()) $this->clearDisableMsg();
		if(!$this->getConcludingDisableMsg()) $this->clearConcludingDisableMsg();
		if(!$this->getTxtDisabledMsg()) $this->clearTxtDisabledMsg();

		# enable
		if(!$this->getTxtIntroEnable()) $this->clearTxtIntroEnable();
		if(!$this->getTxtEnable()) $this->clearTxtEnable();
		if(!$this->getStyleLinkEnable()) $this->clearStyleLinkEnable();
		if(!$this->getEnableSubject()) $this->clearEnableSubject();
		if(!$this->getEnableMsg()) $this->clearEnableMsg();
		if(!$this->getConcludingEnableMsg()) $this->clearConcludingEnableMsg();
		if(!$this->getTxtEnabledMsg()) $this->clearTxtEnabledMsg();

		# suspend
		if(!$this->getSuspendSubject()) $this->clearSuspendSubject();
		if(!$this->getSuspendMsg()) $this->clearSuspendMsg();
		if(!$this->getTxtSuspendedMsg()) $this->clearTxtSuspendedMsg();
		if(!$this->getConcludingSuspendMsg()) $this->clearConcludingSuspendMsg();
		if(!$this->getTxtIntroSuspend()) $this->clearTxtIntroSuspend();
		if(!$this->getTxtSuspend()) $this->clearTxtSuspend();
		if(!$this->getStyleLinkSuspend()) $this->clearStyleLinkSuspend();

		# changemode
		if(!$this->getChangeModeSubject()) $this->clearChangeModeSubject();
		if(!$this->getHeaderChangeModeMsg()) $this->clearHeaderChangeModeMsg();
		if(!$this->getFooterChangeModeMsg()) $this->clearFooterChangeModeMsg();
		if(!$this->getChangeModeMsg()) $this->clearChangeModeMsg();

		# resume
		if(!$this->getResumeSubject()) $this->clearResumeSubject();
		if(!$this->getHeaderResumeMsg()) $this->clearHeaderResumeMsg();
		if(!$this->getFooterResumeMsg()) $this->clearFooterResumeMsg();

		# subscribe
		if(!$this->getFormTitlePage()) $this->clearFormTitlePage();
		if(!$this->getTxtSubscribedMsg()) $this->clearTxtSubscribedMsg();
		
		# public page
		if(!$this->getNbNewslettersPerPublicPage()) $this->clearNbNewslettersPerPublicPage();
		if(!$this->getNewslettersPublicPageOrder()) $this->clearNewslettersPublicPageOrder();
		if(!$this->getNewslettersPublicPageSort()) $this->clearNewslettersPublicPageSort();
		
		$this->save();
	}

	/**
	* recover old settings
	*/
	public function repriseSettings()
	{
		global $core;
		
		$this->blog_settings =& $core->blog->settings->newsletter;
		$this->system_settings = $core->blog->settings->system;
		
		$old_parameters = array(
						// parameters
						'editorName',
						'editorEmail',
						'maxposts',
						'minposts',
						'autosend',
						'captcha',			
						'view_content_post',
						'size_content_post',
						'category',
						'check_schedule',
						'check_notification',
						'use_CSSForms',
						'mode',
						'check_use_suspend',
						'use_default_format',
						'use_default_action',
						'send_update_post',
						'view_content_in_text_format',
						'view_thumbnails',
						'size_thumbnails',
						'excerpt_restriction',
						'txt_link_visu_online',
						'style_link_visu_online',
						'style_link_read_it',
						'check_letter_date',
						'date_format_post_info',
						'nb_subscribers_per_page',				
						// agora link
						'check_agora_link',
						// newsletter	
						'newsletter_subject',
						'introductory_msg',
						'concluding_msg',
						'msg_presentation_form',
						'presentation_msg',
						'presentation_posts_msg',
						// confirm
						'txt_intro_confirm',
						'txtConfirm',
						'style_link_confirm',
						'confirm_subject',
						'confirm_msg',
						'concluding_confirm_msg',		
						// disable
						'txt_intro_disable',
						'txtDisable',
						'style_link_disable',
						'disable_subject',
						'disable_msg',
						'concluding_disable_msg',
						'txt_disabled_msg',
						// enable
						'txt_intro_enable',
						'txtEnable',
						'style_link_enable',
						'enable_subject',
						'enable_msg',
						'concluding_enable_msg',
						'txt_enabled_msg',
						// suspend
						'txt_intro_suspend',
						'txtSuspend',
						'style_link_suspend',
						'suspend_subject',
						'suspend_msg',
						'concluding_suspend_msg',
						'txt_suspended_msg',						
						// changemode
						'change_mode_subject',
						'header_changemode_msg',
						'footer_changemode_msg',
						'changemode_msg',
						// resume
						'resume_subject',
						'header_resume_msg',
						'footer_resume_msg',		
						// subscribe
						'form_title_page',
						'txt_subscribed_msg',
						'date_previous_send',
						//public page
						'nb_newsletters_per_public_page',
						'newsletters_public_page_order',
						'newsletters_public_page_sort'
						);

		// reprise des parametres
		foreach ($old_parameters as $v) {
			// get old parameter
			$value = $this->blog_settings->get('newsletter_'.$v);
			
			if($value) {
				$this->setParameter($v,$value);
				$this->core->error->add('maj param : v='.$v.', value='.$value);
			}
			
			// suppression de l'ancien parametre
			$this->blog_settings->drop('newsletter_'.$v);
		}
		unset($v);

		$this->clearCheckSubCategories();
		$this->clearOrderDate();

		$this->save();
	}
}

?>