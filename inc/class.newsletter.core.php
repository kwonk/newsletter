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

# Loading libraries
require dirname(__FILE__).'/class.template.php';
require dirname(__FILE__).'/class.newsletter.mailing.php';

class newsletterCore
{
	/**
	 * getRawDatas
	 * 
	 * Retrieves table newsletter from database.
	 * 
	 * @param boolean $onlyblog  only the current blog
	 * @return recordSet  the retrieved recordset
	 */
	public static function getRawDatas($onlyblog = false)
	{
		global $core;
		try
		{
			$blog = &$core->blog;
			$con = &$core->con;
			$blogid = (string)$blog->id;

			$strReq =
				'SELECT *'.
				' FROM '.$core->prefix.newsletterPlugin::pname();
				
			if($onlyblog) {
				$strReq .= ' WHERE blog_id=\''.$blogid.'\'';	
			}

			$rs = $con->select($strReq);
			return($rs->isEmpty() ? null : $rs);
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	* retourne le prochain id de la table
	*/
	public static function nextId()
	{
		global $core;
		try {
			$blog = &$core->blog;
			$con = &$core->con;
			$blogid = (string)$blog->id;

			$strReq =
				'SELECT max(subscriber_id)'.
				' FROM '.$core->prefix.newsletterPlugin::pname();

			$rs = $con->select($strReq);
			return($rs->isEmpty() ? 0 : ((integer)$rs->f(0)) +1);
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	* renvoi un id pris au hasard dans la table
	*/
	public static function randomId()
	{
		global $core;
		try {
			$blog = &$core->blog;
			$con = &$core->con;
			$blogid = (string)$blog->id;

			$strReq =
    			'SELECT min(subscriber_id), max(subscriber_id)'.
    			' FROM '.$core->prefix.newsletterPlugin::pname().
    			' WHERE blog_id=\''.$blogid.'\'';

			$rs = $con->select($strReq);
			return($rs->isEmpty() ? 0 : rand($rs->f(0), $rs->f(1)));
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	* test l'existence d'un abonne par son id
	*/
	public static function exist($id = -1) 
	{
		if (!is_numeric($id)) {
			return null;
		} else if ($id < 0) {
			return null;
		} else {
			global $core;
			try {
				$blog = &$core->blog;
				$con = &$core->con;
				$blogid = (string)$blog->id;

	           	$strReq =
	    			'SELECT subscriber_id'.
	    			' FROM '.$core->prefix.newsletterPlugin::pname().
	    			' WHERE blog_id=\''.$blogid.'\' AND subscriber_id='.$id;

				$rs = $con->select($strReq);
				return ($rs->isEmpty() ? false : true);
			} catch (Exception $e) { 
	        	$core->blog->dcNewsletter->addError($e->getMessage());
			}
		}
	}

	/**
	* getEmail
	* 
	* Retrieves a subscriber
	* 
	* @param string $_email  the mail of the subscriber
	* @return recordSet  the retrieved recordset
	*/
	public static function getEmail($_email = null)
	{
		if ($_email == null) {
			return null;
		} else {
			global $core;

			$blog = &$core->blog;
			$con = &$core->con;
			$blogid = (string)$blog->id;

			$email = $con->escape(html::escapeHTML(html::clean($_email)));
	
         	$strReq =
				'SELECT subscriber_id'.
				' FROM '.$core->prefix.newsletterPlugin::pname().
				' WHERE blog_id=\''.$blogid.'\' AND email=\''.$email.'\'';

			$rs = $con->select($strReq);
			return ($rs->isEmpty() ? null : self::get($rs->f('subscriber_id')));
		}
	}

	/**
	* recupère des abonnes par leur id
	*/
	public static function get($id = -1)
	{
		if ($id < 0) { 
			return null;
		} else {
			global $core;
			try {
				$blog = &$core->blog;
				$con = &$core->con;
				$blogid = (string)$blog->id;

				# mise en forme du tableau d'id
                if (is_array($id)) 
                	$ids = implode(", ", $id);
                else 
					$ids = $id;

				$strReq =
	    			'SELECT subscriber_id,email,regcode,state,subscribed,lastsent,modesend' .
	    			' FROM '.$core->prefix.newsletterPlugin::pname().
	    			' WHERE blog_id=\''.$blogid.'\' AND subscriber_id IN('.$ids.')';

				$rs = $con->select($strReq);
				return($rs->isEmpty() ? null : $rs);
			} catch (Exception $e) { 
				$core->blog->dcNewsletter->addError($e->getMessage());
			}
		}
	}

	/**
	* add
	* 
	* Add a subscriber
	* 
	* @param string $_email  the mail
	* @param integer $_blogid  the blog
	* @param string $_regcode  the security code
	* @param string $_modesend  the format of the mail
   
	* @return integer  the result of the SQL insert
	*/	
	public static function add($_email = null, $_blogid = null, $_regcode = null, $_modesend = null)
	{
		global $core;
		
		if ($_email == null) {
			throw new Exception(__('You must input an email'));
		} else {
			$blog = $core->blog;
			$con = $core->con;
			$blogid = $con->escape((string)$blog->id);
			$newsletter_settings = new newsletterSettings($core);

			if (!text::isEmail($_email)) {
				throw new Exception(__('The given email is invalid').' : '.$_email);
			}

			if (newsletterCore::getEmail($_email)) {
				throw new Exception(__('The email already exist').' : '.$_email);
			}

			# generate regcode
			if ($_regcode == null) {
				$_regcode = newsletterTools::regcode();
			}

			if ($_modesend == null) {
				$_modesend = $newsletter_settings->getSendMode();
			}

			if ($_blogid == null) {
				$_blogid = $blogid;
			}
				
			# create SQL request
			$cur = $con->openCursor($core->prefix.newsletterPlugin::pname());
			$cur->subscriber_id = self::nextId();
			$cur->blog_id = $_blogid;
			$cur->email = $con->escape(html::escapeHTML(html::clean($_email)));
			$cur->regcode = $con->escape(html::escapeHTML(html::clean($_regcode)));
			$cur->state = 'pending';

			$system_settings = $core->blog->settings->system;
			
			$time = time() + dt::getTimeOffset($system_settings->blog_timezone);
			
			$cur->lastsent = $cur->subscribed = date('Y-m-d H:i:s',$time);
			$cur->modesend = $con->escape(html::escapeHTML(html::clean($_modesend)));

			# launch SQL request
			return($cur->insert());
		}
	}
	
	/**
	* update subscriber
	*/
	public static function update($id = -1, $_email = null, $_state = null, $_regcode = null, $_subscribed = null, $_lastsent = null, $_modesend = null) 
	{
		if (!self::exist($id)) {
			return null;
		} else {
			global $core;
			try {
				$blog = &$core->blog;
				$con = &$core->con;
				$blogid = $con->escape((string)$blog->id);

				# generate request
				$cur = $con->openCursor($core->prefix.newsletterPlugin::pname());

				$cur->subscriber_id = $id;
				$cur->blog_id = $blogid;

				if ($_email != null) 
					$cur->email = $con->escape(html::escapeHTML(html::clean($_email)));
				
				if ($_state != null) 
					$cur->state = $con->escape(html::escapeHTML(html::clean($_state)));
				
				if ($_regcode != null) 
					$cur->regcode = $con->escape(html::escapeHTML(html::clean($_regcode)));
				
				if ($_subscribed != null) 
					$cur->subscribed = $con->escape(html::escapeHTML(html::clean($_subscribed)));
				
				if ($_lastsent != null) 
					$cur->lastsent = $con->escape(html::escapeHTML(html::clean($_lastsent)));
				
				if ($_modesend != null) 
					$cur->modesend = $con->escape(html::escapeHTML(html::clean($_modesend)));

				$cur->update('WHERE blog_id=\''.$con->escape($blogid).'\' AND subscriber_id='.$id);
				
				return true;
			} catch (Exception $e) { 
				$core->blog->dcNewsletter->addError($e->getMessage());
			}
		}
	}

	/**
	* delete subscriber
	*/
	public static function delete($id = -1)
	{
		if ($id < 0) {
			return null;
		} else {
			global $core;
			try {
				$blog = &$core->blog;
				$con = &$core->con;
				$blogid = $con->escape((string)$blog->id);

				# mise en forme du tableau d'id
				if (is_array($id)) 
					$ids = implode(", ", $id);
				else 
					$ids = $id;

				$strReq =
				'DELETE FROM '.$core->prefix.newsletterPlugin::pname().
				' WHERE blog_id=\''.$blogid.'\' AND subscriber_id IN('.$ids.')';

				return($con->execute($strReq) ? true : false);
			} catch (Exception $e) { 
				$core->blog->dcNewsletter->addError($e->getMessage());
			}
		}
	}

	/**
	* retourne le contenu de la table sous forme de tableau de donnees brutes
	*/
	public static function getSubscribers($params=array(),$count_only=false)
	{
		global $core;
		$blog = &$core->blog;
		$con = &$core->con;
		$blogid = $con->escape((string)$blog->id);

		if ($count_only) {
			$strReq = 'SELECT count(N.subscriber_id) ';
		} else {
			$strReq =
			'SELECT N.subscriber_id, N.blog_id, N.email, N.regcode, '.
			'N.state, N.subscribed, N.lastsent, N.modesend ';
		}

		$strReq .=
		'FROM '.$core->prefix.newsletterPlugin::pname().' N ';

		$strReq .=
		'WHERE N.blog_id = \''.$blogid.'\' ';

		if (!empty($params['state'])) {
			$strReq .= 'AND N.state = \''.$con->escape($params['state']).'\' ';
		}

		if (!empty($params['subscriber_id'])) {
			if (is_array($params['subscriber_id'])) {
				array_walk($params['subscriber_id'],create_function('&$v,$k','if($v!==null){$v=(integer)$v;}'));
			} else {
				$params['subscriber_id'] = array((integer) $params['subscriber_id']);
			}
			$strReq .= 'AND N.subscriber_id '.$con->in($params['subscriber_id']);
		}
	
		if (!$count_only)
		{
			if (!empty($params['order'])) {
				$strReq .= 'ORDER BY '.$con->escape($params['order']).' ';
			} else {
				$strReq .= 'ORDER BY N.subscribed DESC ';
			}
		}
			
		if (!$count_only && !empty($params['limit'])) {
			$strReq .= $con->limit($params['limit']);
		}

		$rs = $con->select($strReq);

		return $rs;
	}

	/**
	* retourne le contenu de la table sous forme de tableau de donnees brutes
	*/
	public static function getlist($active = false)
	{
		global $core;
		try {
			$blog = &$core->blog;
			$con = &$core->con;
			$blogid = $con->escape((string)$blog->id);

			$strReq =
				'SELECT *'.
				' FROM '.$core->prefix.newsletterPlugin::pname().
				' WHERE blog_id=\''.$blogid.'\'';

			if ($active) { 
				$strReq .= ' AND state=\'enabled\'';
			}            
                
			$rs = $con->select($strReq);
			return($rs->isEmpty() ? null : $rs);
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	* modifie l'etat de l'abonne
	*/
	public static function state($id = -1, $_state = null)
	{
		# test sur la valeur de l'id qui doit être positive ou null
		if ($id < 0) {
			return null;
		} else { 
			global $core;
			
			# modifie l'etat des abonnes
		
			# filtrage sur le code de status
			switch ($_state) {
				case 'pending':
				case 'enabled':
				case 'suspended':
				case 'disabled':
					break;
				default:
					return false;
			}
			
			try {
				$blog = &$core->blog;
				$con = &$core->con;
				$blogid = $con->escape((string)$blog->id);

				# mise en forme du tableau d'id
				if (is_array($id)) 
					$ids = implode(", ", $id);
				else 
					$ids = $id;

				# generate request
				$cur = $con->openCursor($core->prefix.newsletterPlugin::pname());

				$cur->state = $con->escape(html::escapeHTML(html::clean($_state)));

				$ret = $cur->update('WHERE blog_id=\''.$con->escape($blogid).'\' AND subscriber_id IN('.$ids.')');
				return $ret;
			} catch (Exception $e) { 
				$core->blog->dcNewsletter->addError($e->getMessage());
			}
		}
	}

	/**
	* place les comptes en attente
	*/
	public static function pending($id = -1) 
	{ 
		return self::state($id, 'pending'); 
	}

	/**
	* active les comptes
	*/
	public static function enable($id = -1) 
	{ 
		return self::state($id, 'enabled'); 
	}

	/**
	* suspend les comptes
	*/
	public static function suspend($id = -1)
	{ 
		return self::state($id, 'suspended'); 
	}

	/**
	* desactive les comptes
	*/
	public static function disable($id = -1)
	{ 
		return self::state($id, 'disabled'); 
	}

	/**
	* remove accounts
	*/
	public static function remove($id = -1)
	{ 
		return self::delete($id); 
	}
		
	/**
	* comptes en attente de confirmation
	*/
	public static function confirm($id = -1)
	{ 
		return self::state($id, 'confirm'); 
	}

	/**
	* modifie la date de dernier envoi
	*/
	public static function lastsent($id = -1, $_lastsent = null) 
	{
		if ($id < 0) {
			return false;
		} else { 		
			global $core;
			try {
				$blog = &$core->blog;
				$con = &$core->con;
				$blogid = $con->escape((string)$blog->id);

				if (is_array($id)) 
					$ids = implode(", ", $id);
				else 
					$ids = $id;

				if ($_lastsent == 'clear')
                		$req = 'UPDATE '.$core->prefix.newsletterPlugin::pname().' SET lastsent=subscribed';
				else if ($_lastsent == null) 
					$req = 'UPDATE '.$core->prefix.newsletterPlugin::pname().' SET lastsent=now()';
				else 
					$cur->lastsent = $con->escape(html::escapeHTML(html::clean($_lastsent)));
                
				$req .= ' WHERE blog_id=\''.$con->escape($blogid).'\' AND subscriber_id IN('.$ids.')';
				return ($con->execute($req));
	        } catch (Exception $e) { 
	        	$core->blog->dcNewsletter->addError($e->getMessage());
	        }
		}
	}

	/**
	* modifie le format de la lettre pour l'abonne
	*/
	public static function changemode($id = -1, $_modesend = null)
	{
		if ($id < 0) {
			return null;
		} else {
			global $core;
			
			switch ($_modesend) {
				case 'html':
				case 'text':
					break;
				default:
					return false;
			}
			
			try {
				$blog = &$core->blog;
				$con = &$core->con;
				$blogid = $con->escape((string)$blog->id);

				if (is_array($id)) 
					$ids = implode(", ", $id);
				else 
					$ids = $id;

				$cur = $con->openCursor($core->prefix.newsletterPlugin::pname());
				$cur->modesend = $con->escape(html::escapeHTML(html::clean($_modesend)));
				$cur->update('WHERE blog_id=\''.$con->escape($blogid).'\' AND subscriber_id IN('.$ids.')');
				return true;
			} catch (Exception $e) { 
				$core->blog->dcNewsletter->addError($e->getMessage());
			}
		}
	}
	
	/**
	* change le format en html des comptes
	*/
	public static function changemodehtml($id = -1)
	{ 
		return self::changemode($id, 'html'); 
	}

	/**
	* change le format en text des comptes
	*/
	public static function changemodetext($id = -1)
	{ 
		return self::changemode($id, 'text'); 
	}

	/* ==================================================
		billets
	================================================== */

	/**
	* retourne les billets pour la newsletter:
	*/
	public static function getPosts($l_post_id=null)
	{
		global $core;
		try {
			$con = &$core->con;
			$blog = &$core->blog;
			$newsletter_settings = new newsletterSettings($core);
			$debug = false;
			
			$system_settings = $core->blog->settings->system;
			
			# parametrage de la recuperation des billets
			$params = array();

			# selection du contenu
			//$params['no_content'] = ($newsletter_settings->getViewContentPost() ? false : true);
			 $params['no_content'] = false;
			 
			# selection des billets
			$params['post_type'] = 'post';
			# uniquement les billets publies
			$params['post_status'] = 1;
			# sans mot de passe
			$params['sql'] = ' AND P.post_password IS NULL';
			
			# envoi d'un billet specifique
			if($l_post_id !== null) {
				$params['post_id'] = (integer) $l_post_id;
			} else {
				# filtre sur la date du dernier envoi
				if ($newsletter_settings->getDatePreviousSend() !== null) {

					if ($newsletter_settings->getOrderDate() == 'post_dt') {
						$date_previous_send = date('Y-m-j H:i:s',$newsletter_settings->getDatePreviousSend() + dt::getTimeOffset($system_settings->blog_timezone));
						$now = date('Y-m-j H:i:s',time() + dt::getTimeOffset($system_settings->blog_timezone));
					} else {
						$date_previous_send = date('Y-m-j H:i:s',$newsletter_settings->getDatePreviousSend());
						$now = date('Y-m-j H:i:s',time());
					}
					$params['sql'] = ' AND P.'.$newsletter_settings->getOrderDate().' BETWEEN \''.$date_previous_send.'\' AND \''.$now.'\' ';
				}
			}
			
			# limitations du nombre de billets
			$maxPost = $newsletter_settings->getMaxPosts();
			if ($maxPost > 1) {
				$params['limit'] = $maxPost;
			}
		
			# definition du tris des enregistrements et filtrage dans le temps
			$params['order'] = ' P.'.$newsletter_settings->getOrderDate().' DESC';

			# filtre sur la categorie
			$category = $newsletter_settings->getCategory();
			
			if ($category) {
				# filtre sur les sous-categories
				if ($newsletter_settings->getCheckSubCategories()) {
				
					$rs = $con->select(
						'SELECT cat_lft, cat_rgt FROM '.$core->prefix.'category '.
						'WHERE blog_id = \''.$con->escape($blog->id).'\' '.
						'AND cat_id='.(integer)$category
					);
					
					$cat_borders = array();
					while ($rs->fetch()) {
						$cat_borders = 'C.cat_lft BETWEEN '.$rs->cat_lft.' AND '.$rs->cat_rgt.'';
					}
					if (count($cat_borders) > 0) {
						$params['sql'] = ' AND (C.cat_id IS NOT NULL AND '.$cat_borders.')';
					}
				} else {
					if ($category == 'null') {
						$params['sql'] = ' AND P.cat_id IS NULL ';
					} elseif (is_numeric($category)) {
						$params['cat_id'] = (integer) $category;
					} else {
						$params['cat_url'] = $category;
					}
				}
			}

			# recuperation des billets
			$rs = $blog->getPosts($params, false);
			
			if($debug) {
				$core->blog->dcNewsletter->addMessage('nb billets='.$rs->count().', $category='.$category);
			}
			
			$minPosts = $newsletter_settings->getMinPosts();
           	if($rs->count() < $minPosts)
           		return null;
           	else 
           		return($rs->isEmpty() ? null : $rs);
		} catch (Exception $e) { 
				$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	public static function getNewsletterPosts($l_post_id=null)
	{
		global $core;
		$newsletter_settings = new newsletterSettings($core);
		$system_settings = $core->blog->settings->system;
		
		# boucle sur les billets concernes pour l'abonnes
		$bodies = array();
		$posts = array();
	
		$posts = self::getPosts($l_post_id);
		
		if($posts!==null) {
			$posts->core = $core;
			$posts->moveStart();

			while ($posts->fetch())
			{
				$_body_swap = html::escapeHTML('');
				
				# Affiche les miniatures
				if ($newsletter_settings->getViewThumbnails()) {
					
					# reprise du code de context::EntryFirstImageHelper et adaptation
					$size=$newsletter_settings->getSizeThumbnails();
					if (!preg_match('/^sq|t|s|m|o$/',$size)) {
						$size = 's';
					}
					$class = !empty($attr['class']) ? $attr['class'] : '';
					
					$p_url = $system_settings->public_url;
					$p_site = preg_replace('#^(.+?//.+?)/(.*)$#','$1',$core->blog->url);
					$p_root = $core->blog->public_path;
				
					$pattern = '(?:'.preg_quote($p_site,'/').')?'.preg_quote($p_url,'/');
					$pattern = sprintf('/<img.+?src="%s(.*?\.(?:jpg|gif|png))"[^>]+/msu',$pattern);
				
					$src = '';
					$alt = '';
				
					# We first look in post content
					$subject = $posts->post_excerpt_xhtml.$posts->post_content_xhtml.$posts->cat_desc;
						
					if (preg_match_all($pattern,$subject,$m) > 0)
					{
						foreach ($m[1] as $i => $img) {
							if (($src = newsletterLetter::ContentFirstImageLookup($p_root,$img,$size)) !== false) {
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
					if (!$src && $posts->cat_desc)
					{
						if (preg_match_all($pattern,$posts->cat_desc,$m) > 0)
						{
							foreach ($m[1] as $i => $img) {
								if (($src = newsletterLetter::ContentFirstImageLookup($p_root,$img,$size)) !== false) {
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
						$_body_swap .= '<p class="content_img" style="border: 0px;">';
						$_body_swap .= html::absoluteURLs('<img alt="'.$alt.'" src="'.$src.'" class="'.$class.'" />',$posts->getURL()); 
						$_body_swap .= '</p>';
					}				
				}
				
				# Contenu des billets
				$news_content = '';
				if ($newsletter_settings->getExcerptRestriction()) {
					# Get only Excerpt
					$news_content = $posts->getExcerpt($posts,true);
					$news_content = html::absoluteURLs($news_content,$posts->getURL());
				} else {
					if ($newsletter_settings->getViewContentPost()) {
						$news_content = $posts->getExcerpt($posts,true).' '.$posts->getContent($posts,true);
						$news_content = html::absoluteURLs($news_content,$posts->getURL());
					}
				}
				
				if(!empty($news_content)) {
					
					# supprimer le contenu script dans les extraits si plugin GalleryInsert activé
					if($core->plugins->moduleExists('GalleryInsert') && !isset($core->plugins->getDisabledModules['GalleryInsert'])) {
						$search = array('@<script[^>]*?>.*?</script>@si');
						$news_content = preg_replace($search, '', $news_content);
					}
										
					if($newsletter_settings->getViewContentInTextFormat()) {
						$news_content = context::remove_html($news_content);
						$news_content = text::cutString($news_content,$newsletter_settings->getSizeContentPost());
						$news_content = html::escapeHTML($news_content);
						$news_content = $news_content.' ... ';
					} else {
						$news_content = newsletterTools::truncateHtmlString($news_content,$newsletter_settings->getSizeContentPost(),'...',false,true);
						$news_content = html::decodeEntities($news_content);
					}

					# Affichage
					$_body_swap .= $news_content;
				}
				
				# Affiche le lien "read more"
				//$style_readmore='style="color: #d5b72b; text-decoration: none"';
				$style_link_read_it = $newsletter_settings->getStyleLinkReadIt();
				$_body_swap .= '<p class="read-it">';
				$_body_swap .= '<a href="'.$posts->getURL().'" style="'.$style_link_read_it.'">Read more - Lire la suite</a>';
				$_body_swap .= '</p>';
				$_body_swap .= '<br /><br />';
				
				$format = $newsletter_settings->getDateFormatPostInfo();
				$tdate = $newsletter_settings->getOrderDate();
				
				if($tdate == 'post_dt')
					$sdate = dt::dt2str($format,$posts->$tdate);
				else
					$sdate = dt::dt2str($format,$posts->$tdate,$posts->post_tz);
			
				$bodies[] = array(
					'title' => $posts->post_title,
					'url' => $posts->getURL(),
					'date' => $sdate,
					'category' => $posts->getCategoryURL(),
					'content' => $_body_swap,
					'author' => $posts->getAuthorCN(),
					'post_dt' => $posts->post_dt,
					'post_creadt' => $posts->post_creadt,
					'post_upddt' => $posts->post_upddt
				);
				
			}
		}
		return $bodies;
	}

	public static function getUserPosts($posts=array(),$dt=null)
	{
		$bodies = array();
		foreach ($posts as $k => $v) {
			if($dt < $v[$newsletter_settings->getOrderDate()]) {
				$bodies[] = $posts[$k];
			}
		}
		return $bodies;
	}

	/* ==================================================
		emails
	================================================== */

	/**
	* renvoi l'url de base de newsletter
	*/
	public static function url($cmd = '')
	{
		global $core;
		try {
			$url = &$core->url;
			$blog = &$core->blog;
			$blogurl = &$blog->url;

			if ($cmd == '') 
				return newsletterTools::concatURL($blogurl, $url->getBase('newsletter'));
			else 
				return newsletterTools::concatURL($blogurl, $url->getBase('newsletter')).'/'.$cmd;
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	* preparation de l'envoi d'un mail a un abonne
	*/
	private static function BeforeSendmailTo($header, $footer)
	{
		global $core;
		try
		{
			$url = &$core->url;
			$blog = &$core->blog;
			$blogname = &$blog->name;
			$blogdesc = &$blog->desc;
			$blogurl = &$blog->url;
			$urlBase = newsletterTools::concatURL($blogurl, $url->getBase('newsletter'));
			$newsletter_settings = new newsletterSettings($core);

			nlTemplate::clear();
			nlTemplate::assign('header', $header);
			nlTemplate::assign('footer', $footer);
			nlTemplate::assign('blogName', $blogname);
			nlTemplate::assign('blogDesc', $blogdesc);
			nlTemplate::assign('blogUrl', $blogurl);
			nlTemplate::assign('txtIntroductoryMsg', $newsletter_settings->getIntroductoryMsg());
			nlTemplate::assign('txtMsgPresentationForm', $newsletter_settings->getMsgPresentationForm());
			nlTemplate::assign('txtHeading', $newsletter_settings->getPresentationPostsMsg());
			nlTemplate::assign('txt_intro_confirm', $newsletter_settings->getTxtIntroConfirm().', ');
			nlTemplate::assign('txtConfirm', $newsletter_settings->getTxtConfirm());
			nlTemplate::assign('txt_intro_disable', $newsletter_settings->getTxtIntroDisable().', ');
			nlTemplate::assign('txtDisable', $newsletter_settings->getTxtDisable());
			nlTemplate::assign('txt_intro_enable', $newsletter_settings->getTxtIntroEnable().', ');
			nlTemplate::assign('txtEnable', $newsletter_settings->getTxtEnable());
			nlTemplate::assign('txtChangingMode', $newsletter_settings->getChangeModeMsg());
			nlTemplate::assign('txt_visu_online', $newsletter_settings->getTxtLinkVisuOnline());	

			if($newsletter_settings->getCheckUseSuspend()) {
				nlTemplate::assign('txt_intro_suspend', $newsletter_settings->getTxtIntroSuspend().', ');
				nlTemplate::assign('txtSuspend', $newsletter_settings->getTxtSuspend());
				nlTemplate::assign('txtSuspended', $newsletter_settings->getTxtSuspendedMsg());
			} else {
				nlTemplate::assign('txt_intro_suspend', ' ');
				nlTemplate::assign('txtSuspend', ' ');
				nlTemplate::assign('txtSuspended', ' ');
			}
						
			nlTemplate::assign('txtDisabled',$newsletter_settings->getTxtDisabledMsg());
			nlTemplate::assign('txtEnabled', $newsletter_settings->getTxtEnabledMsg());
			nlTemplate::assign('txtBy', __(', by'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}

	/**
	 * Prepare la liste des messages et declenche l'envoi de cette liste.
	 * Retourne les resultats des envois dans un string
	 *
	 * @param:	$id			array
	 * @param:	$action		string
	 *
	 * @return:	string
	 */
	public static function send($id=-1,$action=null,$l_post_id=null)
	{
		global $core;

		$url = &$core->url;
		$blog = &$core->blog;
		$blogurl = &$blog->url;
		$send = array();
		$blog_settings =& $core->blog->settings->newsletter;
		$system_settings = $core->blog->settings->system;
		$newsletter_flag = (boolean)$blog_settings->newsletter_flag;

		try {
			if (!$newsletter_flag) {
				return false;
			} else if ($id == -1 || $action === null) {
				return false;
			} else {
				# envoi des mails aux abonnes

				# list id or single id 
				if (is_array($id)) {
					$ids = $id;
				} else { 
					$ids = array(); 
					$ids[] = $id; 
				}

				$msg = '';
				$newsletter_mailing = new newsletterMailing($core);		
				$newsletter_settings = new newsletterSettings($core);
				
				# filtrage sur le type de mail
				switch ($action) {
					case 'newsletter':
						$tmp_letter_id = self::insertMessageNewsletter($newsletter_mailing,$newsletter_settings,$l_post_id);
						if ($tmp_letter_id === null) {
							return false;
						}
						self::prepareMessagesNewsletter($ids,$newsletter_mailing,$newsletter_settings,$tmp_letter_id);
						break;
					case 'confirm':
						self::prepareMessagesConfirm($ids,$newsletter_mailing,$newsletter_settings);
						break;
					case 'suspend':
						self::prepareMessagesSuspend($ids,$newsletter_mailing,$newsletter_settings);
						break;
					case 'enable':
						self::prepareMessagesEnable($ids,$newsletter_mailing,$newsletter_settings);
						break;
					case 'disable':
						self::prepareMessagesDisable($ids,$newsletter_mailing,$newsletter_settings);
						break;
					case 'resume':
						self::prepareMessagesResume($ids,$newsletter_mailing,$newsletter_settings);
						break;
					case 'changemode':
						self::prepareMessagesChangeMode($ids,$newsletter_mailing,$newsletter_settings);
						break;
					default:
						return false;
				}

				# Envoi des messages
				$newsletter_mailing->batchSend();

				if($action == 'newsletter') {
					$newsletter_settings->setDatePreviousSend();
					$newsletter_settings->save();
					$msg=date('Y-m-j H:i',$newsletter_settings->getDatePreviousSend() + dt::getTimeOffset($system_settings->blog_timezone)).': ';
				}				
				
				$sent_states = $newsletter_mailing->getStates();
				$sent_success = $newsletter_mailing->getSuccess();
				$sent_errors = $newsletter_mailing->getErrors();
				$sent_nothing = $newsletter_mailing->getNothingToSend();
				
				if (is_array($sent_states) && count($sent_states) > 0) {
					# positionnement de l'etat des comptes
					switch ($action) {
						case 'newsletter':
							self::lastsent($sent_states);
							break;
						case 'confirm':
							self::confirm($sent_states);
							break;
						case 'suspend': 
							self::suspend($sent_states);
                    			break;
						case 'enable': 
							self::enable($sent_states);
                    			break;
						case 'disable': 
							self::remove($sent_states);
                    			break;
						case 'resume':
                    			break;
						case 'changemode':
                    			break;
					}
				}		
                
				if (isset($sent_success) && count($sent_success) > 0) 
					$msg .= __('Successful mail sent for').' '.implode(', ', $sent_success).'<br />';

				if (isset($sent_errors) && count($sent_errors) > 0) {
					$msg .= __('Mail sent error for').' '.implode(', ', $sent_errors).'<br />';
					$core->blog->dcNewsletter->addError($msg);
				}

				if (isset($sent_nothing) &&count($sent_nothing) > 0) 
					$msg .= __('Nothing to send for').' '.implode(', ', $sent_nothing).'<br />';
				
				return $msg;
			}
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	 * Prepare le message de type newsletter pour chaque subscriber
	 * Modifie l'objet newsletterMailing fourni en parametre
	 * - utilisee pour l'envoi manuel : NON
	 * - utilisee pour l'envoi automatique : OUI
	 * - utilisee pour l'envoi automatique par declenchement manuel : NON
 	 *
	 * @param:	$ids					array
	 * @param:	$newsletter_mailing		newsletterMailing
	 *
	 * @return:	boolean
	 */
	private static function prepareMessagesNewsletter($ids=-1,$newsletter_mailing, newsletterSettings $newsletter_settings, $letter_id=null)
	{
		global $core;
		
		$subject='';
		$body='';		
		$mode = $newsletter_settings->getSendMode();

		# recupere le contenu de la newsletter
		$params = array();
		$params['post_type'] = 'newsletter';
		$params['post_id'] = (integer) $letter_id;
	
		$rs = $core->blog->getPosts($params);
		
		if ($rs->isEmpty()) {
			throw new Exception('No post for this ID');
		}
		
		# formate les champs de la lettre pour l'envoi
		$subject=text::toUTF8($rs->post_title);
		$body=$rs->post_content_xhtml;
		$body=text::toUTF8($body);
		
		foreach ($ids as $subscriber_id)
		{
			# get subscriber and extract datas
			$subscriber = self::get($subscriber_id);

			# define mode for the current subscriber
			if (!$newsletter_settings->getUseDefaultFormat() && $subscriber->modesend != null) {
				$mode = $subscriber->modesend;
			}
			
			# Remplacement des liens pour les users
			$patterns[0] = '/USER_DELETE/';
			$patterns[1] = '/USER_SUSPEND/';
			$patterns[2] = '/LINK_VISU_ONLINE/';

			if($mode != 'text') {
				$patterns[3] = '/<body>/';
			}
			
			$style_link_disable = $newsletter_settings->getStyleLinkDisable();
			$style_link_suspend = $newsletter_settings->getStyleLinkSuspend();
			$replacements[0] = '<a href='.newsletterCore::url('disable/'.newsletterTools::base64_url_encode($subscriber->email)).'" style="'.$style_link_disable.'">';
			$replacements[0] .= html::escapeHTML($newsletter_settings->getTxtDisable()).'</a>';
			$replacements[1] = '<a href='.newsletterCore::url('suspend/'.newsletterTools::base64_url_encode($subscriber->email)).'" style="'.$style_link_suspend.'">';
			$replacements[1] .= html::escapeHTML($newsletter_settings->getTxtSuspend()).'</a>';
			$text_visu_online = $newsletter_settings->getTxtLinkVisuOnline();
			$style_link_visu_online = $newsletter_settings->getStyleLinkVisuOnline();
			$replacements[2] = '';
			$replacements[2] .= '<p>';
			$replacements[2] .= '<span class="letter-visu"><a href="'.newsletterLetter::getURL($letter_id).'" style="'.$style_link_visu_online.'">'.$text_visu_online.'</a></span>';
			$replacements[2] .= '</p>';			

			if($mode != 'text') {
				$replacements[3] = '<body>';
				$replacements[3] .= newsletterLetter::letter_style();
			}
		
			# chaine initiale
			$count = 0;
			$scontent = preg_replace($patterns, $replacements, $body, 1, $count);

			if($mode == 'text') {
				$convert = new html2text();
				$convert->set_html($scontent);
				$convert->labelLinks = __('Links:');
				$scontent = $convert->get_text();
			}

			# ajoute le message dans la liste d'envoi
			$newsletter_mailing->addMessage($subscriber_id,$subscriber->email,$subject,$scontent,$mode);
		}
		return true;
	}

	/**
	 * Prepare le contenu des messages de type newsletter
	 * Modifie l'objet newsletterMailing fourni en parametre
	 *
	 * @param:	$ids					array
	 * @param:	$newsletter_mailing		newsletterMailing
	 *
	 * @return:	boolean
	 */
	public static function insertMessageNewsletter($newsletter_mailing, newsletterSettings $newsletter_settings, $l_post_id=null)
	{
		global $core;
		$system_settings = $core->blog->settings->system;
		$subject_with_date = $newsletter_settings->getCheckSubjectWithDate();
		
		if($subject_with_date) {
			$subject = text::toUTF8($newsletter_settings->getNewsletterSubjectWithDate());
		} else {
			$subject = text::toUTF8($newsletter_settings->getNewsletterSubject());
		}
			
		$minPosts = $newsletter_settings->getMinPosts();

		# initialisation du moteur de template
		self::BeforeSendmailTo($newsletter_settings->getPresentationMsg(), $newsletter_settings->getConcludingMsg());

		# recuperation des billets
		$newsletter_posts = self::getNewsletterPosts($l_post_id);
		
		if(count($newsletter_posts) < $minPosts) {
			return null;
		} else {
			$body = '';

			# include posts in the template
			nlTemplate::assign('posts', $newsletter_posts);

			# rendering template
			$body = nlTemplate::render('newsletter', 'html');

			# ajoute le message dans la liste d'envoi
			$old_nltr = new newsletterLetter($core);
			$old_nltr->insertOldLetter($subject,$body);
			return $old_nltr->getLetterId();
   		}
	}
	
	/**
	 * Prepare le contenu des messages de type confirm
	 * Modifie l'objet newsletterMailing fourni en parametre
	 *
	 * @param:	$ids					array
	 * @param:	$newsletter_mailing		newsletterMailing
	 *
	 * @return:	boolean
	 */
	private static function prepareMessagesConfirm($ids=-1,$newsletter_mailing,newsletterSettings $newsletter_settings)
	{
		# initialisation des variables de travail
		$mode = $newsletter_settings->getSendMode();
		$subject = text::toUTF8($newsletter_settings->getConfirmSubject());

		# initialisation du moteur de template
		self::BeforeSendmailTo($newsletter_settings->getConfirmMsg(),$newsletter_settings->getConcludingConfirmMsg());
		
		# boucle sur les ids des abonnes
		foreach ($ids as $subscriber_id)
		{
			$body = '';
			# recuperation de l'abonne et extraction des donnees
			$subscriber = self::get($subscriber_id);

			# definition du format d'envoi
			if (!$newsletter_settings->getUseDefaultFormat() && $subscriber->modesend != null) {
				$mode = $subscriber->modesend;
			}

			# generation du rendu
			nlTemplate::assign('urlConfirm', self::url('confirm/'.newsletterTools::base64_url_encode($subscriber->email).'/'.$subscriber->regcode.'/'.newsletterTools::base64_url_encode($subscriber->modesend)));
			nlTemplate::assign('urlDisable', self::url('disable/'.newsletterTools::base64_url_encode($subscriber->email)));

			$body = nlTemplate::render('confirm', $mode);

			if($mode == 'text') {
				$convert = new html2text();
				$convert->set_html($body);
				$convert->labelLinks = __('Links:');
				$body = $convert->get_text();
			}

			# ajoute le message dans la liste d'envoi
			$newsletter_mailing->addMessage($subscriber_id,$subscriber->email,$subject,$body,$mode);
		}
		return true;
	}

	/**
	 * Prepare le contenu des messages de type suspend
	 * Modifie l'objet newsletterMailing fourni en parametre
	 *
	 * @param:	$ids					array
	 * @param:	$newsletter_mailing		newsletterMailing
	 *
	 * @return:	boolean
	 */
	private static function prepareMessagesSuspend($ids=-1,$newsletter_mailing,newsletterSettings $newsletter_settings)
	{
		$mode = $newsletter_settings->getSendMode();
		$subject = text::toUTF8($newsletter_settings->getSuspendSubject());

		# initialisation du moteur de template
		self::BeforeSendmailTo($newsletter_settings->getSuspendMsg(),$newsletter_settings->getConcludingSuspendMsg());

		# boucle sur les ids des abonnes
		foreach ($ids as $subscriber_id)
		{
			# recuperation de l'abonne et extraction des donnees
			$subscriber = self::get($subscriber_id);

			# definition du format d'envoi
			if (!$newsletter_settings->getUseDefaultFormat() && $subscriber->modesend != null) {
				$mode = $subscriber->modesend;
			}

			# generation du rendu
			nlTemplate::assign('urlEnable', self::url('enable/'.newsletterTools::base64_url_encode($subscriber->email)));

			$body = nlTemplate::render('suspend', $mode);
			
			if($mode == 'text') {
				$convert = new html2text();
				$convert->set_html($body);
				$convert->labelLinks = __('Links:');
				$body = $convert->get_text();
			}

			# ajoute le message dans la liste d'envoi
			$newsletter_mailing->addMessage($subscriber_id,$subscriber->email,$subject,$body,$mode);
		}
		return true;
	}

	/**
	 * Prepare le contenu des messages de type enable
	 * Modifie l'objet newsletterMailing fourni en parametre
	 *
	 * @param:	$ids					array
	 * @param:	$newsletter_mailing		newsletterMailing
	 *
	 * @return:	boolean
	 */
	private static function prepareMessagesEnable($ids=-1,$newsletter_mailing,newsletterSettings $newsletter_settings)
	{
		$mode = $newsletter_settings->getSendMode();
		$subject = text::toUTF8($newsletter_settings->getEnableSubject());

		# initialisation du moteur de template
		self::BeforeSendmailTo($newsletter_settings->getEnableMsg(),$newsletter_settings->getConcludingEnableMsg());

		# boucle sur les ids des abonnes
		foreach ($ids as $subscriber_id)
		{
			# recuperation de l'abonne et extraction des donnees
			$subscriber = self::get($subscriber_id);

			# definition du format d'envoi
			if (!$newsletter_settings->getUseDefaultFormat() && $subscriber->modesend != null) {
				$mode = $subscriber->modesend;
			}

			# generation du rendu
			nlTemplate::assign('urlDisable', self::url('disable/'.newsletterTools::base64_url_encode($subscriber->email)));
			if($newsletter_settings->getCheckUseSuspend()) {
				nlTemplate::assign('urlSuspend', self::url('suspend/'.newsletterTools::base64_url_encode($subscriber->email)));
			} else {
				nlTemplate::assign('urlSuspend', ' ');
			}

			$body = nlTemplate::render('enable', $mode);

			if($mode == 'text') {
				$convert = new html2text();
				$convert->set_html($body);
				$convert->labelLinks = __('Links:');
				$body = $convert->get_text();
			}
			
			# ajoute le message dans la liste d'envoi
			$newsletter_mailing->addMessage($subscriber_id,$subscriber->email,$subject,$body,$mode);
		}
		return true;
	}

	/**
	 * Prepare le contenu des messages de type disable
	 * Modifie l'objet newsletterMailing fourni en parametre
	 *
	 * @param:	$ids					array
	 * @param:	$newsletter_mailing		newsletterMailing
	 *
	 * @return:	boolean
	 */
	private static function prepareMessagesDisable($ids=-1,$newsletter_mailing,newsletterSettings $newsletter_settings)
	{
		$mode = $newsletter_settings->getSendMode();
		$subject = text::toUTF8($newsletter_settings->getDisableSubject());

		# initialisation du moteur de template
		self::BeforeSendmailTo($newsletter_settings->getDisableMsg(),$newsletter_settings->getConcludingDisableMsg());

		# boucle sur les ids des abonnes
		foreach ($ids as $subscriber_id)
		{
			# recuperation de l'abonne et extraction des donnees
			$subscriber = self::get($subscriber_id);

			# definition du format d'envoi
			if (!$newsletter_settings->getUseDefaultFormat() && $subscriber->modesend != null) {
				$mode = $subscriber->modesend;
			}

			# generation du rendu
			nlTemplate::assign('urlEnable', self::url('enable/'.newsletterTools::base64_url_encode($subscriber->email)));

			$body = nlTemplate::render('disable', $mode);

			if($mode == 'text') {
				$convert = new html2text();
				$convert->set_html($body);
				$convert->labelLinks = __('Links:');
				$body = $convert->get_text();
			}
		
			# ajoute le message dans la liste d'envoi
			$newsletter_mailing->addMessage($subscriber_id,$subscriber->email,$subject,$body,$mode);
		}
		return true;
	}

	/**
	 * Prepare le contenu des messages de type resume
	 * Modifie l'objet newsletterMailing fourni en parametre
	 *
	 * @param:	$ids					array
	 * @param:	$newsletter_mailing		newsletterMailing
	 *
	 * @return:	boolean
	 */
	private static function prepareMessagesResume($ids=-1,$newsletter_mailing,newsletterSettings $newsletter_settings)
	{
		$mode = $newsletter_settings->getSendMode();
		$subject = text::toUTF8($newsletter_settings->getResumeSubject());

		# initialisation du moteur de template
		self::BeforeSendmailTo($newsletter_settings->getHeaderResumeMsg(),$newsletter_settings->getFooterResumeMsg());

		# boucle sur les ids des abonnes
		foreach ($ids as $subscriber_id)
		{
			# recuperation de l'abonne et extraction des donnees
			$subscriber = self::get($subscriber_id);

			$txt_intro_enable = $newsletter_settings->getTxtIntroEnable().', ';
			$urlEnable = self::url('enable/'.newsletterTools::base64_url_encode($subscriber->email));
			$txtEnable = $newsletter_settings->getTxtEnable();
					
			$txt_intro_disable = $newsletter_settings->getTxtIntroDisable().', ';
			$urlDisable = self::url('disable/'.newsletterTools::base64_url_encode($subscriber->email));
			$txtDisable = $newsletter_settings->getTxtDisable();

			$txt_intro_suspend = $newsletter_settings->getTxtIntroSuspend().', ';
			$urlSuspend = self::url('suspend/'.newsletterTools::base64_url_encode($subscriber->email));
			$txtSuspend = $newsletter_settings->getTxtSuspend();
					
			$txt_intro_confirm = $newsletter_settings->getTxtIntroConfirm().', ';
			$urlConfirm = self::url('confirm/'.newsletterTools::base64_url_encode($subscriber->email).'/'.$subscriber->regcode.'/'.newsletterTools::base64_url_encode($subscriber->modesend));
			$txtConfirm = $newsletter_settings->getTxtConfirm();
			
			$urlResume = '';
					
			switch ($subscriber->state) {
				case 'suspended':
					$urlResume = $txt_intro_enable.' <a href="'.$urlEnable.'">'.$txtEnable.'</a><br />';
					$urlResume .= $txt_intro_disable.' <a href="'.$urlDisable.'">'.$txtDisable.'</a>';
					nlTemplate::assign('txtResume', __('Your account is suspended'));
					break;
				case 'disabled':
					$urlResume = $txt_intro_enable.' <a href="'.$urlEnable.'">'.$txtEnable.'</a><br />';
					if($newsletter_settings->getCheckUseSuspend()) {
						$urlResume .= $txt_intro_suspend.' <a href="'.$urlSuspend.'">'.$txtSuspend.'</a>';
					}
					nlTemplate::assign('txtResume', __('Your account is disabled'));
					break;
				case 'enabled':
					$urlResume = $txt_intro_disable.' <a href="'.$urlDisable.'">'.$txtDisable.'</a><br />';
					if($newsletter_settings->getCheckUseSuspend()) {
						$urlResume .= $txt_intro_suspend.' <a href="'.$urlSuspend.'">'.$txtSuspend.'</a>';
					}
					nlTemplate::assign('txtResume', __('Your account is enabled'));
					break;
				case 'pending':
					$urlResume = $txt_intro_disable.' <a href="'.$urlDisable.'">'.$txtDisable.'</a><br />';
					$urlResume .= $txt_intro_confirm.' <a href="'.$urlConfirm.'">'.$txtConfirm.'</a>';
					nlTemplate::assign('txtResume', __('Your account is pending confirmation'));
					break;
				default:
					break;
			}
 
			# definition du format d'envoi
			if (!$newsletter_settings->getUseDefaultFormat() && $subscriber->modesend != null) {
				$mode = $subscriber->modesend;
			}
			$text_mode = sprintf(__('Your sending mode is %s'),$mode);
			
			nlTemplate::assign('txtMode', $text_mode);
			nlTemplate::assign('urlResume', $urlResume);
			$body = nlTemplate::render('resume', $mode);

			if($mode == 'text') {
				$convert = new html2text();
				$convert->set_html($body);
				$convert->labelLinks = __('Links:');
				$body = $convert->get_text();
			}

			# ajoute le message dans la liste d'envoi
			$newsletter_mailing->addMessage($subscriber_id,$subscriber->email,$subject,$body,$mode);
		}
		return true;
	}

	/**
	 * Prepare le contenu des messages de type changemode
	 * Modifie l'objet newsletterMailing fourni en parametre
	 *
	 * @param:	$ids					array
	 * @param:	$newsletter_mailing		newsletterMailing
	 *
	 * @return:	boolean
	 */
	private static function prepareMessagesChangeMode($ids=-1,$newsletter_mailing,newsletterSettings $newsletter_settings)
	{
		# initialisation des variables de travail
		$mode = $newsletter_settings->getSendMode();
		$subject = text::toUTF8($newsletter_settings->getChangeModeSubject());

		# initialisation du moteur de template
		self::BeforeSendmailTo($newsletter_settings->getHeaderChangeModeMsg(),$newsletter_settings->getFooterChangeModeMsg());		

		# boucle sur les ids des abonnes
		foreach ($ids as $subscriber_id)
		{
			# recuperation de l'abonne et extraction des donnees
			$subscriber = self::get($subscriber_id);

			# definition du format d'envoi
			if (!$newsletter_settings->getUseDefaultFormat() && $subscriber->modesend != null) {
				$mode = $subscriber->modesend;
			}					
					
			# generation du rendu
			nlTemplate::assign('urlEnable', self::url('enable/'.newsletterTools::base64_url_encode($subscriber->email)));

			$body = nlTemplate::render('changemode', $mode);

			if($mode == 'text') {
				$convert = new html2text();
				$convert->set_html($body);
				$convert->labelLinks = __('Links:');
				$body = $convert->get_text();
			}

			# ajoute le message dans la liste d'envoi
			$newsletter_mailing->addMessage($subscriber_id,$subscriber->email,$subject,$body,$mode);
		}
		return true;
	}

	/**
	 * Envoi automatique de la newsletter pour tous les abonnes actifs
	 *
	 * @return:	boolean
	 */
	public static function autosendNewsletter($l_post_id=null)
	{
		global $core;

		$blog_settings =& $core->blog->settings->newsletter;
		$system_settings =& $core->blog->settings->system;
		$newsletter_flag = (boolean)$blog_settings->newsletter_flag;
		
		# test si le plugin est actif
		if (!$newsletter_flag) {
			return;
		}
		
		$newsletter_settings = new newsletterSettings($core);
		
		# test si l'envoi automatique est active
		if ($newsletter_settings->getAutosend() || $newsletter_settings->getSendUpdatePost()) {
			
			$datas = self::getlist(true);
			if (!is_object($datas)) {
				return;
			} else {
				$ids = array();
				$datas->moveStart();
               	while ($datas->fetch()) { 
               		$ids[] = $datas->subscriber_id;
               	}

               	if ($newsletter_settings->getMinPosts() > 1) {
               		self::send($ids,'newsletter');
               	} else {
					self::send($ids,'newsletter',$l_post_id);
               	}
			}            	
		} else {
			return;
		}
	}

	/**
	 * Envoi par tâche planifiee de la newsletter pour tous les abonnes actifs
	 *
	 * @return:	boolean
	 */
	public static function cronSendNewsletter()
	{
		global $core;

		$blog_settings =& $core->blog->settings->newsletter;
		$system_settings = $core->blog->settings->system;
		$newsletter_flag = (boolean)$blog_settings->newsletter_flag;
		
		# test si le plugin est actif
		if (!$newsletter_flag) {
			return;
		}

		$newsletter_settings = new newsletterSettings($core);		
		# test si la planification est activee
		if (!$newsletter_settings->getCheckSchedule()) {
			return;
		} else {
			$datas = self::getlist(true);
			if (!is_object($datas)) {
				return;
			} else {
				$ids = array();
				$datas->moveStart();
               	while ($datas->fetch()) { 
               		$ids[] = $datas->subscriber_id;
               	}
				self::send($ids,'newsletter');
			}
		}
	}

	/*
	 * ==================================================
	 * Account management
	 * ==================================================
	 */

	/**
	* create an account
	*/
	public static function accountCreate($email = null, $regcode = null, $modesend = null)
	{
		global $core;
		try {
			if ($email == null) {
				return __('Bad email');
			} else {
				if (self::getemail($email) != null) {
					return __('Email already subscribed');
				} else if (!self::add($email, null, null, $modesend)) {
					return __('Error during account creation');
				} else {
					$subscriber = self::getemail($email);
					
					$newsletter_settings = new newsletterSettings($core);		
					# automatic confirmation
					if ($newsletter_settings->getAutoConfirmSubscription()) {
						$msg = self::send($subscriber->subscriber_id,'enable');
					} else {
						$msg = self::send($subscriber->subscriber_id,'confirm');
					}
					return $msg;
				}
			}
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	* suppression du compte
	*/
	public static function accountDelete($email = null)
	{
		global $core;
		try {		
			if ($email == null) {
				return __('Bad email');
			} else {
				# suppression du compte
				$subscriber = self::getemail($email);
				$msg = null;
				if (!$subscriber || $subscriber->subscriber_id == null) 
					return __('Email does not exist');
				else {
					$msg = self::send($subscriber->subscriber_id,'disable');
					self::delete($subscriber->subscriber_id);
					return $msg;
				}
			}
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	* suspension du compte
	*/
	static function accountSuspend($email = null)
	{
		global $core;
		try {

			if ($email == null) {
				return __('Bad email');
			} else {
				# suspension du compte
				$subscriber = self::getemail($email);
				$msg = '';
				if (!$subscriber || $subscriber->subscriber_id == null) 
					return __('Email does not exist');
				else {
					$msg = self::send($subscriber->subscriber_id,'suspend');					
					self::suspend($subscriber->subscriber_id);
					return $msg;
				}
			}
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}

	/**
	* information sur le compte
	*/
	public static function accountResume($email = null)
	{
		global $core;
		try {		
			if ($email == null) {
				return __('Bad email');
			} else {
				# information sur le compte
				$subscriber = self::getemail($email);
				$msg = '';
				if (!$subscriber || $subscriber->subscriber_id == null) 
					return __('Email does not exist');
				else {
					$msg = self::send($subscriber->subscriber_id,'resume');					
					return $msg;
				}
			}
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}		
	}

	/**
	* changement du format sur le compte
	*/
	public static function accountChangeMode($email = null, $modesend = null)
	{
		global $core;
		try {
			if ($email == null) {
				return __('Bad email');
			} else {
				# information sur le compte
				$subscriber = self::getemail($email);
				$msg = '';
				if (!$subscriber || $subscriber->subscriber_id == null) 
					return __('Email does not exist');
				else {
					$msg = self::send($subscriber->subscriber_id,'changemode');					
					self::changeMode($subscriber->subscriber_id, $modesend);
					return $msg;
				}
			}
		} catch (Exception $e) { 
			$core->blog->dcNewsletter->addError($e->getMessage());
		}
	}
	
} // end class newsletterCore

?>