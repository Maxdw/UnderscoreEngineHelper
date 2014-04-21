<?php
App::uses('JsBaseEngineHelper', 'View/Helper');
App::uses('JqueryEngineHelper', 'View/Helper');
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');

/**
 * Underscore Engine Helper for JsHelper
 *
 * Provides Underscore specific Javascript for JsHelper.
 */
class UnderscoreEngineHelper extends JqueryEngineHelper {
	
	/**
	 * Root folder where underscore template
	 * files may be stored.
	 *
	 * @var Folder
	 */
	protected $_templateRoot = null;
	
	/**
	 * File extensions to parse as possible
	 * underscore template files.
	 *
	 * @var array
	 */
	protected $_templateExtensions = array('html', 'jst');
	
	/**
	 * Helper dependencies
	 *
	 * @var array
	 */
	public $helpers = array('Html');
	
	/**
	 * Sets the relative path from the webroot
	 * to the folder where the template files are
	 * stored and optionally provide extenions to
	 * look for.
	 *
	 * @param string $path
	 * @param array $extensions
	 * @return boolean
	 */
	public function setTemplateRoot($path, $extensions = array()) {
		$extensions = array_map('is_string', $extensions);
		$path = ltrim($path, '/\\');
		$root = WWW_ROOT . $path;
		
		$Folder	= new Folder($root);

		if (!$Folder->path || !$Folder->inPath(WWW_ROOT)) {
			return false;
		}
		
		$this->_templateRoot = $Folder;
		return true;
	}
	
	/**
	 * Loads the templates inline into the window object
	 * under a object called 'jst', mapping paths
	 * to the underscore templates.
	 * 
	 * @param string $path
	 * @param boolean $evaluate scripts
	 * @param boolean $includeExt
	 * @return boolean|string
	 */
	public function loadTemplates($path = null, $evaluate = false, $includeExt = false) {
		if (is_string($path) and !$this->setTemplateRoot($path)) {
			return false;
		}
		
		$Folder = $this->_templateRoot;
		
		if (!$Folder || !$Folder->path || !file_exists($Folder->path)) {
			return false;
		}
		
		$pattern = '.*';
		
		if ($this->_templateExtensions) {
			$pattern = '.*\.(' . implode('|', $this->_templateExtensions) . ')';
		}
		
		$paths = $Folder->findRecursive($pattern, true);
		$namespace = "jst";
		$templates = "window.{$namespace} = {};" . PHP_EOL;

		$root_length = strlen($Folder->path);
		
		foreach ($paths as $path) {
			$File = new File($path);
			
			if (!$File->readable()) {
				continue;
			}
			
			$path_length = strlen($path) - $root_length;
			
			if (!$includeExt) {
				$extension = strrchr($path, '.');
				$extension_length = strlen($extension);
				$path_length -= $extension_length;
			}
			
			$relative = substr($path, $root_length, $path_length);
			$relative = ltrim($relative, DS);
			
			$template = "''";
			
			if ($evaluate) {
				ob_start();
				include $File->path;
				$template = ob_get_clean();
				$template = json_encode($template);
			}
			else if ($template = $File->read()) {
				$template = json_encode($template);
			}
			
			$relative = str_replace('\\', '/', $relative);
			$templates .= "window.{$namespace}['{$relative}'] = _.template({$template});" . PHP_EOL;
		}
		
		$templates = rtrim($templates, PHP_EOL);
		
		return $templates;
	}
	
}
?>