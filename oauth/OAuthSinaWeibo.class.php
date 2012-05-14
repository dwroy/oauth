<?php

/**
 * open authorization protocol base class
 * @author Roy
 */

class OAuthSinaWeibo extends OAuthProtocolV2
{
	/**
	 * @ignore
	 */
	protected $access_token;
	/**
	 * @ignore
	 */
	protected $refresh_token;
	/**
	 * Set up the API root URL.
	 *
	 * @ignore
	 */
	protected $host = "https://api.weibo.com/2/";
	/**
	 * Set the useragnet.
	 *
	 * @ignore
	 */
	protected $useragent = 'Sina Weibo OAuth2 v0.1';


  protected static $app_key;

  protected static $secret_key;

  public function __construct($user_id, $app_key, $secret_key, $access_token = null, $refresh_token = null) 
  {
    if(empty(self::$app_key))
    {
      self::$app_key = $app_key;
    }

    if(empty(self::$secret_key))
    {
      self::$secret_key = $secret_key;
    }

    parent::__construct($user_id, $access_token, $refresh_token);
    $this->headers[] = "Authorization: OAuth2 ".$this->access_token; 
    $this->headers[] = "API-RemoteIP: " . $_SERVER['REMOTE_ADDR'];
  }

  public function getSnsName() 
  {
    return ApiClientSinaWeibo::SNS_NAME;
  }

  public function call($url, $method, $parameters, $multi = false)
  {
    $response = $this->request($url, $method, $parameters, $multi);
    $data = json_decode($response, true);

    if(is_array($data) && empty($data['error']))
    {
      return $data;
    }

    throw new Exception($response);
  }

  protected function getSnsUserIdFromToken($token) 
  {
    return $token['uid'];
  }

  protected function appKey()
  {
    return self::$app_key;
  }

  protected function secretKey()
  {
    return self::$secret_key;
  }

  protected function authorizeUrl()
  {
    return'https://api.weibo.com/oauth2/authorize'; 
  }

  protected function accessTokenUlr()
  {
    return 'https://api.weibo.com/oauth2/access_token';
  }
}
