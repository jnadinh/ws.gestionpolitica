<?php

require_once __DIR__ . '/google/vendor/autoload.php';

require_once __DIR__ . '/google/src/Google/Client.php';

define('CLIENT_SECRET_PATH', __DIR__ . '/google/client_secret.json');
define('SCOPES', implode(' ', array(Google_Service_Calendar::CALENDAR,Google_Service_Drive::DRIVE)));

if (!class_exists("Mod_Idioma"))
	require dirname(__FILE__) . '/../../../mods/Mod_Idioma.php';

class GoogleDrive {
	private $client;
	public function __construct(){
		$this -> client = new Google_Client();
		$this -> client -> setApplicationName("FocusMeet");
		$this -> client -> setAuthConfig(CLIENT_SECRET_PATH);
		$this -> client -> setScopes(SCOPES);
		$this -> client -> setAccessType("offline");
		$this -> client -> setApprovalPrompt("force");
		$this -> client -> setRedirectUri('http://ws.alpha.focusmeet.co/libs/google/calendario.php');
	}
	public function get_token($codigo) {
		$this -> client -> authenticate($codigo);
		//echo $this->client->getAccessToken();
		return $this -> client -> getAccessToken();
	}
	public function get_files($token_json,$filtro=""){
		$token = json_decode($token_json,true);
		$this -> client -> setAccessToken($token);
		$drive =new Google_Service_Drive($this -> client);
		$files = array();
		$pageToken = null;
		do {
		    $response = $drive->files->listFiles(array(
		        'q' => $filtro,
		        'spaces' => 'drive',
		        'pageToken' => $pageToken,
		        'fields' => 'nextPageToken, files(id,mimeType,name)',
		    ));
				$files=$response;

    		$pageToken = $repsonse->pageToken;
			} while ($pageToken != null);

		return json_decode(json_encode($files->files),true);
	}
	public function new_folder($token_json,$nombre){
		$token = json_decode($token_json,true);
		$this -> client -> setAccessToken($token);
		$drive =new Google_Service_Drive($this -> client);
		$fileMetadata = new Google_Service_Drive_DriveFile(array(
	    'name' => $nombre,
	    'mimeType' => 'application/vnd.google-apps.folder'));
		$file = $drive->files->create($fileMetadata, array(
		    'fields' => 'id'));
		return $file->id;
	}
	function insert_file($token_json,$nombre, $parentId, $filename) {
		$token = json_decode($token_json,true);
		$this -> client -> setAccessToken($token);
		$drive =new Google_Service_Drive($this -> client);

	  $fileMetadata =new Google_Service_Drive_DriveFile(array(
	    'name' => $nombre,
			'parents' => array($parentId)
		));


	  try {
	    $data = file_get_contents($filename);

			$file = $drive->files->create($fileMetadata, array(
				  'data'=>$data,
			    'fields' => 'id,size,name,webContentLink'));

			return $file;
	  } catch (Exception $e) {
	    print "An error occurred: " . $e->getMessage();
	  }
}
public function set_permisos($token_json,$fileId,$email,$permiso='reader'){
		$token = json_decode($token_json,true);
		$this -> client -> setAccessToken($token);
		$drive =new Google_Service_Drive($this -> client);
		try {

		$batch = $drive->createBatch();

		$userPermission = new Google_Service_Drive_Permission(array(
	        'type' => 'user',
	        'role' => $permiso,
	        'emailAddress' => $email
	    ));
		$request = $drive->permissions->create($fileId, $userPermission, array(
			'fields' => 'id',
			'sendNotificationEmail'=>false
		));
		//'emailMessage'=>'jajajaja'
	}finally {
		 $drive->getClient()->setUseBatch(false);
	}

}
public function get_compartidos($token_json,$fileId){
	$token = json_decode($token_json,true);
	$this -> client -> setAccessToken($token);
	$drive =new Google_Service_Drive($this -> client);
	try {
		$pageToken = null;
		do {
		    $response = $drive->permissions->listPermissions($fileId, array('fields' => 'permissions'));
				$files=$response;
    		$pageToken = $repsonse->pageToken;
			} while ($pageToken != null);
				return (json_decode(json_encode($files->getPermissions()),true));

  } catch (Exception $e) {
    print "An error occurred: " . $e->getMessage();
  }
  return NULL;
}
public function get_file($token_json,$codigo){
	$token = json_decode($token_json,true);
	$this -> client -> setAccessToken($token);
	$drive =new Google_Service_Drive($this -> client);
	$response = $drive->files->get($codigo);
	return $response;
}
public function delete_file($token_json,$codigo){
	$token = json_decode($token_json,true);
	$this -> client -> setAccessToken($token);
	$drive =new Google_Service_Drive($this -> client);
	$response = $drive->files->delete($codigo);
	return $response;
}
public function download_file($token_json,$codigo){
		$token = json_decode($token_json,true);
		$this -> client -> setAccessToken($token);
		$drive =new Google_Service_Drive($this -> client);
		$response = $drive->files->get($codigo, array(
		    'alt' => 'media'));
		$content = $response->getBody()->getContents();
	  return $content;
	}
}
?>
