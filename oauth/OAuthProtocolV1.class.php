<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class OAuthProtocolV1 extends SwRestProtocol
{

  protected $user_id;
  protected $access_token = '';
  protected $token_secret = '';
  protected $format = '';

  /**
   * @return string request token url
   */
  abstract protected function requestTokenUrl();

  /**
   * @return string app key
   */
  abstract protected function appKey();

  /**
   * @return string app secret key
   */
  abstract protected function secretKey();

  /**
   * @return string authorize url
   */
  abstract protected function authorizeUrl();

  /**
   * @return string access token url
   */
  abstract protected function accessTokenUlr();

  /**
   * @return string get user's sns id
   */
  abstract protected function getSnsUserIdFromToken($token);

  /**
   * @return string get sns name
   */
  abstract public function getSnsName();

  /**
   * get request token
   * @return array
   */
  public function getRequestToken()//default to string null if no callback
  {
    $params = array
    (
      'oauth_consumer_key' => $this->appKey(),
      'oauth_signature_method' => 'HMAC-SHA1',
      'oauth_timestamp' => $_SERVER['REQUEST_TIME'],
      'oauth_nonce' => self::randStr(),
      'oauth_callback' => sfConfig::get('custom_oauth_callback') . '?state=' . $this->getSnsName(),
      'oauth_version' => '1.0'
    );

    $params['oauth_signature'] = $this->getSignature($this->requestTokenUrl(), 'GET', $params);
    $response = $this->get($this->requestTokenUrl(), $params);
    $token = $this->getQueryParams($response);

    if(empty($token['oauth_token']))
    {
      throw new Exception($response);
    }

    return $token;
  }

  /**
   * get authorize url
   * @param type $a
   * @param type $b
   * @param type $c
   * @return string authorize url
   */
  public function getAuthorizeUrl($a = null, $b = null, $c = null)
  {
    $token = $this->getRequestToken();
    $this->saveToken($token, UserOauthPeer::SNS_ACTIVATION_TEMP);
    $url = $this->authorizeUrl() . '?oauth_token=' . $token['oauth_token'];

    if ($this->getSnsName() === ApiClientDouban::SNS_NAME)
    {
      $url .= '&oauth_callback=' . urlencode(sfConfig::get('custom_oauth_callback') . '?state=' . $this->getSnsName());
    }

    return $url;
  }

  /**
   * callback after user authorizion.
   * @param type $code 
   */
  public function callback($code)
  {
    $criteria = new Criteria();
    $criteria->add(UserOauthPeer::USER_ID, $this->user_id);
    $criteria->add(UserOauthPeer::SNS_NAME, $this->getSnsName());
    $user = UserOauthPeer::doSelectOne($criteria);
    $this->access_token = $user->getAccessToken();
    $this->token_secret = $user->getRefreshToken();

    $params = $this->getOAuthParams();

    if ($this->getSnsName() !== ApiClientDouban::SNS_NAME)
    {
      $params['oauth_verifier'] = $code;
    }

    $params['oauth_signature'] = $this->getSignature($this->accessTokenUlr(), 'GET', $params);
    $response = $this->get($this->accessTokenUlr(), $params);
    $token = $this->getQueryParams($response);

    if (empty($token['oauth_token']))
    {
      throw new Exception($response);
    }

    $this->access_token = $token['oauth_token'];
    $this->token_secret = $token['oauth_token_secret'];

    $this->saveToken($token);
  }

  /**
   * get oauth 1.0 common params
   * @return array
   */
  protected function getOAuthParams()
  {
    return array
        (
        'oauth_consumer_key' => $this->appKey(),
        'oauth_token' => $this->access_token,
        'oauth_signature_method' => 'HMAC-SHA1',
        'oauth_timestamp' => $_SERVER['REQUEST_TIME'],
        'oauth_nonce' => self::randStr(),
        'oauth_version' => '1.0',
    );
  }

  /**
   * parse string
   * @param string $query_string
   * @return array
   */
  protected function getQueryParams($query_string)
  {
    $parts = explode('&', $query_string);
    $params = array();

    foreach ($parts as $part)
    {
      $pair = explode('=', $part);
      $params[$pair[0]] = $pair[1];
    }

    return $params;
  }

  /**
   * save token to local db
   * @param array $token 
   */
  protected function saveToken($token, $activation = UserOauthPeer::SNS_ACTIVATION_YES)
  {
    $criteria = new Criteria();
    $criteria->add(UserOauthPeer::USER_ID, $this->user_id);
    $criteria->add(UserOauthPeer::SNS_NAME, $this->getSnsName());
    $user = UserOauthPeer::doSelectOne($criteria);

    if ($user === null)
    {
      $user = new UserOauth();
      $user->setUserId($this->user_id);
      $user->setSnsName($this->getSnsName());
    }

    if( $activation === UserOauthPeer::SNS_ACTIVATION_YES)
    {
      $user->setSnsUserId($this->getSnsUserIdFromToken($token));
    }

    $user->setAccessToken($token['oauth_token']);
    $user->setRefreshToken($token['oauth_token_secret']);
    $user->setActivation($activation);
    $user->save();
  }

  /**
   * generate oatuh 1.0 signatrue
   * @param type $url
   * @param type $method
   * @param type $params
   * @return string
   */
  public function getSignature($url, $method, $params)
  {
    uksort($params, 'strcmp');
    $pairs = array();

    foreach ($params as $key => $value)
    {
      $key = self::urlencode_rfc3986($key);
      if (is_array($value))
      {
        // If two or more parameters share the same name, they are sorted by their value
        // Ref: Spec: 9.1.1 (1)
        natsort($value);
        foreach ($value as $duplicate_value)
        {
          $pairs[] = $key . '=' . self::urlencode_rfc3986($duplicate_value);
        }
      }
      else
      {
        $pairs[] = $key . '=' . self::urlencode_rfc3986($value);
      }
    }

    $sign_parts = self::urlencode_rfc3986(implode('&', $pairs));
    $base_string = implode('&', array(strtoupper($method), self::urlencode_rfc3986($url), $sign_parts));
    $key = self::urlencode_rfc3986($this->secretKey()) . '&' . self::urlencode_rfc3986($this->token_secret);
    $sign = base64_encode(self::hash_hmac('sha1', $base_string, $key, true));

    return $sign;
  }

  /**
   * rfc3986 encode
   * why not encode ~
   *
   * @param string|mix $input
   * @return string
   */
  public static function urlencode_rfc3986($input)
  {
    if (is_array($input))
    {
      return array_map(array(__CLASS__, 'urlencode_rfc3986'), $input);
    }
    else if (is_scalar($input))
    {
      return str_replace('%7E', '~', rawurlencode($input));
    }
    else
    {
      return '';
    }
  }

  /**
   * fix hash_hmac
   *
   * @see hash_hmac
   * @param string $algo
   * @param string $data
   * @param string $key
   * @param bool $raw_output
   */
  public static function hash_hmac($algo, $data, $key, $raw_output = false)
  {
    if (function_exists('hash_hmac'))
    {
      return hash_hmac($algo, $data, $key, $raw_output);
    }

    $algo = strtolower($algo);
    if ($algo == 'sha1')
    {
      $pack = 'H40';
    }
    elseif ($algo == 'md5')
    {
      $pach = 'H32';
    }
    else
    {
      return '';
    }
    $size = 64;
    $opad = str_repeat(chr(0x5C), $size);
    $ipad = str_repeat(chr(0x36), $size);

    if (strlen($key) > $size)
    {
      $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
    }
    else
    {
      $key = str_pad($key, $size, chr(0x00));
    }

    for ($i = 0; $i < strlen($key) - 1; $i++)
    {
      $opad[$i] = $opad[$i] ^ $key[$i];
      $ipad[$i] = $ipad[$i] ^ $key[$i];
    }

    $output = $algo($opad . pack($pack, $algo($ipad . $data)));

    return ($raw_output) ? pack($pack, $output) : $output;
  }

  /**
   * get a random string
   * @staticvar string $pattern
   * @param type $length
   * @return string
   */
  public static function randStr($length = 32)
  {
    static $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for($i = 0; $i < $length; $i++)
    {
      $string .= $pattern[rand(0, 61)];
    }

    return $string;
  }
}