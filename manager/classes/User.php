<?php
class User
{
	public static function generateToken()
	{
		//TODO: Check user/pass params..
		if (!isset($_SERVER["PHP_AUTH_USER"]) || !isset($_SERVER["PHP_AUTH_PW"]))
		{
			return static::error(403, "invalid_request");
		}

		$rs = ORM::make("manager_users")->select(array("id", "password"))->where("username", "=", $_SERVER["PHP_AUTH_USER"]);

		if (!$rs->EOF)
		{
			if ($rs->fields["password"] == md5($_SERVER["PHP_AUTH_PW"]))
			{
				$userID = $rs->fields["id"];

				//Clear old tokens for this client
				$rs = ORM::make("manager_tokens")->where("id", "=", $userID)->findAll();
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

	public static function error($code, $description)
	{
		Response::code($code);
		return Response::json(array("error_description" => $description));
	}
}
?>