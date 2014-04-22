<?php
App::uses('View', 'View');
App::uses('UnderscoreEngineHelper', 'View/Helper');
App::uses('String', 'Utility');

class UnderscoreEngineHelperTest extends CakeTestCase {
	
	/**
	 * Test folder prefix
	 *
	 * @var string
	 */
	protected $_testFolderPrefix = 'UnderscoreEngineHelperTest';
	
	/**
	 * Sets up a test file inside a new test folder in WWW_ROOT
	 * using $_testFolderPrefix.
	 *
	 * @return File
	 */
	public function setUpTestFile($content) {
		$uuid = $this->_testFolderPrefix . '_' . String::uuid();
		
		$path = WWW_ROOT . $uuid . DS;
		$filename = "test.jst";
		
		$folder = new Folder($path, true);
		
		$this->skipUnless($folder->path, 'Test file folder could not be made: ' . implode('', $folder->errors()));
		
		$file = new File($path . $filename);
		$file->create();
		
		$this->skipUnless($file->write($content), 'Test file write error, check permissions of ' . WWW_ROOT);
		$this->skipUnless($file->exists(), 'Test file could not be found');
		$this->skipUnless($file->readable(), 'Test file could not read, check permissions of ' . WWW_ROOT);
		
		return $file;
	}
	
	/**
	 * Is called before each and every test to set things up.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$controller = null;
		$this->View = $this->getMock('View', null, array(&$controller));
		$this->Underscore = new UnderscoreEngineHelper($this->View);
		clearstatcache();
	}
	
	/**
	 * Is called after each and every test to break things down.
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->Underscore);
		
		$paths = glob("{$this->_testFolderPrefix}*", GLOB_ONLYDIR);
		$filesystem = new Folder();
		
		array_map(array($filesystem, 'delete'), $paths);	
		
		parent::tearDown();
	}
	
	/**
	 * test Each method
	 *
	 * @return void
	 */
	public function testEach() {
		$this->Underscore->get('#foo');
		$result = $this->Underscore->each('$(element).hide();');
		$expected = '_($("#foo")).forEach(function (element, index, list) {$(element).hide();});';
		$this->assertEquals($expected, $result);
	}
	
	/**
	 * test Pluck method
	 *
	 * @return void
	 */
	public function testPluck() {
		$this->Underscore->get('#foo');
		$result = $this->Underscore->pluck('id');
		$expected = '_($("#foo")).pluck("id");';
		$this->assertEquals($expected, $result);
	}
	
	/**
	 * Tests setting and getting the template root folder
	 *
	 * @covers UnderscoreEngineHelper::setTemplateRoot
	 * @covers UnderscoreEngineHelper::getTemplateRoot
	 * @return void
	 */
	public function testTemplateRoot() {
		$this->assertTrue($this->Underscore->setTemplateRoot('js'));
		$this->assertInstanceOf('Folder', $this->Underscore->getTemplateRoot());
		$this->assertFalse($this->Underscore->setTemplateRoot('js/non-existing-folder'));
		$this->assertNull($this->Underscore->getTemplateRoot());
	}
	
	/**
	 * Tests loading the templates into a javascript object
	 *
	 * @covers UnderscoreEngineHelper::loadTemplates
	 * @return void
	 */
	public function testLoadTemplates() {	
		$file = $this->setUpTestFile("<p>test</p><?php echo 'stdout'; ?>");
		$dirname = basename($file->Folder->path);
		
		$this->Underscore->setTemplateRoot($dirname);
		
		$result = $this->Underscore->loadTemplates();
		$expected = 'window.jst = {};' . PHP_EOL . 'window.jst[\'test\'] = _.template("<p>test<\/p><?php echo \'stdout\'; ?>");';
		$this->assertEquals($expected, $result);
		
		$result = $this->Underscore->loadTemplates(null, true);
		$expected = 'window.jst = {};' . PHP_EOL . 'window.jst[\'test\'] = _.template("<p>test<\/p>stdout");';
		$this->assertEquals($expected, $result);
		
		$result = $this->Underscore->loadTemplates(null, true, true);
		$expected = 'window.jst = {};' . PHP_EOL . 'window.jst[\'test.jst\'] = _.template("<p>test<\/p>stdout");';
		$this->assertEquals($expected, $result);
		
		$result = $this->Underscore->loadTemplates($dirname, false, true);
		$expected = 'window.jst = {};' . PHP_EOL . 'window.jst[\'test.jst\'] = _.template("<p>test<\/p><?php echo \'stdout\'; ?>");';
		$this->assertEquals($expected, $result);
		
		$result = $this->Underscore->loadTemplates('assumablynonexistantdirname36485121695');
		$this->assertFalse($result);
	}
	
}
?>