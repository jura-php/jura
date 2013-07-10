<?php

class User
{
	//TODO: Poder passar o token pelo header

	public static function generateToken()
	{
		//Check user/pass params..
		$credentials = Request::credentials();
		if (!$credentials)
		{
			return static::error(403, "invalid_request");
		}

		$rs = ORM::make("manager_users")->select(array("id", "password"))->where("username", "=", $credentials[0])->findFirst();

		if (!$rs->EOF)
		{
			if ($rs->fields["password"] == md5($credentials[1]))
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
				Session::set("j_manager_token_expiration", time() + 3600);

				return Response::json(array(
					"access_token" => $orm->token,
					"expires_in" => 3600,
					"scope" => "all"
				));
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
				Session::set("j_manager_token_expiration", time() + 3600);

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

	public static function profile()
	{
		$info = static::token(false);

		if (!isset($info["error"]))
		{
			$rs = ORM::make("manager_users")->select(array("name", "username", "email"))->where("id", "=", $info["userID"])->findFirst();	

			if (!$rs->EOF)
			{
				return $rs->fields;
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