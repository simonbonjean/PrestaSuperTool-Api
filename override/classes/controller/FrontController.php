<?php

class FrontController extends  FrontControllerCore
{



    public function init(){
        Hook::exec('controllerConstruct');
        parent::init();
    }
}