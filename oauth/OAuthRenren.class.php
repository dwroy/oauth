<?php

/**
 * @author Roy
 */

class OAuthRenren extends OAuthProtocolV2
{
  protected static $app_key;

  protected static $secret_key;

  protected $format = 'json';

  /**
	 * Set up the API root URL.
	 *
	 * @ignore
	 */
	protected $host = 'http://api.renren.com/restserver.do';

  /**
	 * Set the useragnet.
	 *
	 * @ignore
	 */
	protected $useragent = 'Renren OAuth2 v0.1';

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
  }

  public function getSnsName() 
  {
    return ApiClientRenren::SNS_NAME;
  }


  protected function getSnsUserIdFromToken($token)
  {
    return $token['user']['id'];
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
    return'https://graph.renren.com/oauth/authorize'; 
  }

  protected function accessTokenUlr()
  {
    return 'https://graph.renren.com/oauth/token';
  }


  public function call($url, $method, $parameters, $multi = false) 
  {
    $parameters['format'] = $this->format;
    $parameters['v'] = '1.0';
    $parameters['access_token'] = $this->access_token;
    $parameters['sig'] = $this->getRenrenSignature($parameters);

    $response = json_decode($this->request($url, $method, $parameters, $multi), true);

    if(is_array($response) && empty($response['error']))
    {
      return $response;
    }

    $this->refreshToken();

    $response = json_decode($this->request($url, $method, $parameters, $multi), true);

    if(is_array($response) && empty($response['error']))
    {
      return $response;
    }

    throw new Exception('call api failure');
  }

  protected function getRenrenSignature($params)
  {
    ksort($params);
    $str = '';

    foreach($params as $k=>$v)
    {
      $str .= $k.'='.$v;
    }
    
    return md5($str.$this->secretKey());
	}
}