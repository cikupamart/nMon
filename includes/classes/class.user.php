<?php

class User extends App {

    public static function add($data) {
    	global $database;

    	// Sanitize inputs
    	$email = sanitizeEmail($data['email']);
    	$name = sanitizeInput($data['name']);

    	// Validate email
    	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    		return "12"; // Invalid email
    	}

    	$count = $database->count("core_users",["email" => $email]);
    	if ($count == "1") { return "11"; }

    	// Use modern password hashing
    	$password = hashPassword($data['password']);

    	$lastid = $database->insert("core_users", [
    		"roleid" => sanitizeInt($data['roleid']),
    		"name" => $name,
    		"email" => $email,
    		"password" => $password,
            "groups" => serialize($data['groups']),
    		"theme" => "skin-green",
    		"sidebar" => "opened",
    		"layout" => "",
    		"notes" => "",
    		"sessionid" => "",
    		"resetkey" => "",
    		"lang" => getConfigValue("default_lang"),
            "autorefresh" => 0,
    	]);

    	if ($lastid == "0") { return "11"; } else {
    		if(isset($data['notification'])) {
    			if($data['notification'] == true) {
    				Notification::newUser($lastid,$data['password']);
    			}
    		}
    		logSystem("User Added - ID: " . $lastid);
    		return "10";
    	}
    }

    public static function edit($data) {
    	global $database;

    	// Sanitize inputs
    	$email = sanitizeEmail($data['email']);
    	$name = sanitizeInput($data['name']);

    	// Validate email
    	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    		return "12"; // Invalid email
    	}

    	if ($data['password'] == "") {
    		$database->update("core_users", [
    			"roleid" => sanitizeInt($data['roleid']),
    			"name" => $name,
    			"email" => $email,
                "groups" => serialize($data['groups']),
    			"theme" => $data['theme'],
    			"sidebar" => $data['sidebar'],
    			"layout" => $data['layout'],
    			"notes" => sanitizeInput($data['notes']),
    			"lang" => $data['lang']
    			],["id" => sanitizeInt($data['id'])]);
    		logSystem("User Edited - ID: " . $data['id']);
    		return "20";
    	}
    	else {
    		// Use modern password hashing
    		$password = hashPassword($data['password']);

    		$database->update("core_users", [
    			"roleid" => sanitizeInt($data['roleid']),
    			"name" => $name,
    			"email" => $email,
    			"password" => $password,
                "groups" => serialize($data['groups']),
    			"theme" => $data['theme'],
    			"sidebar" => $data['sidebar'],
    			"layout" => $data['layout'],
    			"notes" => sanitizeInput($data['notes']),
    			"lang" => $data['lang']
    			],["id" => sanitizeInt($data['id'])]);
    		logSystem("User Edited - ID: " . $data['id']);
    		return "20";
    	}
    }

    public static function delete($id) {
    	global $database;
        $database->delete("core_users", [ "id" => sanitizeInt($id) ]);
    	logSystem("User Deleted - ID: " . $id);
    	return "30";
    }

}


?>
