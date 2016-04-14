<?php

class PstModuleCore extends Module
{
    const LIFETIME_HOUR     = 3600;
    const LIFETIME_DAY      = 86400;
    const LIFETIME_WEEK     = 604800;
    const LIFETIME_MOUTH    = 2678400;
    const LIFETIME_YEARS    = 31536000;

    const TAB_DEFAULT_ROOT = 0;
    const TAB_DEFAULT_NOWHERE = -1;

    const NOTICE_MESSAGE_INFO ='info';
    const NOTICE_MESSAGE_ERROR ='error';
    const NOTICE_MESSAGE_WARNING ='warning';
    const NOTICE_MESSAGE_SUCCESS ='success';
    /* @description configuration installer */
    protected $_config = array();
    protected $_field_types = null;

    /* @description Classes installer */
    protected $_classes_add = array();
    protected $_classes_override = array();
    protected $_controller_override_front = array();
    protected $_controller_override_admin = array();

    /* @description configuration admin button link */
    protected $_configuration_url = null;

    /* @description on uninstall don't remove meta from db */
    protected $_no_remove_meta = true;
    /* @description on uninstall don't remove module configuration from db */
    protected $_no_remove_config = false;
    /* @description on uninstall don't remove module database */
    protected $_no_db_install = true;

    /* @description Default template normayl ues */
    protected $_templateName = null;
    /* @description admin class controller to install */
    protected $_admin_class = null;
    /* @description hook list to install */
    protected $_hook_list = null;

    protected $flash_message = null;
    /**
     * @description reset related database on installation.
     * @var Bool
     */
    protected $_drop_db = false;


    /** @var Context */
    protected $context;

    /** @var Smarty_Data */
    protected $smarty;

    public function __construct()
    {
        $this->author = 'Prestashop Super Tool';
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->dependencies[] = 'pst';
        parent::__construct();
    }


    public function setContext($context)
    {
        $this->context = $context;
    }


    /**
     * Clear all cache for $this->templateName
     */
    protected function clearCache()
    {
        if(!is_null($this->_templateName))
        {
            if(is_string($this->_templateName))
                $this->_clearCache($this->_templateName);
            elseif(is_array($this->_templateName))
                foreach($this->_templateName as $templateName)
                    $this->_clearCache($templateName);
        }
    }

    /**
     * @param $configuration_url
     */
    public function setConfigurationUrl($configuration_url)
    {
        $this->_configuration_url = $configuration_url;
    }

    /**
     * @return string Admin controller configure url
     */
    public function getConfigurationUrl()
    {
        if(is_null($this->_configuration_url))
        {
            global $currentIndex;
            if ($currentIndex == '' && _PS_VERSION_ >= 1.5)
                $currentIndex = 'index.php?controller=' . Tools::safeOutput(Tools::getValue('controller'));

            $this->_configuration_url = $currentIndex . '&configure=' . $this->name . '&token=' . Tools::safeOutput(Tools::getValue('token'));
        }
        return $this->_configuration_url;

    }

    /**
     * @description remove meta information from ps_meta table
     * @param string $controller
     * @return bool
     */
    protected function removeMeta($controller='default'){
        if($this->_no_remove_meta)
            return true;
        $page_name = 'module-'.$this->name.'-' . $controller;
        $sql = 'DELETE FROM `'._DB_PREFIX_.'meta` WHERE `page` = "'.pSQL($page_name) .'"';
        $result = Db::getInstance()->execute($sql);
        return true;
    }

    /**
     * @param $title
     * @param $url_rewrite
     * @param $description
     * @param string $keywords
     * @param string $controller
     * @return bool
     */
    protected function installMeta($title, $url_rewrite,$description, $keywords='', $controller='default'){
        $page_name = 'module-'.$this->name.'-' . $controller;

        $sql = 'SELECT count(page) as nbr
            FROM `'._DB_PREFIX_.'meta` m
            WHERE m.`page` = "'.pSQL($page_name) .'"';
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        $result = (int)$result['nbr'];
        if($result==0)
        {
            $meta = new Meta();
            foreach(Language::getLanguages(true) as $lang) {
                $id_lang = (int)$lang['id_lang'];
                $meta->title[$id_lang] = $this->l($title, true, $id_lang);
                $meta->url_rewrite[$id_lang] = $this->l($url_rewrite, true, $id_lang);
                $meta->keywords[$id_lang] = $this->l($keywords, true, $id_lang);
                $meta->description[$id_lang] = $this->l($description, true, $id_lang);
            }
            $meta->page = $page_name;
            if($meta->save())
                return true;
        }
        return true;

    }



