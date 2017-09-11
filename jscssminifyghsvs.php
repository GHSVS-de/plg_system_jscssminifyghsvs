<?php
/**
 * @package plugin.system jscssminifyghsvs for Joomla!
 * @version See jscssminifyghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2016, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license See jscssminifyghsvs.xml
 * @authorUrl http://www.ghsvs.de
 * @authorEmail jscssminifyghsvs @ ghsvs.de

 * This Joomla plugin is using a function getMinified() of https://github.com/promatik/PHP-JS-CSS-Minifier/blob/master/minifier.php provided under the following license:

  * JS and CSS Minifier 
  * version: 1.0 (2013-08-26)
  *
  * This document is licensed as free software under the terms of the
  * MIT License: http://www.opensource.org/licenses/mit-license.php
  *
  * Toni Almeida wrote this plugin, which proclaims:
  * "NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK."
  * 
  * This plugin uses online webservices from javascript-minifier.com and cssminifier.com
  * This services are property of Andy Chilton, http://chilts.org/
  *
  * Copyrighted 2013 by Toni Almeida, promatik.
 */
?>
<?php
defined('JPATH_BASE') or die;

use Joomla\Registry\Registry;

class PlgSystemJsCssMinifyGhsvs extends JPlugin
{

 protected $app;

 protected $autoloadLanguage = true;
 
 protected $urlcss = 'https://cssminifier.com/raw';
 
 protected $urljs = 'https://javascript-minifier.com/raw';
 
 protected $mediaversion;

 function __construct(&$subject, $config = array())
 {
  parent::__construct($subject, $config);
  $this->mediaversion = time();
 }
 
