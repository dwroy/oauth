<?php

/**
 * sina weibo api client
 * @author Roy
 */

class ApiClientSinaWeibo extends SwApiClient
{
  const SNS_NAME = 'sina.weibo';

  public function __construct($user_id) 
  {
    parent::__construct($user_id, ApiClientSinaWeibo::SNS_NAME);
  }

  /**
   * get followers by user id
   * @param type $user_id
   * @param type $cursor
   * @param type $count
   * @return type 
   */
  public function getFriends($user_id = 0, $cursor = 0, $count = 200)
	{
		$params = array
    (
      'cursor' => $cursor,
      'count'  => $count,
      'uid'    => $user_id ? $user_id : $this->sns_user_id,
    );

		return $this->oauth->call( 'friendships/friends.json', 'get', $params );
	}
}