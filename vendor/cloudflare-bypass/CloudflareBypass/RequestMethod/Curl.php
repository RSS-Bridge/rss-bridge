<?php
namespace CloudflareBypass\RequestMethod;

class Curl
{
    /**
     * Cookies set per request.
     * @var array
     */
    private $cookies = array();

    /**
     * Request headers per request.
     * @var array
     */
    private $request_headers = array();

    /**
     * cURL handle.
     * @var resource
     */
    private $ch;

    /**
     * Sets $this->ch to specified cURL handle.
     *
     * @access public
     * @param resource $curl cURL handle
     * @throws \ErrorException if $ch IS NOT a cURL handle
     */
    public function __construct($ch = null)
    {
        if (!is_resource($ch)) {
            throw new \ErrorException('Curl handle is required!');
        }

        $this->ch = $ch;
    }

    /**
     * Get request headers set for current request.
     *
     * @access public
     */
    public function getRequestHeaders()
    {
        return $this->request_headers;
    }

    /**
     * Get cookies set for current request.
     *
     * @access public
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Enables cURL object handle to fetch and store response headers and cookies.
     * WARNING: Overrides "CURLOPT_HEADERFUNCTION".
     *
     * @access public
     * @return bool
     */
    public function enableResponseStorage()
    {
        return $this->setopt(CURLOPT_HEADERFUNCTION, array($this, 'storeResponseHeader'));
    }

    /**
     * Clones cURL object handle.
     *
     * @access public
     * @see http://php.net/curl-copy-handle
     * @return object cURL object
     */
    public function copyHandle()
    {
        return new Curl(curl_copy_handle($this->ch));
    }

    /**
     * @access public
     * @see http://php.net/curl_setopt
     * @param integer $opt
     * @param mixed $val
     * @return bool
     */
    public function setopt($opt, $val)
    {
        return curl_setopt($this->ch, $opt, $val);
    }

    /**
     * @access public
     * @see http://php.net/curl-getinfo
     * @param integer $opt (optional)
     * @return mixed $val
     */
    public function getInfo($opt = null)
    {
        $args = array($this->ch);

        if (func_num_args()) {
            $args[] = $opt;
        }
        
        return call_user_func_array('curl_getinfo', $args);
    }

    /**
     * Truncates cookie feed.
     * Truncates request headers.
     *
     * @access public
     * @see http://php.net/curl_exec
     * @return mixed
     */
    public function exec()
    {
        $this->cookies = array();
        $this->request_headers = array();

        $res = curl_exec($this->ch);
        $info = curl_getinfo($this->ch);

        if (isset($info['request_header'])) {
            // Store request headers and cookies.
            $headers = explode("\n", $info['request_header']);
            $headers_count = count($headers);

            for ($i=0; $i<$headers_count; $i++) {
                $this->storeRequestHeader($this->ch, $headers[$i]);
            }
        }

        return $res;
    }

    /**
     * @access public
     * @see http://php.net/curl_close
     */
    public function close()
    {
        curl_close($this->ch);
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

        return null;
    }

    /**
     * Populates request headers into $this->request_headers (header name -> value).
     * Populates cookies into $this->cookies (cookie name -> cookie value).
     *
     * @access private 
     * @param resource $ch cURL handle
     * @param string $header Request header
     */
    private function storeRequestHeader($ch, $header)
    {
        if (strpos($header, ':') !== false) {
            // Match request header and value.
            list($name, $val) = explode(':', $header);
            $this->request_headers[$name] = $val;
        }

        if (strpos($header, 'Cookie') !== false) {
            // Convert string into array of cookies.
            $cookies = explode(';', substr($header, strpos($header, ':')+1));
            $cookies_count = count($cookies);

            // Store cookies.
            for ($i=0; $i<$cookies_count; $i++) {
                list($cookie, $val) = explode('=', trim($cookies[$i]));
                $this->cookies[$cookie] = $cookie . '=' . $val . ';';                   
            }
        }
    }

    /**
     * Populates cookies set into $this->cookies (cookie name -> full cookie).
     *
     * @access private
     * @param resource $ch cURL handle
     * @param string $header Request header
     * @return integer Byte-length of request header
     */
    private function storeResponseHeader($ch, $header)
    {
        if (strpos($header, 'Set-Cookie') !== false) {
            // Match cookie name and value.
            preg_match('/Set\-Cookie: ([^=]+)(.+)/', $header, $matches);
            $this->cookies[$matches[1]] = $matches[1] . $matches[2];
        }

        return strlen($header);
    }
}