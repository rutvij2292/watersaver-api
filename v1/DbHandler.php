<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($username, $email, $password, $fname, $lname, $lat, $long) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($username)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);
            $user_id = uniqid();
            // insert query

            $stmt = $this->conn->prepare("INSERT INTO user_details(user_id,username, email, password_hash, firstname, lastname, latitude, longitude) values(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $user_id, $username, $email, $password_hash, $fname, $lname, $lat, $long);
            $result = $stmt->execute();
            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($username, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM user_details WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password
            $stmt->fetch();
            $stmt->close();
            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();
            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * To store user's score for goal purpose
     * @param String username and user_score;
     * @return boolean
     */
    public function storeScore($username, $user_score, $water_use, $badge_status){

        if (!$this->isUserScoreExists($username))
        {
            $stmt = $this->conn->prepare("INSERT INTO user_score (username, water_use, user_score, badge_stauts) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siii", $username, $water_use, $user_score, $badge_stauts);
            $result = $stmt->execute();
            $stmt->close();
            if($result){
                return SCORE_ADDED_SUCCESSFULLY;
            }
            else{
                return FALSE;
            }
        }
        else{
            $stmt = $this->conn->prepare("UPDATE user_score SET  user_score = ?, water_use = ?, badge_stauts = ?  WHERE username = ?");
            $stmt->bind_param("iiis", $user_score, $water_use, $badge_stauts, $username);
            $result = $stmt->execute();
            $stmt->close();
            if($result){
                return SCORE_UPDATED;
            }
            else{
                return FALSE;
            }   

        }

    }

    private function isUserScoreExists($username) {
        $stmt = $this->conn->prepare("SELECT username from user_score WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Printing Leaderboard
     * @param no parameters
     * @return array of result
     */
    public function getLeaderboardData(){
        $res = array();
        $stmt = $this->conn->prepare("SELECT ud.username, ud.firstname, ud.lastname, ud.latitude, ud.longitude, us.user_score FROM user_details ud, user_score us WHERE ud.username = us.username ORDER BY us.user_score DESC LIMIT 10");
        if($stmt)
        {
            $stmt->execute();
            $stmt->bind_result($username, $fname, $lname, $lat, $long, $score);
            $temp = $stmt->get_result();
            while($data =  $temp->fetch_assoc())
            {
                /*$result["username"] = $username;
                $result['first_name'] = $fname; 
                $result['last_name'] = $lname;
                $result["latitude"] = $lat;
                $result["longitude"] = $long;
                $result["user_score"] = $score;*/
                

                array_push($res, $data);
            }
            $stmt->close();
            return $res;
        }
        else
        {
            return null;
        }

    }   



    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($username) {
        $stmt = $this->conn->prepare("SELECT username from user_details WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

     /* Fetching user by email
     * @param String $email User email id
     */
    public function getUserByUsername($username) {
        $stmt = $this->conn->prepare("SELECT firstname,lastname, latitude, longitude FROM user_details WHERE username = ?");
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($fname, $lname, $lat, $long);
            $stmt->fetch();
            $user = array();
            $user["first_name"] = $fname;
            $user["last_name"] = $lname;
            $user["latitude"] = $lat;
            $user["longitude"] = $long;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    public function getUserId($username){
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($user_id);
        $stmt->fetch();
        $stmt->close();
        return $user_id;

    }

}

?>
