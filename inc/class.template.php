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

# chargement des librairies
require_once dirname(__FILE__).'/class.html2text.php';

class nlTemplate
{
	# variables
	protected static $metas = null;

	/**
	* renvoi une liste de fichiers de templates
	*/
	public static function templates()
	{
		return array(
			'newsletter' => array('text' => 'newsletter.txt', 'html' => 'newsletter.html'),
			'confirm' => array('text' => 'confirm.txt', 'html' => 'confirm.html'),
			'suspend' => array('text' => 'suspend.txt', 'html' => 'suspend.html'),
			'enable' => array('text' => 'enable.txt', 'html' => 'enable.html'),
			'disable' => array('text' => 'disable.txt', 'html' => 'disable.html'),
			'changemode' => array('text' => 'changemode.txt', 'html' => 'changemode.html'),
			'resume' => array('text' => 'resume.txt', 'html' => 'resume.html')
		);
	}
	
	/**
	* vide le tableau de champs
	*/
	public static function clear() 
	{ 
		self::$metas = array(); 
	}
	
	/**
	* affectation d'une valeur à un méta champs
	*/
	public static function assign($name, $value) 
	{ 
		if (isset($name) && !empty($name)) 
			self::$metas[$name] = $value; 
	}
	
	/**
	* génère la transformation et le rendu du template
	*/
	public static function render($template, $mode = 'text')
	{
		global $core;
		
		# test de la variable mode de rendu
		switch ($mode)
		{
			case 'text':
			case 'html':
				break;
			default: 
				return false;
		}

		# test de la variable de nom de template
		switch ($template)
		{
			case 'newsletter':
			case 'confirm':
			case 'suspend':
			case 'enable':
			case 'disable':
			case 'changemode':
			case 'resume':
				break;
			default: 
				return false;
		}
		
		try {
			$blog = $core->blog;
			$templates = self::templates();
			
			if('newsletter' == $template)
				$mode = 'html';
			
			$filename = newsletterTools::requestTemplate($core,$templates[$template][$mode]).$templates[$template][$mode];
			
			# test d'existence du fichier de droits en lecture
			if (!is_file($filename) || !file_exists($filename) || !is_readable($filename)) 
				return null;
			
			# lecture du fichier et test d'erreur
			$content = @file_get_contents($filename);
			if ($content === FALSE) 
				return null;
			
			# détection d'une boucle de traitement
			$tagStart = "{loop:";
			$tagEnd = "{/loop}";
			$_p1 = stripos($content, "{loop:");
			if ($_p1 !== FALSE) {
				# détermination des différentes valeurs de position dans le contenu
				$p1 = (integer)$_p1;
				$p2 = $p1 + strlen("{loop:");
				$p3 = (integer)stripos($content, "}", $p1);
				$p4 = $p3 +1;
				$p5 = (integer)stripos($content, "{/loop}", $p4);
				$p6 = $p5 + strlen("{/loop}");

				# identification du nom du meta champ et du meta contenu
				$pTag = trim(substr($content, $p2, $p3 - $p2));
				$pContent = trim(substr($content, $p4, $p5 - $p4));

				# on remplace le meta contenu par un tag simple
				$zContent = substr($content, $p1, $p6 - $p1);
				$content = str_replace($zContent, "{*}", $content);

				# si on a bien le meta champ et le meta contenu, alors on boucle le remplacement
				if (!empty($pTag) && !empty($pContent)) {
					# contenu final de la boucle
					$bContent = '';

					# contenu à boucler
					$aContent = (array)self::$metas[$pTag];
					foreach ($aContent as $index => $elem)
					{
						# contenu du tour de boucle
						$_content = $pContent;

						# traite chaque élement
						foreach ($elem as $tagKey => $tagValue)
						{
							$tag = $pTag.'.'.$tagKey;
							$_content = str_replace($tag, $tagValue, $_content);
						}

						# ajoute le contenu du tour de boucle à la boucle
						$bContent .= $_content;
					}

					$content = str_replace("{*}", $bContent, $content);

					if ($mode == 'text') 
						$content = str_replace("{nl}", "\n", $content);
					else if ($mode == 'html')
						$content = str_replace("{nl}", "<br />", $content);
				}
			}
			
			# boucle sur la liste des méta champs pour en remplacer les valeurs
			foreach (self::$metas as $k => $v)
			{
				if (!is_array($v)) {
					$tag = '{$'.$k.'}';
					$content = str_replace($tag, $v, $content);
					
					if ($mode == 'text') 
						$content = str_replace("{nl}", "\n", $content);
					else if ($mode == 'html') 
						$content = str_replace("{nl}", "<br />", $content);						
					
				}
			}
			
			# retourne le contenu transformé
			return $content;
		
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}
}

?>