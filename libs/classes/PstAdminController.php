<?php

class PstAdminController extends AdminController{
    protected $module_path = null;
    protected $module_web_path = null;
    protected $core_module_web_path = null;
    protected $module_instance = null;
    protected $_field_types = null;
    protected $_config = null;

    public function __construct(){

        $this->module_path = _PS_MODULE_DIR_ . $this->module_name . DIRECTORY_SEPARATOR;
        $this->module_web_path = __PS_BASE_URI__ .'modules/'.$this->module_name . DIRECTORY_SEPARATOR;
        $this->core_module_web_path = __PS_BASE_URI__ .'modules/pst' . DIRECTORY_SEPARATOR;

        $this->pst_module_path = _PS_MODULE_DIR_ . 'pst' . DIRECTORY_SEPARATOR;
        $this->pst_module_web_path = __PS_BASE_URI__ .'modules/pst' . DIRECTORY_SEPARATOR;


        $this->context = Context::getContext();
        $this->id_lang = $this->context->language->id;

        parent::__construct();
        $this->context->smarty->assign('isPresta16', $this->isPresta16());
    }
    public function isPresta16()
    {
        return PstToolsHelper::isPresta16();
    }
    public function addTemplateDir($dir)
    {
        if($this->isPresta16())
        {
            $this->context->smarty->setTemplateDir(array(
                    _PS_MODULE_DIR_ . $this->module_name . DIRECTORY_SEPARATOR . $dir,
                    _PS_BO_ALL_THEMES_DIR_.$this->bo_theme.DIRECTORY_SEPARATOR.'template',
                    _PS_OVERRIDE_DIR_.'controllers'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'templates',
            ));
        }
        else
        {
            $dirs = $this->context->smarty->getTemplateDir();
            array_unshift($dirs, _PS_MODULE_DIR_ . $this->module_name . DIRECTORY_SEPARATOR . $dir);
            $this->context->smarty->setTemplateDir($dirs);
        }
    }
    public function addPstMedia(){
        $this->context->controller->addCSS($this->pst_module_path . 'admin/css/pst.css');
    }
    public function setMedia(){
        $this->addPstMedia();
        parent::setMedia();
    }
    public function getPictureModule(){
        return $this->module_web_path . 'logo.png';
    }

    public function getModuleInstance(){
        if(is_null($this->module_instance))
            $this->module_instance = Module::getInstanceByName($this->module_name);

        return $this->module_instance;
    }


