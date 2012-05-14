<?php

/**
 * Restfull api protocol, base class for all third part api protocol, such as oauth
 * Modify from sina weibo php sdk
 * @author Roy
 */
class SwRestProtocol
{

  /**
   * Contains the last HTTP status code returned. 
   *
   * @ignore
   */
  protected $http_code;
  protected $host;

  /**
   * Contains the last API call.
   *
   * @ignore
   */
  protected $url;

  /**
   * Set timeout default.
   *
   * @ignore
   */
  protected $timeout = 30;

  /**
   * Set connect timeout.
   *
   * @ignore
   */
  protected $connecttimeout = 30;

  /**
   * Verify SSL Cert.
   *
   * @ignore
   */
  protected $ssl_verifypeer = FALSE;

  /**
   * Respons format.
   *
   * @ignore
   */
  protected $format = '';

  /**
   * Decode returned json data.
   *
   * @ignore
   */
  protected $decode_json = TRUE;

  /**
   * Contains the last HTTP headers returned.
   *
   * @ignore
   */
  protected $http_info;

  /**
   * Set the useragnet.
   *
   * @ignore
   */
  protected $useragent = 'Sidways Patrick REST 0.1';

  /**
   * print the debug info
   *
   * @ignore
   */
  protected $debug = FALSE;

  /**
   * http headers
   * @var array
   */
  protected $headers = array();

  /**
   * boundary of multipart
   * @ignore
   */
  protected static $boundary = '';

  public function setHeaders($header)
  {
    $this->headers[] = $header;
  }

  public function setHost($host)
  {
    $this->host;
  }

  /**
   * GET wrappwer for request.
   *
   * @return mixed
   */
  public function get($url, $parameters = array())
  {
    return $this->request($url, 'get', $parameters);
  }

  /**
   * POST wreapper for request.
   *
   * @return mixed
   */
  public function post($url, $parameters = array(), $multi = false)
  {
    return $this->request($url, 'post', $parameters, $multi);
  }

  function delete($url, $parameters = array())
  {
    return $this->request($url, 'delete', $parameters);
  }

  /**
   * Format and sign an OAuth / API request
   *
   * @return string
   * @ignore
   */
  public function request($url, $method, $parameters, $multi = false)
  {
    if (strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0)
    {
      $url = "{$this->host}{$url}";
    }

    switch ($method)
    {
      case 'get':
        $url = $url . '?' . http_build_query($parameters);
        return $this->http($url, 'get');
      default:
        $headers = array();
        if (!$multi && (is_array($parameters) || is_object($parameters)))
        {
          $body = http_build_query($parameters);
        }
        else
        {
          $body = self::build_http_query_multi($parameters);
          $headers[] = "Content-Type: multipart/form-data; boundary=" . self::$boundary;
        }
        return $this->http($url, $method, $body, $headers);
    }
  }

  /**
   * Make an HTTP request
   *
   * @return string API results
   * @ignore
   */
  public function http($url, $method, $postfields = NULL, $headers = array())
  {
    $this->http_info = array();
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_ENCODING, "");
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
    curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
    curl_setopt($ci, CURLOPT_HEADER, FALSE);

    switch ($method)
    {
      case 'post':
        curl_setopt($ci, CURLOPT_POST, TRUE);
        if (!empty($postfields))
        {
          curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
          $this->postdata = $postfields;
        }
        break;
      case 'delete':
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($postfields))
        {
          $url = "{$url}?{$postfields}";
        }
    }

    curl_setopt($ci, CURLOPT_URL, $url);
    curl_setopt($ci, CURLOPT_HTTPHEADER, $this->headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);
    $response = curl_exec($ci);
    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
    $this->url = $url;
    curl_close($ci);

    return $response;
  }

  /**
   * Get the header info to store.
   *
   * @return int
   * @ignore
   */
  public function getHeader($ch, $header)
  {
    $i = strpos($header, ':');
    if (!empty($i))
    {
      $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
      $value = trim(substr($header, $i + 2));
      $this->http_header[$key] = $value;
    }
    return strlen($header);
  }

  /**
   * @ignore
   */
  public static function build_http_query_multi($params)
  {
    if (!$params)
      return '';

    uksort($params, 'strcmp');

    $pairs = array();

    self::$boundary = $boundary = uniqid('------------------');
    $MPboundary = '--' . $boundary;
    $endMPboundary = $MPboundary . '--';
    $multipartbody = '';

    foreach ($params as $parameter => $value)
    {

      if (in_array($parameter, array('pic', 'image')) && $value{0} == '@')
      {
        $url = ltrim($value, '@');
        $content = file_get_contents($url);
        $array = explode('?', basename($url));
        $filename = $array[0];

        $multipartbody .= $MPboundary . "\r\n";
        $multipartbody .= 'Content-Disposition: form-data; name="' . $parameter . '"; filename="' . $filename . '"' . "\r\n";
        $multipartbody .= "Content-Type: image/unknown\r\n\r\n";
        $multipartbody .= $content . "\r\n";
      }
      else
      {
        $multipartbody .= $MPboundary . "\r\n";
        $multipartbody .= 'content-disposition: form-data; name="' . $parameter . "\"\r\n\r\n";
        $multipartbody .= $value . "\r\n";
      }
    }

    return $multipartbody . $endMPboundary;
  }

}
