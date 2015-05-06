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

/**
* Utilisation:
*
* <img src="script.php?name=newsletter&strlen=5" alt="anti spam" />
*/

class Captcha
{
    var $font = 'comic.ttf';
    var $width = 170;
    var $height = 60;
    var $length = 0;
    var $img = null;
    var $code = '';
    var $colors = array();
    var $rgb_font = array(
        array('r' => 70, 'v' => 130, 'b' => 255),
        array('r' => 255, 'v' => 237, 'b' => 175),
        array('r' => 166, 'v' => 250, 'b' => 186),
        array('r' => 253, 'v' => 188, 'b' => 251),
        array('r' => 255, 'v' => 255, 'b' => 255)
        );
    var $offsetX = 15;
    var $offsetY = 10;
    var $size = 25;
    var $dstWidth = 0;
    var $dstHeight = 0;
    var $bkrgb = array('r' => 200, 'v' => 200, 'b' => 200);
    var $bkgradient = true;
    var $noise = false;
    var $type = 'png';

    var $filecode = '';    
    var $fileimg = '';
    
	/**
	* test de disponibilité de la librairie GD
	*/
    public static function isGD()
    {
        if (!function_exists('imagecreatetruecolor')) return false;
        else return true;
    }

	/**
	* constructeur de la classe
	*/
	public function __construct($_width, $_height, $_length)
	{
		if (!self::isGD()) 
			return;
        
		if (isset($_width) && !empty($_width)) 
			$this->dstWidth = (integer) $_width;
		
		if (isset($_height) && !empty($_height)) 
			$this->dstHeight = (integer) $_height;
		
		if (isset($_length) && !empty($_length)) 
			$this->length = (integer) $_length;

		# création de l'image
		$this->img = imagecreateTRUEcolor($this->width, $this->height);
		
		if(function_exists('imageantialias')) {
			imageantialias($this->img, 1);
		}

		# création du code
		$this->filecode = md5(uniqid());
	}

	public function getCodeFileName() {
		return $this->filecode;
	}
	
	/**
	* génération du code à saisir
	*/
	private function generateCode()
	{
		$string = 'ABCDEFGHJKLMNPRSTUVWXYZ23456789';
		$this->code = '';
		for ($i = 0; $i < $this->length; $i++) 
			$this->code .= $string[ mt_rand(0, strlen($string) - 1) ];
	}

