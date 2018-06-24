<?php
namespace CloudflareBypass;

class Storage
{

    /**
     * Path storage
     *
     * @var string
     */
    protected $path;

    /**
     * Creates Cache directory if it does NOT exist
     *
     * @access public
     * @throws \ErrorException if cache directory CAN NOT be created
     */
    public function __construct($path)
    {
        $this->path = $path;

        // Suffix path with forward-slash if not done already.
        if (substr($this->path, -1) !== "/") {
            $this->path .= "/";
        }

        $this->createBaseFolder();
    }

    /**
     * Create base folder path
     *
     * @return void
     */
    public function createBaseFolder()
    {
        if (!is_dir($this->path)) {
            if (!mkdir($this->path, 0755, true)) {
                throw new \ErrorException('Unable to create Cache directory!');
            }
        }
    }

    /**
     * Returns clearance tokens from the specified cache file.
     *
     * @access public
     * @param $site_host Site host
     * @throws \ErrorException if $site_host IS empty
     * @return array Clearance tokens or FALSE
     */
    public function fetch($site_host)
    {
        if (trim($site_host) === "") {
            throw new \ErrorException("Site host should not be empty!");
        }

        // Construct cache file endpoint.
        $file = md5($site_host);

        if (!file_exists($this->path . $file)) {
            if (preg_match('/^www./', $site_host)) {
                $file = md5(substr($site_host, 4));
            }
        }

        if (file_exists($this->path . $file)) {
            return json_decode(file_get_contents($this->path . $file), true);
        }

        return false;
    }

    /**
     * Stores clearance tokens into a cache file in cache folder.
     *
     * File name:           Data:
     * -------------------------------------------
     * md5( file name )     {"__cfduid":"<cfduid>", "cf_clearance":"<cf_clearance>"}
     *
     * @access public
     * @param string $site_host site host name
     * @param array $clearance_tokens Associative array containing "__cfduid" and "cf_clearance" cookies
     * @throws \ErrorException if $site_host IS empty
     * @throws \ErrorException if $clearance_tokens IS missing token fields, OR contains rubbish
     * @throws \ErrorException if file_put_contents FAILS to write to file
     */
    public function store($site_host, $clearance_tokens)
    {
        if (trim($site_host) === "") {
            throw new \ErrorException("Site host should not be empty!");
        }

        if (!(
            is_array($clearance_tokens) && 
            isset($clearance_tokens['__cfduid']) &&
            isset($clearance_tokens['cf_clearance'])
        )) {
            throw new \ErrorException("Clearance tokens not in a valid format!");
        }

        // Construct cache file endpoint.
        $filename = $this->path . md5($site_host);

        // Perform data retention duties.
        $this->retention();

        if (!file_put_contents($filename, json_encode($clearance_tokens))) {
            // Remove file if it exists.
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    /**
     * Deletes files from cache folder which are older than 24 hours.
     *
     * @access private
     */
    private function retention()
    {
        if ($handle = opendir($this->path)) {
            while (false !== ($file = readdir($handle))) {
                // Skip special directories.
                if ('.' === $file || '..' === $file || strpos($file, '.') === 0) {
                    continue;
                }
        
                // Delete file if last modified over 24 hours ago.
                if (time()-filemtime($this->path . "/" . $file) > 86400) {
                    unlink($this->path . "/". $file);
                }
            }
        }
    }
}
