<?php

class User
{
	public static function logged()
	{
		$info = static::token(false);

		if (!isset($info["error"]))
		{
			$orm = ORM::make("manager_users")
						->select(array("id", "name", "profile", "username", "email"))
						->where("id", "=", $info["userID"])
						->findFirst();

			return $orm;
		}

		return false;
	}

	public static function generateToken()
	{
		//Check user/pass params..
		$user = Request::post("user");
		$pass = Request::post("pass");
		if (!$user || !$pass)
		{
			return static::error(403, "invalid_request");
		}

		$orm = ORM::make("manager_users")
					->select(array("id", "password"))
					->where("username", "=", $user)
					->findFirst();

		if ($orm)
		{
			if ($orm->password == md5($pass))
			{
				$userID = $orm->id;

				//Clear expired tokens for this client
				ORM::make("manager_tokens")
							->where("userID", "=", $userID)
							->where("expirationDate", "<", php_sql_datetime(time()))
							->deleteMany();

				//Create token and return the json
				$orm = ORM::make("manager_tokens");
				$orm->userID = $userID;
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
			$orm = ORM::make("manager_tokens")
						->where("token", "=", $info["token"])
						->findFirst();

			if ($orm)
			{
				$orm->token = md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
				$orm->expirationDate = php_sql_datetime(time() + 3600); //one hour
				$orm->update();

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
				$orm = ORM::make("manager_users")
							->select(array("name", "username", "email"))
							->where("id", "=", $info["userID"])
							->findFirst();

				if ($orm)
				{
					return array_merge($orm->asArray(), array(
						"access_token" => $info["token"],
						"gravatar_hash" => md5($orm->email))
					);
				}
			}
		}
		else
		{
			$orm = ORM::make("manager_users")
						->select(array("name", "username", "email"))
						->where("id", "=", $userID)
						->findFirst();

			if ($orm)
			{
				return array_merge($orm->asArray(), array(
					"gravatar_hash" => md5($orm->fields["email"]))
				);
			}
		}

		return false;
	}

	public static function error($code, $description)
	{
		Response::code($code);
		return Response::json(array("error" => true, "error_description" => $description));
	}

	private static function token($emitError = true)
	{
		$token = Request::get("access_token", Request::post("access_token", Session::get("j_manager_token", "")));
		$result = array();

		if ($token != "")
		{
			$orm = ORM::make("manager_tokens")
						->select(array("expirationDate", "userID"))
						->where("token", "=", $token)
						->findFirst();

			if ($orm)
			{
				$time = $orm->expirationDate;

				$result["token"] = $token;
				$result["expirationDate"] = $time;
				$result["userID"] = $orm->userID;

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

		if (!isset($result["token"]) || empty($token))
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