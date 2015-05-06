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

if (!defined('DC_CONTEXT_ADMIN')) { return; }

class newsletterLinkedPostList extends adminGenericList
{
	protected $letter_id;
	
	public function display($page,$nb_per_page,$enclose_block='',$letter_id)
	{
		if ($letter_id)
			$this->letter_id = $letter_id;
		
		if (!$this->rs->isEmpty())
		{
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			
			$html_block =
			'<div class="table-outer">'.
			'<table id="linkedPostList">'.
			'<tr>'.
				'<th scope="col">'.__('Remove').'</th>'.
				'<th scope="col">'.__('Title').'</th>'.
				'<th scope="col">'.__('Date').'</th>'.
				'<th scope="col">'.__('Author').'</th>'.
				'<th scope="col">'.__('Status').'</th>'.
			'</tr>%s</table></div>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			//echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				echo $this->postLine();
			}
			
			echo $blocks[1];
			
			//echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function postLine()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('pending'),'check-wrn.png');
				break;
		}
		
		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('protected'),'locker.png');
		}
		
		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('selected'),'selected.png');
		}
		
		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}
		
		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';
	
		$res .=
		'<td class="nowrap">'.
			'<form action="plugin.php?p=newsletter&amp;m=letter" method="post" id="letter_detach">'.
			'<input type="image" src="images/trash.png" alt="'.__('Remove').'" style="border: 0px;" '.
			'title="'.__('Remove').'" />&nbsp;'.__('Remove').' '.
			form::hidden(array('link_id'),$this->rs->post_id).
			form::hidden(array('m'),'letter').
			form::hidden(array('p'),newsletterPlugin::pname()).	
			form::hidden(array('id'),$this->letter_id).
			form::hidden(array('action'),'unlink').
			$this->core->formNonce().
			'</form>'.
		'</td>'.
		'<td class="maximal"><a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'" '.
		'title="'.html::escapeHTML($this->rs->getURL()).'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.$this->rs->user_id.'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'</tr>';

		return $res;
	}
}

?>