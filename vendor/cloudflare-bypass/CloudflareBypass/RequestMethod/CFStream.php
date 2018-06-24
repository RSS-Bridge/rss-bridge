<?php
namespace CloudflareBypass\RequestMethod;

class CFStream extends \CloudflareBypass\CFCore
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
        if ($root_scope) {
            // Extract array if context is a resource.
            if (is_resource($context)) {
                $context = stream_context_get_options($context);
            }

            $stream = new StreamContext($url, $context);

            // Check if clearance tokens exists in a cache file.
            if (isset($this->cache) && $this->cache) {
                $components = parse_url($url);

                if (($cached = $this->cache->fetch($components['host'])) !== false) {
                    // Set clearance tokens.
                    foreach ($cached as $cookie => $val) {
                        $stream->setCookie($cookie, $val);
                    }
                }
            }
        }

        // Request original page.
        $response = $stream->fileGetContents();
        $response_info = array(
            'http_code'     => $stream->getResponseHeader('http_code')
        );

        if ($root_scope) {
            // Check if page is protected by Cloudflare.
            if (!$this->isProtected($response, $response_info)) {
                return $stream;
            }

            /*
             * 1. Check if user agent is set in context
             */
            if (!$stream->getRequestHeader('User-Agent')) {
                throw new \ErrorException('User agent needs to be set in context!');
            }

            /*
             * 2. Extract "__cfuid" cookie
             */
            if (!($cfduid_cookie = $stream->getCookie('__cfduid'))) {
                return $stream;
            }

            // Clone streamcontext object handle.
            $stream_copy = $stream->copyHandle();
            $stream_copy->setCookie('__cfduid', $cfduid_cookie);
        } else {
            // Not in root scope so $stream is a clone.
            $stream_copy = $stream;
        }

        /*
         * 3. Solve challenge and request clearance link
         */
        if (!($cfclearance_cookie = $stream_copy->getCookie('cf_clearance'))) {
            $stream_copy->setURL($this->getClearanceLink($response, $url));
            $stream_copy->setHttpContextOption('follow_location', 1);

            // GET clearance link.
            $stream_copy->setHttpContextOption('method', 'GET');
            $stream_copy->fileGetContents();

            /*
             * 4. Extract "cf_clearance" cookie
             */
            if (!($cfclearance_cookie = $stream_copy->getCookie('cf_clearance'))) {
                if ($retry > $this->max_retries) {
                    throw new \ErrorException("Exceeded maximum retries trying to get CF clearance!");
                }

                $cfclearance_cookie = $this->create($url, false, $stream_copy, false, $retry+1);
            }
        }

        if (!$root_scope) {
            return $cfclearance_cookie;
        }

        if (isset($this->cache) && $this->cache) {
            $cookies = array();
            $components = parse_url($url);

            foreach ($stream_copy->getCookies() as $cookie => $val) {
                $cookies[$cookie] = $val;
            }

            // Store clearance tokens in cache.
            $this->cache->store($components['host'], $cookies);
        }

        /*
         * 5. Set "__cfduid" and "cf_clearance" in original stream
         */
        $stream->setCookie('__cfduid', $cfduid_cookie);
        $stream->setCookie('cf_clearance', $cfclearance_cookie);
        $stream->updateContext();

        return $stream;
    }
}
