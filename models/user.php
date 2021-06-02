<?php

namespace models;

class user
{
  private $email, $password, $salt, $firstName, $lastName,
    $isSlipAdmin, $passwordAuth, $superuser, $lastLoggedIn, $updated;
  private $_lastUsedGroup;  //an instance of UserGroup
  private $_groups = array(); //an array of UserGroup objects

  public function __construct($email) {
    $this->email = $email;
  }

  public function getEmail() {
    return $this->email;
  }

  public function getPassword() {
    return $this->password;
  }

  public function getSalt() {
    if (empty($this->salt)) {
      $this->salt = uniqid(mt_rand(), true);
    }
    return $this->salt;
  }

  public function setSalt($salt) {
    $this->salt = $salt;
  }

  public function checkPassword($password) {
    return md5($this->getSalt() . $password) == $this->getPassword() ? 1 : 0;
  }

  public function setPassword($password) {
    $this->password = $password;
  }

  public function encryptPassword() {
    $this->password = md5($this->getSalt() . $this->getPassword());
  }

  public function getFirstName() {
    return $this->firstName;
  }

  public function setFirstName($name) {
    $this->firstName = $name;
  }

  public function getLastName() {
    return $this->lastName;
  }

  public function setLastName($name) {
    $this->lastName = $name;
  }

  public function getIsSlipAdmin() {
    return $this->isSlipAdmin;
  }

  public function setIsSlipAdmin($flag) {
    $this->isSlipAdmin = $flag;
  }

  public function getLastLoggedIn() {
    return $this->lastLoggedIn;
  }

  public function getPasswordAuth() {
    return $this->passwordAuth;
  }

  public function getSuperuser() {
  	return $this->superuser;
  }

  public function setSuperuser($flag) {
  	$this->superuser = $flag;
  }

  public function setPasswordAuth($auth) {
    $this->passwordAuth = $auth;
  }

  public function setLastLoggedIn($timestamp) {
    $this->lastLoggedIn = $timestamp;
  }

  public function getUpdated() {
    return $this->updated;
  }

  public function setUpdated($timestamp) {
    $this->updated = $timestamp;
  }

  public function getGroups() {
  	return $this->_groups;
  }

  public function addGroup($group) {
  	array_push($this->_groups, $group);
  }

  public function getLastUsedGroup() {
  	return $this->_lastUsedGroup;
  }

  public function setLastUsedGroup($group) {
  	$this->_lastUsedGroup = $group;
  }
}