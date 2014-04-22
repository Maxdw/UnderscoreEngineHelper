UnderscoreEngineHelper
======================

This CakePHP helper can help you generate some basic Underscore Javascript and
can be useful loading a small amount of your Underscore templates without requiring
further AJAX requests in order to fetch your templates seperately.

----------

Loading templates
---------

Organize your templates in a folder under your webroot `WWW_ROOT` and configure the
helper in your view file to look for this folder.

```
$this->Underscore->setTemplateRoot('templates');
```

Then `loadTemplates` will scan the configured folder for all files with a `.html` or `.jst`
extension and will produce a object under `window.jst` containing all of the templates
and their relative paths.

```
if($templates = $this->UnderscoreEngine->loadTemplates()) {
	echo $this->Html->scriptBlock($templates);
}
```

Example result:

```
window.jst = {};
window.jst['test'] = _.template('template content');
window.jst['subfolder/test'] = _.template('template content from a template under a subfolder of WWW_ROOT/templates');
```


Installation
---------
Drop UnderscoreEngineHelper.php in your application's app/View/Helper folder.
Tested for CakePHP 2.3 and above.