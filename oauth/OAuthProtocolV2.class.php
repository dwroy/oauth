<?php

/**
 * open authorization protocol base class
 * @author Roy
 */
abstract class OAuthProtocolV2 extends SwRestProtocol
{

  protected $user_id;

  /**
   * @ignore
   */
  protected $access_token;

  /**
   * @ignore
   */
  protected $refresh_token;

  /**
   * construct WeiboOAuth object
   */
  public function __construct($user_id, $access_token = NULL, $refresh_token = NULL)
  {
    $this->user_id = $user_id;
    $this->access_token = $access_token;
    $this->refresh_token = $refresh_token;
  }

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
   *
   * @param type $url
   * @param type $response_type
   * @param type $state
   * @param type $display
   * @return type 
   */
  public function getAuthorizeURL($response_type = 'code', $state = NULL, $display = NULL)
  {
    $params = array();
    $params['client_id'] = $this->appKey();
    $params['redirect_uri'] = sfConfig::get('custom_oauth_callback');
    $params['response_type'] = $response_type;
    $params['state'] = $this->getSnsName();
    $params['display'] = $display;

    return $this->authorizeUrl() . "?" . http_build_query($params);
  }

  public function callback($code)
  {
    $keys = array
        (
        'code' => $code,
        'redirect_uri' => sfConfig::get('custom_oauth_callback'),
    );

    $this->saveToken($this->getAccessToken($keys));
  }

  public function refreshToken()
  {
    $this->saveToken($this->getAccessToken(array(), 'token'));
  }

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

    $user->setSnsUserId($this->getSnsUserIdFromToken($token));
    $user->setAccessToken($token['access_token']);
    $user->setRefreshToken($token['refresh_token']);
    $user->setActivation($activation);
    $user->save();

    $this->access_token = $token['access_token'];
    $this->refresh_token = $token['refresh_token'];
  }

  /**
   *
   * @param type $keys
   * @param type $type
   * @return type 
   */
  public function getAccessToken($keys = array(), $type = 'code')
  {
    $params = array
        (
        'client_id' => $this->appKey(),
        'client_secret' => $this->secretKey(),
    );

    if ($type === 'token')
    {
      $params['grant_type'] = 'refresh_token';
      $params['refresh_token'] = $this->refresh_token;
    }
    else if ($type === 'code')
    {
      $params['grant_type'] = 'authorization_code';
      $params['code'] = $keys['code'];
      $params['redirect_uri'] = $keys['redirect_uri'];
    }
    else if ($type === 'password')
    {
      $params['grant_type'] = 'password';
      $params['username'] = $keys['username'];
      $params['password'] = $keys['password'];
    }
    else
    {
      throw new OAuthException("wrong auth type");
    }

    $response = $this->post($this->accessTokenUlr(), $params);
    $token = json_decode($response, true);

    if (is_array($token) && empty($token['error']))
    {
      if (empty($token['refresh_token']))
      {
        $token['refresh_token'] = 'fuck sina';
      }

      $this->access_token = $token['access_token'];
      $this->refresh_token = $token['refresh_token'];
      return $token;
    }

    throw new Exception("get access token failed." . $token['error']);
  }

  /**
   *
   * @param type $signed_request
   * @return type 
   */
  public function parseSignedRequest($signed_request)
  {
    list($encoded_sig, $payload) = explode('.', $signed_request, 2);
    $sig = self::base64decode($encoded_sig);
    $data = json_decode(self::base64decode($payload), true);
    if (strtoupper($data['algorithm']) !== 'HMAC-SHA256')
      return '-1';
    $expected_sig = hash_hmac('sha256', $payload, $this->secretKey(), true);
    return ($sig !== $expected_sig) ? '-2' : $data;
  }

  /**
   * @ignore
   */
  public function base64decode($str)
  {
    return base64_decode(strtr($str . str_repeat('=', (4 - strlen($str) % 4)), '-_', '+/'));
  }

  /**
   *
   * @return type 
   */
  public function getTokenFromJSSDK()
  {
    $key = "weibojs_" . $this->appKey();
    if (isset($_COOKIE[$key]) && $cookie = $_COOKIE[$key])
    {
      parse_str($cookie, $token);
      if (isset($token['access_token']) && isset($token['refresh_token']))
      {
        $this->access_token = $token['access_token'];
        $this->refresh_token = $token['refresh_token'];
        return $token;
      }
      else
      {
        return false;
      }
    }
    else
    {
      return false;
    }
  }

  /**
   *
   * @param type $arr
   * @return type 
   */
  public function getTokenFromArray($arr)
  {
    if (isset($arr['access_token']) && $arr['access_token'])
    {
      $token = array();
      $this->access_token = $token['access_token'] = $arr['access_token'];
      if (isset($arr['refresh_token']) && $arr['refresh_token'])
      {
        $this->refresh_token = $token['refresh_token'] = $arr['refresh_token'];
      }

      return $token;
    }
    else
    {
      return false;
    }
  }

}
