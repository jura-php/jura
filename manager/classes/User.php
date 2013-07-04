<?php
class User
{
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
		$token = static::token();

		$rs = ORM::make("manager_tokens")->select("expirationDate")->where("token", "=", $token)->findFirst();

		if (!$rs->EOF)
		{
			$time = $rs->fields["expirationDate"];

			if (time() > $time)
			{
				return true;
			}
			else
			{
				return static::error(403, "expired_token");
			}
		}
		else
		{
			return static::error(403, "invalid_token");
		}
	}

	public static function renewToken()
	{
		$token = static::token();

		$rs = ORM::make("manager_tokens")->select("expirationDate")->where("token", "=", $token)->findFirst();

		if (!$rs->EOF)
		{
			$time = $rs->fields["expirationDate"];

			$orm->token = md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
			$orm->expirationDate = php_sql_datetime(time() + 3600); //one hour

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

	public static function profile()
	{
		$token = static::token();

		$rs = ORM::make("manager_tokens")->select("idUser")->where("token", "=", $token)->findFirst();

		if (!$rs->EOF)
		{
			$rs = ORM::make("manager_users")->select(array("name", "username", "email"))->where("id", "=", $rs->idUser)->findFirst();

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

	private static function token()
	{
		return Request::get("access_token", Request::post("access_token"));
	}
}
?>