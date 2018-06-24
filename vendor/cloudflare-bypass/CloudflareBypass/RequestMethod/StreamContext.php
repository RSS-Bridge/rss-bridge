<?php
namespace CloudflareBypass\RequestMethod;

class StreamContext
{
    /**
     * Cookies set per request.
     * @var array
     */
    private $cookies = array();

    /**
     * Cookies set per request.
     * @var array
     */
    private $cookies_original = array();

    /**
     * Request headers per request.
     * @var array
     */
    private $request_headers = array();

    /**
     * Response headers per request.
     * @var array
     */
    private $response_headers = array();

    /**
     * Context options.
     * @var array
     */
    private $context = array();

    /**
     * Stream context.
     * @var resource
     */
    private $stream_context = array();

    /**
     * Follow location.
     * @var bool
     */
    private $follow_location = false;

    /**
     * Request URL.
     * @var string
     */
    private $url;

    /**
     * Sets $this->URL to request URL.
     * Sets $this->context to given context options array.
     * Populates cookies in context options array into $this->cookies.
     * 
     * @access public
     * @param string $url Request URL
     * @param array $context Array of context options
     * @throws \ErrorException if $url is not a valid URL
     * @throws \ErrorException if $context is not a valid context
     */
    public function __construct($url, $context)
    {
        if (!is_string($url) || !parse_url($url)) {
            throw new \ErrorException('Url is not valid!');
        }

        if (!is_array($context) || !isset($context['http'])) {
            throw new \ErrorException('Context is not valid!');
        }

        $this->url = $url;
        $this->context = $context;

        $this->updateRequestHeaders();
        $this->updateCookies();
    }

    /**
     * Clones StreamContext object handle.
     *
     * @access public
     * @return object StreamContext object
     */
    public function copyHandle()
    {
        return new StreamContext($this->url, $this->context);
    }

    /**
     * Returns stream context.
     *
     * @access public
     * @return resource
     */
    public function getContext()
    {
        return $this->stream_context;
    }

    /**
     * Updates stream context
     *
     * @access public
     */
    public function updateContext()
    {
        $this->stream_context = stream_context_create($this->context);
    }

    /**
     * Get cookies set for current request.
     *
     * @access public
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Get request headers set for current request.
     *
     * @access public
     * @return array
     */
    public function getRequestHeaders()
    {
        return $this->request_headers;
    }

    /**
     * Get response headers set for current request.
     *
     * @access public
     */
    public function getResponseHeaders()
    {
        return $this->response_headers;
    }
 
    /**
     * Returns full config for specified cookie name.
     *
     * @access public
     * @param string $cookie Cookie name
     * @return string Cookie value or NULL
     */
    public function getCookie($cookie)
    {
        if (isset($this->cookies[$cookie])) {
            return $this->cookies[$cookie];
        }

        return null;
    }

