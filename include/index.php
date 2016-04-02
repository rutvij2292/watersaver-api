<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->request->headers->set('Content-Type', 'application/json');



$app->get('/hello', function() use ($app){
    $response = array();
    date_default_timezone_set('America/Los_Angeles');
    $info = getdate();
    $response["month"] = $info['mon'];
    echoRespnse(200, $response);


});
/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
$app->post('/test1', function() use ($app){
    $data_back = json_decode(file_get_contents('php://input'));
    $response = $data_back;
    echoRespnse(201, $response);
}



/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('username', 'email', 'password', 'fname', 'lname', 'lat', 'long'));

            $response = array();

            // reading post params
            $username = $app->request->post('username');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
            $fname = $app->request->post('fname');
            $lname = $app->request->post('lname');
            $lat = $app->request->post('lat');
            $long = $app->request->post('long');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($username, $email, $password, $fname, $lname, $lat, $long);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this username already existed";
            }
            // echo json response
            echoRespnse(201, $response);
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

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('username', 'password'));

            // reading post params
            $username = $app->request()->post('username');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($username, $password)) {
                // get the user by email
                $user = $db->getUserByUsername($username);

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['first_name'] = $user['first_name'];
                    $response['last_name'] = $user['last_name'];
                    //$response['Content-Type'] = $app->response->headers->get('Content-Type');
                    $response['test'] = apache_response_headers();
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
                /*$response['error'] = false;
                $response['message'] = 'Login True. ';
                $response['id'] = $user_id;*/


            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
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
                $response['status'] = true;
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
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

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

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('multipart/form-data');

    echo json_encode($response);
}

$app->run();



?>