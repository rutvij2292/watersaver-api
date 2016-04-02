<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

//$app->request->headers->set('Content-Type', 'application/json');



$app->get('/hello', function() use ($app){
    $response = array();
    $response["error"] = true;
    $response["message"] = "Sorry, this username already existed";
    echoRespnse(200, $response);


});

$app->get('/register/:username/:email/:password/:fname/:lname/:lat/:long', function($username, $email, $password, $fname, $lname, $lat, $long) {
            // check for required params
            //verifyRequiredParams(array('username', 'email', 'password', 'fname', 'lname', 'lat', 'long'));
            $response = array();

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($username, $email, $password, $fname, $lname, $lat, $long);

           if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["status"] = true;
                $response["message"] = "You are successfully registered";
                $response["username"]=$username;
                $response["first_name"] = $fname;
                $response["last_name"] = $lname;
                $response["latitude"]= $lat;
                $response["longitude"] = $long;
            } else if ($res == USER_CREATE_FAILED) {
                $response["status"] = false;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["status"] = false;
                $response["message"] = "Sorry, this username already existed";
            }            // echo json response

            echoRespnse(201, $response);
        });

$app->get('/login/:username/:password', function($username, $password)  {
            // check for required params
            //verifyRequiredParams(array('username', 'password'));

            // reading post params
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($username, $password)) {
                // get the user by email
                $user = $db->getUserByUsername($username);

                if ($user != NULL) {
                    $response["status"] = true;
                    $response["username"]=$username;
                    $response['first_name'] = $user['first_name'];
                    $response['last_name'] = $user['last_name'];
                    $response["latitude"] = $user["latitude"];
                    $response["longitude"] = $user["longitude"];
                } else {
                    // unknown error occurred
                    $response["status"] = false;
                    $response['message'] = "An error occurred. Please try again";
                }
                /*$response['error'] = false;
                $response['message'] = 'Login True. ';
                $response['id'] = $user_id;*/


            } else {
                // user credentials are wrong
                $response['status'] = false;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });

$app->get('/leaderboard', function()  {
            $response = array();
            $db = new DbHandler();
            $result = $db->getLeaderboardData();
            $response["error"] = false;
            //$response["tasks"] = array();
            $response["result"] = $result;
            echoRespnse(200, $response);
        });

$app->get('/storescore/:username/:water_use/:userscore/:badge_status', function($username, $water_use, $user_score, $badge_status)  {
            $response = array();
            $db = new DbHandler();
            $result = $db->storeScore($username, $user_score, $water_use, $badge_status);
            if ($result == SCORE_ADDED_SUCCESSFULLY) {
                $response["status"] = true;
                $response["message"] = "Successfully score added";
            } else if ($result == SCORE_UPDATED) {
                $response["status"] = true;
                $response["message"] = "Score Updated!";
            } else{
                $response["status"] = false;
                $response["message"] = "An error occurred. Please try again";
            }     
            echoRespnse(200, $response);
        });

		

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}


function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType("application/json");
    echo json_encode($response);
}

$app->run();



?>