 public function onExtensionAfterSave($context, $table, $isNew)
 {
  if (
   $this->app->isAdmin()
   && $context == 'com_plugins.plugin'
   && !empty($table->params) && is_string($table->params)
   && strpos($table->params, '"jscssminifyghsvsplugin":"1"') !== false
   && $table->enabled
  ){

   $errors = $success = array();
   
   // First x characters output.
   $noSplit = $html = false;
   $max_length = 80;
   
   jimport('joomla.filesystem.file');
   jimport('joomla.filesystem.folder');
   
   // Get params while saving the new settings!
   $this->params = new Registry($table->params);

   $todo = array('js', 'css');
   
   // Backups?
   $bak_target = $this->params->get('bak_target', 0);
   
   // Collect backups in folder instead of 
   $bak_to_folder = ($bak_target && $this->params->get('bak_to_folder')) ? '/tmp/jscssminifyghsvs' : '';
   
   // Create and/or check backup folder.
   if ($bak_to_folder && !JFolder::create(JPATH_SITE . $bak_to_folder))
   {
    $errors[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_BAKFOLDER_ERROR', $bak_to_folder);
   }
   elseif ($bak_to_folder)
   {
    $success[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_BAKFOLDER_SUCCESS', $bak_to_folder);
   }
   
   if (!$errors)
   {
    foreach ($todo as $what)
    {
     // Count successfully minified files.
     $done = 0;
     $success[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_START', strtoupper($what));
     
     // Get all file sets.     
     $files = $this->params->get($what);
     if (empty($files) || !is_object($files))
     {
      $success[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_NO_FILES_ENTERED', strtoupper($what));
      continue;
     }
     
     // Get relevant online service url.
     $url = 'url' . $what;
     $url = $this->$url;
     
     foreach ($files as $key => $file)
     {
      // License
      $first_comment = '';
      
      $content = '';
      $collectComment = array();
      
      // No source entered or not activated.
      if (!($file->source = trim($file->source)) || !$file->active)
      {
       continue;
      }
      
      // $file_: Save also short path for messages.
      if (!JFile::exists( ($file->source = JPATH_SITE . ($file_ = $file->source)) ))
      {
       $errors[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_SOURCE_NOT_FOUND', $file_);
       continue;
      }
      
      // Read source.
      if (
       ($content = file_get_contents($file->source)) === false
       || !($content = trim($content))
      ){
       $errors[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_SOURCE_EMPTY', $file_);
       continue;
      }
      
      if ($this->params->get('preserve_first_comment', 0))
      {

       // Something to do here?
       // Strange behavior of online service with CSS. It preserves comments starting with /*!
       // But NOT with JavaScript!
       $type1 = (strpos($content, '/*') === 0) && !($what == 'css' && strpos($content, '/*!') === 0);
       if (
        $type1
        || ($what == 'js' && ($type2 = (strpos($content, '//') === 0)))
       ){
        
        // Thanks to ? (forgot it) for preg_match that detects also conditional JS-comments.
        $matched = preg_match('/^\/(?:\*(@(?:cc_on|if|elif|else|end))?.*?\*\/|\/[^\n]*)/s', $content, $match);
        
        if ($matched && $type1)
        {
         // Not a conditional comment.
         // substr: just a paranoia check
         $match[0] = JString::trim($match[0]);
         if (
          empty($match[1])
          && JString::substr($match[0], -3) != '@*/'
          && JString::substr($match[0], -2) == '*/'
         ){
          $first_comment = $match[0];
         }
        }
        elseif ($matched)
        {
         $chunksize = 5000;
         $input = JString::substr($content, 0, $chunksize);
         $input = str_replace("\r", "\n", $input);
         $input = preg_replace('/\n\n+/', "\n", $input);
         $input = explode("\n", $input);
         foreach ($input as $line)
         {
          if (!($line = JString::trim($line)))
          {
           continue;
          }
          if (strpos($line, '//') === 0)
          {
           $collectComment[] = $line;
          }
          else
          {
           break;
          }
         }
         $first_comment = implode("\n", $collectComment);
        }
        $first_comment = $first_comment . "\n";
       }
      } // preserve_first_comment

						// 2017-09 for fallback behavior.
						$content_tmp = $content;
						
      // Get minified target content from web service sending source content.
      if (!($content = self::getMinified($url, $content_tmp)))
      {
       $errors[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_RETURNED_EMPTY', $url, $file_);
							if ($this->params->get('fallback_' . $what, 0))
							{
								$content = $content_tmp;
								$errors[] = JText::_('PLG_SYSTEM_JSCSSMINIFYGHSVS_RETURNED_EMPTY_FALLBACK');
								unset($content_tmp);
							}
							else
							{
        continue;
							}
      }

      // Create target path plus "secure" file name if no target in file-set entered.
      if (!($file->target = trim($file->target)))
      {
       $ext = trim($this->params->get('extender_prefix_' . $what));
       $old = JFile::getExt($file_);
       $ext = ($ext ? $ext : '.min') . ($old ? '.' . $old : '');
       $file->target = JFile::stripExt($file_) . $ext;
      }
      
      // Absolute target path and save short path in $file->backup. For messages: $target_.
      $file->target = JPATH_SITE . ($file->backup = $target_ = $file->target);
 
      // Want a backup of already existing target file?
      if ($bak_target && JFile::exists($file->target))
      {
       // Create human readable timestamped backup file extension from target extension.
       // E.g. ".20160318_080112.js"
       $ext = '.' . date('Ymd_His') . (($ext = JFile::getExt($file->backup)) ? '.' . $ext : '');

       // Append new file extension to backup file path/name. 
       $file->backup = ($bak_to_folder ? $bak_to_folder : '') . JFile::stripExt($file->backup) . $ext;
       
       // JFile::write creates not existing folders. Easier than JFile::copy.
       if (
        ($targetContent = file_get_contents($file->target)) === false
        || !JFile::write(JPATH_SITE . $file->backup, $targetContent))
       {
        $errors[] = JText::sprintf(
         'PLG_SYSTEM_JSCSSMINIFYGHSVS_BACKUPFILE_NOT_WRITEABLE',
         $file->backup,
         $target_,
         $file_
        );
        continue;       
       }
       $success[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_BACKUPFILE_WRITTEN', $file->backup);
      } // end backups

      // Combine target content
      $first_comment .= $content;
      
      if (!JFile::write($file->target, $first_comment))
      {
       $errors[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_TARGET_NOT_WRITEABLE', $target_, $file_);
       continue;
      }
 
      ++$done;
      $success[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_TARGET_WRITTEN', $target_, $file_);
      
      // first x characters w\o first comment. And a link to new target.
      $success[] = JText::sprintf(
       'PLG_SYSTEM_JSCSSMINIFYGHSVS_TARGET_CONTENT',
       $max_length,
       $content = JHtmlString::truncate($content, $max_length, $noSplit, $html),
       $link = JUri::root(true) . $target_ . '?' . $this->mediaversion
      );
     
     } // foreach ($files as $key => $file)
     $success[] = JText::sprintf('PLG_SYSTEM_JSCSSMINIFYGHSVS_END', strtoupper($what), $done);
    } // foreach $todos as $what
   } // if (!$errors)
   if ($success)
   {
    $this->app->enqueueMessage(implode('<br />', $success), 'notice');
   }
   
   if ($errors)
   {
    $this->app->enqueueMessage(implode('<br />', $errors), 'error');
   }
  } // if $this->app->isAdmin and so on.
 }

 /**
 Thanks to https://github.com/promatik/PHP-JS-CSS-Minifier/blob/master/minifier.php
 */
 protected function getMinified($url, $content)
 {
  $postdata = array(
   'http' => array(
   'method'  => 'POST',
   'header'  => 'Content-type: application/x-www-form-urlencoded',
   'content' => http_build_query(array('input' => $content))));
  return trim(file_get_contents($url, false, stream_context_create($postdata)));
 }
}
