# Prestashop Super Tool

Extended API allow you to do quickly : 

* Generating thumbnail image on the fly.
  * Example
    * PHP : PstImage::cacheResize($path, $name, $height, $width, PstImage::METHOD_CROP);
    * Smarty : {imageResize path=$path name=$name with=150}
* Adding new smarty function with the method smartyRegisterFunction
  * Exemple : self::smartyRegisterFunction($this->context->smarty, 'function', 'imageResize', 'smartyImageResize');
* Easy installation of hooks thanks to a simple array
* Easy Installation tab in the navigation of backoffice
  * Exemple : $this->installTab('PstTools','Super Tools',self::TAB_DEFAULT_ROOT)
* A function that forces the reload class.
* Overloading the autoloader to include self class modules.

## API Exemple
### Automatic configuration 
```php
class PstBlog extends PstModule
{
    protected $_config = array(
        'general' => array(
            'label' => 'General',
            'fields' => array(
                'PST_PSTBLOG_GENERAL_BLOG_TITLE' => array(
                    'type' => 'text_lang',
                    'default_value' => '',
                    'label' => 'Blog title',
                    )
              )
          )
    );
}
```
### Automatic Hook linker
```php
protected $_hook_list = array('displayFooter','displayHeader');
```
### Hook creator 
```php
$this->createHook('CustomHook');
```
### Admin Tab creator 
```php
$this->installTab('AdminPstBlogConfig', "Super Blog", self::TAB_DEFAULT_ROOT);
```
### Meta information creator (SEO&URL) 
```php
$this->installMeta('Article detail', 'show-article', 'Show Articles', '', 'articledetail');
```
### Create img directory 
```php
$this->createImgDirectory('pstblog/ArticleCategory/cover');
```
### Cached automatic resize image
```php
PstImage::cacheResize($path, $fileName, 60,60);
```
### Smarty resize image 
```smarty
<img class="img-responsive" src="{imageResize name=$element->cover path=$element->cover_path width=500 height=500}" />
```

### Module cache enhanced 
```php
$this->setLifetime(self::LIFETIME_WEEK);
$smartyCacheId = $this->getCacheKey($tempalteName, $addictionalParams);
```

[Offical Web site](http://prestasupertool.com/en/accueil/1-pst-api.html)
