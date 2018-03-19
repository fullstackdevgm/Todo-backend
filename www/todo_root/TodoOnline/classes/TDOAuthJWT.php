<?php
//  TDOAuthJWT
//  This contains utility classes for creating stateless user authentication
//  using Java Web Tokens (JWT) instead of normal PHP sessions.

class TDOAuthJWT
{
  public static function userIDFromToken($jwtToken = NULL)
  {
    $payload = TDOAuthJWT::payloadForToken($jwtToken);
    if ($payload) {
      // Don't return a user if the token is not valid
      // or the expiration date has passed.
      if (TDOAuthJWT::_isPayloadValid($payload) == FALSE) {
        error_log("Detected an expired JWT session.");
        return NULL;
      }

      return $payload->data->userid;
    }

    return NULL;
  }

  public static function payloadForToken($jwtToken)
  {
    if (empty($jwtToken)) {
      return NULL;
    }

    $payload = NULL;
    try {
      $payload = JWT::decode($jwtToken, TDO_JWT_SECRET);
    } catch (Exception $e) {
      error_log("JWT::decode() had an exception: " . $e->getMessage());
    }

    return $payload;
  }

  public static function isValid($jwtToken = NULL)
  {
    $payload = TDOAuthJWT::payloadForToken($jwtToken);
    return TDOAuthJWT::_isPayloadValid($payload);
  }

  private static function _isPayloadValid($payload)
  {
    if (empty($payload)) {
      return FALSE;
    }

    if (empty($payload->exp)) {
      return FALSE;
    }

    // Check to see if the expiration date has passed.
    $now = time();
    $expiration = $payload->exp;
    if ($expiration < $now) {
      return FALSE;
    }

    return TRUE;
  }

  public static function createToken($userid, $username, $validDuration = SESSION_TIMEOUT)
  {
    $tokenId = base64_encode(TDOUtil::uuid());
    $issuedAt = time();
    $expire = $issuedAt + $validDuration;
    $serverName = "todo-cloud.com-" . TDO_VERSION;

    // Create the token as an array
    $data = array(
      'iat' => $issuedAt, // the timestamp the token was issued
      'jti' => $tokenId, // JSON Token ID: a unique token identifier
      'iss' => $serverName, // Issuer of the token
      'exp' => $expire, // when the token expires
      'data' => array(
        'userid' => $userid,
        'username' => $username
      )
    );

    try {
      $token = JWT::encode($data, TDO_JWT_SECRET);
      return $token;
    } catch (Exception $e) {
      error_log("JWT::encode() had an exception: " . $e->getMessage());
    }

    return NULL;
  }

  public static function login($username, $password)
  {

    $result = array();

    $user = TDOUser::getUserForUsername($username);
    if ($user == false) {
      $error = array(
        'id'  => 4792,
        'msg' => "User not found: " . $username
      );
      error_log("TDOAuthJWT::login() failed with error: " . $error['msg']);
      $result['error'] = $error;
      return $result;
    } else {
      if (TDOUserMaintenance::isMaintenanceInProgressForUser($user->userId())) {
        $error = array(
          'id'  => 1,
          'msg' => "User account maintenance still in process for user: " . $username
        );
        error_log("TDOAuthJWT::login() failed with error: " . $error['msg']);
        $result['error'] = $error;
        return $result;
      }
    }

    if ($user->matchPassword($password) == false) {
      $error = array(
        'id'  => 4792,
        'msg' => "TDOUser->matchPassword() returned false for user: ". $username
      );
      error_log("TDOAuthJWT::login() failed with error: " . $error['msg']);
      $result['error'] = $error;
      return $result;
    }

    $userid = $user->userId();

    if (!isset($userid)) {
      $error = array(
        'id'  => 4793,
        'msg' => "User authenticated but was missing a userid: " . $username
      );
      error_log("TDOAuthJWT::login() failed with error: " . $error['msg']);
      $result['error'] = $error;
      return $result;
    } else {
      $sessionTimeout = SESSION_TIMEOUT;
      if (!empty($_SESSION['ADMIN']) && $_SESSION['ADMIN'] == TRUE) {
        $sessionTimeout = ADMIN_SESSION_TIMEOUT;
      }

      $token = TDOAuthJWT::createToken($userid, $user->username(), $sessionTimeout);
      if ($token) {
        $result['userid'] = $userid;
        $result['token'] = $token;
      } else {
        $error = array(
          'id'  => 4794,
          'msg' => "User authenticated but could not create a JWT token: " . $username
        );
        error_log("TDOAuthJWT::login() failed with error: " . $error['msg']);
        $result['error'] = $error;
      }

      return $result;
    }
  }
}


?>
