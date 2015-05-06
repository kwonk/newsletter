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

# Rest methods
class newsletterRest
{
	# Prepare the xml tree
	public static function prepareALetter($core,$get,$post)
	{
		if (empty($get['letterId'])) {
			throw new Exception('No letter selected');
		}
		$letterId = $get['letterId'];

		$nltr = new newsletterLetter($core,$letterId);

		$letterTag = new xmlTag();
		$letterTag = $nltr->getXmlLetterById();

		# retrieve lists of active subscribers or selected
		$subscribers_up = array();

		if (empty($get['subscribersId'])) {
			$subscribers_up = newsletterCore::getlist(true);
		} else {
			$sub_tmp=array();
			$sub_tmp = explode(",", $get['subscribersId']);
			$params['subscriber_id'] = $sub_tmp;
			$params['state'] = "enabled";
			$subscribers_up = newsletterCore::getSubscribers($params);
		}

		if (empty($subscribers_up)) {
			throw new Exception('No subscribers');
		}

		$rsp = new xmlTag();
		$rsp->insertNode($letterTag);

		$subscribers_up->moveStart();
		while ($subscribers_up->fetch()) {
			$subscriberTag = new xmlTag('subscriber');
			$subscriberTag->id=$subscribers_up->subscriber_id;
			$subscriberTag->email=$subscribers_up->email;
			$subscriberTag->mode=$subscribers_up->modesend;
			$subscriberTag->body=$nltr->getLetterBody($subscribers_up->modesend);
			$rsp->insertNode($subscriberTag);
		}

		# set status to publish
		$status = 1;
		$core->blog->updPostStatus((integer) $letterId,$status);

		# set date of last sending
		$nltr_settings = new newsletterSettings($core);
		$nltr_settings->setDatePreviousSend();
		$nltr_settings->save();

		return $rsp;
	}

	/**
	 * Rest send letter
	 * - utilisee pour l'envoi manuel : OUI
	 * - utilisee pour l'envoi automatique : NON
	 * - utilisee pour l'envoi automatique par declenchement manuel : OUI
	 *
	 * Actions :
	 * - recuperation les champs dynamiques
	 * - selectionne le mode texte ou html
	 * - transforme les mots-cles pour chaque abonne
	 * - transforme le mot-cle de visualisation online
	 *
	 */
	public static function sendLetterBySubscriber($core,$get,$post)
	{
		# retrieve selected letter
		if (empty($post['p_letter_id'])) {
			throw new Exception('No letter selected');
		}

		# retrieve selected subscriber
		if (empty($post['p_sub_email']) || empty($post['p_sub_id'])) {
			throw new Exception('No subscriber selected');
		}

		if (empty($post['p_letter_subject'])) {
			throw new Exception('No subject found');
		}

		if (empty($post['p_letter_header'])) {
			throw new Exception('No header found');
		}

		if (empty($post['p_letter_footer'])) {
			throw new Exception('No footer found');
		}

		if (empty($post['p_sub_mode'])) {
			throw new Exception('No mode found');
		}

		if (empty($post['p_letter_body'])) {
			throw new Exception('No body found');
		}

		if($post['p_sub_mode'] == 'text') {
			# define text content
			$letter_content = newsletterLetter::renderingSubscriber($post['p_letter_body'], $post['p_sub_email']);
			$convert = new html2text();
			$convert->set_html($letter_content);
			$convert->labelLinks = __('Links:');
			$letter_content = $convert->get_text();
				
		} else {
			# define html content
			$letter_content = $post['p_letter_header'];
			$letter_content .= newsletterLetter::renderingSubscriber($post['p_letter_body'], $post['p_sub_email']);
			$letter_content .= $post['p_letter_footer'];
		}
			
		# send letter to user
		$mail = new newsletterMail($core);
		$mail->setMessage($post['p_sub_id'],$post['p_sub_email'],$post['p_letter_subject'],$letter_content,$post['p_sub_mode']);
		//throw new Exception('content='.$scontent);
		$mail->send();
		$result = $mail->getState();

		if(!$result) {
			throw new Exception($mail->getError());
		} else {
			$ls_val = newsletterCore::lastsent($post['p_sub_id']);
			if($ls_val != 1)
				throw new Exception($ls_val);
		}

		return $result;
	}

} # end class newsletterRest

?>