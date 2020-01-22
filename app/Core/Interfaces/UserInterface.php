<?php

namespace App\Core\Interfaces;
 
interface UserInterface
{		
    public function findByCredentials($username, $password, $userListId, $active);

    public function findByUsername($username, $userListId);

    public function getData($name);

    public function setData($name, $value);

    public function setReferrer($referrer);

    public function getReferrer();

    public function getReferrerByCode($code);   

    public function getIndustries();

    public function addAccountPhone($accountId, $number);

    public function getResetLinks($link);

    public function setGaData($update, $userId);

    public function assignAssets($user);
}
