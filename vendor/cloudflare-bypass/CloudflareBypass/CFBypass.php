<?php
namespace CloudflareBypass;

class CFBypass
{
    /**
     * Check if page is a IUAM page. Given page content and headers, will check if page is
     * protected by CloudFlare (to my best of judgment).
     *
     * Response Headers Properties
     *
     * Name                 Description
     * -------------------------------------------
     * http_code            Response http code
     *
     * @access protected
     * @param string $content Response body
     * @param array $headers Response headers
     * @return bool 
     */
    protected function isProtected($content, $headers)
    {
        /*
         * 1. Cloudflare UAM page always throw a 503
         */
        if ((int)$headers['http_code'] !== 503) {
            return false;
        }

        /*
         * 2. Cloudflare UAM page contains the following strings:
         * - jschl_vc
         * - pass
         * - jschl_answer
         * - /cdn-cgi/l/chk_jschl
         */
        if (!(
            strpos($content, "jschl_vc")                !== false &&
            strpos($content, "pass")                    !== false &&
            strpos($content, "jschl_answer")            !== false &&
            strpos($content, "/cdn-cgi/l/chk_jschl")    !== false
        )) {
            return false;
        }

        return true;
    }

    /**
     * Get clearance link. Given IUAM page contents, will solve JS challenge and return clearance link
     * e.g. http://test/cdn-cgi/l/chk_jschl?jschl_vc=X&pass=X&jschl_answer=X
     *
     * @access protected
     * @param string $content Response body
     * @param string $url Request URL
     * @throws \ErrorException if values for the "jschl_vc" / "pass" inputs CAN NOT be found
     * @throws \ErrorException if JavaScript arithmetic code CAN NOT be extracted
     * @throws \ErrorException if PHP evaluation of JavaScript arithmetic code FAILS
     * @return string Clearance link
     */
    protected function getClearanceLink($content, $url)
    {
        /*
         * 1. Mimic waiting process
         */
        sleep(4);
        
        /*
         * 2. Extract "jschl_vc" and "pass" params
         */
        preg_match_all('/name="\w+" value="(.+?)"/', $content, $matches);
        
        if (!isset($matches[1]) || !isset($matches[1][1])) {
            throw new \ErrorException('Unable to fetch jschl_vc and pass values; maybe not protected?');
        }
        
        $params = array();
        list($params['jschl_vc'], $params['pass']) = $matches[1];

        // Extract CF script tag portion from content.
        $cf_script_start_pos    = strpos($content, 's,t,o,p,b,r,e,a,k,i,n,g,f,');
        $cf_script_end_pos      = strpos($content, '</script>', $cf_script_start_pos);
        $cf_script              = substr($content, $cf_script_start_pos, $cf_script_end_pos-$cf_script_start_pos);

        /*
         * 3. Extract JavaScript challenge logic
         */
        preg_match_all('/:[\/!\[\]+()]+|[-*+\/]?=[\/!\[\]+()]+/', $cf_script, $matches);
        
        if (!isset($matches[0]) || !isset($matches[0][0])) {
            throw new \ErrorException('Unable to find javascript challenge logic; maybe not protected?');
        }
        
        try {
            /*
             * 4. Convert challenge logic to PHP
             */
            $php_code = "";
            foreach ($matches[0] as $js_code) {
                // [] causes "invalid operator" errors; convert to integer equivalents
                $js_code = str_replace(array(
                    ")+(",  
                    "![]",
                    "!+[]", 
                    "[]"
                ), array(
                    ").(", 
                    "(!1)", 
                    "(!0)", 
                    "(0)"
                ), $js_code);

                $php_code .= '$params[\'jschl_answer\']' . ($js_code[0] == ':' ? '=' . substr($js_code, 1) : $js_code) . ';';
            }
            
            /*
             * 5. Eval PHP and get solution
             */
            eval($php_code);

            // toFixed(10).
            $params['jschl_answer'] = round($params['jschl_answer'], 10);

            // Split url into components.
            $uri = parse_url($url);

            // Add host length to get final answer.
            $params['jschl_answer'] += strlen($uri['host']);

            /*
             * 6. Generate clearance link
             */
            return sprintf("%s://%s/cdn-cgi/l/chk_jschl?%s", 
                $uri['scheme'], 
                $uri['host'], 
                http_build_query($params)
            );
        }
        catch (Exception $ex) {
            // PHP evaluation bug; inform user to report bug
            throw new \ErrorException(sprintf('Something went wrong! Please report an issue: %s', $ex->getMessage()));
        }
    }
}
