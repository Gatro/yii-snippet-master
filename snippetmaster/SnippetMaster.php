<?php

class SnippetMaster extends CApplicationComponent {
    
    private $_initialize = false;
    private $_currentDirectory = null;
    private $_banners = array();
    private $_defaultBannerMaxCount = self::DEFAULT_BANNER_MAX_COUNT;
    private $_bannerCounter = 0;
    
    protected $_errors = array();
    protected $_isError = false;
    
    const DEFAULT_BANNER_MAX_COUNT = 50;
    
    //Service methods
    private function getPath($resourceName) {
        if(!is_null($this->_currentDirectory)) {
            return $this->_currentDirectory.$resourceName;
        }
        
        return false;
    }
    
    private function getViewPath($viewName) {
        return $this->getPath('views'.DIRECTORY_SEPARATOR.$viewName);
    }
    
    private function getBannerCounter($increase = true) {
        if($increase) {
            $this->_bannerCounter++;
        }
        return $this->_bannerCounter;
    }
    
    
    //Error handling
    protected function addError($error) {
        array_push($this->_errors, $error);
        $this->_isError = true;
    }
    
    private function checkRequirements() {
        try {
            if (!file_exists($this->getPath('config.php'))) {
                throw new Exception('Confing.php doesn\'t exist');
            }

            if (!is_dir($this->getPath('views'))) {
                throw new Exception('View directory doesn\'t exist');
            }
            
        } catch (Exception $e) {
            $this->addError($e->getMessage());
            $this->_isError = true;
            throw $e;
        }
    }
    
    public function isErrors() {
        return $this->_isError;
    }
    
    public function getErrors() {
        return $this->_errors;
    }

    //Initial methods
    private function configureBanners($banners) {
        foreach ($banners as $bannerName => $oneBanner) {
            if(isset($this->_banners[$bannerName])) {
                throw new Exception('Config file has wrong format. There are more than one banner with name "'.$bannerName.'"');
            }
            
            if(!isset($oneBanner['maxCount'])) {
                $oneBanner['maxCount'] = $this->_defaultBannerMaxCount;
            }
            
            $oneBanner['count'] = $oneBanner['maxCount'];
            
            $this->_banners[$bannerName] = $oneBanner;
        }
    }

    private function configure() {
        
        try {
            $config = require($this->getPath('config.php'));

            if (!is_array($config)) {
                throw new Exception('Config file has wrong format. It must be an array.');
            }

            if (isset($config['defaultBannerMaxCount']) && is_integer($config['defaultBannerMaxCount'])) {
                $this->_defaultBannerMaxCount = $config['defaultBannerMaxCount'];
            }
            
            if(isset($config['banners']) && is_array($config['banners'])) {
                $this->configureBanners($config['banners']);
            }
            
        } catch (Exception $e) {
            $this->addError($e->getMessage());
            $this->_isError = true;
            throw $e;
        }
    }
    
    public function init() {
        parent::init();
        
        $this->_currentDirectory = dirname(__FILE__).DIRECTORY_SEPARATOR;
        
        try {
            $this->checkRequirements();
            $this->configure();
            
            $this->_initialize = true;
        } catch(Exception $e) {
            $this->_initialize = false;
            return false;
        }        
        
        return true;
    }
    
    
    //Main functionality
    private function getBannerParams($bannerName) {
        if(isset($this->_banners[$bannerName])) {
            return $this->_banners[$bannerName];
        }
        
        $bannerElement = array(
            'maxCount' => $this->_defaultBannerMaxCount,
            'count' => $this->_defaultBannerMaxCount,
        );
        
        $this->_banners[$bannerName] = $bannerElement;
        
        return $bannerElement;
    }
    
    private function setBannerParams($bannerName, $bannerElement) {
        
        if(!isset($bannerElement['maxCount'])) {
            throw new Exception('Can\'t set banner param. Empty maxCount field.');
        }
        
        if(!isset($bannerElement['count'])) {
            throw new Exception('Can\'t set banner param. Empty count field.');
        }
        
        
        $this->_banners[$bannerName] = $bannerElement;
    }
    
    private function getBannerBody($name) {
        $viewPath = $this->getViewPath($name.'.php');
        if(!file_exists($viewPath)) {
            throw new Exception('View with given name "'.$name.'" doesn\'t exist in the view directory');
        }
        
        ob_start();
        include $viewPath;
        $viewBody = ob_get_contents();
        ob_end_clean();
        
        return $viewBody;
    }
    
    private function getOutputCountByNumber($bannerName, $bannerCount) {
        $bannerParams = $this->getBannerParams($bannerName);
        
        if($bannerCount > $bannerParams['count']) {
            $outputValue = $bannerParams['count'];
            $bannerParams['count'] = 0;
        } else {
            $outputValue = $bannerCount;
            $bannerParams['count'] = $bannerParams['count'] - $bannerCount;
        }
        
        $this->setBannerParams($bannerName, $bannerParams);
        
        return $outputValue;
    }
    
    private function getOutputCountByPercent($bannerName, $bannerCount) {
        $isPercent = preg_match('/%/', $bannerCount);
        if(!$isPercent) {
            return $this->getOutputCountByNumber($bannerName, intval($bannerCount));
        }
        
        $percent = intval(preg_replace_callback('/%/', function($matches){    
            return '';
        }, $bannerCount));
        
        $bannerParams = $this->getBannerParams($bannerName);
        $outputPercentCount =  ($percent / 100) * $bannerParams['count'];
        $outputCount = intval(ceil($outputPercentCount));
        
        return $this->getOutputCountByNumber($bannerName, $outputCount);
    }
    
    private function getOutputCount($bannerName, $bannerCount) {
        $count = 0;
        
        if(is_string($bannerCount)) {
            //percent
            $count = $this->getOutputCountByPercent($bannerName, $bannerCount);
        } else if(is_integer($bannerCount) && ($bannerCount > 0)) {
            //count
            $count = $this->getOutputCountByNumber($bannerName, $bannerCount);
        }
        return $count;
    }
    
    public function getDrawBanner($bannerName, $bannerCount) {
        if(!$this->_initialize) {
            return false;
        }
        
        try {
            $bannerContent = $this->getBannerBody($bannerName);
            $outputCount = $this->getOutputCount($bannerName, $bannerCount);
            
            $output = '';
            for($i=0; $i != $outputCount; $i++) {
                $count = $this->getBannerCounter();
                $output .= preg_replace_callback('/PLACEHOLDER_BANNER_ID/', function($matches) use($count) {
                    return $count;
                }, $bannerContent);
            }
            
        } catch(Exception $e) {
            $this->addError($e->getMessage());
            $this->_isError = true;
            return false;
        }
        
        return $output;
    }
    
    public function drawBanner($bannerName, $bannerCount) {
        print $this->getDrawBanner($bannerName, $bannerCount);
    }
}
