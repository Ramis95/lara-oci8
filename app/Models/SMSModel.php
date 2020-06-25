<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SMSModel extends Model
{

	private $conn;

	public function __construct(array $attributes = [])
	{
		parent::__construct($attributes);

		$username          = env("orc_username");
		$password          = env("orc_password");
		$connection_string = env("orc_host") . '/' . env("orc_service_name");

		$this->conn = oci_connect($username, $password, $connection_string,
			"UTF8");
	}

	public function executeDB($userRequest, $request)
	{
		$result = [];

		$orc = oci_parse($this->conn,
			"begin :db_result := smsservice.T2_psmsservice.ProcessUSSDRequest(:msisdn, :serviceNumber, :channel, :sessionId, :request1, :errorCode, :faza, :text, :endSession); end;");
		$err = OCIError();

		if ( ! $err)  //Если нет ошибок с запросом
		{
			if ($request->session()->get('us-'.$userRequest['sessionId']))
			{
				$sessionArr = $request->session()->get('us-'.$userRequest['sessionId']);
				$faza = $sessionArr['faza'];
			}

			oci_bind_by_name($orc, "db_result", $db_result, 15);
			oci_bind_by_name($orc, "text", $text, 1000);
			oci_bind_by_name($orc, "endSession", $endSession, 100);

			oci_bind_by_name($orc, ":msisdn", $userRequest['msisdn']);
			oci_bind_by_name($orc, ":serviceNumber",
				$userRequest['serviceNumber']);
			oci_bind_by_name($orc, ":channel", $userRequest['channel']);
			oci_bind_by_name($orc, ":sessionId", $userRequest['sessionId']);
			oci_bind_by_name($orc, ":request1", $userRequest['request']);
			oci_bind_by_name($orc, ":errorCode", $userRequest['errorCode']);
			oci_bind_by_name($orc, ":faza", $faza);

			oci_execute($orc);
			oci_result($orc, $db_result);
			oci_result($orc, $text);
			oci_result($orc, $endSession);

			oci_close($this->conn);

			if ($db_result != "" && $text != "" && $endSession != "") {

				$result = [
					"db_result"  => $db_result,
					"text"       => $text,
					"endSession" => $endSession,
				];

//				var_dump($request->session()->get('us-'.$userRequest['sessionId']));

				if ($request->session()->get('us-'.$userRequest['sessionId']) && $endSession == 1)
				{
					$sessionArr = $request->session()->get('us-'.$userRequest['sessionId']);
					$faza = $sessionArr['faza'];
					$request->session()->put('us-'.$userRequest['sessionId'], ['faza' => $faza + $db_result]);
				}
				elseif ($request->session()->get('us-'.$userRequest['sessionId']) && $endSession == 0)
				{
					$request->session()->remove('us-'.$userRequest['sessionId']);
				}
				elseif ( !$request->session()->get('us-'.$userRequest['sessionId']) && $endSession == 1)
				{
					$request->session()->put('us-'.$userRequest['sessionId'], ['faza' => 1]);
				}
			}
		}

		return $result;

	}

	public function sendSMSDB($userRequest)
	{
		$result = [];

		$orc = oci_parse($this->conn,
			"begin :db_result := psmsManager_TTK.SendSMSpay(:MSISDN, :SMSText, :HeaderName, :BulkId, :Delivery_type, :Response); end;");
		$err = OCIError();

		if (!$err) {

			oci_bind_by_name($orc, "db_result", $db_result, 15);
			oci_bind_by_name($orc, "Response", $Response, 1000);

			oci_bind_by_name($orc, ":MSISDN", $userRequest['MSISDN']);
			oci_bind_by_name($orc, ":SMSText", $userRequest['SMSText']);
			oci_bind_by_name($orc, ":HeaderName", $userRequest['HeaderName']);
			oci_bind_by_name($orc, ":BulkId", $userRequest['BulkId']);
			oci_bind_by_name($orc, ":Delivery_type", $userRequest['Delivery_type']);

			oci_execute($orc);
			oci_result($orc, $db_result);

			$result = $db_result;

		}
		oci_close($this->conn);


		return $result;
	}
}
