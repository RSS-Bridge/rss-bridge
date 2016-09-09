<?php
require_once(__DIR__ . '/BridgeInterface.php');
/**
 * Extension of BridgeAbstract allowing caching of files downloaded over http.
 */
abstract class HttpCachingBridgeAbstract extends BridgeAbstract {
    /**
     * Maintain locally cached versions of pages to download, to avoid multiple downloads.
     * @param url url to cache
     * @param duration duration of the cache file in seconds (default: 24h/86400s)
     * @return content of the file as string
     */
    public function get_cached($url, $duration = 86400){
        $this->debugMessage('Caching url ' . $url . ', duration ' . $duration);

        $filepath = __DIR__ . '/../cache/pages/' . sha1($url) . '.cache';
        $this->debugMessage('Cache file ' . $filepath);

        if(file_exists($filepath) && filectime($filepath) < time() - $duration){
            unlink ($filepath);
            $this->debugMessage('Cached file deleted: ' . $filepath);
        }

        if(file_exists($filepath)){
            $this->debugMessage('Loading cached file ' . $filepath);
            touch($filepath);
            $content = file_get_contents($filepath);
        } else {
            $this->debugMessage('Caching ' . $url . ' to ' . $filepath);
            $dir = substr($filepath, 0, strrpos($filepath, '/'));

            if(!is_dir($dir)){
                $this->debugMessage('Creating directory ' . $dir);
                mkdir($dir, 0777, true);
            }

            $content = $this->getContents($url);
            if($content !== false){
                file_put_contents($filepath, $content);
            }
        }

        return str_get_html($content);
    }
}
