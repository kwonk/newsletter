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

class newsletterMail
{
	private $message = array();
	protected $blog;

	protected $x_mailer;
	protected $x_blog_id;
	protected $x_blog_name;
	protected $x_blog_url;
	protected $x_originating_ip;
	protected $x_content_transfer_encoding;
	protected $date;

	protected $email_from;
	protected $name_from;

	protected $state;
	protected $error;
	/*
	protected $errors;
	protected $success;
	protected $states;
	protected $nothing;
	*/

	protected $newsletter_settings;
	
	/**
	 * Class constructor
	 *
	 * @param:	$core	dcCore
	 */
	public function __construct(dcCore $core)
	{
		$this->core =& $core;
		$this->blog =& $core->blog;
		$this->newsletter_settings = new newsletterSettings($core);
		
		if($this->newsletter_settings->getEditorEmail() == "")
			throw new Exception (__('Editor email is empty'));
		else
			$this->email_from = mail::B64Header('<'.$this->newsletter_settings->getEditorEmail().'>');
		
		if($this->newsletter_settings->getEditorName() == "")
			throw new Exception (__('Editor name is empty'));
		else
			$this->name_from = mail::B64Header($this->newsletter_settings->getEditorName());
		
		$this->x_mailer = mail::B64Header(newsletterPlugin::dbVersion().' newsletter '.newsletterPlugin::dcVersion());
		$this->x_blog_id = mail::B64Header($this->blog->id);
		$this->x_blog_name = mail::B64Header($this->blog->name);
		$this->x_blog_url = mail::B64Header($this->blog->url);		
		//$this->x_originating_ip = http::realIP();
		$this->x_originating_ip = (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null);
		$this->x_content_transfer_encoding = (function_exists('imap_8bit') ? 'quoted-printable' : '8bit');
		
		$system_settings =& $core->blog->settings->system;
		$this->date = date('r', time() + dt::getTimeOffset($system_settings->blog_timezone));
		
		$this->state = false;
		$this->error = '';
	}

	/**
	 * Defini le message
	 *
	 * @param:	$id_subscriber		int
	 * @param:	$email_to			string
	 * @param:	$subject			string
	 * @param:	$body			string
	 * @param:	$mode			string
	 * 
	 * @return:	
	 */	
	public function setMessage($id_subscriber,$email_to,$subject,$body,$mode='html')
	{
		$this->message = array(
			'id' => $id_subscriber,
			'email_to' => $email_to,
			'subject' => $subject,
			'body' => $body,
			'mode' => $mode
		);
	}

	/**
	 * Envoi du message
	 *
	 * @return:
	 */	
	public function send()
	{
		if($this->sendMail($this->message['email_to'], $this->message['subject'], $this->message['body'], $this->message['mode'], $_lang = 'fr')) {
			$this->state = true;
		} else {
			$this->state = false;
		}
	}

	/**
	 * Retourne le result
	 *
	 * @return:	array
	 */	
	public function getState()
	{
		return $this->state;
	}

	/**
	 * Retourne le result
	 *
	 * @return:	array
	 */	
	public function getError()
	{
		return $this->error;
	}
	
	/**
	 * Formate et envoi un message
	 * Utilise la fonction mail() de Dotclear
	 *
	 * @return:	boolean
	 */	
	protected function sendMail($_email, $_subject, $_body, $_type = 'html', $_lang = 'fr')
	{
		try {
			if (empty($_email) || empty($_subject) || empty($_body)) {
				return false;
			} else {
				
				$f_check_notification = $this->newsletter_settings->getCheckNotification();
	
				$email_to = mail::B64Header($_email.' <'.$_email.'>');
	
				$headers = array(
					'From: "'.$this->name_from.'" '.$this->email_from,
					'Reply-To: '.$this->email_from,
					'Delivered-to: '.$email_to,
					'X-Sender:'.$this->email_from,
					'MIME-Version: 1.0',
					(($_type == 'html') ? 'Content-Type: text/html; charset="UTF-8";' : 'Content-Type: text/plain; charset="UTF-8";'),
					'Content-Transfer-Encoding: '.$this->x_content_transfer_encoding,
					'X-Mailer: Dotclear '.$this->x_mailer,
					'X-Blog-Id: '.$this->x_blog_id,
					'X-Blog-Name: '.$this->x_blog_name,
					'X-Blog-Url: '.$this->x_blog_url,
					'X-Originating-IP: '.$this->x_originating_ip,
					'Date:'.$this->date,
					(($f_check_notification) ? 'Disposition-Notification-To: '.$this->email_from : '')
				);
			          
				$subject = mail::B64Header($_subject);
				$_body = (function_exists('imap_8bit') ? imap_8bit($_body) : newsletterTools::mb_wordwrap($_body));
				
				mail::sendMail($_email, $subject, $_body, $headers);
				return true;
			}
		} catch (Exception $e) { 
			$this->error=$e->getMessage();
			return false;
		}
	}

}

?>