    /**
     * Populates response headers into $this->response_headers.
     * Populates cookies set in response headers into $this->cookies.
     *
     * @access public
     * @see http://php.net/file-get-contents
     * @return string
     */
    public function fileGetContents()
    {
        $this->updateContext();

        // cURL response header collection.
        $curl_http_response_header = [];

        $follow_location = isset($this->context['http']['follow_location']) ? $this->context['http']['follow_location'] : 1;
        $method = isset($this->context['http']['method']) ? $this->context['http']['method'] : 'GET';

        // Unfortunately file_get_contents doesn't return contents of a 503 page.
        // Please advise if there is a better way to do this.
        $ch = curl_init($this->url);

        // Set options to match context.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getCurlHttpHeaders());
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, 
            function    ($ch, $header) 
            use         (&$curl_http_response_header) {
            
            // Trim response header.
            $trimmed_header = str_replace("\r\n", "", $header);
            
            // If not empty, add response header to header collection.
            if (!empty($trimmed_header)) { 
                $curl_http_response_header[] = $trimmed_header;
            }
            
            return strlen($header);
        });
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow_location);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Get request body.
        $content = curl_exec($ch);
        curl_close($ch);

        // Get response headers.
        if ($path = $this->updateResponseHeaders($curl_http_response_header)) {
            // Follow location...
            if (strpos($path, '/') === 0) {
                $parsed_url = parse_url($this->url);
                
                $scheme     = isset($parsed_url['scheme'])   ? $parsed_url['scheme'] . '://' : '';
                $host       = isset($parsed_url['host'])     ? $parsed_url['host'] : '';
                $port       = isset($parsed_url['port'])     ? ':' . $parsed_url['port'] : '';
                $user       = isset($parsed_url['user'])     ? $parsed_url['user'] : '';
                $pass       = isset($parsed_url['pass'])     ? ':' . $parsed_url['pass'] : '';
                $pass       = ($user || $pass)               ? $pass . '@' : '';
                
                $this->url = $scheme . $user . $pass . $host . $port . $path;
            } else {
                $this->url .= $path;
            }

            $content = $this->fileGetContents();
        }

        return $content;
    }

    /**
     * Returns value of specified request header.
     *
     * @access public
     * @param string $header Request header
     * @return string Request header or NULL
     */
    public function getRequestHeader($header)
    {
        if (isset($this->request_headers[$header])) {
            return $this->request_headers[$header];
        }

        if ($header == 'User-Agent' && isset($this->context['http']['user_agent'])) {
            return $this->context['http']['user_agent'];
        }

        return null;
    }

    /**
     * Returns value of specified response header.
     *
     * @access public
     * @param string $header Response header
     * @return string Response header or NULL
     */
    public function getResponseHeader($header)
    {
        if (isset($this->response_headers[$header])) {
            return $this->response_headers[$header];
        }
    }

    /**
     * Set Request URL.
     *
     * @access public
     * @param string $url Request URL
     * @throws \ErrorException if $url is not a valid URL
     */
    public function setURL($url)
    {
        if (!is_string($url) || !parse_url($url)) {
            throw new \ErrorException('Url is not valid!');
        }

        $this->url = $url;
    }

    /**
     * Sets option in HTTP context array.
     *
     * @access public
     * @param string $name Option name
     * @param string $val Option value
     */
    public function setHttpContextOption($name, $val)
    {
        if ($name === 'follow_location') {
            $this->follow_location = $val;
            $this->context['http']['follow_location'] = 0;
        } else {
            $this->context['http'][$name] = $val;
        }
    }

    /**
     * Set Cookie in context array.
     *
     * @access public
     * @param string $name Cookie name
     * @param string $val Cookie value
     */
    public function setCookie($name, $val)
    {   
        // Extract value from cookie string.
        $pos = strpos($val, '=');
  
        if ($pos !== false) {
            $val = substr($val, strpos($val, '=')+1);
        }

        $settings = explode(';', $val);

        if (isset($this->request_headers['Cookie'])) {
            // Add cookie to cookie list.
            $match = "/(Cookie:.+?)\r\n/";
            $replace = '$1' . $name . '=' . $settings[0] . ";\r\n";

            if (strpos($this->request_headers['Cookie'], $name . '=') !== false) {
                // Update value for specified cookie.
                $match = "/(Cookie:.+?)$name=(.+?);/";
                $replace = '$1' . $name . '=' . $settings[0] . ';';
            }

            $this->context['http']['header'] = preg_replace($match, $replace, $this->context['http']['header']);
        } else {
            if (empty($this->request_headers)) {
                $this->context['http']['header'] = "";
            } elseif (substr($this->context['http']['header'], -2) !== "\r\n") {
                $this->context['http']['header'] .= "\r\n";
            }

            // Add cookie header with new cookie.
            $this->context['http']['header'] .= 'Cookie:' . $name . '=' . $settings[0] . ";\r\n";
        }

        $this->updateRequestHeaders();
        $this->updateCookies();
    }

    /**
     * Converts context headers into format compatible with "CURLOPT_HTTPHEADER".
     *
     * @access private
     * @return array
     */
    public function getCurlHttpHeaders()
    {
        // Convert headers into format compatible with cURL
        $http_headers = explode("\r\n", $this->context['http']['header']);

        // User agent can be set in 2 places, "header" and "user_agent".
        if (strpos($this->context['http']['header'], 'User-Agent') === false) {
            if (isset($this->context['http']['user_agent'])) {
                $http_headers[] = 'User-Agent: ' . $this->context['http']['user_agent'];
            }
        }

        return $http_headers;
    }

    /**
     * Updates $this->request_headers to match with context array. 
     *
     * @access private
     */
    private function updateRequestHeaders()
    {
        // Extract request headers.
        $headers = explode("\r\n", $this->context['http']['header']);
        $headers_count = count($headers);

        // Set request headers.
        for ($i=0; $i<$headers_count; $i++) {
            if (strpos($headers[$i], ':') !== false) {
                list($name, $val) = explode(':', $headers[$i]);
                $this->request_headers[$name] = $val;
            }
        }
    }

    /**
     * Updates $this->response_headers to match response headers from current request.
     *
     * @access private
     * @param array $http_response_header
     * @return string URI to follow
     */
    private function updateResponseHeaders($headers)
    {
        $this->response_headers = array();
        $follow_uri = "";

        foreach ($headers as $header) {
            if (strpos($header, 'HTTP') === 0) {
                // Store HTTP code as its own response header.
                $matches = explode(' ', $header);
                $this->response_headers['http_code'] = $matches[1];
            } elseif (strpos($header, 'Set-Cookie') !== false) {
                // Extract response cookie.
                list($_, $fullval) = explode(':', $header);
                list($cookie, $val) = explode('=', trim($fullval));
                // Ignore other config options.
                $val = substr($val, 0, strpos($val, ';'));
                // Store cookie.
                $this->setCookie($cookie, $val);
            } elseif (strpos($header, ':') !== false) {
                // Store response header.
                list($name, $val) = explode(':', $header);
                $this->response_headers[$name] = $val;
                // Store location to follow.
                if ($name === "Location") {
                    $follow_uri = $val;
                }
            }
        }

        // Update cookies.
        $this->updateRequestHeaders();
        $this->updateCookies();
        
        // Follow location if location is set and follow location is enabled
        if ($this->follow_location && $follow_uri) {
            return trim($follow_uri);
        }

        return "";
    }

    /**
     * Updates $this->cookies to match with context array.
     *
     * @access private
     */
    private function updateCookies()
    {
        // Extract cookies.        
        if (isset($this->request_headers['Cookie'])) {
            $cookies = explode(';', $this->request_headers['Cookie']);
            $cookies_count = count($cookies);

            // Set cookies.
            for ($i=0; $i<$cookies_count; $i++) {
                if (strpos($cookies[$i], '=') !== false) {
                    list($name, $val) = explode('=', trim($cookies[$i]));
                    $this->cookies[$name] = $name . '=' . $val . ';';
                    $this->cookies_original[$name] = $val;
                }
            }
        }
    }

    /**
     * Retrieve cookies raw
     *
     * @return array
     */
    public function getCookiesOriginal()
    {
        return $this->cookies_original;
    }
}
