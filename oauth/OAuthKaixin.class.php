<?php

/**
 * @author Roy
 */

class OAuthKaixin extends OAuthProtocolV1
{

  /**
   * @var string
   */
  protected static $app_key;

  /**
   *
   * @var string
   */
  protected static $secret_key;
	/**
	 * Set up the API root URL.
	 *
	 * @ignore
	 */
	protected $host = "http://api.kaixin001.com/";
	/**
	 * Set the useragnet.
	 *
	 * @ignore
	 */
	protected $useragent = 'Kaixin OAuth v0.1';


  /**
   *
   * @param type $user_id
   * @param type $app_key
   * @param type $secret_key
   * @param type $access_token
   * @param type $token_secret 
   */
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
  }

  public function getSnsName() 
  {
    return ApiClientKaixin::SNS_NAME;
  }

  public function call($url, $method, $parameters = array(), $multi = false) 
  {
    $parameters = array_merge($parameters, $this->getOAuthParams());
    $parameters['oauth_signature'] = $this->getSignature($this->host.$url, $method, $parameters);
    //$response = $this->request($url, $method, $parameters, $multi);
    $response = json_decode($this->request($url, $method, $parameters, $multi), true);


    if(is_array($response) && empty($response['errcode']))
    {
      return $response;
    }

    throw new Exception($response['msg']);
  }

  protected function getSnsUserIdFromToken($token)
  {
    $me = $this->call('users/me.json', 'get');
    return $me['uid'];
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
    return'http://api.kaixin001.com/oauth/request_token'; 
  }

  protected function authorizeUrl() 
  {
    return'http://api.kaixin001.com/oauth/authorize'; 
  }

  protected function accessTokenUlr() 
  {
    return 'http://api.kaixin001.com/oauth/access_token';
  }
}