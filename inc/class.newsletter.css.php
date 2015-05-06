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

if (!defined('DC_CONTEXT_ADMIN')){return;}

class newsletterCSS
{
	protected $core;
	protected $blog;
	protected $file_css;
	protected $path_css;
	protected $f_content;
	protected $f_name;
	
	public function __construct(dcCore $core,$file_css='style_letter.css')
	{
		$this->core = $core;
		$this->blog = $core->blog;
		$this->file_css = $file_css;
		$this->setPathCSS();
		$this->f_content = '';
		$this->f_name = $this->path_css.'/'.$this->file_css;
		$this->readFileCSS();
	}	
	
	public function setLetterCSS($new_content) 
	{
		$this->f_content = $new_content;
		return($this->writeFileCSS());
	}

	public function getLetterCSS()
	{
		return $this->f_content;
	}	

	public function getFilenameCSS()
	{
		return $this->path_css.'/'.$this->file_css;
	}

	private function setPathCSS()
	{
		$this->path_css = newsletterTools::requestPathFileCSS($this->core,$this->file_css);
	}	
	
	public function getPathCSS()
	{
		return $this->path_css;
	}	
	
	public function isEditable() 
	{
		if (!is_file($this->f_name) || !file_exists($this->f_name) || 
			!is_readable($this->f_name) || !is_writable($this->f_name)) {
			return false;
		} else {
			return true;
		}
	}
		
	private function readFileCSS() 
	{
		if($this->isEditable()) {
			# lecture du fichier et test d'erreur
			$this->f_content = @file_get_contents($this->f_name);
		}		
	}
	
	private function writeFileCSS() 
	{
		try
		{
			$fp = @fopen($this->path_css.'/'.$this->file_css,'wb');
			if (!$fp) {
				throw new Exception('tocatch');
			}
			$content = preg_replace('/(\r?\n)/m',"\n",$this->f_content);
			fwrite($fp,$content);
			fclose($fp);
		}
		catch (Exception $e)
		{
			throw new Exception(sprintf(__('Unable to write file %s. Please check your theme files and folders permissions.'),$f));
		}		
		return __('Document saved');
	}	

	public static function copyFileCSSToTheme($theme = null, $file_css='style_letter.css')
	{
		if ($theme == null) {
			echo __('No template selected');
		} else {
			global $core;
			try {
				$blog = &$core->blog;		

				# source
				$source = path::real(newsletterPlugin::folder().'..').'/'.$file_css;
				
				# fichier destination
				$dest = $blog->themes_path.'/'.$theme.'/'.$file_css;
				
				if (!copy($source, $dest)) {
					throw new Exception('copy fail');
				} else {
					return 'copy success';
				}
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}
	}
}

?>