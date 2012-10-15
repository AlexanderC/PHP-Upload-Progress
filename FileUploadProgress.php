<?php

/**
 * File upload progress class
 *
 * @author AlexanderC
 */
class FileUploadProgress {
    
    const PROGRESS_APC = 0x01;
    const PROGRESS_PHP = 0x02;
    const PROGRESS_PHP_EXT = 0x03;
    
    /**
     * Method used for progress calculations
     *  
     * @var int
     */
    private $progressCalcMethod = self::PROGRESS_APC;
    
    /**
     * The name of uploaded file
     * 
     * @var string
     */
    private $uploadFileName;
    
    /**
     * @param string $uploadFileName
     */
    public function __construct($uploadFileName) {
        $this->uploadFileName = (string) $uploadFileName;
    }
    
    /**
     * Calculate progress method
     * 
     * @return int
     */
    public function getProgressCalcMethod(){
        return $this->progressCalcMethod;
    }
    
    /**
     * Set progress calc method
     * 
     * @param int $method
     * @return void
     * @throws \UnexpectedValueException
     */
    public function setProgressCalcMethod($method){
        if(!in_array($method, array(self::PROGRESS_APC, self::PROGRESS_PHP, self::PROGRESS_PHP_EXT))){
            throw new \UnexpectedValueException("Progress calc. method '{$method}' doesn't exists");
        }
        
        $this->progressCalcMethod = $method;
    }
    
    /**
     * Get upload progress
     * note: 'done' flag is set if only moved uploaded file
     * 
     * @return array
     * @throws \RuntimeException
     */
    public function getProgress(){
        if($this->getProgressCalcMethod() === self::PROGRESS_APC){
            if(!function_exists('apc_fetch') || !(bool) ini_get('apc.rfc1867')){
                throw new \RuntimeException("APC extension should be loaded and 'apc.rfc1867' set to 'on' in order to retrieve upload progress");
            }
            
            $data = apc_fetch(ini_get('apc.rfc1867_prefix') . $this->getProgressFieldUniqueValue());
            
            if(!is_array($data) || empty($data)){
                throw new \RuntimeException("Wrong format returned by fetch function used to retrieve upload progress");
            }
            
            return array('total' => $data['total'], 'current' => $data['current'], 'done' => (bool) $data['done']);
        } else if($this->progressCalcMethod === self::PROGRESS_PHP) {
            if(floatval(phpversion()) < 5.4 || !(bool) ini_get('session.upload_progress.enabled')){
                throw new \RuntimeException("You may have at least PHP v.5.4 installed and 'session.upload_progress.enabled' set to 'on' in order to use upload progress feature");
            }
            
            if (strlen(session_id()) <= 0){
                session_start();
            }

            $data = $_SESSION[ini_get('session.upload_progress.prefix') . $this->getProgressFieldUniqueValue()];
            
            if(!is_array($data) || empty($data)){
                throw new \RuntimeException("Wrong format returned by the session with upload progress data");
            }
            
            return array('total' => $data['content_length'], 'current' => $data['bytes_processed'], 'done' => (bool) $data['done']);
        } else {
            if(!function_exists('uploadprogress_get_info')){
                throw new \RuntimeException("Uploadprogress extension should be loaded in order to retrieve upload progress");
            }
            
            $data = uploadprogress_get_info($this->getProgressFieldUniqueValue());
            
            if(!is_array($data) || empty($data)){
                throw new \RuntimeException("Wrong format returned by uploadprogress_get_info function used to retrieve upload progress");
            }
            
            return array('total' => $data["bytes_total"], 'current' => $data["bytes_uploaded"], 'done' => (int) $data["bytes_uploaded"] === (int) $data["bytes_total"]);
        }
    }

    /**
     * Get html markup for file upload progress field
     * Note: this field should be placed before file field
     * 
     * @return type
     */
    public function getHiddenUploadFieldHTML(){
        return "<input type='hidden' name='{$this->getProgressFieldName()}' value='{$this->getProgressFieldUniqueValue()}'>";
    }
    
    /**
     * Get progress input field name
     * 
     * @return string
     */
    public function getProgressFieldName(){
        if($this->progressCalcMethod === self::PROGRESS_APC){
            return ini_get('apc.rfc1867_name'); 
        } else if($this->progressCalcMethod === self::PROGRESS_PHP){
            return ini_get('session.upload_progress.name');
        } else {
            return 'UPLOAD_IDENTIFIER'; // case php extension
        }
    }
    
    /**
     * Get unique name for file uplad progress field
     * 
     * @return string
     */
    public function getProgressFieldUniqueValue(){
        if($this->progressCalcMethod === self::PROGRESS_APC){
            return sha1($this->uploadFileName.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']);
        }
        return md5($this->uploadFileName).md5($_SERVER['HTTP_USER_AGENT']).ip2long($_SERVER['REMOTE_ADDR']);
    }
}
