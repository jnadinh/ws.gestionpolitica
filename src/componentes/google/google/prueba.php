<?php
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Google/Client.php';

define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
define('REDIRECT_URI_PATH', __DIR__ . '/prueba.php');
define('SCOPES', implode(' ', array(Google_Service_Calendar::CALENDAR)));

$client = new Google_Client();
$client -> setApplicationName("Focusmeet");
$client -> setAuthConfig(CLIENT_SECRET_PATH);
$client -> setScopes(SCOPES);
$client -> setAccessType("offline");
$client -> setApprovalPrompt("");
$client -> setRedirectUri('http://ws.alpha.focusmeet.co/libs/google/google/prueba.php');

if (isset($_GET['logout']) && $_GET['logout'] == "1") {
	unset($_SESSION['token']);
}


if (isset($_GET['code'])) {
	echo $_GET['code'];
	$client -> authenticate($_GET['code']);
	$_SESSION['token'] = $client -> getAccessToken();
	echo "---e";
	print_r($_SESSION);
	echo "---e";
	//return "";
 }


// Step 1:  The user has not authenticated we give them a link to login
if (!$client -> getAccessToken() && !isset($_SESSION['token'])) {
	$authUrl = $client -> createAuthUrl();
	print "<a class='login' href='$authUrl'>Connect Me!</a>";
}

// Step 3: We have access we can now create our service
if (isset($_SESSION['token'])) {
	print "<a class='logout' href='" . $_SERVER['PHP_SELF'] . "?logout=1'>LogOut</a><br>";

	$client -> setAccessToken($_SESSION['token']);

	//echo "<br>".json_encode($_SESSION['token'])."<br>";

	$service = new Google_Service_Calendar($client);

	// Crear un nuevo calendario
	$calendar = new Google_Service_Calendar_Calendar();
	$calendar -> setSummary('Edgar Herrera Gelvez');
	$calendar -> setDescription('systemico');
	$calendar -> setTimeZone('America/Mexico_City');
	$createdCalendar = $service -> calendars -> insert($calendar);
	$calendarId = $createdCalendar -> getId();

	// Crear un nuevo evento

	echo "<pre>".print_r($_SESSION['token'])."</pre>";
}
?>
