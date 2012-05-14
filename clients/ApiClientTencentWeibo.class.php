<?php

/**
 * sina weibo api client
 * @author Roy
 */

class ApiClientTencentWeibo extends SwApiClient
{
  const SNS_NAME = 'tencent.weibo';

  public function __construct($user_id)
  {
    parent::__construct($user_id, ApiClientTencentWeibo::SNS_NAME);
  }

  /**
   * get followers by user id
   * @param type $user_id
   * @param type $cursor
   * @param type $count
   * @return type 
   */
  public function getFriends($user_id = 0, $cursor = 0, $count = 50)
	{
		$params = array
    (
      'startindex' => $cursor,
      'reqnum'     => $count,
    );

		return $this->oauth->call('friends/idollist', 'get', $params);
	}
}