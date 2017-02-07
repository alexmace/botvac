<pre>
<?php

require __DIR__ . '/../vendor/autoload.php';

$email = "user@email.com";
$password = "secretpassword";
$token = false; // Token returned from authorize method

/*
 If you already have a secret and serial, there is no need to authorize, skip this part and go directly to the fun stuff
*/

$client = new NeatoBotvacClient($token);
$robots = array();

$auth = $client->authorize($email, $password);

if($auth !== false) {
	echo "Token: ", $auth, "\n";
	$result = $client->getRobots();

	if($result !== false) {
		foreach ($result["robots"] as $robot) {
			echo "Serial: ", $robot['serial'], "\n";
			echo "Secret Key: ", $robot['secret_key'], "\n";
			$robots[] = new NeatoBotvacRobot($robot["serial"], $robot["secret_key"]);
		}
	}
} else {
	echo "Unable to authorize";
}

/* Doing actions against the robot(s) */

foreach ($robots as $robot) {
	print_r($robot->getState());
	print_r($robot->getSchedule());
}

?>
</pre>
