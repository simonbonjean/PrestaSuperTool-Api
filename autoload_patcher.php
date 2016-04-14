<?php
/**
 * Created by Produweb
 * User: Simon Bonjean
 * Date: 24/07/13
 * Time: 17:02
 */

if (!defined('_PS_VERSION_'))
    exit;

$file = 'Autoload.php';
$fileName = _PS_CLASS_DIR_ . $file;

$search = "\$this->getClassesFromDir('override/controllers/')";
$replace = "\$this->getClassesFromDir('override/controllers/'),
			include _PS_MODULE_DIR_ . 'pst/modulesClasses.php'";


$include = 'modulesClasses';


if(!file_exists($fileName))
{
    // Maybe 1.6
    $include = 'modulesClasses16';
    $file = 'PrestaShopAutoload.php';
    $fileName = _PS_CLASS_DIR_ . $file;
    $search = "\$this->getClassesFromDir('controllers/')";
    $replace = "\$this->getClassesFromDir('controllers/'),
			    include _PS_MODULE_DIR_ . 'pst/".$include.".php'";
    if(!file_exists($fileName))
    {
        die('PST Module can’t find Autoload to fix it');
    }
}

$content = file_get_contents($fileName);
if(strpos($content, "include _PS_MODULE_DIR_ . 'pst/".$include.".php") !== false)
{
    unlink(_PS_CACHE_DIR_ . 'class_index.php');
    die('PST Module already write /classes/Autoload.php file');
}



if(!@file_put_contents($fileName,str_replace($search, $replace, $content)))
{
    die('PST Module need to edit /classes/' .$file. ' file');
}
else{
    unlink(_PS_CACHE_DIR_ . 'class_index.php');
    die('PST Module patch /classes/' .$file. '');
}