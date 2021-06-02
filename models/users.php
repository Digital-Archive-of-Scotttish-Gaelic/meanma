<?php

namespace models;

class users
{
  public static function getUser($email) {
    $db = new database();
    $dbh = $db->getDatabaseHandle();
    try {
      $sth = $dbh->prepare("SELECT `password`, `salt`, `firstname`, `lastname`, `slip_admin`, 
        `passwordAuth`, `superuser`, UNIX_TIMESTAMP(`last_logged_in`) AS last_logged_in, 
				UNIX_TIMESTAMP(`updated`) AS updated FROM user WHERE email = :email;");
      $sth->execute(array(":email"=>$email));
      $row = $sth->fetch();
      $user = new user($email);
      $user->setPassword($row['password']);
      $user->setSalt($row['salt']);
      $user->setFirstName($row['firstname']);
      $user->setLastName($row['lastname']);
      $user->setIsSlipAdmin($row['slip_admin']);
      $user->setPasswordAuth($row['passwordAuth']);
      $user->setSuperuser($row["superuser"]);
      $user->setUpdated($row["updated"]);
      self::_setGroups($user);  //set the user's groups
      return $user;
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }

	/**
	 * Fetches a list of a user's groups from the DB and sets them in the user's instance
	 * @param $user
	 * @return user
	 */
  private static function _setGroups($user) {
  	$db = new database();
  	$dbh = $db->getDatabaseHandle();
	  $sql = <<<SQL
			SELECT id, name, theme, lastUsed
				FROM userGroup ug
				JOIN userGroupMembers ugm ON ugm.groupId = ug.id  
				 WHERE ugm.userEmail = :email
				 ORDER BY lastUsed DESC
SQL;

	  try {
		  $sth = $dbh->prepare($sql);
		  $sth->execute(array(":email"=>$user->getEmail()));
		  $i = 0;
		  while ($row = $sth->fetch()) {
		  	$group = new usergroup($row["id"], $row["name"], $row["theme"], $row["lastUsed"]);
			  $user->addGroup($group);
			  if ($i == 0) {  //the most recently used group will always be the first item due to ORDER BY lastUsed DESC
			  	$user->setLastUsedGroup($group);
			  }
			  $i++;
		  }
		  return $user;
	  } catch (PDOException $e) {
		  echo $e->getMessage();
	  }
  }

  public static function updateGroupLastUsed($groupId) {
	  $db = new database();
	  $dbh = $db->getDatabaseHandle();
	  $sql = <<<SQL
			UPDATE userGroupMembers 
				SET lastUsed = now()
				WHERE groupId = :groupId AND userEmail = :userEmail
SQL;
	  try {
		  $sth = $dbh->prepare($sql);
		  $sth->execute(array(":groupId"=>$groupId, ":userEmail"=>$_SESSION["user"]));
	  } catch (PDOException $e) {
		  echo $e->getMessage();
	  }
  }

  public static function saveUser($user) {
    $db = new database();
    $dbh = $db->getDatabaseHandle();
    try {
      $sth = $dbh->prepare("REPLACE INTO user(email, password, salt, firstname, lastname, slip_admin, passwordAuth, superuser, last_logged_in) VALUES 
				(:email, :password, :salt, :firstname, :lastname, :slip_admin, :passwordAuth, :superuser, now())");
      $sth->execute(array(":email"=>$user->getEmail(),
        ":password"=>$user->getPassword(), ":salt"=>$user->getSalt(), ":firstname"=>$user->getFirstName(),
        ":lastname"=>$user->getLastName(), ":slip_admin"=>$user->getIsSlipAdmin(),
        ":superuser"=>$user->getSuperuser(), ":passwordAuth"=>$user->getPasswordAuth()));
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }

  public static function getAllUsers() {
    $users = array();
    $db = new database();
    $dbh = $db->getDatabaseHandle();
    try {
      $sth = $dbh->prepare("SELECT email, firstname FROM user ORDER BY firstname ASC;");
      $sth->execute();
      while ($row = $sth->fetch()) {
        $users[] = self::getUser($row["email"]);
      }
      return $users;
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }

  public static function getAllSuperusers() {
	  $users = array();
	  $db = new database();
	  $dbh = $db->getDatabaseHandle();
	  try {
		  $sth = $dbh->prepare("SELECT email FROM user WHERE superuser = 1 ;");
		  $sth->execute();
		  while ($row = $sth->fetch()) {
			  $users[] = self::getUser($row["email"]);
		  }
		  return $users;
	  } catch (PDOException $e) {
		  echo $e->getMessage();
	  }
  }

  public static function checkSuperuserAuth() {
  	$user = self::getUser($_SESSION["email"]);
  	return $user->getSuperuser();
  }
}