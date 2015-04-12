# Yii Snippet Master
Snippet master is a simple yii extension for placing limited number of snippets (for example: banners).

### How to install

Just put the "snippetmaster" directory to yii extensions directory.

### How to include

You must update config file, add follow lines to components section:

```php
'snippetmaster'=>array(
    'class' => 'ext.snippetmaster.SnippetMaster',
),
```

### How to configure

Views directory contains snippets, which you want to use. If you want to use your own snippet, just put them on the views directory.
If you want to add a using limit to your snippet, edit the config file (Add a snippet title to the banners section, Set field 'maxCount')

Snippet master can number each snippet, that draw. Add placeholder PLACEHOLDER_BANNER_ID to the view file:
```html
<div id="firstBanner-PLACEHOLDER_BANNER_ID"> 
    <div>Click me once</div>
</div>
```

### How to run

Just call this:
```php
Yii::app()->snippetmaster->drawBanner('firstBanner', '10');
```
Or this:
```php
echo Yii::app()->snippetmaster->getDrawBanner('firstBanner', '10');
```

You can use percentage to call snippet master like this: 
```php
Yii::app()->snippetmaster->drawBanner('firstBanner', '50%');
```
displays 50% of the remaining amount.

### How to handle errors

Call:
```php
Yii::app()->snippetmaster->isErrors();
```
to check errors existing.

Call:
```php
Yii::app()->snippetmaster->getErrors();
```
to return an array with errors.