    /**
     * @description little function help to add error.
     * @param $error_message
     * @return false
     */
    public function _abortInstall($error_message)
    {
        $this->_errors[] = $error_message;
        return false;
    }


    /**
     * @description install model controller and override at the good place
     * @return bool
     * @deprecated
     */
    public function installLibs()
    {
        foreach ($this->_classes_add as $class) {
            $class = $class . '.php';
            if (!is_dir(_PS_CLASS_DIR_ . $this->name))
                mkdir(_PS_CLASS_DIR_ . $this->name);

            if (file_exists(_PS_CLASS_DIR_ . $class)) {
                if (!is_dir(_PS_CLASS_DIR_ . $class))
                    mkdir(_PS_CLASS_DIR_ . $class, 0777, true);
                copy(_PS_CLASS_DIR_ . '/classes/' . $class, $this->getLocalPath() . 'libs/backup/' . $class);
            }
            copy($this->getLocalPath() . 'libs/classes/' . $class, _PS_CLASS_DIR_ . $this->name . '/' . $class);
        }

        foreach ($this->_classes_override as $class) {
            $class = $class . '.php';
            if (file_exists(_PS_OVERRIDE_DIR_ . 'classes/'. $class)) {
                if (!is_dir($this->getLocalPath() . 'libs/backup/override/'))
                    mkdir($this->getLocalPath() . 'libs/backup/override', 0777, true);
                copy(_PS_OVERRIDE_DIR_ . 'classes/' . $class, $this->getLocalPath() . 'libs/backup/override/' . $class);
            }
            copy($this->getLocalPath() . 'libs/override/classes/' . $class, _PS_OVERRIDE_DIR_ . 'classes/' . $class);
        }


        foreach ($this->_controller_override_front as $class) {
            $class = $class . '.php';
            if (file_exists(_PS_OVERRIDE_DIR_ . 'controllers/front/'. $class)) {
                if (!is_dir($this->getLocalPath() . 'libs/backup/override/controllers/front/'))
                    mkdir($this->getLocalPath() . 'libs/backup/override/controllers/front', 0777, true);
                copy(_PS_OVERRIDE_DIR_ . 'controllers/front/' . $class, $this->getLocalPath() . 'libs/backup/override/controllers/front/' . $class);
            }
            copy($this->getLocalPath() . 'libs/override/controllers/front/' . $class, _PS_OVERRIDE_DIR_ . 'controller/front/' . $class);
        }

        foreach ($this->_controller_override_admin as $class) {
            $class = $class . '.php';
            if (file_exists(_PS_OVERRIDE_DIR_ . 'controllers/admin/'. $class)) {
                if (!is_dir( $this->getLocalPath() . 'libs/backup/override/controllers/admin/'))
                    mkdir( $this->getLocalPath() . 'libs/backup/override/controllers/admin', 0777, true);
                copy(_PS_OVERRIDE_DIR_ . 'controllers/admin/' . $class, $this->getLocalPath() . 'libs/backup/override/controllers/admin/' . $class);
            }
            copy($this->getLocalPath() . 'libs/override/controllers/admin/' . $class, _PS_OVERRIDE_DIR_ . 'controllers/admin/' . $class);
        }

        Autoload::getInstance()->generateIndex();
        return true;
    }


    /**
     * @description set Life time to smarty cache manager
     * @param $lifetime
     */
    public function setLifetime($lifetime)
    {
        $this->context->smarty->cache_lifetime = $lifetime; // 1 week
    }

    public function getCacheKey($templateName, $params=false)
    {
        $cache_id = $templateName;
        if($this->isPresta16())
            $cache_id = $this->getCacheId($templateName);
        else
            $cache_id .= '|id_lang|' . $this->context->language->id. '|';

        if($params)
            $extra = join('|',$params);
        else
            $extra = '';
        return $cache_id. $extra;
    }
    public function getPath()
    {
        return $this->_path;
    }



    /**
     * @description Pst Default installer
     * @return bool|false
     */
    public function install(){

        if($this->name != 'pst' && !Module::isInstalled('pst'))
            return $this->_abortInstall('Pst Tool Module need to be install');

        if(parent::install() == false)
            return $this->_abortInstall('Parent installer');

        if(!$this->installConfiguration())
            return $this->_abortInstall('Configuration installer failed');

        /* Depreciate
        if(!$this->installLibs())
            return $this->_abortInstall('Libs installer failed');
        */

        if(!is_null($this->_admin_class))
        {
            if(!$this->installTab($this->_admin_class, $this->displayName))
                return $this->_abortInstall('Tab invalid');
        }

        if(!$this->installHookList())
            return $this->_abortInstall('Hook list installer failed');


        return true;
    }

