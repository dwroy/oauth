<?php

/**
 * sina weibo api client
 * @author Roy
 */

class ApiClientKaixin extends SwApiClient
{
  const SNS_NAME = 'kaixin';

  public function __construct($user_id) 
  {
    parent::__construct($user_id, ApiClientKaixin::SNS_NAME);
  }

  /**
   * get followers by user id
   * @param type $user_id
   * @param type $cursor
   * @param type $count
   * @return type 
   */
  public function getFriends($cursor = 0, $count = 50)
	{
		$params = array
    (
      'start' => $cursor,
      'num'  => $count,
    );

		return $this->oauth->call('friends/me.json', 'get', $params);
	}

  public function getFriendIds()
  {
    
  }

  public function me()
  {
    return $this->oauth->call('users/me.json', 'get');
  }
}