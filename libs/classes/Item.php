<?php
class ItemCore
{
    public $id;
    /** @var integer category ID */
    public $id_menu_item = null;

    /** @var integer category ID */
    public $id_menu = null;

    /** @var string Type of Element */
    public $type = null;

    /** @var string Url */
    public $link = null;

    /** @var array Title */
    public $title = null;

    /** @var array Title */
    public $name = null;

    /** @var array Level */
    public $level_depth = null;

    /** @var boolean Status for display */
    public $active = null;

    /** @var  integer category position */
    public $position = null;

    /** @var string Description */
    public $description = null;

    /** @var integer Parent category ID */
    public $id_parent = null;

    /** @var integer Element ID */
    public $id_cms = null;
    public $id_meta = null;
    public $id_category = null;
    public $id_cms_category = null;
    public $id_manufacturer = null;
    public $id_supplier = null;
    public $id_product = null;
    public $id_attachment = null;


    /** @var */
    public $external = null;
    public $anchor = null;
    public $has_anchor = null;

    public $member_only = null;
    public $guest_only = null;
    public $no_follow = null;

    public $new_window = null;
    public $css = null;
    public $lightbox = null;
    public $lightbox_width = null;
    public $lightbox_height = null;

    public $display_prod_cat_childs = null;
    public $display_cms_cat_childs = null;

    public $date_add = null;
    public $date_upd = null;
    /** @var CMSCategory */
    public $element = null;

    /** @var array Childrens */
    public $childrens = null;

    public $element_childrens = null;

    public $required = array('type');


    protected static $id_selected  = null;
    protected static $is_logged = null;
    protected static $class_link = false;
    protected static $id_lang = false;
    protected static $types = false;
    protected static $is_admin = false;
    protected static $_ssl_enable = null;

    public function __construct($datas = array())
    {
        $this->dataSetter($datas);
        $this->init();
    }

    public static function isSSL(){
        if(is_null(self::$_ssl_enable))
            self::$_ssl_enable = Configuration::get('PS_SSL_ENABLED');
        return self::$_ssl_enable;
    }
    public static function setIsLogged($is_logged) {
        self::$is_logged = $is_logged;
    }

    /**
     * @return LinkCore
     */
    public static function getClassLink() {
        if (self::$class_link === false)
            self::$class_link = new Link;
        return self::$class_link;
    }
    public static function getIdLang() {
        if (self::$id_lang === false)
            self::$id_lang = Context::getContext()->language->id;
        return self::$id_lang;
    }
    public function getTypes() {
        if (self::$types === false)
            self::$types = MenuItem::getTypes();
        return self::$types;
    }


    public function isAdmin() {
        return self::$is_admin;
    }

    public static function activeAdminMode()
    {
        self::$is_admin = true;
    }

    public function isSelected()
    {
        if($this->has_anchor)
            return false;

        if(isset($this->primary) && $this->type == self::$id_selected['type'] && $this->getId() == (int)self::$id_selected['id'])
            return true;
        return false;
    }


    public static function getSelected()
    {
        return self::$id_selected;
    }

    public static function setSelected($id_selected)
    {
        self::$id_selected = $id_selected;
    }
    public function getLightbox()
    {
        if($this->lightbox)
            return 'class="'.$this->getLightboxClass().'" ' .$this->getLightboxData();
    }
    public function getLightboxClass(){

        if($this->lightbox)
            return 'lightbox';
    }
    public function getLightboxData(){

        if($this->lightbox)
            return ' data-fancybox-height="'.$this->lightbox_height . '" data-fancybox-width="' . $this->lightbox_width .'" ';
    }

    public function getRel(){
        return ($this->no_follow)?' rel="nofollow"':'';
    }

    public function isAllowed()
    {
        if($this->member_only)
        {
            return self::$is_logged;
        }
        if($this->guest_only)
        {
            return !self::$is_logged;
        }

        return true;
    }

    /**
     * @param $datas
     * @throws PrestaShopExceptionCore
     */
    public function dataSetter($datas)
    {
        foreach ($datas as $key => $value) {
            if (is_null($this->{$key}))
                $this->{$key} = $value;
            else
                throw new PrestaShopExceptionCore('Data invalid ('.$key.')');
        }
    }

    /**
     * @param array $childrens
     */
    public function init()
    {
        $this->initElement();
        $this->extractElementInfo();
    }

    public function getClass($first=false, $last=false, $default=false)
    {
        $class = array();

        if($this->has_anchor)
        {
            $class[] = 'item_has_anchor';
        }
        if(is_string($default))
        {
            $class[] = $default;
        }
        if($first)
            $class[] = 'first';

        if($last)
            $class[] = 'last';

        if ($this->isSelected())
            $class[] = 'selected';

        if (!empty($this->css))
            $class[] = $this->css;

        if (count($class) >= 1)
            return ' class="' . implode(' ', $class) . '"';

        return '';

    }

