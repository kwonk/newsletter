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

class newsletterMailing implements IteratorAggregate
{
	private $items = array();
	private $count = 0;
	
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

	protected $errors;
	protected $success;
	protected $states;
	protected $nothing;

	protected $limit;
	protected $offset;
	
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
		
		$this->success = array();
		$this->errors = array();
		$this->states = array();
		$this->nothing = array();
		
		$this->limit = 10;
		
	}

	public function getIterator() {
		return new MyIterator($this->items);
	}

	/**
	 * Ajoute un message à la liste des envois
	 *
	 * @param:	$id_subscriber		int
	 * @param:	$email_to			string
	 * @param:	$subject			string
	 * @param:	$body			string
	 * @param:	$mode			string
	 * 
	 * @return:	
	 */	
	public function addMessage($id_subscriber,$email_to,$subject,$body,$mode='html')
	{
		$this->items[$this->count++] = array(
			'id' => $id_subscriber,
			'email_to' => $email_to,
			'subject' => $subject,
			'body' => $body,
			'mode' => $mode
		);
	}

	/**
	 * Retourne le nombre de messages dans la liste
	 *
	 * @return:	int
	 */	
	public function getCount()
	{
		return $this->count;
	}
	
	// supprime un message de la liste
	/*
	public function del()
	{
	}

	function __toString()
	{
		return 'to do ...';
	}
	//*/ 
	
	/**
	 * Gère le traitement d'envoi des messages de la liste
	 *
	 * @return:
	 */	
	public function batchSend()
	{
		$this->offset = 0;
		do {
			$portion = array_slice($this->items,$this->offset,$this->limit,true);
			$this->offset += $this->limit;
			$this->send($portion);
		} while (count($portion) > 0);
	}
	
	/**
	 * Envoi des messages de la liste
	 *
	 * @return:
	 */	
	protected function send($portion)
	{
		foreach ($portion as $k => $v) {
			
			if($this->sendMail($v['email_to'], $v['subject'], $v['body'], $v['mode'], $_lang = 'fr')) {
				$this->success[$k] = $v['email_to'];
				$this->states[$k] = $v['id'];
			} else {
				$this->errors[$k] = $v['email_to'];
			}
		}
	}

	/**
	 * Retourne le tableau des emails dont les envois sont passés correctement
	 *
	 * @return:	array
	 */	
	public function getSuccess()
	{
		return $this->success;
	}

	/**
	 * Retourne le tableau des emails dont les envois ne sont pas passés correctement
	 *
	 * @return:	array
	 */	
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Retourne le tableau des id pour une modification de l'état suite au succès de l'envoi
	 *
	 * @return:	array
	 */	
	public function getStates()
	{
		return $this->states;
	}

	/**
	 * Ajoute un message à la liste des messages avec rien à envoyer
	 *
	 * @return:
	 */	
	public function addNothingToSend($id=-1,$email=null)
	{
		if ($email) {
			$this->nothing[$id] = $email;
		}
	}

	/**
	 * Retourne le tableau des emails dont le contenu des messages étaient vides
	 *
	 * @return:	array
	 */	
	public function getNothingToSend()
	{
		return $this->nothing;
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
					(($f_check_notification) ? 'Disposition-Notification-To: '.$this->email_from : '')
				);
			          
				$subject = mail::B64Header($_subject);
				$_body = (function_exists('imap_8bit') ? imap_8bit($_body) : newsletterTools::mb_wordwrap($_body));
				return (mail::sendMail($_email, $subject, $_body, $headers));
			}
		} catch (Exception $e) { 
			return false;
		}
	}

}

?>