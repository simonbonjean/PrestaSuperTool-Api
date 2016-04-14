<?php

class MyAccountController extends MyAccountControllerCore{
    public function initContent()
    {
        if(Configuration::get('PST_DISPLAY_NO_MY_ACCOUNT'))
            Tools::redirect($this->context->link->getPageLink('history'));

        parent::initContent();
    }
}