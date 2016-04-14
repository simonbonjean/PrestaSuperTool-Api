<?php
/**
 * User: Simon
 * Date: 25/11/15
 * Time: 11:05
 */

class Tools extends ToolsCore{
    public static function getPath($id_category, $path = '', $link_on_the_item = false, $category_type = 'products', Context $context = null)
    {
        if (ConfigurationCore::get('PST_DISPLAY_LAST_BREADCRUMB') == false)
            $path = '';
        return parent::getPath($id_category, $path, $link_on_the_item, $category_type, $context);
    }
}