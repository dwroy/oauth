<?php

/**
 * open authorization protocol base class
 * @author Roy
 */

class OAuthTencentWeibo extends OAuthProtocolV1
{

  protected static $app_key;

  protected static $secret_key;

	/**
	 * Set up the API root URL.
	 *
	 * @ignore
	 */
	protected $host = "http://open.t.qq.com/api/";
	/**
	 * Set the useragnet.
	 *
	 * @ignore
	 */
	protected $useragent = 'Tencent Weibo OAuth v0.1';

  public function __construct($user_id, $app_key, $secret_key, $access_token = null, $token_secret = null) 
  {
    if(empty(self::$app_key))
    {
      self::$app_key = $app_key;
    }

    if(empty(self::$secret_key))
    {
      self::$secret_key = $secret_key;
    }

    $this->user_id = $user_id;
    $this->access_token = $access_token;
    $this->token_secret = $token_secret;
    $this->format = 'json';
  }

  public function getSnsName() 
  {
    return ApiClientTencentWeibo::SNS_NAME;
  }

  public function call($url, $method, $parameters, $multi = false)
  {
    $parameters['format'] = $this->format;
    $parameters = array_merge($parameters, $this->getOAuthParams());
    $parameters['oauth_signature'] = $this->getSignature($this->host.$url, $method, $parameters);
    $response = json_decode($this->request($url, $method, $parameters, $multi), true);

    if(is_array($response) && empty($response['errcode']))
    {
      return $response;
    }

    throw new Exception($response['msg']);
  }

  protected function getSnsUserIdFromToken($token) 
  {
    return isset($token['name']) ? $token['name'] : 0;
  }

  protected function appKey() 
  {
    return self::$app_key;
  }

  protected function secretKey() 
  {
    return self::$secret_key;
  }

  protected function requestTokenUrl() 
  {
    return'https://open.t.qq.com/cgi-bin/request_token'; 
  }

  protected function authorizeUrl() 
  {
    return'http://open.t.qq.com/cgi-bin/authorize'; 
  }

  protected function accessTokenUlr() 
  {
    return 'http://open.t.qq.com/cgi-bin/access_token';
  }
}