    /**
     * @description Pst Default uninstaller
     * @return bool|false
     */
    public function uninstall(){
        if(!$this->uninstallHookList())
            return $this->_abortInstall('Hook list uninstaller failed');

        if(!$this->uninstallConfiguration())
            return $this->_abortInstall('Hook list uninstaller failed');

        if(!parent::uninstall())
            return $this->_abortInstall('Parent uninstaller failed');

        return true;
    }


    /**
     * @description tools to quick register Hooks
     * @param $hooks
     * @return bool|false
     */
    protected function registerHooks($hooks)
    {
        foreach ($hooks as $hook)
            if (!$this->registerHook($hook))
            {
                return $this->_abortInstall($this->l('Unable register hook : ') . $hook);
            }
        return true;
    }

    /**
     * @description tools to quick remove Hooks
     * @param $hooks
     * @return bool|false
     */
    protected function unregisterHooks($hooks)
    {
        foreach ($hooks as $hook)
            if (!$this->unregisterHook($hook))
                return $this->_abortInstall($this->l('Unable remove hook : ') . $hook);
        return true;
    }
    /**
     * @description tools to quick register Hook list
     */
    private function installHookList(){
        if(is_null($this->_hook_list))
            return true;

        if(is_array($this->_hook_list))
        {
            return $this->registerHooks($this->_hook_list);
        }
        return false;
    }
    /**
     * @description tools to quick remove Hook list
     */
    private function uninstallHookList(){
        if(is_null($this->_hook_list))
            return true;
        if(is_array($this->_hook_list))
        {
            return $this->unregisterHooks($this->_hook_list);
        }
        return false;
    }


    /**
     * return formated array with module configuration
     * @return array
     */
    public function getPstFieldTypes()
    {
        if(is_null($this->_field_types))
            $this->_field_types = PstToolsHelperCore::getType();

        return $this->_field_types;
    }

    /**
     * @description configuration Tools install
     */
    protected function installConfiguration()
    {
        $field_types = $this->getPstFieldTypes();
        foreach ($this->_config as $fieldset => $fieldset_data)
        {
            if(isset($fieldset_data['fields']))
            {
                foreach ($fieldset_data['fields'] as $field_name => $data)
                {
                    if($field_types[$data['type']]['is_multi_lang'])
                        Configuration::updateValue($field_name, PstToolsHelper::multiLanger($data['default_value']));
                    else
                        Configuration::updateValue($field_name, $data['default_value']);
                }
            }
        }
        return true;
    }


    /**
     * @description configuration Tools uninstall
     */
    public function uninstallConfiguration()
    {
        if($this->_no_remove_config)
            return true;
        foreach ($this->_config as $fieldset => $fieldset_data)
        {
            foreach ($fieldset_data['fields'] as $field_name => $field_data)
            {
                Configuration::deleteByName($field_name);
            }
        }
        return true;
    }


    /**
     * @description configuration Tools update
     */
    protected function updateConfiguration($_config)
    {
        foreach ($_config as $key => $value) {

            Configuration::updateValue($key, $value);
        }
        return true;
    }

