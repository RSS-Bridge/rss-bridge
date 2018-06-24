<?php
namespace CloudflareBypass\RequestMethod;

class CFCurl extends \CloudflareBypass\CFCore
{
    /**
     * Bypasses cloudflare using a curl handle. Given a curl handle this method will behave 
     * like "curl_exec" however it will take care of the IUAM page if it pops up. This method 
     * creates a copy of the curl handle passed through for the CF process.
     *
     * @access public
     * @param resource $ch cURL handle
     * @param bool $root_scope Used in retry process (DON'T MODIFY)
     * @param integer $retry   Used in retry process (DON'T MODIFY)
     * @throws \ErrorException if "CURLOPT_USERAGENT" IS NOT set
     * @throws \ErrorException if retry process FAILS more than 4 times consecutively
     * @return string Response body
     */
    public function exec($ch, $root_scope = true, $retry = 1)
    {
        if ($root_scope) {
            $ch = new Curl($ch);
            
            // Check if clearance tokens exists in a cache file. 
            if (isset($this->cache) && $this->cache) {
                $info = $ch->getinfo();
                $components = parse_url($info['url']);

                // Set clearance tokens.
                if (($cached = $this->cache->fetch($components['host'])) !== false) {
                    foreach ($cached as $cookie => $val) {
                        $ch->setopt(CURLOPT_COOKIELIST, 'Set-Cookie: ' . $val);
                    }
                }
            }

            // Request original page.
            $response = $ch->exec();
            $response_info = $ch->getinfo();

            // Check if page is protected by Cloudflare.
            if (!$this->isProtected($response, $response_info)) {
                return $response;
            }

            // Clone curl object handle.
            $ch_copy = $ch->copyHandle();

            // Enable response header and cookie storage.
            $ch_copy->enableResponseStorage();

            // Assign neccessary options.
            $ch_copy->setopt(CURLINFO_HEADER_OUT, true);
        } else {
            // Not in root scope so $ch is a clone.
            $ch_copy = $ch;
        }

        // Request UAM page with necessary settings.
        $uam_response = $ch_copy->exec();
        $uam_response_info = $ch_copy->getinfo();

        if ($root_scope) {
            /*
             * 1. Check if user agent is set in cURL handle
             */
            if (!$ch_copy->getRequestHeader('User-Agent')) {
                throw new \ErrorException('CURLOPT_USERAGENT is a mandatory field!');
            }

            /*
             * 2. Extract "__cfuid" cookie
             */
            if (!($cfduid_cookie = $ch_copy->getCookie('__cfduid'))) {
                return $response;
            }
            
            $ch_copy->setopt(CURLOPT_COOKIELIST, $cfduid_cookie);
        }

        /*
         * 3. Solve challenge and request clearance link
         */
        if (!($cfclearance_cookie = $ch_copy->getCookie('cf_clearance'))) {
            $ch_copy->setopt(CURLOPT_URL, $this->getClearanceLink($uam_response, $uam_response_info['url']));
            $ch_copy->setopt(CURLOPT_FOLLOWLOCATION, true);

            // GET clearance link.
            $ch_copy->setopt(CURLOPT_CUSTOMREQUEST, 'GET');
            $ch_copy->setopt(CURLOPT_HTTPGET, true);
            $ch_copy->exec();

            /*
             * 4. Extract "cf_clearance" cookie
             */
            if (!($cfclearance_cookie = $ch_copy->getCookie('cf_clearance'))) {
                if ($retry > $this->max_retries) {
                    throw new \ErrorException("Exceeded maximum retries trying to get CF clearance!");
                }

                $cfclearance_cookie = $this->exec($ch, false, $retry+1);
            }
        }

        // Not in root scope, return clearance cookie.
        if ($cfclearance_cookie && !$root_scope) {
            return $cfclearance_cookie;
        }

        if (isset($this->cache) && $this->cache) {
            $cookies = array();
            $components = parse_url($uam_response_info['url']);

            foreach ($ch_copy->getCookies() as $cookie => $val) {
                $cookies[$cookie] = $val;
            }

            // Store clearance tokens in cache.
            $this->cache->store($components['host'], $cookies);
        }
       
        /*
         * 5. Set "__cfduid" and "cf_clearance" in original cURL handle
         */
        foreach ($ch_copy->getCookies() as $cookie => $val) {
            $ch->setopt(CURLOPT_COOKIELIST, 'Set-Cookie: ' . $val);
        }

        return $ch->exec();
    }
}