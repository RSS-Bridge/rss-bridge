<?php
namespace CloudflareBypass;

class CFCore extends CFBypass
{
    /**
     * Maximum retries allowed.
     * @var integer
     */ 
    protected $max_retries = 5;

    /**
     * Use caching mechanism.
     * @var bool
     */
    protected $cache = false;

    /**
     * Configuration properties:
     *
     * Key                  Sets
     * -------------------------------------------
     * "cache"              $this->cache (to Storage class)
     * "max_retries"        $this->max_retries (to value given)
     *
     * @access public
     * @param array $config Config containing any of the properties above.
     * @throws \ErrorException if "max_retries" IS NOT an integer
     */
    public function __construct($config = array())
    {

        $cache = isset($config['cache']) ? $config['cache'] : true;
        $cache_path = isset($config['cache_path']) ? $config['cache_path'] : sys_get_temp_dir()."/cf-bypass";

        if ($cache === true) {
            $this->cache = new Storage($cache_path);
        }

        // Set $this->max_retries
        if (isset($config['max_retries'])) {
            if (!is_numeric($config['max_retries'])) {
                throw new \ErrorException('"max_retries" should be an integer!');
            }

            $this->max_retries = $config['max_retries'];
        }
    }
}