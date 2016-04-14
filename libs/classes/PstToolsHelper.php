<?php
/**
 * Created by Produweb
 * User: Simon Bonjean
 * Date: 13/09/13
 * Time: 11:34
 */

class PstToolsHelperCore extends Helper{
    static function clearCachePicture($filename, $path){
        foreach (scandir($dir) as $file)
        {
            if (!in_array($file, array('.','..','index.php','.htaccess','.DS_Store')))
            {
                if(is_dir($path.DS.$file))
                {
                    self::clearCachePicture($filename, $path.DS.$file);
                }
                elseif($file == $filename)
                {
                    @unlink($path.DS.$file);
                }
            }
        }
    }
    static function cleanCacheLoader(){
        unlink(_PS_CACHE_DIR_ . 'class_index.php');
    }
    static function multiLanger($str)
    {
        $output = array();
        foreach(Language::getLanguages(false) as $lang)
        {
            $output[$lang['id_lang']] = $str;
        }
        return $output;
    }
    static function namerMl($key){
        return self::multiLanger(self::namer($key));
    }
    static function cleaner($string){
        return Tools::replaceAccentedChars($string);
    }
    static function namer($key){
        return ucfirst(str_replace(array('-','_'), ' ', $key));
    }

    static function isPresta15(){
        return !version_compare(_PS_VERSION_, '1.5.0.0', '<');
    }
    static function isPresta16(){
        return !version_compare(_PS_VERSION_, '1.6.0.0', '<');
    }
    static function sortByField($multArray,$sortField,$desc=true){
        $tmpKey='';
        $ResArray=array();

        $maIndex=array_keys($multArray);
        $maSize=count($multArray)-1;

        for($i=0; $i < $maSize ; $i++) {

            $minElement=$i;
            $tempMin=$multArray[$maIndex[$i]][$sortField];
            $tmpKey=$maIndex[$i];

            for($j=$i+1; $j <= $maSize; $j++)
                if($multArray[$maIndex[$j]][$sortField] < $tempMin ) {
                    $minElement=$j;
                    $tmpKey=$maIndex[$j];
                    $tempMin=$multArray[$maIndex[$j]][$sortField];

                }
            $maIndex[$minElement]=$maIndex[$i];
            $maIndex[$i]=$tmpKey;
        }

        if($desc)
            for($j=0;$j<=$maSize;$j++)
                $ResArray[$maIndex[$j]]=$multArray[$maIndex[$j]];
        else
            for($j=$maSize;$j>=0;$j--)
                $ResArray[$maIndex[$j]]=$multArray[$maIndex[$j]];

        return $ResArray;
    }
    public static function getType(){
        /**
        ObjectModel::TYPE_INT = 1;
        ObjectModel::TYPE_BOOL = 2;
        ObjectModel::TYPE_STRING = 3;
        ObjectModel::TYPE_FLOAT = 4;
        ObjectModel::TYPE_DATE = 5;
        ObjectModel::TYPE_HTML = 6;
        ObjectModel::TYPE_NOTHING = 7;
         */


        /**
        isIp2Long
        isAnything
        isEmail
        isModuleUrl
        isMd5
        isSha1
        isFloat
        isUnsignedFloat
        isOptFloat
        isCarrierName
        isImageSize
        isName
        isHookName
        isMailName
        isMailSubject
        isModuleName
        isTplName
        isImageTypeName
        isPrice
        isNegativePrice
        isLanguageIsoCode
        isLanguageCode
        isStateIsoCode
        isNumericIsoCode
        isDiscountName
        isCatalogName
        isMessage
        isCountryName
        isLinkRewrite
        isRoutePattern
        isAddress
        isCityName
        isValidSearch
        isGenericName
        isCleanHtml
        isReference
        isPasswd
        isPasswdAdmin
        isConfigName
        isPhpDateFormat
        isDateFormat
        isDate
        isBirthDate
        isBool
        isPhoneNumber
        isEan13
        isUpc
        isPostCode
        isZipCodeFormat
        isOrderWay
        isOrderBy
        isTableOrIdentifier
        isValuesList
        isTagsList
        isProductVisibility
        isInt
        isUnsignedInt
        isPercentage
        isUnsignedId
        isNullOrUnsignedId
        isLoadedObject
        isColor
        isUrl
        isTrackingNumber
        isUrlOrEmpty
        isAbsoluteUrl
        isMySQLEngine
        isUnixName
        isTablePrefix
        isFileName
        isDirName
        isTabName
        isWeightUnit
        isDistanceUnit
        isSubDomainName
        isVoucherDescription
        isSortDirection
        isLabel
        isPriceDisplayMethod
        isDniLite
        isCookie
        isString
        isReductionType
        isBoolId
        isBool_Id
        isLocalizationPackSelection
        isSerializedArray
        isCoordinate
        isLangIsoCode
        isLanguageFileName
        isArrayWithIds
        isSceneZones
        isStockManagement
        isSiret
        isApe
        isControllerName
        isPrestaShopVersion
        isOrderInvoiceNumber
         */

        return array(
            'date'=>array(
                'form_type'=>'date',
                'const_type'=>'TYPE_DATE',
                'is_multi_lang' => false,
                'dbDesc'=> 'DATETIME DEFAULT NULL',
                'type_php'=>'date',
                'validate' => 'isDate',
            ),
            'datetime'=>array(
                'form_type'=>'datetime',
                'const_type'=>'TYPE_DATE',
                'is_multi_lang' => false,
                'dbDesc'=> 'DATETIME DEFAULT NULL',
                'type_php'=>'date',
                'validate' => 'isDate',
                'callback'=>'callBackDatetime',
            ),
            'float'=>array(
                'form_type'=>'text',
                'const_type'=>'TYPE_FLOAT',
                'is_multi_lang' => false,
                'dbDesc'=>'decimal(10,2) unsigned NOT NULL DEFAULT 0',
                'type_php'=>'float',
                'validate' => 'isFloat',
            ),
            'int'=>array(
                'form_type'=>'text',
                'const_type'=>'TYPE_INT',
                'is_multi_lang' => false,
                'dbDesc'=>'int(10) unsigned NOT NULL DEFAULT 0',
                'type_php'=>'int',
                'validate' => 'isUnsignedInt',
            ),
            'textarea_rte'=>array(
                'form_type'=>'textarea',
                'const_type'=>'TYPE_HTML',
                'is_multi_lang' => false,
                'dbDesc'=>'text NOT NULL',
                'type_php'=>'string',
                'rte'=>true,
                'validate' => 'isCleanHtml',
            ),
            'textarea'=>array(
                'form_type'=>'textarea',
                'const_type'=>'TYPE_HTML',
                'is_multi_lang' => false,
                'validate' => 'isString',
                'type_php'=>'string',
                'dbDesc'=>'text NOT NULL',
            ),

            'file'=>array(
                'form_type'=>'file',
                'const_type'=>'TYPE_STRING',
                'is_multi_lang' => false,
                'validate' => 'isString',
                'type_php'=>'string',
                'dbDesc'=>'VARCHAR(255) NOT NULL',
                'size'=>255,
            ),

            'image_ml'=>array(
                'form_type'=>'file',
                'const_type'=>'TYPE_STRING',
                'is_multi_lang' => false,
                'validate' => 'isString',
                'type_php'=>'string',
                'dbDesc'=>'VARCHAR(255) NOT NULL',
                'size'=>255,
            ),

            'image'=>array(
                'form_type'=>'file',
                'const_type'=>'TYPE_STRING',
                'is_multi_lang' => false,
                'validate' => 'isString',
                'type_php'=>'string',
                'type_php'=>'string',
                'dbDesc'=>'VARCHAR(255) NOT NULL',
                'callback'=>'getThumbImage',
                'size'=>255,
            ),

            'email'=>array(
                'form_type'=>'text',
                'const_type'=>'TYPE_STRING',
                'is_multi_lang' => false,
                'validate' => 'isEmail',
                'type_php'=>'string',
                'dbDesc'=>'VARCHAR(255) NOT NULL',
                'size'=>255,
            ),
            'text'=>array(
                'form_type'=>'text',
                'const_type'=>'TYPE_STRING',
                'is_multi_lang' => false,
                'validate' => 'isString',
                'type_php'=>'string',
                'dbDesc'=>'VARCHAR(255) NOT NULL',
                'size'=>255,
            ),

            'textarea_rte_lang'=>array(
                'form_type'=>'textarea',
                'const_type'=>'TYPE_HTML',
                'is_multi_lang' => true,
                'dbDesc'=>'text NOT NULL',
                'type_php'=>'string',
                'rte'=>true,
                'validate' => 'isCleanHtml',
            ),
            'textarea_lang'=>array(
                'form_type'=>'textarea',
                'const_type'=>'TYPE_HTML',
                'is_multi_lang' => true,
                'validate' => 'isString',
                'type_php'=>'string',
                'dbDesc'=>'text NOT NULL',
            ),
            'text_lang'=>array(
                'form_type'=>'text',
                'const_type'=>'TYPE_STRING',
                'is_multi_lang' => true,
                'validate' => 'isString',
                'type_php'=>'string',
                'dbDesc'=>'VARCHAR(255) NOT NULL',
                'size'=>255,
            ),
            'color'=>array(
                'form_type'=>'color',
                'const_type'=>'TYPE_STRING',
                'is_multi_lang' => false,
                'type_php'=>'string',
                'size'=>7,
                'dbDesc'=>" VARCHAR(7) NOT NULL DEFAULT \'#FFFFFF\'",
                'validate' => 'isColor',
                'callback'=>'callBackColor',
            ),
            'bool'=>array(
                'form_type'=>'switch',
                'const_type'=>'TYPE_BOOL',
                'is_multi_lang' => false,
                'is_bool' => true,
                'type_php'=>'boolean',
                'dbDesc'=>'tinyint(1) unsigned NOT NULL DEFAULT 0',
                'validate' => 'isBool',
            ),

        );
    }
}