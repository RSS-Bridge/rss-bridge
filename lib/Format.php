<?php
/**
* All format logic
* Note : adapter are store in other place
*/

interface FormatInterface{
    public function stringify();
    public function display();
    public function setItems(array $bridges);
}

abstract class FormatAbstract implements FormatInterface{
    const DEFAULT_CHARSET = 'UTF-8';

    protected 
        $contentType,
        $charset,
        $items,
        $extraInfos
    ;

    public function setCharset($charset){
        $this->charset = $charset;

        return $this;
    }

    public function getCharset(){
        $charset = $this->charset;

        return is_null($charset) ? self::DEFAULT_CHARSET : $charset;
    }

    protected function setContentType($contentType){
        $this->contentType = $contentType;

        return $this;
    }

    protected function callContentType(){
        header('Content-Type: ' . $this->contentType);
    }

    public function display(){
        echo $this->stringify();

        return $this;
    }

    public function setItems(array $items){
        $this->items = $items;
        return $this;
    }

    public function getItems(){
        if(!is_array($this->items))
            throw new \LogicException('Feed the ' . get_class($this) . ' with "setItems" method before !');

        return $this->items;
    }

    /**
    * Define common informations can be required by formats and set default value for unknow values
    * @param array $extraInfos array with know informations (there isn't merge !!!)
    * @return this
    */
    public function setExtraInfos(array $extraInfos = array()){    
        foreach(array('name', 'uri') as $infoName){
            if( !isset($extraInfos[$infoName]) ){
                $extraInfos[$infoName] = '';
            }
        }

        $this->extraInfos = $extraInfos;

        return $this;
    }

    /**
    * Return extra infos
    * @return array See "setExtraInfos" detail method to know what extra are disponibles
    */
    public function getExtraInfos(){
        if( is_null($this->extraInfos) ){ // No extra info ?
            $this->setExtraInfos(); // Define with default value
        }

        return $this->extraInfos;
    }
    
    /**
     * Sanitized html while leaving it functionnal.
     * The aim is to keep html as-is (with clickable hyperlinks)
     * while reducing annoying and potentially dangerous things.
     * Yes, I know sanitizing HTML 100% is an impossible task.
     * Maybe we'll switch to http://htmlpurifier.org/
     * or http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php
     */
    public function sanitizeHtml($html)
    {
        $html = str_replace('<script','<&zwnj;script',$html); // Disable scripts, but leave them visible.
        $html = str_replace('<iframe','<&zwnj;iframe',$html);
        $html = str_replace('<link','<&zwnj;link',$html);
        // We leave alone object and embed so that videos can play in RSS readers.
        return $html;
    }
}

class Format{

    static protected $dirFormat;

    public function __construct(){
        throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
    }

    static public function create($nameFormat){
        if( !preg_match('@^[A-Z][a-zA-Z]*$@', $nameFormat)){
            throw new \InvalidArgumentException('Name format must be at least one uppercase follow or not by alphabetic characters.');
        }

        $nameFormat=$nameFormat.'Format';
        $pathFormat = self::getDir() . $nameFormat . '.php';

        if( !file_exists($pathFormat) ){
            throw new \Exception('The format you looking for does not exist.');
        }

        require_once $pathFormat;

        return new $nameFormat();
    }

    static public function setDir($dirFormat){
        if( !is_string($dirFormat) ){
            throw new \InvalidArgumentException('Dir format must be a string.');
        }

        if( !file_exists($dirFormat) ){
            throw new \Exception('Dir format does not exist.');
        }

        self::$dirFormat = $dirFormat;
    }

    static public function getDir(){
        $dirFormat = self::$dirFormat;

        if( is_null($dirFormat) ){
            throw new \LogicException(__CLASS__ . ' class need to know format path !');
        }

        return $dirFormat;
    }

    /**
    * Read format dir and catch informations about each format depending annotation
    * @return array Informations about each format
    */
    static public function searchInformation(){
        $pathDirFormat = self::getDir();

        $listFormat = array();

        $searchCommonPattern = array('name');

        $dirFiles = scandir($pathDirFormat);
        if( $dirFiles !== false ){
          foreach( $dirFiles as $fileName ){
            if( preg_match('@^([^.]+)Format\.php$@U', $fileName, $out) ){ // Is PHP file ?
              $listFormat[] = $out[1];
            }
          }
        }

        return $listFormat;
    }
}
