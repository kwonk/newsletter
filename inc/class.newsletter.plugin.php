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

class newsletterPlugin
{
	/**
	* nom du plugin
	*/
	public static function pname()
	{ 
		return (string)'newsletter'; 
	}

	/**
	* delete parameters from table dc_setting
	*/
	public static function deleteSettings()
	{
		global $core;

		try {
			$param = array('active', 
						'installed',
						'parameters',
						'errors',
						'messages',
						'flag'
						);

			// deleting settings
			foreach ($param as $v) {
				$core->blog->settings->newsletter->drop('newsletter_'.$v);
			}
			unset($v);
			self::triggerBlog();

		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}		
	}
    
	/** ==================================================
	gestion de base
	================================================== */

	/**
	* répertoire du plugin
	*/
	public static function folder() 
	{ 
		return (string)dirname(__FILE__).'/'; 
	}

	/**
	* adresse pour la partie d'administration
	*/
	public static function urlwidgets() 
	{ 
		return (string)'plugin.php?p=widgets'; 
	}

	/**
	* adresse pour la partie d'administration
	*/
	public static function urladmin() 
	{ 
		return (string)'index.php?'; 
	}

	/**
	* adresse pour la partie d'administration
	*/
	public static function urlplugin() 
	{ 
		return (string)'plugin.php'; 
	}

	/**
	* adresse du plugin pour la partie d'administration
	*/
	public static function adminLetter() 
	{ 
		return (string)self::urlplugin().'?p='; 
	}

	/**
	* adresse du plugin pour la partie d'administration
	*/
	public static function admin() 
	{ 
		return (string)self::adminLetter().self::pname(); 
	}

	/** ==================================================
	gestion des paramètres
	================================================== */

	/**
	* notifie le blog d'une mise à jour
	*/
	public static function triggerBlog()
	{
		global $core;
		try {
	   		$blog = &$core->blog;
			$blog->triggerBlog();
		} catch (Exception $e) { 
	    		$core->error->add($e->getMessage()); 
		}
	}

	/**
	* redirection http
	*/
	public static function redirect($url)
	{
		global $core;
		try {
			http::redirect($url);
      	} catch (Exception $e) { 
	   		$core->error->add($e->getMessage()); 
	   	}
	}

	/**
	* delete the record from table dc_version 
	*/
	public static function deleteVersion()
	{
		global $core;

		try {
			$blog = &$core->blog;
			$con = &$core->con;

			$strReq = 
				'DELETE FROM '.$core->prefix.'version '.
				'WHERE module = \''.newsletterPlugin::pname().'\';';

			$core->con->execute($strReq);

		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}

	/**
	* delete the table dc_newsletter
	*/
	public static function deleteTableNewsletter()
	{
		global $core;

		try {
			$con = &$core->con;

			$strReq =
				'DROP TABLE '.
				$core->prefix.newsletterPlugin::pname();

			$rs = $con->execute($strReq);
			
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}
	
	
	/** ==================================================
	récupération des informations de mise à jour
	================================================== */

	protected static $remotelines = null;

	/**
	* url de base pour les mises à jour
	*/
	public static function baseUpdateUrl() 
	{ 
		return html::escapeURL("http://"); 
	}

	/**
	* url pour le fichier de mise à jour
	*/
	public static function updateUrl() 
	{ 
		return html::escapeURL(self::baseUpdateUrl().self::pname().'.txt'); 
	}

	/**
	* retourne le nom du plugin
	*/
	public static function Name() 
	{ 
		return (string)self::tag('name'); 
	}

	/**
	* est-ce qu'on a le nom du plugin
	*/
	public static function hasName() 
	{ 
		return (bool)(self::pname() != null && strlen(self::pname()) > 0); 
	}

	/**
	* retourne la version du plugin
	*/
	public static function Version() 
	{ 
		return (string)self::tag('version'); 
	}

	/**
	* est-ce qu'on a la version du plugin
	*/
	public static function hasVersion() 
	{ 
		return (bool)(self::Version() != null && strlen(self::Version()) > 0); 
	}

	/**
	* retourne l'url du billet de publication du plugin
	*/
	public static function Post() 
	{ 
		return (string)self::tag('post'); 
	}

	/**
	* est-ce qu'on a l'url du billet de publication du plugin
	*/
	public static function hasPost() 
	{ 
		return (bool)(self::Post() != null && strlen(self::Post()) > 0); 
	}

	/**
	* retourne l'url du package d'installation du plugin
	*/
	public static function Package() 
	{ 
		return (string)self::tag('package'); 
	}

	/**
	* est-ce qu'on a l'url du package d'installation du plugin
	*/
	public static function hasPackage() 
	{ 
		return (bool)(self::Package() != null && strlen(self::Package()) > 0); 
	}

	/**
	* retourne l'url de l'archive du plugin
	*/
	public static function Archive() 
	{ 
		return (string)self::tag('archive'); 
	}

	/**
	* est-ce qu'on a l'url de l'archive du plugin
	*/
	public static function hasArchive() 
	{ 
		return (bool)(self::Archive() != null && strlen(self::Archive()) > 0); 
	}

	/**
	* est-ce qu'on a les informations lues depuis le fichier de mise à jour
	*/
	public static function hasDatas() 
	{ 
		return (bool)(self::$remotelines != null && is_array(self::$remotelines)); 
	}

	/**
	* renvoi une information parmis les lignes lues
	*/
	protected static function tag($tag)
	{
		global $core;
		try {
			if ($tag == null) 
				return null;
			else if (!self::hasDatas()) 
				return null;
	      	else if (!array_key_exists($tag, self::$remotelines)) 
				return null;
			else 
				return (string) self::$remotelines[$tag];
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}

	/** ==================================================
	mises à jour
	================================================== */

	protected static $newversionavailable;

	/**
	* retourne l'indicateur de disponibilité de mise à jour
	*/
	public static function isNewVersionAvailable() 
	{ 
		return (boolean)self::$newversionavailable; 
	}

	/**
	* lecture d'une information particulière concernant un plugin (api dotclear 2)
	*/
	protected static function getInfo($info)
	{
		global $core;
		try {
			$plugins = $core->plugins;
			return $plugins->moduleInfo(self::pname(), $info);
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}

	/**
	* racine des fichiers du plugin
	*/
	public static function dcRoot() 
	{ 
		return self::getInfo('root'); 
	}

	/**
	* nom du plugin
	*/
	public static function dcName() 
	{ 
		return self::getInfo('name'); 
	}

	/**
	* description du plugin
	*/
	public static function dcDesc() 
	{ 
		return self::getInfo('desc'); 
	}

	/**
	* auteur du plugin
	*/
	public static function dcAuthor() 
	{ 
		return self::getInfo('author'); 
	}

	/**
	* version du plugin
	*/
	public static function dcVersion() 
	{ 
		return self::getInfo('version'); 
	}

	/**
	* permissions du plugin
	*/
	public static function dcPermissions() 
	{ 
		return self::getInfo('permissions'); 
	}

	/**
	* comparaison des deux versions
	* retour <0 si old < new
	* retour >0 si old > new
	* retour =0 si old = new
	*/
/*	public static function compareVersion($oldv, $newv) 
	{ 
		return (integer)version_compare($oldv, $newv); 
	}
*/
	/**
	* permet de savoir si la version de Dotclear installé une version finale
	* compatible Dotclear 2.0 beta 6 ou SVN
	*/
	public static function dbVersion()
	{
		global $core;
		try {
			return (string)$core->getVersion('core');
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}

}

?>