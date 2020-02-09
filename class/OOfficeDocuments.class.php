<?php

class OOfficeDocuments
{

    public $entity;

    public $filename;

    public $fileFullPath;

    public $fileRelativePath;

    public $modulePart;

    public $modulePartFolder;

    public $param;

    /**
     * @var $user User
     */
    public $user;


    /**
     * @param $modulePart
     * @param $file
     * @param array $params
     * @param bool $currentUser User
     * @param int $entity
     */
    public function fetchDocument($modulePart,$file,$params = array(), $currentUser = false, $entity = false){
        global $conf;

        if($entity === false){
            $this->entity = $conf->entity;
        }

        if($currentUser === false){
            global $user;
            $this->user = $user;
        }

        $this->params = $params;

        return setPath($modulePart,$file);

    }

    public function setPath($file){

        $this->filename = basename($file);

        if($this->modulepart === 'documentstemplates')
        {
            DOL_DATA_ROOT . "/doctemplates";
            $this->modulePartFolder =  DOL_DATA_ROOT . "/doctemplates";
            $this->fileRelativePath = $file;
            $this->fileFullPath = $this->modulePartFolder.'/'.$file;

            return true;
        }

        return false;
    }


    public function readRight(){

        if($this->modulepart === 'documentstemplates' && (!empty($this->user->rights->oofficeconnector->template->read) || !empty($this->user->rights->oofficeconnector->template->write)) ){
            return true;
        }

        return false;
    }


    public function writeRight(){

        if($this->modulepart === 'documentstemplates' && !empty($this->user->rights->oofficeconnector->template->read)){
            return true;
        }

        return false;
    }
}