    protected function getTabIdParent($parent_class)
    {

        if($parent_class === self::TAB_DEFAULT_ROOT)
        {
            return self::TAB_DEFAULT_ROOT;
        }
        elseif($parent_class === self::TAB_DEFAULT_NOWHERE)
        {

            return self::TAB_DEFAULT_NOWHERE;
        }
        else
        {
            $parent = new Tab(Tab::getIdFromClassName($parent_class));
            $parent->active = true;
            $parent->save();
            return $parent->id;
        }
    }
    /**
     * @description install admin tab.
     * @param $admin_module_class
     * @param $parent_class string
     *     AdminCatalog  AdminParentOrders AdminParentCustomer AdminPriceRule AdminParentShipping AdminParentLocalization
     *     AdminParentModules AdminParentPreferences AdminTools AdminAdmin AdminParentStats AdminStock
     * @param $tabTitle string need to $this->l implemented in module
     * @return bool
     */
    protected function installTab($admin_module_class, $tabTitle=false, $parent_class='PstTools' )
    {

        if($tabTitle===false || empty($tabTitle))
        {
            $tabTitle = array();
            foreach (Language::getLanguages(false) as $lang)
                $tabTitle[(int)$lang['id_lang']]  = $this->l($this->displayName);
        }


        if (!$id_tab = Tab::getIdFromClassName($admin_module_class)) {
            $tab = new Tab();
            $tab->class_name = $admin_module_class;
        } else {
            $tab = new Tab((int)$id_tab);
            $tab->class_name = $admin_module_class;
        }

        if (is_string($tabTitle)) {
            foreach (Language::getLanguages(false) as $lang)
                $tab->name[(int)$lang['id_lang']] = $this->l($tabTitle);
        } elseif(is_array($tabTitle)) {
            $tab->name = $tabTitle;
        }
        else{
            var_dump($tabTitle); die;
        }


        $tab->active = true;
        $tab->module = $this->name;
        $tab->id_parent = $this->getTabIdParent($parent_class);


        @copy(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'logo.gif', _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 't' . DIRECTORY_SEPARATOR . $admin_module_class . '.gif');
        @copy(_PS_MODULE_DIR_ . $this->name . DIRECTORY_SEPARATOR . 'logo.png', _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 't' . DIRECTORY_SEPARATOR . $admin_module_class . '.gif');


        if (!$tab->save())
            return $this->_abortInstall($this->l('Unable to create the "' . $admin_module_class . '" tab'));



        if (!Validate::isLoadedObject($tab))
            return $this->_abortInstall($this->l('Unable to load the "' . $admin_module_class . '" tab'));

        return true;
    }

    protected function uninstallTab($admin_module_class)
    {
        /* Delete the 1-click upgrade Back-office tab */
        if ($id_tab = Tab::getIdFromClassName($admin_module_class)) {
            $tab = new Tab((int)$id_tab);
            $tab->delete();
        }
        return true;
    }

    /**
     * @param $message
     * @param PstModule::NOTICE_MESSAGE_$type
     */
    public function setSetupMessageNotice($message, $type=null){
        if(is_null($type))
            $type = PstModule::NOTICE_MESSAGE_INFO;

        if(!is_array($this->flash_message))
            $this->flash_message = array();

        $this->flash_message[] = array('type'=>$type, 'message'=>$message);

    }

    public function addImageType($newField){
        Db::getInstance()->Execute('
        IF NOT EXISTS( SELECT NULL
            FROM INFORMATION_SCHEMA.COLUMNS
           WHERE table_name = `'._DB_PREFIX_.'image_type`
             AND column_name = `'.$newField.'`)  THEN
        ALTER TABLE `'._DB_PREFIX_.'image_type` ADD `'.$newField.'` TINYINT(1)  UNSIGNED  NULL  DEFAULT NULL  AFTER `stores`;');


    }

    /**
     * Return list of all Message notice from module setup
     * @return array
     */
    public function getSetupMessageNotice(){
        if(!is_array($this->flash_message))
            $this->flash_message = array();

        return $this->flash_message;
    }

    /**
     * Display at the top of configuration form code
     * @return string
     */
    public function displaySetupMessageNotice(){
        $output = '';
        foreach($this->getSetupMessageNotice() as $message)
        {
            $output .= '<div id="'.$message['type'].'_block">'.$message['message'].'</div>';
        }
        return $output;
    }

    public function createImgDirectory($directory_name){

        if(!is_dir(_PS_IMG_DIR_ . $directory_name))
            mkdir(_PS_IMG_DIR_ . $directory_name, 0777,true);
    }

    static public function smartyRegisterFunction($smarty, $type, $function, $params) {
        /**
         * @var $smarty Smarty
         */
        if (!in_array($type, array('function', 'modifier')))
            return false;

        $smarty->registerPlugin($type,$function, $params);
    }
    public function createHook($hookName, $title=null, $description=null,$live_edit=0)
    {

        if(Db::getInstance()->getValue('
            SELECT count(`id_hook`)
            FROM `'._DB_PREFIX_.'hook`
            WHERE `name` = \''.pSQL($hookName).'\'') == '1')
            return true;


        if(is_null($description))
            $description = ucfirst($hookName);

        if(is_null($title))
            $title = ucfirst($hookName);



        $new_hook = new Hook();
        $new_hook->title = $title;
        $new_hook->description = $description;
        $new_hook->live_edit = $live_edit;
        $new_hook->name = $hookName;
        $new_hook->save();

        return true;
    }

    /**
     * return true if your prestashop version is prestashop 1.6
     * @return bool
     */
    public function isPresta16()
    {
        return PstToolsHelper::isPresta16();
    }


    /**
     * return formated array with module configuration
     * @return array
     */
    public function getPstConfig()
    {
        return $this->_config;
    }

}
