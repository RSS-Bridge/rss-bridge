<?php
/**
* All format logic
* Note : adapter are store in other place
*/

interface FormatInterface
{
    public function stringify();
    public function display();
    public function setDatas(array $bridge);
}

abstract class FormatAbstract implements FormatInterface
{
    const DEFAULT_CHARSET = 'UTF-8';

    protected $contentType,
        $charset,
        $datas,
        $extraInfos
    ;

    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    public function getCharset()
    {
        $charset = $this->charset;

        return is_null($charset) ? self::DEFAULT_CHARSET : $charset;
    }

    protected function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    protected function callContentType()
    {
        header('Content-Type: ' . $this->contentType);
    }

    public function display()
    {
        echo $this->stringify();

        return $this;
    }

    public function setDatas(array $datas)
    {
        $this->datas = $datas;

        return $this;
    }

    public function getDatas()
    {
        if ( !is_array($this->datas) ) {
            throw new \LogicException('Feed the ' . get_class($this) . ' with "setDatas" method before !');
        }

        return $this->datas;
    }

    /**
    * Define common informations can be required by formats and set default value for unknow values
    * @param array $extraInfos array with know informations (there isn't merge !!!)
    * @return this
    */
    public function setExtraInfos(array $extraInfos = array())
    {
        foreach (array('name', 'uri') as $infoName) {
            if ( !isset($extraInfos[$infoName]) ) {
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
    public function getExtraInfos()
    {
        if ( is_null($this->extraInfos) ) { // No extra info ?
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

class Format
{
    protected static $dirFormat;

    public function __construct()
    {
        throw new \LogicException('Please use ' . __CLASS__ . '::create for new object.');
    }

    public static function create($nameFormat)
    {
        if ( !static::isValidNameFormat($nameFormat) ) {
            throw new \InvalidArgumentException('Name format must be at least one uppercase follow or not by alphabetic characters.');
        }

        $pathFormat = self::getDir() . $nameFormat . '.php';

        if ( !file_exists($pathFormat) ) {
            throw new \Exception('The format you looking for does not exist.');
        }

        require_once $pathFormat;

        return new $nameFormat();
    }

    public static function setDir($dirFormat)
    {
        if ( !is_string($dirFormat) ) {
            throw new \InvalidArgumentException('Dir format must be a string.');
        }

        if ( !file_exists($dirFormat) ) {
            throw new \Exception('Dir format does not exist.');
        }

        self::$dirFormat = $dirFormat;
    }

    public static function getDir()
    {
        $dirFormat = self::$dirFormat;

        if ( is_null($dirFormat) ) {
            throw new \LogicException(__CLASS__ . ' class need to know format path !');
        }

        return $dirFormat;
    }

    public static function isValidNameFormat($nameFormat)
    {
        return preg_match('@^[A-Z][a-zA-Z]*$@', $nameFormat);
    }

    /**
    * Read format dir and catch informations about each format depending annotation
    * @return array Informations about each format
    */
    public static function searchInformation()
    {
        $pathDirFormat = self::getDir();

        $listFormat = array();

        $searchCommonPattern = array('name');

        $dirFiles = scandir($pathDirFormat);
        if ($dirFiles !== false) {
            foreach ($dirFiles as $fileName) {
                if ( preg_match('@([^.]+)\.php@U', $fileName, $out) ) { // Is PHP file ?
                    $infos = array(); // Information about the bridge
                    $resParse = token_get_all(file_get_contents($pathDirFormat . $fileName)); // Parse PHP file
                    foreach ($resParse as $v) {
                        if ( is_array($v) && $v[0] == T_DOC_COMMENT ) { // Lexer node is COMMENT ?
                            $commentary = $v[1];
                            foreach ($searchCommonPattern as $name) { // Catch information with common pattern
                                preg_match('#@' . preg_quote($name, '#') . '\s+(.+)#', $commentary, $outComment);
                                if ( isset($outComment[1]) ) {
                                    $infos[$name] = $outComment[1];
                                }
                            }
                        }
                    }

                    if ( isset($infos['name']) ) { // If informations containt at least a name
                        $listFormat[$out[1]] = $infos;
                    }
                }
            }
        }

        return $listFormat;
    }
}
