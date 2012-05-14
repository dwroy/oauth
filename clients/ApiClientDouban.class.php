<?php

/**
 * sina weibo api client
 * @author Roy
 */

class ApiClientDouban extends SwApiClient
{
  const SNS_NAME = 'douban';

  public function __construct($user_id) 
  {
    parent::__construct($user_id, ApiClientDouban::SNS_NAME);
  }

  /**
   * get followers by user id
   * @param type $user_id
   * @param type $cursor
   * @param type $count
   * @return type 
   */
  public function getFriends($user_id = 0, $cursor = 0, $count = 2000)
	{
		$params = array
    (
      'start-index' => $cursor,
      'max-results'  => $count,
    );


		return $this->oauth->call('people/'.$this->sns_user_id.'/contacts', 'get', $params);
	}
}