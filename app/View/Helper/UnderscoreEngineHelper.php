<?php
App::uses('JsBaseEngineHelper', 'View/Helper');
App::uses('JqueryEngineHelper', 'View/Helper');
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');

/**
 * Underscore Engine Helper for JsHelper
 *
 * Provides Underscore specific JavaScript and
 * Underscore template utilities.
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
	 * Underscore template files.
	 *
	 * @var array
	 */
	protected $_templateExtensions = array('html', 'jst');
	
	/**
	 * Create an iteration over the current selection result.
	 *
	 * @param string $callback The function body you wish to apply during the iteration.
	 * @return string completed iteration
	 */
	public function each($callback) {
		return '_(' . $this->selection . ').forEach(function (element, index, list) {' . $callback . '});';
	}
	
	/**
	 * Plucks the provided propertyName from the elements of the current selection result.
	 * Returns an array with all the values of the targeted property.
	 *
	 * @param string $propertyName The property you wish to pluck from the HTMLElements
	 * @return string
	 */
	public function pluck($propertyName) {
		return '_(' . $this->selection . ').pluck("' . $propertyName . '");';
	}
	
	/**
	 * Sets the relative path from the webroot to
	 * the folder where the template files are stored.
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function setTemplateRoot($path) {
		$path = ltrim($path, '/\\');
		$root = WWW_ROOT . $path;
		
		$Folder	= new Folder($root);

		if (!$Folder->path || !$Folder->inPath(WWW_ROOT)) {
			$this->_templateRoot = null;
			return false;
		}
		
		$this->_templateRoot = $Folder;
		return true;
	}
	
	/**
	 * Returns a clone of the currently configured
	 * template root Folder instance.
	 *
	 * @return Folder|null
	 */
	public function getTemplateRoot() {
		if (!$root = $this->_templateRoot) {
			return null;
		}
		
		return clone $root;
	}
	
	/**
	 * Loads the Underscore templates inline into the
	 * window object under a object called 'jst', mapping
	 * paths to the underscore templates.
	 * 
	 * @param string $path
	 * @param boolean $evaluate scripts
	 * @param boolean $includeExt
	 * @return boolean|string
	 */
	public function loadTemplates($path = null, $evaluate = false, $includeExt = false) {
		if ($path && !$this->setTemplateRoot($path)) {
			return false;
		}
		if (!$Folder = $this->getTemplateRoot()) {
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
				// @codeCoverageIgnoreStart
				include $File->path;
				// @codeCoverageIgnoreEnd
				$template = ob_get_clean();
				$template = json_encode($template);
			}
			elseif ($template = $File->read()) {
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