    public function getId(){
        if(isset($this->primary) && $this->{$this->primary})
            return (int)$this->{$this->primary};
    }

    public function initElement()
    {
        $type_info = $this->getType($this->type);
        if(is_null($this->element))
        {
            if (isset($type_info['class'])) {
                $class = $type_info['class'];
                if ($class == false)
                    $this->element = false;
                elseif (class_exists($class, true)) {
                    $this->primary = $type_info['primary'];
                    $this->element = new $class($this->{$type_info['primary']}, self::getIdLang());
                }
            }
        }
    }
    private function getCMSPages($id_cms_category, $id_shop = false, $id_lang = false)
    {
        $id_shop = ($id_shop !== false) ? (int)$id_shop : (int)Context::getContext()->shop->id;
        $id_lang = $id_lang ? (int)$id_lang : (int)Context::getContext()->language->id;

        $sql = 'SELECT c.`id_cms`
			FROM `'._DB_PREFIX_.'cms` c
			INNER JOIN `'._DB_PREFIX_.'cms_shop` cs
			ON (c.`id_cms` = cs.`id_cms`)

			WHERE c.`id_cms_category` = '.(int)$id_cms_category.'
			AND cs.`id_shop` = '.(int)$id_shop.'
			AND c.`active` = 1
			ORDER BY `position`';

        return Db::getInstance()->executeS($sql);
    }

    public function extractElementInfo()
    {

        switch ($this->type) {
            case 'external':
                $this->link = $this->external;
                break;
        }
        if ($this->element) {
            switch ($this->type) {
                case 'cms_category':
                    $title = $this->element->name;
                    $this->link = $this->element->getLink();
                    if($this->display_cms_cat_childs && !self::isAdmin())
                    {
                        foreach (CmsCategory::getChildren($this->element->id, self::getIdLang()) as $children) {

                            $obj = new CmsCategory($children['id_cms_category'], self::getIdLang());
                            $child = new self(array(
                                'type' => 'cms_category',
                                'id_cms_category' => $obj->id,
                                'element' => $obj,
                                'display_cms_cat_childs' => $this->display_cms_cat_childs,
                            ));
                            $this->childrens[] = $child;
                        }

                        foreach($this->getCMSPages($this->element->id) as $page)
                        {
                            $obj = new CMS($page['id_cms'], self::getIdLang());
                            $child = new self(array(
                                'type' => 'cms',
                                'id_cms' => $obj->id,
                                'element' =>  $obj,
                                'display_cms_cat_childs' => $this->display_cms_cat_childs,
                            ));

                            $this->childrens[] = $child;
                        }
                    }

                    break;
                case 'product_category':
                    $title = $this->element->name;
                    $link = $this->element->getLink();
                    if($this->display_prod_cat_childs && !self::isAdmin())
                    {
                        /** @var $this->element CategoryCore */
                        foreach (Category::getChildren($this->element->id,self::getIdLang()) as $children) {

                            $obj = new Category($children['id_category'], self::getIdLang());
                            $child = new self(array(
                                'type' => 'product_category',
                                'id_category' => $obj->id,
                                'title' => $obj->name,
                                'link' => $obj->getLink(),
                                'display_prod_cat_childs' => $this->display_prod_cat_childs,
                            ));
                            $this->childrens[] = $child;
                        }
                    }
                    break;
                case 'product':
                    $link = $this->element->getLink();
                    $title = $this->element->name;
                    break;
                case 'internal':
                    $link = self::getClassLink()->getPageLink($this->element->page);
                    $title = $this->element->title;
                    break;
                case 'cms':
                    $link = self::getClassLink()->getCMSLink($this->element, null);
                    $title = $this->element->meta_title;
                    break;
                case 'manufacturer':
                    $link = $this->element->getLink();
                    $title = $this->element->name;
                    break;
                case 'supplier':
                    $link = $this->element->getLink();
                    $title = $this->element->name;
                    break;
                case 'attachment':
                    $link = self::getClassLink()->getPageLink('attachment', NULL, "id_attachment=".$this->id_attachment);
                    $title = $this->element->name;
                    break;
            }
        }
//        if(self::isSSL())
//            $this->link = str_replace('http://','https://',$this->link);

        if(empty($this->link))
        {
            $this->link = @$link;
        }

        if(empty($this->title))
        {
            $this->title = @$title;
        }

        if(is_array($this->title))
        {
            $this->title = $this->title[self::getIdLang()];
        }


        if($this->has_anchor)
            $this->link .= '#' . $this->anchor;
    }


    public function setType($type)
    {
        $types = $this->getTypes();
        if (isset($types[$type]))
            $this->type = $type;
        else {
            throw new Exception('Type of element "' . $type . '" not found');
        }
    }

    public function getType($type)
    {
        $types = $this->getTypes();
        if (isset($types[$type]))
            return $types[$type];
        else {
            throw new Exception('Type of element "' . $type . '" not found');
        }
    }


}
