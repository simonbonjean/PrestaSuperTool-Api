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


[Offical Web site](http://prestasupertool.com/en/accueil/1-pst-api.html)
