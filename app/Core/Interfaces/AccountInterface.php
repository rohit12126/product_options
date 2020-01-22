<?php

namespace App\Core\Interfaces;
 
interface AccountInterface
{    
	public function getParentAccount();
	
	public function getAccount($portalName);

	public function findByGroupId($groupId);
	
	public function getAccountUsersByRole($role, $profileAccountId);

	public function getUsersByRole($role, $users);	
}
