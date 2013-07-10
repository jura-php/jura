<?php

class User
{
	public static function generateToken()
	{
		//Check user/pass params..
		$user = Request::post("user");
		$pass = Request::post("pass");
		if (!$user || !$pass)
		{
			return static::error(403, "invalid_request");
		}

		$rs = ORM::make("manager_users")->select(array("id", "password"))->where("username", "=", $user)->findFirst();

		if (!$rs->EOF)
		{
			if ($rs->fields["password"] == md5($pass))
			{
				$userID = $rs->fields["id"];

				//Clear old tokens for this client
				$rs = ORM::make("manager_tokens")->where("id", "=", $userID)->find();
				while (!$rs->EOF)
				{
					$rs->orm->delete();

					$rs->moveNext();
				}

				//Create token and return the json
				$orm = ORM::make("manager_tokens");
				$orm->idUser = $userID;
				$orm->token = md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
				$orm->expirationDate = php_sql_datetime(time() + 3600); //one hour
				$orm->insert();

				Session::set("j_manager_token", $orm->token);

				$response = array(
					"access_token" => $orm->token,
					"expires_in" => 3600,
					"scope" => "all"
				);

				return Response::json(array_merge($response, User::profile($userID)));
			}
			else
			{
				return static::error(403, "invalid_client");
			}
		}
		else
		{
			return static::error(403, "invalid_client");
		}

	}

	public static function validateToken()
	{
		$info = static::token();

		if (!isset($info["error"]))
		{
			return true;
		}

		return $info["error"];
	}

	public static function renewToken()
	{
		$info = static::token();

		if (!isset($info["error"]))
		{
			$rs = ORM::make("manager_tokens")->where("token", "=", $info["token"])->findFirst();

			if (!$rs->EOF)
			{
				$rs->orm->token = md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
				$rs->orm->expirationDate = php_sql_datetime(time() + 3600); //one hour
				$rs->orm->update();

				Session::set("j_manager_token", $orm->token);

				return Response::json(array(
						"access_token" => $orm->token,
						"expires_in" => 3600,
						"scope" => "all"
					));
			}
			else
			{
				return static::error(403, "invalid_token");
			}
		}

		return $info["error"];
	}

	public static function logout()
	{
		Session::clear("j_manager_token");
	}

	public static function profile($userID = 0)
	{
		if ($userID == 0)
		{
			$info = static::token(false);

			if (!isset($info["error"]))
			{
				$rs = ORM::make("manager_users")->select(array("name", "username", "email"))->where("id", "=", $info["userID"])->findFirst();	

				if (!$rs->EOF)
				{
					return array_merge($rs->fields, array("access_token" => $info["token"], "gravatar_hash" => md5($rs->fields["email"])));
				}
			}
		}
		else
		{
			$rs = ORM::make("manager_users")->select(array("name", "username", "email"))->where("id", "=", $userID)->findFirst();	

			if (!$rs->EOF)
			{
				return array_merge($rs->fields, array("gravatar_hash" => md5($rs->fields["email"])));
			}
		}

		return false;
	}

	public static function error($code, $description)
	{
		Response::code($code);
		return Response::json(array("error_description" => $description));
	}

	private static function token($emitError = true)
	{
		$token = Request::get("access_token", Request::post("access_token", Session::get("j_manager_token", "")));
		$result = array();

		if ($token != "")
		{
			$rs = ORM::make("manager_tokens")->select(array("expirationDate", "idUser"))->where("token", "=", $token)->findFirst();

			if (!$rs->EOF)
			{
				$time = $rs->fields["expirationDate"];

				$result["token"] = $token;
				$result["expirationDate"] = $time;
				$result["userID"] = $rs->fields["idUser"];

				if (time() < $time)
				{
					if ($emitError)
					{
						$result["error"] = static::error(403, "expired_token");
					}
					else
					{
						$result["error"] = true;
					}
				}
			}
		}

		if (!isset($result["token"]) || $token == "")
		{
			if ($emitError)
			{
				$result["error"] = static::error(403, "invalid_token");
			}
			else
			{
				$result["error"] = true;
			}
		}
		
		return $result;
	}
}

?>