	/**
	* prépare l'image en remplissant par une couleur
	*/
	private function prepareImg()
	{
		if (!self::isGD()) 
			return;
        
		$bk_color = imagecolorallocate($this->img, $this->bkrgb['r'], $this->bkrgb['v'], $this->bkrgb['b']);
		imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $bk_color);
	}

	/**
	* génère l'image
	*/
	private function generateImg()
	{
		if (!self::isGD()) 
			return;
        
		# on crée les couleurs (départ, finale et liste)
		$c1 = array(mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
		$c2 = array(mt_rand(150, 200), mt_rand(150, 200), mt_rand(150, 200));

		$this->colors = array();
		foreach ($this->rgb_font as $rgb) 
		{ 
			$this->colors[] = imagecolorallocate($this->img, $rgb['r'], $rgb['v'], $rgb['b']); 
		}

		if ($this->bkgradient) {
			# on crée l'image
			for ($i = 0; $i < $this->width; $i++)
			{
				$r = $c1[0] + $i * ($c2[0] - $c1[0]) / $this->width;
				$v = $c1[1] + $i * ($c2[1] - $c1[1]) / $this->width;
				$b = $c1[2] + $i * ($c2[2] - $c1[2]) / $this->width;
				$color = imagecolorallocate($this->img, $r, $v, $b);
				imageline($this->img, $i, 0, $i, $this->height, $color);
			}
		}
	}

	/**
	* écriture du code
	*/
	private function writeCode()
	{
		if (!self::isGD())
			return;
        
		$font = dirname(__FILE__).'/'.$this->font;
		for ($i = 0; $i < $this->length; $i++)
		{
			$col = imagecolorallocate($this->img, mt_rand(0, 120), mt_rand(0, 120), mt_rand(0, 120));
			imagettftext($this->img, mt_rand($this->size -2, $this->size + 2), mt_rand(-30, 30), $this->offsetX + $i * $this->width / 6, $this->offsetY + $this->height / 2, $col, $font, $this->code[$i]);
		}
	}

	/**
	* on rajoute du bruit sur l'image
	*/
	private function addNoise()
	{
		if (!self::isGD()) {
			return;
		} else if (!$this->noise) {
			return;
		} else { # on rajoute des petites lignes pour rendre un peu moins lisible
			for ($i = 0; $i < 8; $i++) 
			{ 
				imageline($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $this->colors[mt_rand(0, 4)]); 
			}
		}
	}
	
	/**
	* on finalise le dessin de l'image
	*/
	private function finalizeImg()
	{
		if (!self::isGD()) 
			return;

		# on dessine la bordure
		$noir = imagecolorallocate($this->img, 0, 0, 0);
		imageline($this->img, 0, 0, $this->width, 0, $noir);
		imageline($this->img, 0, 0, 0, $this->height, $noir);
		imageline($this->img, $this->width -1, 0, $this->width -1, $this->height, $noir);
		imageline($this->img, 0, $this->height -1, $this->width -1, $this->height -1, $noir);
	}

	/**
	* redimensionne l'image
	*/
	private function resizeImg()
	{
		if (!self::isGD()) 
			return;
        
		$nimg = imagecreateTRUEcolor($this->dstWidth, $this->dstHeight);
		imagecopyresampled($nimg, $this->img, 0, 0, 0, 0, $this->dstWidth, $this->dstHeight, $this->width, $this->height);
		imagedestroy($this->img);
		$this->img = $nimg;
	}

	/**
	* génère l'image
	*/
	public function generate()
	{
		if (!self::isGD()) 
			return;
            
		$this->generateCode();
		$this->prepareImg();
		$this->generateImg();
		$this->writeCode();
		$this->addNoise();
		$this->finalizeImg();
		$this->resizeImg();
		$this->writeImgCaptcha();
		$this->writeCodeFile();
	}

	/**
	* on affiche l'image
	*/
	public function header()
	{
		if (!self::isGD())
			return;
            
		switch ($this->type)
		{
			case 'jpg':
				header("Content-type: image/jpg");
				imagejpg($this->img, null, 80);
				break;

			case 'gif':
				header("Content-type: image/gif");
				imagegif($this->img);
				break;

			case 'png':
				header("Content-type: image/png");
				imagepng($this->img);
				break;
		}

		imagedestroy($this->img);
	}

	/**
	* url de génération des fichiers
	*/
	public static function newsletter_private_path()
	{
		global $core;
		$blog = &$core->blog;

		$newsletter_cache = DC_TPL_CACHE.'/newsletter';
		if (!file_exists($newsletter_cache)) {
			@mkdir($newsletter_cache);
		}
		if (!is_writable($newsletter_cache)) {
			throw new Exception('Failed to get temporary directory');
		}
		return $newsletter_cache;
	}

	public static function newsletter_public_path()
	{
		global $core;
		$blog = &$core->blog;
		
		$newsletter_public_cache = $blog->public_path.'/newsletter'; 
		if (!file_exists($newsletter_public_cache)) {
			@mkdir($newsletter_public_cache);
		}
		if (!is_writable($newsletter_public_cache)) {
			throw new Exception('Failed to get temporary directory');
		}
		
		return $newsletter_public_cache;
	}
	
	
	/**
	* url de génération des fichiers
	*/
	public static function newsletter_public_url()
	{
		global $core;
		return $core->blog->settings->system->public_url.'/newsletter';
	}

	/**
	* on affiche l'image
	*/
	public function writeImgCaptcha()
	{
		if (!self::isGD())
			return;
                    
		$this->fileimg = self::newsletter_public_path().'/'.md5(uniqid());
		switch ($this->type)
		{
			case 'jpg':
				$this->fileimg .= '.jpg';
				imagejpg($this->img, $this->fileimg, 80);
				break;

			case 'gif':
				$this->fileimg .= '.gif';
				imagegif($this->img, $this->fileimg);
				break;

			case 'png':
				$this->fileimg .= '.png';
				imagepng($this->img, $this->fileimg);
				break;
		}
		imagedestroy($this->img);
	}
	
	public function getImgFileName() {
		return basename($this->fileimg);
	}

	public static function deleteImgCaptcha($imgFileName) {
		if(file_exists(self::newsletter_public_path().'/'.$imgFileName))
			@unlink(self::newsletter_public_path().'/'.$imgFileName);
	}
	
	/**
	* écrit le code dans un fichier du cache
	*/
	public function writeCodeFile()
	{
		@file_put_contents(self::newsletter_private_path().'/'.$this->filecode, $this->code);
		self::cleanOldFilesInCache();
	}

	public function cleanOldFilesInCache()
	{
		$expires = '600'; # clean files after 10min
		$dirCache = opendir(self::newsletter_private_path());
		while(false !== ($curFileName = readdir($dirCache)))
		{
			$curFilePath = self::newsletter_private_path()."/".$curFileName;
			$infos = pathinfo($curFilePath);
			
			$timeCurFile = time() - filemtime($curFilePath);
			
			if($curFileName!="." && $curFileName!=".." && !is_dir($curFileName) && $timeCurFile > $expires)
			{
				unlink($curFilePath);
			}			
		}
		closedir($dirCache);
	}	
	
	/**
	* lit le fichier du cache
	*/
	public static function readCodeFile($codeFileName)
	{
		$content = @file_get_contents(self::newsletter_private_path().'/'.$codeFileName);
		return (($content === FALSE) ? null : $content);
	}
	
	public static function deleteCodeFile($codeFileName)
	{
		if(file_exists(self::newsletter_private_path().'/'.$codeFileName))
			@unlink(self::newsletter_private_path().'/'.$codeFileName);
	}	
}

?>