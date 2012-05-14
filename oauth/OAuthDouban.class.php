<?php

/**
 * @author Roy
 */

class OAuthDouban extends OAuthProtocolV1
{
  protected static $app_key;

  protected static $secret_key;
	/**
	 * Set up the API root URL.
	 *
	 * @ignore
	 */
	protected $host = "http://api.douban.com/";
	/**
	 * Set the useragnet.
	 *
	 * @ignore
	 */
	protected $useragent = 'Douban OAuth v0.1';


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

  /**
   * sns name
   * @return string
   */
  public function getSnsName() 
  {
    return ApiClientDouban::SNS_NAME;
  }

  /**
   * call api with rest protocol
   * @param type $url
   * @param type $method
   * @param type $parameters
   * @param type $multi
   * @return array
   */
  public function call($url, $method, $parameters, $multi = false)
  {
    $parameters['alt'] = $this->format;
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
    return isset($token['douban_user_id']) ? $token['douban_user_id'] : 0;
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
    return'http://www.douban.com/service/auth/request_token';
  }

  protected function authorizeUrl()
  {
    return'http://www.douban.com/service/auth/authorize';
  }

  protected function accessTokenUlr()
  {
    return 'http://www.douban.com/service/auth/access_token';
  }
}