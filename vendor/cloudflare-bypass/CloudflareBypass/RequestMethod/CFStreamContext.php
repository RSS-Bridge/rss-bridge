<?php
namespace CloudflareBypass\RequestMethod;

class CFStreamContext extends \CloudflareBypass\CFCore
{
    /**
     * Given a URL and a context (stream / array), if URL is protected by the Cloudflare,
     * this method will add the "__cfduid" and "cf_clearance" cookies to the "Cookie" header 
     * (or update them if they exist).
     *
     * @access public
     * @param string $url Request URL
     * @param mixed $context Stream / array of context options
     * @param resource $stream Stream context; used in retry process (DONT MODIFY)
     * @param bool $root_scope Used in retry process (DON'T MODIFY)
     * @param integer $retry   Used in retry process (DON'T MODIFY)
     * @throws \ErrorException if $url is not a valid URL
     * @throws \ErrorException if $context if not valid context
     * @return resource $context
     */
    public function create($url, $context, $stream = null, $root_scope = true, $retry = 1)
    {
        $stream_cf_wrapper = new CFStream(array(
            'cache'         => $this->cache,
            'max_retries'   => $this->max_retries
        ));

        $stream = $stream_cf_wrapper->create($url, $context, $stream, $root_scope, $retry);

        return $stream->getContext();
    }
}
