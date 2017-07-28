<?php
defined('JPATH_PLATFORM') or die;
class PlgSystemJscssminifyghsvsFormFieldAssetsghsvs extends JFormField
{
 protected $type = 'assetsghsvs';
 protected static $basePath = 'plg_system_jscssminifyghsvs';
 protected function getInput()
 {
  JHtml::_('stylesheet', self::$basePath . '/backend.css', array('relative' => true));
  JHtml::_('script', self::$basePath . '/backend.js', array('relative' => true));
  return '';
 }
}