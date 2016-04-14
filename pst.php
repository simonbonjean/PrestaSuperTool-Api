<?php

/**
 * Protect your templates
 *
 * @category Prestashop
 * @category Module
 * @author Samdha <contact@samdha.net>
 * @copyright Samdha
 * @license http://www.opensource.org/licenses/osl-3.0.php Open-source licence 3.0
 * @version 1.0
 * @license logo http://www.gnu.org/copyleft/gpl.html GPL
 * @see logo http://icones.pro/diabolique-visage-image-png.html
 * */


if (!defined('_PS_VERSION_'))
    exit;

if (!class_exists('PstModule', true))
{
    include dirname(__FILE__) . '/autoload_patcher.php';
}


class Pst extends PstModule
{
    protected $_config=array(
        'general'=>array(
            'label' => 'General',
            'fields' => array(
                'PST_DISPLAY_NO_MY_ACCOUNT'=>array(
                    'type'=>'bool',
                    'default_value'=>false,
                    'label'=>'Disabled my account page',
                    'desc'=>'redirect to history',
                ),
                'PST_DISPLAY_LAST_BREADCRUMB'=>array(
                    'type'=>'bool',
                    'default_value'=>false,
                    'label'=>'Display last breadcrumb',
                    'desc'=>'Avoid title duplication',
                )
            )
        ),
        'javascript'=>array(
            'label' => 'JavaScript library',
            'fields' => array(
                'PST_CONFIG_LIGHTBOX_EVERYWHERE'=>array(
                    'type'=>'bool',
                    'default_value'=>true,
                    'label'=>'Light box everywhere',
                ),
                'PST_CONFIG_JS_PLACEHOLDER_FALLBACK'=>array(
                    'type'=>'bool',
                    'default_value'=>true,
                    'label'=>'Place holder fallback',
                ),
                'PST_CONFIG_JS_MINITIP'=>array(
                    'type'=>'bool',
                    'default_value'=>true,
                    'label'=>'Mini tip',
                ),
            )
        ),
        'controller'=>array(
            'label' => 'List Controller',
            'fields' => array(),
        )
    );

    function __construct()
    {
        $exculde = array('pagenotfound', 'index');
        foreach(Meta::getMetas() as $meta){
            if(!in_array($meta['page'], $exculde))
                $this->_config['controller']['fields']['PST_CONTROLLER_'.strtoupper(str_replace('-', '_',$meta['page']))] = array(
                    'type'=>'bool',
                    'label'=>ucfirst(str_replace('-',' ', $meta['page'])),
                    'default_value'=>true
                );
        }
        $this->name = 'pst';
        $this->tab = version_compare(_PS_VERSION_, '1.4.0.0', '<') ? 'Tools' : 'administration';
        $this->version = '0.1.4';


        parent::__construct();

        $this->_hook_list = array('header','controllerConstruct','backOfficeHome', 'displayBackOfficeFooter');
        /* The parent construct is required for translations */
        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('Tools');
        $this->description = $this->l('A set of tools by PrestaSuperTool');
        $this->_html = '';
    }


    public function install()
    {
        $this->createHook('controllerConstruct', 'Controller construct','Exec before every hook');

        if(parent::install() === false)
        {
           return $this->_abortInstall('Parent installer');
    	}

        if (!$this->installTab('PstTools','Super Tools',self::TAB_DEFAULT_ROOT)) {
            return false;
        }

        return true;
    }

    public function checkControllerActive(){
        if(Configuration::get('PST_CONTROLLER_'. strtoupper(str_replace('-','_',$this->context->controller->php_self))) === '0')
            Tools::redirect('index.php?controller=404');
    }

    public function hookControllerConstruct(){
        $this->checkControllerActive();
        $this->registerImageResizeSmarty();
    }
    public function loadLightBox(){
        if(Configuration::get('PST_CONFIG_LIGHTBOX_EVERYWHERE')) {
            $this->context->controller->addJqueryPlugin(array('fancybox'));
        }
    }
    public function changeSessionTime(){
        /*if(_PS_MODE_DEV_)
            $this->context->cookie->setExpire(3600*24);*/
    }

    public function registerImageResizeSmarty(){
        if(!function_exists('smartyImageResize'))
        {
            function smartyImageResize($params,&$smarty){
                $path   = @$params['path'];
                $name   = @$params['name'];
                $height = @$params['height'];
                $width  = @$params['width'];
                $customPath  = @$params['customPath'];
                $method = isset($params['method'])?$params['method']:PstImage::METHOD_RESIZE;
                if($customPath)
                {
                    return PstImage::cacheResizeCustomPath($path, $name,$height, $width,$method);
                }
                else
                {
                    return PstImage::cacheResize($path, $name,$height, $width,$method);
                }
            }
            self::smartyRegisterFunction($this->context->smarty, 'function', 'imageResize', 'smartyImageResize');
        }
    }


    public function _installDb(){
        $query = '
		CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'pst_image_type (
			`id_pst_image_type` int(10) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(60) NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
			`position` int(10) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY(`id_pst_image_type`)
		) ENGINE=MyISAM default CHARSET=utf8';
        if (!Db::getInstance()->Execute($query))
            return true;

        return true;
    }
    public function uninstall()
    {
        return parent::uninstall();
    }

    public function _displayPstDescription()
    {
        return '<div class="admin-box1" style="width: 100%; margin-top: 15px;">
          <h5>
            <img src="' . _MODULE_DIR_ . $this->name . '/logo.gif" /> ' . $this->l('Prestashop Super Tool') . '
          </h5>
          <div class="pst-box-content" style="padding: 0px 5px 0px 15px;font-family: Georgia, Lucida Grande, Arial;font-style: italic;font-size: 12px;line-height: 14px; color: #484848;">
          <p>

          </div>
        </div>';
    }



    public function getContent()
    {
        header('Location: index.php?controller=PstTools&token='.md5(pSQL(_COOKIE_KEY_.'PstTools'.(int)Tab::getIdFromClassName('PstTools').(int)$this->context->employee->id)));
        exit;
    }
    public function hookBackOfficeHome()
    {
    }

    public function hookDisplayBackOfficeFooter(){
        return '
        <style>
        .icon-PstTools:before
        {
            content: "\\f135";
            display: block;
        }
        </style>';
    }


    public function hookHeader()
    {
        if(Configuration::get('PST_CONFIG_JS_MINITIP')) {

            $this->context->controller->addJs($this->_path . 'js/jquery.minitip.js');
            $this->context->controller->addCss($this->_path . 'js/jquery.minitip.css', 'all');
        }

//        $this->context->controller->addJs($this->_path . 'js/stepper.js', 'all');
//        $this->context->controller->addJs($this->_path.'js/jquery.livequery.js');
//        $this->context->controller->addJs($this->_path . 'js/jquery.uniform.js');
//        $this->context->controller->addCss($this->_path . 'uniform.default.css');
//        $this->context->controller->addJs($this->_path.'js/ieversion.js');

        if(Configuration::get('PST_CONFIG_JS_PLACEHOLDER_FALLBACK'))
        {
            $this->context->controller->addJs($this->_path . 'js/modernizr.js', 'all');
            $this->context->controller->addJs($this->_path . 'js/placeholder.js', 'all');
            $this->context->controller->addCss($this->_path . 'placeholder.css', 'all');
        }

    }

    private function _postProcess($token)
    {
        global $currentIndex;

        if (Tools::isSubmit('pstSubmit')) {
            Tools::redirectAdmin($currentIndex . '&configure=' . $this->name . '&conf=1&token=' . $token);
        }
    }
}