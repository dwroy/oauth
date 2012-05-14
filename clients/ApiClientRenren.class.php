<?php

/**
 * sina weibo api client
 * @author Roy
 */

class ApiClientRenren extends SwApiClient
{
  const SNS_NAME = 'renren';

  public function __construct($user_id) 
  {
    parent::__construct($user_id, ApiClientRenren::SNS_NAME);
  }

  /**
   * get followers by user id
   * @param type $user_id
   * @param type $cursor
   * @param type $count
   * @return type 
   */
  public function getFriends($page = 1, $count = 50, $fields = '')
	{
		$params = array
    (
      'method' => 'friends.getFriends',
      'page' => $page,
      'count'  => $count,
      'fields' => $fields,
    );

		return $this->oauth->call('', 'post', $params);
	}
}