    /**
     * Method called when an ajax request is made
     * @see AdminController::postProcess()
     */
    public function ajaxProcess()
    {
        if (Tools::getValue('action') == 'searchProducts')
        {
            die(json_encode($this->searchProducts(Tools::getValue('product_search'))));
        }
        die();
    }


    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
        if(isset($this->_virtual_item_list) && !empty($this->_virtual_item_list))
        {
            foreach($this->_list as &$item)
            {
                foreach($this->_virtual_item_list as $key)
                {
                    $item[$key] = '';
                }
            }
        }
    }

    public static function displayColor($fieldName, $param)
    {
        if($fieldName != '')
            return "<div style='display: block; width: 20px; height: 20px; border-radius: 5px;  background-color: ".$param[$fieldName].";'></div>";
    }
    public static function displayDatetime($fieldName, $param)
    {
        if($fieldName != '')
            return $param[$fieldName];
    }



    public function processForceDeleteImage()
    {
        $record = $this->loadObject(true);
        if (Validate::isLoadedObject($record))
            $record->deleteImage(true, Tools::getValue('name', null), Tools::getValue('iso_code', null));
    }

    public function ajaxProcessSearchProducts()
    {
        if ($products = Product::searchByName((int)$this->context->language->id, pSQL(Tools::getValue('product_search')))) {
            $to_return = array(
                'products' => $products,
                'found' => true
            );
        } else
            $to_return = array('found' => false);
        $this->content = Tools::jsonEncode($to_return);
    }

    protected function searchProducts($search)
    {
        if ($products = Product::searchByName((int)$this->context->language->id, $search))
        {
            foreach ($products as &$product)
            {
                $combinations = array();
                $productObj = new Product((int)$product['id_product'], false, (int)$this->context->language->id);
                $attributes = $productObj->getAttributesGroups((int)$this->context->language->id);
                $product['formatted_price'] = Tools::displayPrice(Tools::convertPrice($product['price_tax_incl'], $this->context->currency), $this->context->currency);

                foreach ($attributes as $attribute)
                {
                    if (!isset($combinations[$attribute['id_product_attribute']]['attributes']))
                        $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                    $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'].' - ';
                    $combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];
                    $combinations[$attribute['id_product_attribute']]['default_on'] = $attribute['default_on'];
                    if (!isset($combinations[$attribute['id_product_attribute']]['price']))
                    {
                        $price_tax_incl = Product::getPriceStatic((int)$product['id_product'], true, $attribute['id_product_attribute']);
                        $combinations[$attribute['id_product_attribute']]['formatted_price'] = Tools::displayPrice(Tools::convertPrice($price_tax_incl, $this->context->currency), $this->context->currency);
                    }
                }

                foreach ($combinations as &$combination)
                    $combination['attributes'] = rtrim($combination['attributes'], ' - ');
                $product['combinations'] = $combinations;
            }
            return array(
                'products' => $products,
                'found' => true
            );
        }
        else
            return array('found' => false, 'notfound' => Tools::displayError('No product has been found.'));
    }

    public function fieldsetCmsCategory($children = true)
    {
        $categories = CMSCategory::getCategories($this->context->language->id, false);
        $id_cms_category = 1;
        if(isset($this->object->id_cms_category))
        {
            $id_cms_category = $this->object->id_cms_category;
        }
        $html_categories = CMSCategory::recurseCMSCategory($categories, $categories[0][1], 1,$id_cms_category, 1,false);
        $fields = array(
            'legend' => array(
                'title' => $this->l('Cms Category'),
                'class' => 'fieldset-cms-category',
                'image' =>$this->getPstPictureUrl('cms_category.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'select_html',
                    'label' => $this->l('CMS Category'),
                    'name' => 'id_cms_category',
                    'options' => array(
                        'html' => $html_categories,
                    ),
                ),

            )
        );
        if($children)
        {
            $fields['input'][]= array(
                'type' => 'radio',
                'class' => 't',
                'label' => $this->l('Display childs ? :'),
                'container_class'=> 'display_cms_cat_childs',
                'required' => false,
                'name' => 'display_cms_cat_childs',
                'desc' => $this->l('Display all sub categories'),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'display_cms_cat_childs_on',
                        'value' => 1,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'display_cms_cat_childs_off',
                        'value' => 0,
                        'label' => $this->l('Disabled')
                    )
                ),
            );
        }

        $this->fields_form[] = array('class' => 'cms_category box-hide','form' => $fields);
    }
    public function fieldsetCategory($children=true)
    {
        $root_category = Category::getRootCategory();
        $selected_cat = array($this->object->id_category);//array(5); // array((isset($obj->id_parent) && $obj->isParentCategoryAvailable($id_shop))? (int)$obj->id_parent : (int)Tools::getValue('id_parent', Category::getRootCategory()->id));
        $root_category_id =  $root_category->id_category;
        $root_category = array('id_category' => $root_category->id_category, 'name' => $root_category->name);
        $fields = array(
            'legend' => array(
                'title' => $this->l('Product Category'),
                'class' => 'fieldset-product-category',
                'image' => $this->getPstPictureUrl('product_category.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'categories',
                    'label' => $this->l('Choose a category:'),
                    'tree'  => array(
                        'id'                  => 'categories-tree',
                        'selected_categories' => $selected_cat,
                        'root_category' => $root_category_id,
                        'use_search' => true,
//                        'disabled_categories' => !Tools::isSubmit('add'.$this->table) ? array($this->_category->id) : null
                    ),
                    'name' => 'id_category',
                    'values' => array(
                        'trads' => array(
                            'search' => $this->l('Search'),
                            'Root' => $root_category,
                            'selected' => $this->l('selected'),
                            'Collapse All' => $this->l('Collapse All'),
                            'Expand All' => $this->l('Expand All')
                        ),
                        'selected_cat' => $selected_cat,
                        'input_name' => 'id_category',
                        'disabled_categories' => array(),
                        'use_radio' => true,
                        'use_search' => false,
                        'top_category' => Category::getTopCategory()
                    )
                ),


            )
        );
        if($children)
        {
            $fields['input'][] =  array(
                'type' => 'radio',
                'class' => 't',
                'label' => $this->l('Display childs ? :'),
                'container_class'=> 'display_prod_cat_childs',
                'required' => false,
                'name' => 'display_prod_cat_childs',
                'desc' => $this->l('Display all sub categories'),
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'display_prod_cat_childs_on',
                        'value' => 1,
                        'label' => $this->l('Enabled')
                    ),
                    array(
                        'id' => 'display_prod_cat_childs_off',
                        'value' => 0,
                        'label' => $this->l('Disabled')
                    )
                ),
            );
        }

        $this->fields_form[] = array('class' => 'product_category box-hide','form' => $fields);
    }
    public function getPstPictureUrl($name,$dir=null)
    {
        return PstImage::cacheResizeCustomPath(_MODULE_DIR_.'pst/img/'.$dir.'/', $name, 20,20);
    }
    public function getPictureUrl($name,$dir=null)
    {
        return PstImage::cacheResizeCustomPath(_MODULE_DIR_.$this->module_name.'/img/'.$dir.'/', $name, 20,20);
    }
    public function fieldsetSupplier()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('Supplier'),
                'class' => 'fieldset-supplier',
                'image' => $this->getPstPictureUrl('supplier.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Supplier :'),
                    'name' => 'id_supplier',
                    'options' => array(
                        'query' => MenuHelper::getSupplierSelect($this->context->language->id),
                        'id' => 'id_supplier',
                        'name' => 'name',
                        'default' => array(
                            'label' => $this->l('No Supplier'),
                            'value' => 0
                        )
                    )
                )
            )
        );
        $this->fields_form[] = array('class' => 'supplier box-hide','form' => $fields);
    }

    public function fieldsetAttachment()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('Attachment'),
                'class' => 'fieldset-attachment',
                'image' => $this->getPstPictureUrl('attachment.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Attachment :'),
                    'name' => 'id_attachment',
                    'options' => array(
                        'query' => MenuHelper::getAttachmentSelect($this->context->language->id),
                        'id' => 'id_attachment',
                        'name' => 'name',
                        'default' => array(
                            'label' => $this->l('No Attachment'),
                            'value' => 0
                        )
                    )
                )
            )
        );
        $this->fields_form[] = array('class' => 'attachment box-hide','form' => $fields);
    }
    public function fieldsetLightbox()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('Lightbox'),
                'class' => 'fieldset-lightbox',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Light box width : '),
                    'min'=> 200,
                    'max'=> 1600,
                    'name' => 'lightbox_width',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Light box height : '),
                    'min'=> 200,
                    'max'=> 1600,
                    'name' => 'lightbox_height',
                ),
            )
        );
        $this->fields_form[] = array('class' => 'lightbox','form' => $fields);
    }
    public function fieldsetManufacturer()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('Manufacturer'),
                'class' => 'fieldset-manufacturer',
                'image' => $this->getPstPictureUrl('manufacturer.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Manufacturer :'),
                    'name' => 'id_manufacturer',
                    'options' => array(
                        'query' => MenuHelper::getManufacturerSelect($this->context->language->id),
                        'id' => 'id_manufacturer',
                        'name' => 'name',
                        'default' => array(
                            'label' => $this->l('No Manufacturer'),
                            'value' => 0
                        )
                    )
                )
            )
        );
        $this->fields_form[] = array('class' => 'manufacturer box-hide','form' => $fields);
    }
    public function fieldsetProduct()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('Product'),
                'class' => 'fieldset-product',
                'image' => $this->getPstPictureUrl('product.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Product search'),
                    'name' => 'product_search',
                    'desc'=>'Search a product by tapping the first letters of his name'
                ),
                array(
                    'type' => 'select_product',
                    'label' => $this->l('Product :'),
                    'name' => 'id_product',
                    'options' => array(
                        'html' => '',
                    ),
                )
            )
        );
        $this->fields_form[] = array('class' => 'product box-hide','form' => $fields);
    }
    public function fieldsetCms()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('Cms'),
                'class' => 'fieldset-cms',
                'image' => $this->getPstPictureUrl('cms.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Cms Page:'),
                    'name' => 'id_cms',
                    'options' => array(
                        'query' => MenuHelper::getCmsSelect($this->context->language->id),
                        'id' => 'id_cms',
                        'name' => 'name',
                        'default' => array(
                            'label' => $this->l('No Cms'),
                            'value' => 0
                        )
                    )
                )
            )
        );
        $this->fields_form[] = array('class' => 'cms box-hide','form' => $fields);
    }
    public function fieldsetAnchor()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('Anchor'),
                'class' => 'fieldset-anchor',
                'image' => $this->getPstPictureUrl('anchor.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'size'=> 50,
                    'lang'=>true,
                    'label' => $this->l('Anchor:'),
                    'name' => 'anchor',
                )
            )
        );
        $this->fields_form[] = array('class' => 'anchor','form' => $fields);
    }
    public function fieldsetExternal()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('External'),
                'class' => 'fieldset-external',
                'image' => $this->getPstPictureUrl('external.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'size'=> 50,
                    'label' => $this->l('External Link:'),
                    'name' => 'external',
                    'lang' => true,
                    'default' => array(
                        'label' => $this->l('No External'),
                        'value' => 0
                    )
                ),
            )
        );
        $this->fields_form[] = array('class' => 'external box-hide','form' => $fields);
    }
    public function fieldsetInternal()
    {
        $fields = array(
            'legend' => array(
                'title' => $this->l('Internal'),
                'class' => 'fieldset-internal',
                'image' => $this->getPstPictureUrl('internal.png','type')
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Internal Page:'),
                    'name' => 'id_meta',
                    'options' => array(
                        'query' => MenuHelper::getMetaSelect($this->context->language->id),
                        'id' => 'id_meta',
                        'name' => 'name',
                    )
                )
            )
        );
        $this->fields_form[] = array('class' => 'internal box-hide','form' => $fields);
    }


    public function getButtonSave(){
        return array(
            'title' => $this->l('   Save   '),
            'class' => 'button'
        );
    }
    public function getFieldAnchor(){
        return array(
            'type' => 'switch',
            'label' => $this->l('Has Anchor:'),
            'name' => 'has_anchor',
            'required' => false,
            'class' => 't',
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            ),
            'desc' => $this->l('Allow or disallow to add anchor')
        );
    }
    public function getFieldActive(){
        return array(
            'type' => 'switch',
            'label' => $this->l('Active:'),
            'name' => 'active',
            'required' => false,
            'class' => 't',
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            ),
            'desc' => $this->l('Allow or disallow ')
        );
    }
    public function getFieldLightbox(){
        return array(
            'type' => 'switch',
            'class' => 't',
            'label' => $this->l('Lightbox ? :'),
            'container_class'=> 'lightbox item-bool',
            'required' => false,
            'name' => 'lightbox',
            'desc' => $this->l('Open item in Lighbox'),
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'lightbox_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'lightbox_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            ),
        );
    }
    public function getFieldNewWindow(){
        return array(
            'type' => 'switch',
            'class' => 't',
            'container_class'=> 'new-window item-bool',
            'label' => $this->l('New window:'),
            'container_class'=> 'new_window item-bool',
            'required' => false,
            'name' => 'new_window',
            'desc' => $this->l('Open link into new window or tabs'),
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'new_window_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'new_window_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            ),
        );
    }
    public function getInputAltImage(){
        return array(
            'type' => 'text',
            'label' => $this->l('Image Alt'),
            'name' => 'image_alt',
            'lang' => true,
        );
    }
    public function getInputClassCss(){
        return array(
            'type' => 'text',
            'label' => $this->l('Class CSS'),
            'name' => 'css',
            'desc' => $this->l('Add a custum css class on item'),
        );
    }
    public function getInputMiniType(){
        return array(
            'type' => 'text',
            'label' => $this->l('Mini Tip'),
            'name' => 'minitip',
            'lang' => true,
            'desc' => $this->l('Add a tip on block'),
        );
    }
    public function getRadioMenuType($none=true){
        return array(
            'type' => 'radio',
            'container_class'=>'item_type',
            'class' => 't menu-list',
            'label' => $this->l('Item type'),
            'name' => 'type',
            'values' => MenuHelper::getMenuTypeRadio($none),
            'desc' => $this->l('Choose type of item')
        );
    }
    public function getFieldNoFollow(){
        return array(
            'type' => 'switch',
            'container_class'=> 'no-follow item-bool',
            'class' => 't',
            'label' => $this->l('No follow :'),
            'required' => false,
            'name' => 'no_follow',
            'is_bool' => true,
            'desc' => $this->l('SEO information'),
            'values' => array(
                array(
                    'id' => 'no_follow_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'no_follow_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            ),
        );
    }
    public function getFieldGuestOnly(){
        return array(
            'type' => 'switch',
            'container_class'=> 'guest item-bool',
            'class' => 't',
            'label' => $this->l('Guest only:'),
            'required' => false,
            'name' => 'guest_only',
            'desc' => $this->l('Item show only for not authentified user'),
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'guest_only_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'guest_only_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            ),
        );
    }
    public function getFieldMemberOnly(){
        return array(
            'type' => 'switch',
            'class' => 't',
            'container_class'=> 'member item-bool',
            'label' => $this->l('Member only:'),
            'required' => false,
            'name' => 'member_only',
            'desc' => $this->l('Item show only for authentified user'),
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'member_only_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => 'member_only_off',
                    'value' => 0,
                    'label' => $this->l('Active ? ')
                )
            ),
        );
    }

    public function ajaxProcessUpdatePositions()
    {
        $way = (int)(Tools::getValue('way'));
        $id_tab = (int)(Tools::getValue('id'));
        $positions = Tools::getValue($this->position_identifier);


        // when changing positions in a tab sub-list, the first array value is empty and needs to be removed
        if (!$positions[0])
        {
            unset($positions[0]);
            // reset indexation from 0
            $positions = array_merge($positions);
        }

        foreach ($positions as $position => $value)
        {
            $pos = explode('_', $value);

            if (isset($pos[2]) && (int)$pos[2] === $id_tab)
            {
                if ($obj = new $this->className((int)$pos[2]))
                    if (isset($position) && $obj->updatePosition($way, $position))
                        echo 'ok position '.(int)$position.' for tab '.(int)$pos[1].'\r\n';
                    else
                        echo '{"hasError" : true, "errors" : "Can not update tab '.(int)$id_tab.' to position '.(int)$position.' "}';
                else
                    echo '{"hasError" : true, "errors" : "This tab ('.(int)$id_tab.') can t be loaded"}';

                break;
            }
        }
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
     * return formated array with module configuration
     * @return array
     */
    public function getPstConfig()
    {
        if(is_null($this->_config))
            $this->_config = $this->getModuleInstance()->getPstConfig();
        return $this->_config;
    }

    /**
     * function need to be call in getContent implementation of your module
     * @return string
     */
    public function getPstContentConfig()
    {
        $html = '';
        // If we try to update the settings
        if (Tools::isSubmit('submitPstModuleConfig'))
        {
            $field_types = $this->getPstFieldTypes();
            foreach($this->getPstConfig() as $key => $fieldset_data)
            {
                foreach($fieldset_data['fields'] as $field_name => $data)
                {
                    if($field_types[$data['type']]['is_multi_lang'])
                    {
                        $value = array();
                        foreach(Language::getLanguages() as $lang)
                        {
                            $value_lang = Tools::getValue(strtolower($field_name).'_' .$lang['id_lang'], @$data['default_value']);
                            if(isset($field_types[$data['type']]['validate']))
                            {
                                $function = $field_types[$data['type']]['validate'];
                                if(method_exists(new Validate,$function))
                                {
                                    if(is_array($value)) {
                                        foreach ($value as $val)
                                        {
                                            if (!call_user_func(array('Validate', $function), $val)) {
                                                $this->_errors[] = Tools::displayError(@$data['validate_error']);
                                            }
                                        }
                                    }
                                    else
                                    {
                                        if(!call_user_func(array('Validate',$function),$value))
                                        {
                                            $this->_errors[] = Tools::displayError(@$data['validate_error']);
                                        }
                                    }
                                }
                            }
                            $value[$lang['id_lang']] = $value_lang;
                        }
                    }
                    else
                    {
                        $value = Tools::getValue(strtolower($field_name), @$data['default_value']);
                        if(isset($field_types[$data['type']]['validate']))
                        {
                            $function = $field_types[$data['type']]['validate'];
                            if(method_exists(new Validate,$function))
                            {
                                if(is_array($value))
                                {
                                    foreach ($value as $val)
                                    {
                                        if(!call_user_func(array('Validate',$function),$val))
                                        {
                                            $this->_errors[] = Tools::displayError($data['validate_error']);
                                            continue ;
                                        }
                                    }
                                } else {
                                    if (!call_user_func(array('Validate', $function), $value)) {
                                        $this->_errors[] = Tools::displayError($data['validate_error']);
                                        continue;
                                    }
                                }
                            }
                        }
                    }
                    Configuration::updateValue(strtoupper($field_name), $value);
                }
            }
            $html .= $this->displayInformation($this->l('Configuration updated'));
        }

        $html .= $this->renderPstConfiForm();

        return $html;
    }

    /**
     * function render form of your module configuration
     * @return string
     */
    public function renderPstConfiForm()
    {
//        $fields_form = array(
//            'form' => array(
//                'legend' => array(
//                    'title' => $this->l('Settings') . ' ' . $this->displayName,
//                    'icon' => 'icon-cogs'
//                ),
//                'input' => array(),
//                'submit' => array(
//                    'title' => $this->l('Save')
//                )
//            )
//        );

        $field_types = $this->getPstFieldTypes();

        $fieldset_list = array();
        $is_multi_lang = false;
        foreach($this->getPstConfig() as $key => $fieldset_data)
        {
            $fieldset =array(
                'tinymce' => true,
                'legend' => array(
                    'title' => $fieldset_data['label'],
                    'icon' => 'icon-cogs'
                ),

                'input'=>array()
            );
            foreach($fieldset_data['fields'] as $field_name => $data)
            {
                if (!isset($data['type'])) {
                    throw new PrestaShopException('Type is not define : ' . $data['type']);
                }

                if (!isset($field_types[$data['type']])) {
                    throw new PrestaShopException('Type is unknow : ' . $data['type']);
                }


                $type_option = $field_types[$data['type']];

                $field = array(
                    'type' => $type_option['form_type'],
                    'name' => strtolower($field_name),
                    'class' => 't',
                );
                if (isset($type_option['is_bool']) && $type_option['is_bool']) {
                    $field['is_bool'] = true;
                    $field['values'] = array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    );

                }

                if (isset($type_option['rte']) && $type_option['rte']) {
                    $field['autoload_rte'] = true;
                    $field['rows'] = 5;
                    $field['cols'] = 40;
                    $field['hint'] = $this->l('Invalid characters:') . ' <>;=#{}';
                }

                if (isset($type_option['is_multi_lang']) && $type_option['is_multi_lang']) {
                    $field['lang'] = true;
                    $is_multi_lang = true;
                }
                if (isset($data['required']) && $data['required']) {
                    $field['required'] = true;
                }

                if (isset($data['desc']) && !empty($data['desc'])) {
                    $field['desc'] = $this->l($data['desc']);
                }

                if (isset($data['label']) && !empty($data['label'])) {
                    $field['label'] = $this->l($data['label']);
                }






                $fieldset['input'][] = $field;
                switch($data['type']) {
                    case 'image':
                    case 'image_ml':
                        foreach (Language::getLanguages() as $lang) {
                            $iso_code = $lang['iso_code'];
                            $ext = 'jpg';
                            $image = file_exists(
                                _PS_IMG_DIR_ . '/' . $this->module_name . '/config/' . strtolower(
                                    $field_name
                                ) . '/' . $iso_code . '.jpg'
                            );
                            if (!$image) {
                                $ext = 'png';
                                $image = file_exists(
                                    _PS_IMG_DIR_ . '/' . $this->module_name . '/config/' . strtolower(
                                        $field_name
                                    ) . '/' . $iso_code . '.png'
                                );
                            }
                            $image_size = $image_url = false;
                            if ($image) {
                                $image_url = PstImage::cacheResizeCustomPath(
                                    '/img/' . $this->module_name . '/config/' . strtolower($field_name) . '/',
                                    $iso_code . '.' . $ext,
                                    150,
                                    150
                                );
                                $image_url = $image ? '<img src="' . $image_url . '" />' : false;
                                $image_size = filesize(
                                        _PS_IMG_DIR_ . '/' . $this->module_name . '/config/' . strtolower(
                                            $field_name
                                        ) . '/' . $iso_code . '.' . $ext
                                    ) / 1000;
                            }


                            $fieldset['input'][] = array(
                                'type' => 'file',
                                'label' => $this->l($data['label']) . ' ' . $iso_code . ':',
                                'name' => $field_name . '_' . $iso_code,
                                'display_image' => true,
                                'image' => $image_url ? $image_url : false,
                                'size' => $image_size,
                                'delete_url' => self::$currentIndex . '&token=' . $this->token . '&forcedeleteImage=1&name=' . $field_name
                            );
                        }
                    break;
                }

            }
            $fieldset_list[] = array('form'=>$fieldset);
        }

        $this->lang = $is_multi_lang;

        $helper = new HelperForm();

        $fieldset_list[count($fieldset_list)-1]['form']['submit'] = array(
            'name' => $helper->submit_action,
            'title' => $this->l('Save')
        );

        $helper->show_toolbar = false;
        $helper->table =  $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPstModuleConfig';
//        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm($fieldset_list);
    }

    /**
     * Retrieve configuration values from database
     * @return array
     */
    public function getConfigFieldsValues()
    {
        $output = array();
        $field_types = $this->getPstFieldTypes();
        foreach($this->getPstConfig() as $key => $fieldset_data)
        {
            foreach($fieldset_data['fields'] as $field_name => $data)
            {
                $field_name = strtoupper($field_name);
                if($field_types[$data['type']]['is_multi_lang'])
                {
                    $default_value = array();
                    foreach (Language::getLanguages(false) as $lang)
                    {
                        if (Configuration::hasKey($field_name, $lang['id_lang']))
                        {
                            $default_value[$lang['id_lang']] = Configuration::get($field_name, $lang['id_lang']);
                        }
                        else
                        {
                            $default_value[$lang['id_lang']] = $data['default_value'];
                        }
                    }
                }
                else
                {
                    if (Configuration::hasKey($field_name))
                    {
                        $default_value = Configuration::get($field_name);
                    }
                    else
                    {
                        $default_value = $data['default_value'];
                    }
                }
                $output[strtolower($field_name)] = $default_value;
            }
        }
        return $output;
    }

}

