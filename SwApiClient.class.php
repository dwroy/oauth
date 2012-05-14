<?php

/**
 * api client base class
 * @author Roy
 */
class SwApiClient
{

  /**
   *
   * @var integer
   */
  protected $user_id;

  /**
   * sns user id
   * @var string
   */
  protected $sns_user_id;

  /**
   * oauth object
   * @var mixed
   */
  protected $oauth;

  /**
   *
   * @param integer $user_id
   * @param string $sns_name 
   */
  public function __construct($user_id, $sns_name)
  {
    $user = UserOauthPeer::getUserOAuth($user_id, $sns_name);

    if ($user === null)
    {
      throw new Exception('Not authorized sns platform: ' . $sns_name);
    }

    $oauth_class = SwApi::parseName($sns_name, SwApi::OAUTH_CLASS_NAME_PREFIX);
    $sns_config = sfConfig::get('custom_oauth_sns_' . $sns_name);
    $this->user_id = $user_id;
    $this->sns_user_id = $user->getSnsUserId();
    $this->oauth = new $oauth_class($user_id, $sns_config['app_key'], $sns_config['secret_key'], $user->getAccessToken(), $user->getRefreshToken());
  }
}