<?php
/**
 * @since 1.5.0
 */
class PstToolsController extends PstAdminController
{


    public function __construct()
    {
        $this->_html ='';
        $this->context = Context::getContext();

        $this->bootstrap = true;
        $this->multiple_fieldsets = true;
        $this->module_name = 'pst';
        parent::__construct();
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUI('ui.dialog');
        $this->addJqueryPlugin(array('autocomplete', 'fancybox', 'typewatch'));
        $this->addCSS($this->module_web_path . 'admin/css/global.css', 'all');
    }
    public function initContent()
    {
        $this->content .= $this->getPstContentConfig();
        parent::initContent();

        $this->context->smarty->assign(array(
            'show_toolbar' => true,
            'toolbar_btn' => $this->toolbar_btn,
            'content' => $this->content
        ));
    }

}
