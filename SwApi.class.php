<?php

/**
 * open auth client for third part platforms such as sina weibo tencent weibo douban renren
 * @author Roy
 */

require_once(sfContext::getInstance()->getConfigCache()->checkConfig(sfConfig::get('sf_config_dir').'/custom/oauth.yml'));

class SwApi
{
  
  /**
   * @var string
   */
  const API_CLASS_NAME_PREFIX = 'ApiClient';

  const OAUTH_CLASS_NAME_PREFIX = 'OAuth';

  /**
   * api client objects
   * @var ApiClient
   */
  protected static $api_pool = array();

  protected static $oauth_pool = array();

  /**
   * get api client instance
   * @param string $name
   * @return ApiClient
   */
  public static function getInstance($user_id, $sns_name)
  {
    if($user_id > 0 && SwApi::isAvailableSns($sns_name))
    {
      if(empty(SwApi::$api_pool[$user_id][$sns_name]))
      {
        $class_name = SwApi::parseName($sns_name);
        SwApi::$api_pool[$user_id][$sns_name] = new $class_name($user_id);
      }

      return SwApi::$api_pool[$user_id][$sns_name];
    }

    throw new Exception('Unsupported user or sns: ' . $sns_name);
  }

  /**
   * get oauth object
   * @param type $sns_name
   * @return type
   */
  public static function getOAuthInstance($user_id, $sns_name)
  {
    if($user_id > 0 && SwApi::isAvailableSns($sns_name))
    {
      if(empty(SwApi::$oauth_pool[$user_id][$sns_name]))
      {
        $class_name = SwApi::parseName($sns_name, SwApi::OAUTH_CLASS_NAME_PREFIX);
        $sns_config = sfConfig::get('custom_oauth_sns_'. $sns_name);
        SwApi::$oauth_pool[$user_id][$sns_name] = new $class_name($user_id, $sns_config['app_key'], $sns_config['secret_key']);
      }

      return SwApi::$oauth_pool[$user_id][$sns_name];
    }


    throw new Exception('Unsupported user or sns: ' . $sns_name);
  }

  public static function getAuthorizeUrl($user_id, $sns = 'all')
  {
    if($sns == 'all')
    {
      $sns_list = self::getAvailableSns();
    }
    else
    {
      $sns_list = array( $sns );
    }
     
    $url = array();

    foreach($sns_list as $sns)
    {
      $oauth_object = self::getOAuthInstance($user_id, $sns);
      $url[$sns] = $oauth_object->getAuthorizeUrl('code', $sns);
    }

    return $url;
  }

  /**
   * get all available platforms
   * @return string
   */
  public static function getAvailableSns()
  {
    return array
    (
      ApiClientSinaWeibo::SNS_NAME,
      ApiClientTencentWeibo::SNS_NAME,
      ApiClientDouban::SNS_NAME,
      ApiClientKaixin::SNS_NAME,
      ApiClientRenren::SNS_NAME,
    );
  }

  /**
   * check is supported
   * @param string $name
   * @return boolean
   */
  public static function isAvailableSns($name)
  {
    return in_array($name, SwApi::getAvailableSns());
  }

  /**
   * parse platform name
   * @param string $name
   * @return string class name of the api client
   */
  public static function parseName($name, $prefix = SwApi::API_CLASS_NAME_PREFIX)
  {
    $name_parts = explode('.', $name);
    $class_name = $prefix;

    foreach($name_parts as $part)
    {
      $class_name .= ucfirst($part);
    }

    if(class_exists($class_name));
    {
      return $class_name;
    }

    throw new Exception('Class undefined: ' . $class_name);
  }
}