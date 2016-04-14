<?php
/**
 * Created by Produweb
 * User: Simon Bonjean
 * Date: 24/07/13
 * Time: 14:30
 */

global $db_link;
if (file_exists(dirname(__FILE__).'/../../config/settings.inc.php'))
    include_once(dirname(__FILE__) . '/../../config/settings.inc.php');
$db_link = mysql_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_);
mysql_select_db(_DB_NAME_, $db_link);



if(!function_exists('getModuleClasses'))
{

    function isInstalled($module_name)
    {
        global $db_link;
        $sql = 'SELECT `id_module` FROM `'._DB_PREFIX_.'module` WHERE `name` = \''.$module_name.'\'';
        return (bool) mysql_query($sql, $db_link);
    }

    function getModuleClasses($module_dir = _PS_MODULE_DIR_,$sub='/libs/classes')
    {
        $classes = array();
        foreach (scandir($module_dir) as $file)
        {
            if (!in_array($file, array('.','..','index.php','.htaccess','.DS_Store')))
            {
                if($sub == '/libs/classes' && @is_dir($module_dir.$file.$sub))
                {
                    if(in_array($file, array('pst', 'pstmenumanager')) || isInstalled($file))
                        $classes = array_merge($classes, getModuleClasses($module_dir.$file.$sub));
                }
                elseif(strpos($module_dir,'/libs/classes'))
                {
                    $path = $module_dir.'/'.$file;

                    if(is_dir($path))
                    {
                        $classes = array_merge($classes, getModuleClasses($path,''));
                    }
                    elseif(substr($file, -4) == '.php')
                    {
                        $content = file_get_contents($path);
                        $pattern = '#\W((abstract\s+)?class|interface)\s+(?P<classname>'.basename($file, '.php').'(Core)?)'
                            .'(\s+extends\s+[a-z][a-z0-9_]*)?(\s+implements\s+[a-z][a-z0-9_]*(\s*,\s*[a-z][a-z0-9_]*)*)?\s*\{#i';
                        if (preg_match($pattern, $content, $m))
                        {
                            $path = 'modules/'. str_replace(_PS_MODULE_DIR_,'',$module_dir) .'/';
                            $classes[$m['classname']] = $path.$file;
                            if (substr($m['classname'], -4) == 'Core')
                                $classes[substr($m['classname'], 0, -4)] = '';
                        }
                    }
                }
            }
        }
        return $classes;
    }
}

$classes = getModuleClasses();
mysql_close($db_link);
return $classes;