<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SMSModel;

class SMSController extends Controller
{
	public $userRequest;
	private $model;

	public function __construct(SMSModel $model, Request $request)
	{
		$this->model = $model;
		$this->userRequest = json_decode($request->getContent(), true);
	}

	public function execute(Request $request)
    {
    	$db_result = $this->model->executeDB($this->userRequest, $request);

    	if($db_result)
	    {
		    return response()->json([
			    'text' => $db_result['text'],
			    'sessionId' => $this->userRequest['sessionId'],
			    'endSession' => $db_result['endSession']
		    ], 200);
	    }
    	else
	    {
		    return response()->json([
			    'text' => 'Не удалось подключиться к БД',
			    'sessionId' => $this->userRequest['sessionId'],
			    'endSession' => 0
		    ], 500);
	    }

    }

    public function sendSMS()
    {
	    $db_result = $this->model->sendSMSDB($this->userRequest);

	    if($db_result)
	    {
		    return response()->json([
			    'text' => 'Success',
		    ], 200);
	    }
	    else
	    {
		    return response()->json([
			    'text' => 'Error',
		    ], 500);
	    }
    }

}
