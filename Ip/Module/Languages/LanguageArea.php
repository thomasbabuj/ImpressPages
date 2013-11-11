<?php
/**
 * @package   ImpressPages
 *
 *
 */
namespace Ip\Module\Languages;



class LanguageArea extends \Ip\Lib\StdMod\Area {

    var $errors = array();
    private $urlBeforeUpdate;

    function __construct() {
        global $parametersMod;
        parent::__construct(
        array(
            'dbTable' => 'language',
            'title' => __('Languages', 'ipAdmin'),
            'dbPrimaryKey' => 'id',
            'searchable' => false,
            'orderBy' => 'row_number',
            'sortable' => true,
            'sortField' => 'row_number',
            'newRecordPosition' => 'bottom'
            )
            );



            $element = new \Ip\Lib\StdMod\Element\Text(
            array(
                    'title' => __('Short', 'ipAdmin'),
                    'showOnList' => true,
                    'dbField' => 'd_short',
                    'required' => true
            )
            );
            $this->addElement($element);


            $element = new \Ip\Lib\StdMod\Element\Text(
            array(
                    'title' => __('Long', 'ipAdmin'),
                    'useInBreadcrumb' => true,
                    'showOnList' => true,
                    'dbField' => 'd_long',
            )
            );
            $this->addElement($element);

            $element = new \Ip\Lib\StdMod\Element\Bool(
            array(
                    'title' => __('Visible', 'ipAdmin'),
                    'showOnList' => true,
                    'dbField' => 'visible',
            )
            );
            $this->addElement($element);





            $element = new ElementUrl(
            array(
                    'title' => __('URL', 'ipAdmin'),
                    'showOnList' => true,
                    'dbField' => 'url',
                    'required' => true,
                    'regExpression' => '/^([^\/\\\])+$/',
                    'regExpressionError' => __('Incorrect URL. You can\'t use slash in URL.', 'ipAdmin')
            )
            );
            $this->addElement($element);



            $element = new \Ip\Lib\StdMod\Element\Text(
            array(
                    'title' => __('RFC 4646 code', 'ipAdmin'),
                    'showOnList' => true,
                    'dbField' => 'code',
                    'required' => true
            )
            );
            $this->addElement($element);



            $element = new \Ip\Lib\StdMod\Element\Text(
            array(
                    'title' => $parametersMod->getValue('Config.text_direction'),
                    'showOnList' => true,
                    'dbField' => 'text_direction',
                    'required' => true,
                    'defaultValue' => 'ltr'
            )
            );
            $this->addElement($element);


    }


    function afterInsert($id) {
        global $site;

        Db::createRootZoneElement($id);
        Db::createEmptyTranslations($id,'par_lang');

        $site->dispatchEvent('standard', 'languages', 'language_created', array('language_id'=>$id));
    }

    function beforeDelete($id) {
        global $site;



        $site->dispatchEvent('standard', 'languages', 'before_delete', array('language_id'=>$id));
    }


    function afterDelete($id) {
        global $site;


        Db::deleteRootZoneElement($id);
        Db::deleteTranslations($id, 'par_lang');

        $site->dispatchEvent('standard', 'languages', 'language_deleted', array('language_id'=>$id));    //deprecated
        $site->dispatchEvent('standard', 'languages', 'after_delte', array('language_id'=>$id));

    }


    function beforeUpdate($id) {
        global $site;

        $tmpLanguage = Db::getLanguageById($id);
        $this->urlBeforeUpdate = $tmpLanguage['url'];


        $site->dispatchEvent('standard', 'languages', 'before_update', array('language_id'=>$id));

    }


    function afterUpdate($id) {
        global $site;
        global $parametersMod;

        $tmpLanguage = Db::getLanguageById($id);
        if($tmpLanguage['url'] != $this->urlBeforeUpdate && $parametersMod->getValue('Config.multilingual')) {
            $oldUrl = \Ip\Config::baseUrl($this->urlBeforeUpdate.'/');
            $newUrl = \Ip\Config::baseUrl($tmpLanguage['url'].'/');
            global $dispatcher;
            $dispatcher->notify(new \Ip\Event\UrlChanged($this, $oldUrl, $newUrl));

        }

        $site->dispatchEvent('standard', 'languages', 'language_updated', array('language_id'=>$id));    //deprecated
        $site->dispatchEvent('standard', 'languages', 'after_update', array('language_id'=>$id));
    }

    function allowDelete($id) {
        global $parametersMod;

        $dbMenuManagement = new \Ip\Module\Pages\Db();

        $answer = true;


        $zones = Db::getZones();
        foreach($zones as $key => $zone) {
            $rootElement = $dbMenuManagement->rootContentElement($zone['id'], $id);
            $elements = $dbMenuManagement->pageChildren($rootElement);
            if(sizeof($elements) > 0) {
                $answer = false;
                $this->errors['delete'] = __('Please delete all pages in this language and then try again.', 'ipAdmin');
            }
        }

        if(sizeof(Db::getLanguages()) ==1) {
            $answer = false;
            $this->errors['delete'] = __('There should be at least one language.', 'ipAdmin');
        }


        return $answer;
    }

    function lastError($action) {
        if(isset($this->errors[$action]))
        return $this->errors[$action];
        else
        return '';
    }



}