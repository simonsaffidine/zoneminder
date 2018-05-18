<?php
//
// ZoneMinder auth library, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
// 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 

function userLogin($username, $password='', $passwordHashed=false) {
  global $user, $cookies;

  $sql = 'SELECT * FROM Users WHERE Enabled=1';
  $sql_values = NULL;
  if ( ZM_AUTH_TYPE == 'builtin' ) {
    if ( $passwordHashed ) {
      $sql .= ' AND Username=? AND Password=?';
    } else {
      $sql .= ' AND Username=? AND Password=password(?)';
    }
    $sql_values = array($username, $password);
  } else {
    $sql .= ' AND Username=?';
    $sql_values = array($username);
  }
  session_start();
  $_SESSION['username'] = $username;
  if ( ZM_AUTH_RELAY == 'plain' ) {
    // Need to save this in session
    $_SESSION['password'] = $password;
  }
  $_SESSION['remoteAddr'] = $_SERVER['REMOTE_ADDR']; // To help prevent session hijacking
  if ( $dbUser = dbFetchOne($sql, NULL, $sql_values) ) {
    Info("Login successful for user \"$username\"");
    $_SESSION['user'] = $user = $dbUser;
    unset($_SESSION['loginFailed']);
    if ( ZM_AUTH_TYPE == 'builtin' ) {
      $_SESSION['passwordHash'] = $user['Password'];
    }
    session_regenerate_id();
  } else {
    Warning("Login denied for user \"$username\"");
    $_SESSION['loginFailed'] = true;
    unset($user);
  }
  session_write_close();
} # end function userLogin

function userLogout() {
  global $user;
  Info('User "'.$user['Username'].'" logged out');
  session_start();
  unset($_SESSION['user']);
  unset($user);

  session_destroy();
}

function getAuthUser($auth) {
  if ( ZM_OPT_USE_AUTH && ZM_AUTH_RELAY == 'hashed' && !empty($auth) ) {
    $remoteAddr = '';
    if ( ZM_AUTH_HASH_IPS ) {
      $remoteAddr = $_SERVER['REMOTE_ADDR'];
      if ( !$remoteAddr ) {
        Error("Can't determine remote address for authentication, using empty string");
        $remoteAddr = '';
      }
    }

    if ( isset($_SESSION['username']) ) {
      # Most of the time we will be logged in already and the session will have our username, so we can significantly speed up our hash testing by only looking at our user.
      # Only really important if you have a lot of users.
      $sql = "SELECT * FROM Users WHERE Enabled = 1 AND Username='".$_SESSION['username']."'";
    } else {
      $sql = 'SELECT * FROM Users WHERE Enabled = 1';
    }

    foreach ( dbFetchAll($sql) as $user ) {
      $now = time();
      for ( $i = 0; $i < ZM_AUTH_HASH_TTL; $i++, $now -= (3600) ) { // Try for last two hours
        $time = localtime($now);
        $authKey = ZM_AUTH_HASH_SECRET.$user['Username'].$user['Password'].$remoteAddr.$time[2].$time[3].$time[4].$time[5];
        $authHash = md5($authKey);

        if ( $auth == $authHash ) {
          return $user;
        }
      } // end foreach hour
    } // end foreach user
  } // end if using auth hash
  Error("Unable to authenticate user from auth hash '$auth'");
  return false;
} // end getAuthUser($auth)

function generateAuthHash($useRemoteAddr) {
  if ( ZM_OPT_USE_AUTH and ZM_AUTH_RELAY == 'hashed' and isset($_SESSION['username']) and $_SESSION['passwordHash'] ) {
    # regenerate a hash at half the liftetime of a hash, an hour is 3600 so half is 1800
    $time = time();
    $mintime = $time - ( ZM_AUTH_HASH_TTL * 1800 );

    if ( ( !isset($_SESSION['AuthHash']) ) or ( $_SESSION['AuthHashGeneratedAt'] < $mintime ) ) {
      # Don't both regenerating Auth Hash if an hour hasn't gone by yet
      $local_time = localtime();
      $authKey = '';
      if ( $useRemoteAddr ) {
        $authKey = ZM_AUTH_HASH_SECRET.$_SESSION['username'].$_SESSION['passwordHash'].$_SESSION['remoteAddr'].$local_time[2].$local_time[3].$local_time[4].$local_time[5];
      } else {
        $authKey = ZM_AUTH_HASH_SECRET.$_SESSION['username'].$_SESSION['passwordHash'].$local_time[2].$local_time[3].$local_time[4].$local_time[5];
      }
      #Logger::Debug("Generated using hour:".$local_time[2] . ' mday:' . $local_time[3] . ' month:'.$local_time[4] . ' year: ' . $local_time[5] );
      $auth = md5($authKey);
      session_start();
      $_SESSION['AuthHash'] = $auth;
      $_SESSION['AuthHashGeneratedAt'] = $time;
      session_write_close();
      #Logger::Debug("Generated new auth $auth at " . $_SESSION['AuthHashGeneratedAt']. " using $authKey" );
    #} else {
      #Logger::Debug("Using cached auth " . $_SESSION['AuthHash'] ." beacuse generatedat:" . $_SESSION['AuthHashGeneratedAt'] . ' < now:'. $time . ' - ' .  ZM_AUTH_HASH_TTL . ' * 1800 = '. $mintime);
    } # end if AuthHash is not cached
    return $_SESSION['AuthHash'];
  } else {
    $auth = '';
  }
  return $auth;
}

function visibleMonitor($mid) {
  global $user;

  return ( empty($user['MonitorIds']) || in_array($mid, explode(',', $user['MonitorIds'])) );
}

function canView($area, $mid=false) {
  global $user;

  return ( ($user[$area] == 'View' || $user[$area] == 'Edit') && ( !$mid || visibleMonitor($mid) ) );
}

function canEdit($area, $mid=false) {
  global $user;

  return ( $user[$area] == 'Edit' && ( !$mid || visibleMonitor($mid) ));
}

?>
