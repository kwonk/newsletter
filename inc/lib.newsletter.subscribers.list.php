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

class newsletterSubscribersList extends adminGenericList
{
	/**
	 * Count subscribers
	 */	
	public static function countSubscribers($state = 'enabled')
	{
		$params['state'] = $state;
		$counter = newsletterCore::getSubscribers($params,true);
		return $counter->f(0);
	}
		
	public static function fieldsetResumeSubscribers()
	{
		$state_combo = array(__('pending') => 'pending',
						__('enabled') => 'enabled',
						__('suspended') => 'suspended',
						__('disabled') => 'disabled');
							
		$resume_content =
				'<div class="fieldset">'.
				'<h4>'.__('Statistics subscribers').'</h4>'.
				'<table summary="resume" class="minimal">'.
				'<thead>'.
					'<tr>'.
			  			'<th>'.__('State').'</th>'.
			  			'<th>'.__('Count').'</th>'.
					'</tr>'.
				'</thead>'.
				'<tbody id="classes-list">';

				foreach($state_combo as $k=>$v) {
					$resume_content .= 
						'<tr class="line">'.
						'<td>'.$k.'</td>'.
						'<td>'.self::countSubscribers($v).'</td>'.
						'</tr>'.
						'';
				}

		$resume_content .= 
				'</tbody>'.
				'</table>'.
				'</div>'.
				'';
		
		return $resume_content;
	}
	
	/**
	 * Display data table for subscribers
	 *
	 * @param	int		page
	 * @param	int		nb_per_page
	 * @param	string	url
	 * @param	boolean	filter
	 */
	public function display($page,$nb_per_page,$enclose_block='',$filter=false)
	{
		global $core;
		
		if ($this->rs->isEmpty())
		{
			if( $filter ) {
				echo '<p><strong>'.__('No subscriber matches the filter').'</strong></p>';
			} else {
				echo '<p><strong>'.__('No subscriber for this blog').'</strong></p>';
			}
		}
		else
		{
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			
			// '<table class="maximal" id="userslist">'.
			$html_block =
			'<div class="table-outer">'.
			'<table id="userslist">'.
			'<tr>'.
				'<th>&nbsp;</th>'.
				'<th scope="col">'.__('Subscriber').'</th>'.
				'<th scope="col">'.__('Subscribed').'</th>'.
				'<th scope="col">'.__('Last sent').'</th>'.
				'<th scope="col">'.__('Mode send').'</th>'.
				'<th scope="col">'.__('Status').'</th>'.
			'</tr>%s</table></div>'.
			'';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				echo $this->subscriberLine();
			}
			
			echo $blocks[1];
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}

	/**
	 * Display a line
	 */	
	private function subscriberLine()
	{
		$subscriber_id = (integer)$this->rs->subscriber_id;
		
		if ($this->rs->subscribed != null) 
			$subscribed = dt::dt2str('%d/%m/%Y', $this->rs->subscribed).' '.dt::dt2str('%H:%M', $this->rs->subscribed);
		else 
			$subscribed = __('Never');
						
		if ($this->rs->lastsent != null) 
			$lastsent = dt::dt2str('%d/%m/%Y', $this->rs->lastsent).' '.dt::dt2str('%H:%M', $this->rs->lastsent);
		else 
			$lastsent = __('Never');		

		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->state) {
			case 'enabled':
				$img_status = sprintf($img,__('enabled'),'check-on.png');
				break;
			case 'disabled':
				$img_status = sprintf($img,__('disabled'),'check-off.png');
				break;
			case 'pending':
				$img_status = sprintf($img,__('pending'),'scheduled.png');
				break;
			case 'suspended':
				$img_status = sprintf($img,__('suspended'),'check-wrn.png');
				break;
		}
		
		$res =
		'<tr class="line">'.
		'<td>'.
		form::checkbox(array('subscriber[]'),$this->rs->subscriber_id,'','','',0).'</td>'.
		'<td class="nowrap"><a href="plugin.php?p=newsletter&amp;m=edit_subscriber&amp;id='.$this->rs->subscriber_id.'">'.
		html::escapeHTML($this->rs->email).'</a></td>'.
		'<td class="nowrap">'.$subscribed.'</td>'.
		'<td class="nowrap">'.$lastsent.'</td>'.
		'<td class="nowrap">'.__($this->rs->modesend).'</td>'.
		'<td class="nowrap status">'.$img_status.'</td>'.
		'</tr>';
		
