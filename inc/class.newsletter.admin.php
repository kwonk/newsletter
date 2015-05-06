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

class newsletterAdmin
{
	/**
	* uninstall plugin
	*/
	public static function uninstall()
	{
		# delete schema
		global $core;
		try {
			# delete parameters
			newsletterPlugin::deleteSettings();
			newsletterPlugin::deleteVersion();
			newsletterPlugin::deleteTableNewsletter();
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}

   	/**
	* export the newsletter's subscribers
	*/
	public static function exportToBackupFile($onlyblog = true, $file_format = 'dat', $file_zip = false, $file_name = null)
	{
		global $core;
		try {
			$blog = &$core->blog;
			$blogid = (string)$blog->id;			
			$fullname = $core->blog->public_path.'/.backup_newsletter_'.sha1(uniqid());
			
			# generate content file
			$content = '';
			$datas = newsletterCore::getRawDatas($onlyblog);
			if (is_object($datas) !== FALSE) {
				$datas->moveStart();
			
				if($file_format == 'txt') {
					while ($datas->fetch())
					{
						$elems = array();
						# generate component
						$elems[] = $datas->subscriber_id;
						$elems[] = $datas->blog_id;
						$elems[] = $datas->email;
						$elems[] = $datas->regcode;
						$elems[] = $datas->state;
						$elems[] = $datas->subscribed;
						$elems[] = $datas->lastsent;
						$elems[] = $datas->modesend;
						$line = implode(";", $elems);
						$content .= "$line\n";
					}
				} else {
					while ($datas->fetch())
					{
						$elems = array();
						# generate component
						$elems[] = $datas->subscriber_id;
						$elems[] = base64_encode($datas->blog_id);
						$elems[] = base64_encode($datas->email);
						$elems[] = base64_encode($datas->regcode);
						$elems[] = base64_encode($datas->state);
						$elems[] = base64_encode($datas->subscribed);
						$elems[] = base64_encode($datas->lastsent);
						$elems[] = base64_encode($datas->modesend);
						$line = implode(";", $elems);
						$content .= "$line\n";
					}
				}
			}

			if ($content == '')
				throw new Exception(__('No data found'));
			
			$export_file = $fullname;
			$export_filename = $file_name;
			$export_fileformat = $file_format;
			$export_filezip = $file_zip;
			
			# write in file
			if(@file_put_contents($export_file, $content)) {

				# Send file content
				if (!file_exists($export_file)) {
					throw new Exception(__('Export file not found'));
				}
                
                ob_end_clean();

				if (substr($export_filename,-4) == '.zip') {
					$export_filename = substr($export_filename,0,-4);
				}
				
				if (empty($export_filezip)) {
					# Flat export
					header('Content-Disposition: attachment;filename='.$export_filename);
					header('Content-Type: text/plain; charset="UTF-8"');
					readfile($export_file);
					
					unlink($export_file);
					unset($export_file,$export_filename,$export_filezip);
					exit;
				} else {
					# Zip export
					try
					{
						$file_zipname = $export_filename.'.zip';
							
						$fp = fopen('php://output','wb');
						$zip = new fileZip($fp);
						$zip->addFile($export_file,$export_filename);
							
						header('Content-Disposition: attachment;filename='.$file_zipname);
						header('Content-Type: application/x-zip');
							
						$zip->write();
							
						unlink($export_file);
						unset($zip,$export_file,$export_filename,$file_zipname);
						exit;
					}
					catch (Exception $e)
					{
						unset($zip,$export_file,$export_filename,$export_filezip,$file_zipname);
						@unlink($export_file);
							
						throw new Exception(__('Failed to compress export file'));
					}					
				}
			} else {
				throw new Exception(__('Error during export'));
			}
		} catch (Exception $e) { 
			@unlink($fullname);
			$core->error->add($e->getMessage()); 
		}
	}

	/**
	* import subscribers from a backup file
	*/
	public static function importFromBackupFile($infile = null, $file_format = 'txt')
	{
		global $core;
		$blog = &$core->blog;
		$blog_id = (string)$blog->id;
		$counter=0;
		$counter_ignore=0;
		$counter_failed=0;

		try {
			files::uploadStatus($infile);
			$file_up = DC_TPL_CACHE.'/'.md5(uniqid());
			if (!move_uploaded_file($infile['tmp_name'],$file_up)) {
				throw new Exception(__('Unable to move uploaded file'));
			}
			
			# Try to unzip file
			$unzip_file = self::unzip($file_up,$file_format);
			if (false !== $unzip_file) {
				$file_up = $unzip_file; 
			}
			
			if (!empty($file_up)){
				if(file_exists($file_up) && is_readable($file_up)) {
					$file_content = file($file_up);		
		
					foreach($file_content as $ligne) {
						# explode line
						$line = (string) html::clean((string) $ligne);
						$elems = explode(";", $line);
						
						# traitement des données lues
						if($file_format == 'dat') {
							$subscriber_id = $elems[0];
							//$blog_id = base64_decode($elems[1]);
							$blog_id = $blog_id;
							$email = base64_decode($elems[2]);
							$regcode = base64_decode($elems[3]);
							$state = base64_decode($elems[4]);
							$subscribed = base64_decode($elems[5]);
							$lastsent = base64_decode($elems[6]);
							$modesend = base64_decode($elems[7]);
						} else {
							$subscriber_id = $elems[0];
							//$blog_id = $elems[1];
							$blog_id = $blog_id;
							$email = $elems[2];
							$regcode = $elems[3];
							$state = $elems[4];
							$subscribed = $elems[5];
							$lastsent = $elems[6];
							$modesend = rtrim($elems[7]);
						}
						
						if (!text::isEmail($email)) {
							$core->error->add(html::escapeHTML($email).' '.__('is not a valid email address'));
							$counter_failed++;
						} else {
							try {
								if(newsletterCore::add($email, $blog_id, $regcode, $modesend)) {
									$subscriber = newsletterCore::getEmail($email);
									if ($subscriber != null) {
										newsletterCore::update($subscriber->subscriber_id, $email, $state, $regcode, $subscribed, $lastsent, $modesend);
									}								
									$counter++;
								} else
									$counter_ignore++;
							} catch (Exception $e) { 
								 $counter_ignore++;
							} 
						}
					}				

					# message de retour
					$res = sprintf(__('%d email inserted','%d emails inserted',$counter),$counter);
					$res .= ', '.sprintf(__('%d email ignored','%d emails ignored',$counter_ignore),$counter_ignore);
					$res .= ', '.sprintf(__('%d incorrect line','%d incorrect lines',$counter_failed),$counter_failed);
					return $res;					
				} else {
					throw new Exception(__('No file to read'));
				}
			} else {
				throw new Exception(__('No file to read'));
			}				
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}
	
	private static function unzip($file, $file_format)
	{
		$zip = new fileUnzip($file);
	
		if ($zip->isEmpty()) {
			$zip->close();
			return false;//throw new Exception(__('File is empty or not a compressed file.'));
		}
	
		foreach($zip->getFilesList() as $zip_file)
		{
			# Check zipped file name
			if (substr($zip_file,-4) != '.'.$file_format) {
				continue;
			}

			# Check zipped file contents
			$content = $zip->unzip($zip_file);
				
			$target = path::fullFromRoot($zip_file,dirname($file));
				
			# Check existing files with same name
			if (file_exists($target)) {
				$zip->close();
				unset($content);
				throw new Exception(__('Another file with same name exists'));
			}
			
			# Extract backup content
			if (file_put_contents($target,$content) === false) {
				$zip->close();
				unset($content);
				throw new Exception(__('Failed to extract backup file'));
			}
				
			$zip->close();
			unset($content);
						
			# Return extracted file name
			return $target;
		}
		
		$zip->close();
		throw new Exception(__('No backup in compressed file'));
	}	

	/**
	* import email addresses from a file
	*/
	public static function importFromTextFile($infile = null)
	{
		global $core;
		try {
			$blog = &$core->blog;
			$blog_id = (string)$blog->id;
			$counter=0;
			$counter_ignore=0;
			$counter_failed=0;
			$tab_mail=array();
			
			$newsletter_settings = new newsletterSettings($core);
			$modesend = $newsletter_settings->getSendMode();
                
 			if (!empty($infile)){
        		//$core->error->add('Traitement du fichier ' . $infile['name']);
				files::uploadStatus($infile);
				$filename = $infile['tmp_name'];
			
				if(file_exists($filename) && is_readable($filename)) {
					$file_content = file($filename);

					foreach($file_content as $ligne) {
						$tab_mail=newsletterTools::extractEmailsFromString($ligne);
						
						foreach($tab_mail as $an_email) {
							$email = trim($an_email);
							if (!text::isEmail($email)) {
								$core->error->add(html::escapeHTML($email).' '.__('is not a valid email address'));
								$counter_failed++;
							} else {
								$regcode = newsletterTools::regcode();
								try {
								if(newsletterCore::add($email, $blog_id, $regcode, $modesend))
									$counter++;
								else
									$counter_ignore++;
								} catch (Exception $e) { 
									 $counter_ignore++;
								} 
							}
						}
					}
					
					# message de retour
					$res = sprintf(__('%d email inserted','%d emails inserted',$counter),$counter);
					$res .= ', '.sprintf(__('%d email ignored','%d emails ignored',$counter_ignore),$counter_ignore);
					$res .= ', '.sprintf(__('%d incorrect line','%d incorrect lines',$counter_failed),$counter_failed);					
					return $res;
				} else {
					throw new Exception(__('No file to read'));
				}
			} else {
				throw new Exception(__('No file to read'));
			}
		} catch (Exception $e) { 
			$core->error->add($e->getMessage()); 
		}
	}
    
	/**
	* formulaire d'adaptation de template
	*/
	public static function adaptTheme($theme = null)
	{
		if ($theme == null) 
			echo __('No template to adapt');
		else {
			global $core;
			try {
				$blog = &$core->blog;
				
				### Formulaire de souscription
				# fichier source
				$sfile = 'home.html';
				$source = $blog->themes_path.'/'.$theme.'/tpl/'.$sfile;
				
				# fichier de template
				$tfile = 'template.newsletter.html';
				$template = dirname(__FILE__).'/../default-templates/'.$tfile;
						
				# fichier destination
				$dest = $blog->themes_path.'/'.$theme.'/tpl/'.'subscribe.newsletter.html';
			
				if (!@file_exists($source)) {
					$msg = $sfile.' '.__('is not in your theme folder.').' ('.$blog->themes_path.')';
					$core->error->add($msg);
					return;
				} else if (!@file_exists($template)) {
					$msg = $tfile.' '.__('is not in the plugin folder.').' ('.dirname(__FILE__).')';
					$core->error->add($msg);
					return;
				} else if (!@is_readable($source)) {
					$msg = $sfile.' '.__('is not readable.');
					$core->error->add($msg);
					return;
				} else {
					# lecture du contenu des fichiers template et source
					$tcontent = @file_get_contents($template);
					$scontent = @file_get_contents($source);

					# definition des remplacements
					switch ($theme) {
						case 'noviny':
						{
							# traitement du theme particulier noviny
							$patterns[0] = '/<div id=\"overview\" class=\"grid-l\">[\S\s]*<div id=\"extra\"/';
							$replacements[0] = '<div class="grid-l">'. "\n" .'<div class="post">'. "\n" . $tcontent . "\n" .'</div>'. "\n" . '</div>'. "\n" .'<div id="extra"';
							$patterns[1] = '/<title>.*<\/title>/';
							$replacements[1] = '<title>{{tpl:NewsletterPageTitle encode_html="1"}} - {{tpl:BlogName encode_html="1"}}</title>';
							$patterns[2] = '/dc-home/';
							$replacements[2] = 'dc-newsletter';
							$patterns[3] = '/<meta name=\"dc.title\".*\/>/';
							$replacements[3] = '<meta name="dc.title" content="{{tpl:NewsletterPageTitle encode_html="1"}} - {{tpl:BlogName encode_html="1"}}" />';
							$patterns[4] = '/<div id=\"lead\" class="grid-l home-lead">[\S\s]*<div id=\"meta\"/';
							$replacements[4] = '<div id="lead" class="grid-l">'. "\n\t" .'<h2>{{tpl:NewsletterPageTitle encode_html="1"}}</h2>'. "\n\t" .'</div>'. "\n\t" . '<div id="meta"';
							$patterns[5] = '/<div id=\"meta\" class=\"grid-s\">[\S\s]*{{tpl:include src=\"inc_meta.html\"}}/';
							$replacements[5] = '<div id="meta" class="grid-s">'. "\n\t" .'{{tpl:include src="inc_meta.html"}}';
							$patterns[6] = '/<h2 class=\"post-title\">{{tpl:NewsletterPageTitle encode_html=\"1\"}}<\/h2>/';
							$replacements[6] = '';
							break;
						}
						case 'hybrid':
						{
							# traitement du theme particulier hybrid
							$patterns[0] = '/<div id=\"maincontent\">[\S\s]*<div id=\"sidenav\"/';
							$replacements[0] = '<div class="maincontent">'."\n".$tcontent."\n".'</div>'."\n".'</div>'."\n".'<div id="sidenav"';
							$patterns[1] = '/<title>.*<\/title>/';
							$replacements[1] = '<title>{{tpl:NewsletterPageTitle encode_html="1"}} - {{tpl:BlogName encode_html="1"}}</title>';
							$patterns[2] = '/dc-home/';
							$replacements[2] = 'dc-newsletter';
							$patterns[3] = '/<script type=\"text\/javascript\">[\S\s]*<\/script>/';
							$replacements[3] = '';
							$patterns[4] = '/<meta name=\"dc.title\".*\/>/';
							$replacements[4] = '<meta name="dc.title" content="{{tpl:NewsletterPageTitle encode_html="1"}} - {{tpl:BlogName encode_html="1"}}" />';
							$patterns[5] = '/<h2 class=\"post-title\">{{tpl:NewsletterPageTitle encode_html=\"1\"}}<\/h2>/';
							$replacements[5] = '<div id="content-info">'."\n".'<h2>{{tpl:NewsletterPageTitle encode_html="1"}}</h2>'."\n".'</div>'."\n".'<div class="content-inner">';
							$patterns[6] = '/<div id=\"sidenav\">[\S\s]*<!-- end #sidenav -->/';
							$replacements[6] = '<div id="sidenav">'."\n".'</div>'."\n".'<!-- end #sidenav -->';
							$patterns[7] = '/<tpl:Categories>[\S\s]*<link rel=\"alternate\"/';
							$replacements[7] = '<link rel=alternate"';
							break;
						}						
						default:
						{
							$patterns[0] = '/<tpl:Entries>[\S\s]*<\/tpl:Entries>/';
							$replacements[0] = $tcontent;
							$patterns[1] = '/<title>.*<\/title>/';
							$replacements[1] = '<title>{{tpl:NewsletterPageTitle encode_html="1"}} - {{tpl:BlogName encode_html="1"}}</title>';
							$patterns[2] = '/dc-home/';
							$replacements[2] = 'dc-newsletter';
							$patterns[3] = '/<meta name=\"dc.title\".*\/>/';
							$replacements[3] = '<meta name="dc.title" content="{{tpl:NewsletterPageTitle encode_html="1"}} - {{tpl:BlogName encode_html="1"}}" />';
							$patterns[4] = '/<tpl:Entries no_content=\"1\">[\S\s]*<\/tpl:Entries>/';
							$replacements[4] = '';
							break;
						}
					}

					$count = 0;
					$scontent = preg_replace($patterns, $replacements, $scontent, 1, $count);

					# suppression des lignes vides et des espaces de fin de ligne
					$a2 = array();
					$tok = strtok($scontent, "\n\r");
					while ($tok !== FALSE)
					{
						$l = rtrim($tok);
						if (strlen($l) > 0)
						    $a2[] = $l;
						$tok = strtok("\n\r");
					}
					$c2 = implode("\n", $a2);
					$scontent = $c2;

					# Writing new template file
					if ((@file_exists($dest) && @is_writable($dest)) || @is_writable($blog->themes_path)) {
                    	$fp = @fopen($dest, 'w');
                    	@fputs($fp, $scontent);
                    	@fclose($fp);
                    	$msg = __('Template created');
                	} else {
                		$msg = __('Unable to write file');
                	}
				}	

				### Liste des newsletters
                # fichier source
                $sfile = 'home.html';
                $source = $blog->themes_path.'/'.$theme.'/tpl/'.$sfile;
                	
                # fichier de template
                $tfile = 'template.newsletters.html';
				$template = dirname(__FILE__).'/../default-templates/'.$tfile;
                	
                # fichier destination
                $dest = $blog->themes_path.'/'.$theme.'/tpl/'.'newsletters.html';
		
                if (!@file_exists($template)) {
                	$msg = $tfile.' '.__('is not in the plugin folder.').' ('.dirname(__FILE__).')';
                	$core->error->add($msg);
                	return;
                } else {
	               	# lecture du contenu des fichiers template et source
                	$tcontent = @file_get_contents($template);
                	$scontent = @file_get_contents($source);
                	
                	# definition des remplacements
                	switch ($theme) {
	                	case 'noviny':
	                	{
	                		# traitement du theme particulier noviny
	                		$patterns[0] = '/<div id=\"overview\" class=\"grid-l\">[\S\s]*<div id=\"extra\"/';
	                		$replacements[0] = '<div class="grid-l">'. "\n" .'<div class="post">'. "\n" . $tcontent . "\n" .'</div>'. "\n" . '</div>'. "\n" .'<div id="extra"';
							$patterns[1] = '/<title>.*<\/title>/';
	                		$replacements[1] = '<title>{{tpl:lang Newsletters}} - {{tpl:BlogName encode_html="1"}}</title>';
							$patterns[2] = '/dc-home/';
							$replacements[2] = 'dc-newsletter';
							$patterns[3] = '/<meta name=\"dc.title\".*\/>/';
	                		$replacements[3] = '<meta name="dc.title" content="{{tpl:lang Newsletters}} - {{tpl:BlogName encode_html="1"}}" />';
							$patterns[4] = '/<div id=\"lead\" class="grid-l home-lead">[\S\s]*<div id=\"meta\"/';
	                		$replacements[4] = '<div id="lead" class="grid-l">'. "\n\t" .'<h2>{{tpl:lang Newsletters}}</h2>'. "\n\t" .'</div>'. "\n\t" . '<div id="meta"';
	                		$patterns[5] = '/<div id=\"meta\" class=\"grid-s\">[\S\s]*{{tpl:include src=\"inc_meta.html\"}}/';
	                		$replacements[5] = '<div id="meta" class="grid-s">'. "\n\t" .'{{tpl:include src="inc_meta.html"}}';
	                		$patterns[6] = '/<h2 class=\"post-title\">{{tpl:lang Newsletters}}<\/h2>/';
							$replacements[6] = '';
	                		break;
						}
						case 'hybrid':
						{
							# traitement du theme particulier hybrid
	                		$patterns[0] = '/<div id=\"maincontent\">[\S\s]*<div id=\"sidenav\"/';
	                		$replacements[0] = '<div class="maincontent">'."\n".$tcontent."\n".'</div>'."\n".'</div>'."\n".'<div id="sidenav"';
	                		$patterns[1] = '/<title>.*<\/title>/';
	                		$replacements[1] = '<title>{{tpl:lang Newsletters}} - {{tpl:BlogName encode_html="1"}}</title>';
	                		$patterns[2] = '/dc-home/';
							$replacements[2] = 'dc-newsletter';
							$patterns[3] = '/<script type=\"text\/javascript\">[\S\s]*<\/script>/';
	                		$replacements[3] = '';
							$patterns[4] = '/<meta name=\"dc.title\".*\/>/';
							$replacements[4] = '<meta name="dc.title" content="{{tpl:lang Newsletters}} - {{tpl:BlogName encode_html="1"}}" />';
	                		$patterns[5] = '/<h2 class=\"post-title\">{{tpl:lang Newsletters}}<\/h2>/';
							$replacements[5] = '<div id="content-info">'."\n".'<h2>{{tpl:lang Newsletters}}</h2>'."\n".'</div>'."\n".'<div class="content-inner">';
							$patterns[6] = '/<div id=\"sidenav\">[\S\s]*<!-- end #sidenav -->/';
	                		$replacements[6] = '<div id="sidenav">'."\n".'</div>'."\n".'<!-- end #sidenav -->';
							$patterns[7] = '/<tpl:Categories>[\S\s]*<link rel=\"alternate\"/';
	                		$replacements[7] = '<link rel=alternate"';
	                		break;
	                	}
						default:
						{
							$patterns[0] = '/<tpl:Entries>[\S\s]*<\/tpl:Entries>/';
							$replacements[0] = $tcontent;
	                		$patterns[1] = '/<title>.*<\/title>/';
	                		$replacements[1] = '<title>{{tpl:lang Newsletters}} - {{tpl:BlogName encode_html="1"}}</title>';
	                		$patterns[2] = '/dc-home/';
							$replacements[2] = 'dc-newsletter';
							$patterns[3] = '/<meta name=\"dc.title\".*\/>/';
	                		$replacements[3] = '<meta name="dc.title" content="{{tpl:lang Newsletters}} - {{tpl:BlogName encode_html="1"}}" />';
	                		$patterns[4] = '/<tpl:Entries no_content=\"1\">[\S\s]*<\/tpl:Entries>/';
							$replacements[4] = '';
							break;
						}
	                }
                	
	                $count = 0;
					$scontent = preg_replace($patterns, $replacements, $scontent, 1, $count);
	                
	                # suppression des lignes vides et des espaces de fin de ligne
	                $a2 = array();
	                $tok = strtok($scontent, "\n\r");
	                while ($tok !== FALSE)
	                {
	                	$l = rtrim($tok);
	                	if (strlen($l) > 0)
	                	$a2[] = $l;
	                	$tok = strtok("\n\r");
	                }
	                $c2 = implode("\n", $a2);
	                $scontent = $c2;
	                	
	                # Writing new template file
	                if ((@file_exists($dest) && @is_writable($dest)) || @is_writable($blog->themes_path)) {
	                	$fp = @fopen($dest, 'w');
	                	@fputs($fp, $scontent);
	                	@fclose($fp);
	                	$msg .= __('Template created').': '.$dest;
	                } else {
	                	$msg .= __('Unable to write file').': '.$dest;
	                }                	
                	
				}
				return $msg;
			} catch (Exception $e) { 
				$core->error->add($e->getMessage()); 
			}
		}
	}    
}

?>