		return $res;
	}

	public static function subcribersActions()
	{
		global $core;

		$params = array();

		# Getting letters
		try {
			$params = array(
				'post_type' => 'newsletter',
				//'post_status' => 1,
			);
			
			$rs_letters = $core->blog->getPosts($params);
			$counter = $core->blog->getPosts($params,true);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}		

		$letters_combo = array();		
		$letters_combo['-'] = '';
		
		while ($rs_letters->fetch()) {
			$letters_combo[html::escapeHTML($rs_letters->post_title).' ('.$rs_letters->post_id.')'] = $rs_letters->post_id;
		}		
		
		if(empty($_POST['subscriber'])) {
			echo '<h3>'.__('Send letters').'</h3>';
			echo '<div class="fieldset">';
			echo '<p>'.__('No enabled subscriber in your selection').'</p>';
			echo '</div>';
			
			echo '<p><a class="back" href="plugin.php?p=newsletter&amp;m=subscribers">'.__('back').'</a></p>';
		} else {

			/* Actions
			-------------------------------------------------------- */
			if (!empty($_POST['op']) && !empty($_POST['subscriber']))
			{
				$action = $_POST['op'];
	
				if ($action == 'send' && $core->auth->check('admin',$core->blog->id)) {
				
					$entries = $_POST['subscriber'];
					foreach ($entries as $k => $v) {
						# check if users are enabled
						if ($subscriber = newsletterCore::get((integer) $v)){
							if ($subscriber->state == 'enabled') {
								$subscribers_id[$k] = (integer) $v;
							}
						}
					}			
				
					//$core->error->add('Launch lettersActions on '.count($subscribers_id));
					if(isset($subscribers_id)) {
						$hidden_fields = '';
						foreach ($subscribers_id as $k => $v) {
							$hidden_fields .= form::hidden(array('subscribers_id[]'),(integer) $v);
						}			
						
						$letters_id = array();
						echo '<h3>'.__('Send letters').'</h3>';
						echo '<div class="fieldset">';
						echo '<h4>'.__('Select letter to send').'</h4>';
						echo '<form action="plugin.php?p=newsletter&amp;m=letters" method="post">';

						echo '<p><label class="classic">'.__('Letter:').'&nbsp;';
						echo form::combo(array('letters_id[]'),$letters_combo,'-');
						echo '</label> ';
						echo '</p>';
						
						echo 
						$hidden_fields.
						$core->formNonce().
						form::hidden(array('action'),'send').
						form::hidden(array('m'),'letters').
						form::hidden(array('p'),newsletterPlugin::pname()).
						form::hidden(array('post_type'),'newsletter').
						form::hidden(array('redir'),html::escapeHTML($_SERVER['REQUEST_URI'])).
						'<input type="submit" value="'.__('send').'" /></p>';
						echo '</form>';
						echo '</div>';
						
						echo '<div class="fieldset">';
						echo '<h4>'.__('Send auto letter').'</h4>';
						echo '<form action="plugin.php?p=newsletter&amp;m=letters" method="post">';
			
						echo 
						$hidden_fields.
						$core->formNonce().
						form::hidden(array('action'),'send_old').
						form::hidden(array('m'),'letters').
						form::hidden(array('p'),newsletterPlugin::pname()).
						form::hidden(array('post_type'),'newsletter').
						form::hidden(array('redir'),html::escapeHTML($_SERVER['REQUEST_URI'])).
						'<input type="submit" value="'.__('send').'" /></p>';
			
						echo '</form>';
	
						echo '</div>';
					} else {
						echo '<h3>'.__('Send letters').'</h3>';
						echo '<div class="fieldset">';
						echo '<p>'.__('No enabled subscriber in your selection').'</p>';
						echo '</div>';
					}
					
					echo '<p><a class="back" href="plugin.php?p=newsletter&amp;m=subscribers">'.__('back').'</a></p>';	
				}
			
			}
		}
	}

}

?>