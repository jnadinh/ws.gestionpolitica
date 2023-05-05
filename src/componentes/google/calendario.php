<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . '/google/vendor/autoload.php';

require_once __DIR__ . '/google/src/Google/Client.php';
if(!defined('CLIENT_SECRET_PATH'))
define('CLIENT_SECRET_PATH', __DIR__ . '/google/client_secret.json');
if(!defined('SCOPES'))
define('SCOPES', implode(' ', array(Google_Service_Calendar::CALENDAR,Google_Service_Drive::DRIVE)));

if (!class_exists("Mod_Idioma"))
	require dirname(__FILE__) . '/../../../mods/Mod_Idioma.php';

class GoogleCalendar {
	private $client;
	private $modidioma;
	private $idioma;
	public function __construct($idioma="es") {
		$this->idioma=$idioma;
		$this -> modidioma = new Mod_Idioma();
		$this -> modidioma -> cargar($idioma);
		$this -> client = new Google_Client();
		$this -> client -> setApplicationName("FocusMeet");
		$this -> client -> setAuthConfig(CLIENT_SECRET_PATH);
		$this -> client -> setScopes(SCOPES);
		$this -> client -> setAccessType("offline");
		$this -> client -> setApprovalPrompt("force");
		$this -> client -> setRedirectUri('http://ws.alpha.focusmeet.co/libs/google/calendario.php');

	}
	public function filtrar($date,$format = 'Y-m-d\TH:i:s'){
		return $date;
		$date.="";
	    if($date=="" || $date=="null" || $date=="NULL" || $date==null){
				return $date;
	    }
	    $aux_vali=explode(":",$date);
	    if(strlen($date)==25 && count($aux_vali)==4){
				if(substr($date, -6, -5)=="+" || substr($date, -6, -5)=="-"){
					$date=substr($date, 0, 19)." ".substr($date, -6, -5)." ".substr($date, -4, -3)." hours";
				}
	    }
	    return date($format,strtotime($date));
 }
	public function get_token($codigo) {
		$this -> client -> authenticate($codigo);
		//echo $this->client->getAccessToken();
		return $this -> client -> getAccessToken();
	}

	public function get_calendarios($token_json) {

		$token = json_decode($token_json);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);
		$calendarList = $service -> calendarList -> listCalendarList();
		$calendar = array();
		while (true) {
			foreach ($calendarList->getItems() as $calendarListEntry) {
				array_push($calendar, array("id" => $calendarListEntry -> getId(), "summary" => $calendarListEntry -> getSummary(), "location" => $calendarListEntry -> getLocation(), "timezone" => $calendarListEntry -> getTimeZone(), "background" => $calendarListEntry -> getBackgroundColor()));
			}
			$pageToken = $calendarList -> getNextPageToken();
			if ($pageToken) {
				$optParams = array('pageToken' => $pageToken);
				$calendarList = $service -> calendarList -> listCalendarList($optParams);
			} else {
				break;
			}
		}
		return $calendar;
	}

	public function get_calendario($token_json, $id_calendar) {
		$token = json_decode($token_json);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);
		//echo $id_calendar;
		$calendar = $service -> calendars -> get($id_calendar);
		$calendario = array("id" => $calendar -> getId(), "summary" => $calendar -> getSummary(), "location" => $calendar -> getLocation(), "timezone" => $calendar -> getTimeZone(), "description" => $calendar -> getDescription());
		return $calendario;
	}

	public function set_calendario($token_json, $json_data) {
		$token = json_decode($token_json);
		$datos = json_decode($json_data);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);
		$calendar = new Google_Service_Calendar_Calendar();
		$calendar -> setSummary($datos -> summary);
		$calendar -> setDescription($datos -> description);
		$calendar -> setTimeZone($datos -> timezone);
		$calendar -> setLocation($datos -> location);
		$createdCalendar = $service -> calendars -> insert($calendar);
		return $createdCalendar -> getId();
	}

	public function update_calendario($token_json, $id_calendar, $json_data) {
		$token = json_decode($token_json);
		$datos = json_decode($json_data);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);
		$calendar = $service -> calendars -> get($id_calendar);
		$calendar -> setSummary($datos -> summary);
		$calendar -> setDescription($datos -> description);
		$calendar -> setTimeZone($datos -> timezone);
		$calendar -> setLocation($datos -> location);
		$createdCalendar = $service -> calendars -> update($id_calendar, $calendar);
		return $createdCalendar -> getEtag();
	}

	public function delete_calendario($token_json, $id_calendar) {
		$token = json_decode($token_json);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);
		$service -> calendars -> delete($id_calendar);
	}

	public function get_actividades($token_json, $id_calendar, $timezone) {
		$token = json_decode($token_json);
		$this -> client -> setAccessToken($token);

		$service = new Google_Service_Calendar($this -> client);
		$activity = array();
		$optParams = array();
		$optParams['showDeleted'] = false;
		$optParams['singleEvents'] = true;
		$optParams['timeZone']=$timezone;

		$events = $service -> events -> listEvents($id_calendar, $optParams);

		while (true) {
			foreach ($events->getItems() as $event) {
				$recurrence = array();
				for ($i = 0; $i < count($event -> recurrence); $i++) {
					array_push($recurrence, $event -> recurrence[$i]);
				}
				$email = array();
				for ($i = 0; $i < count($event -> attendees); $i++) {
					array_push($email, array("email" => $event -> attendees[$i] -> email, "displayName" => $event -> attendees[$i] -> displayName, "responseStatus" => $event -> attendees[$i] -> responseStatus, "fileUrl" => $event -> attendees[$i] -> fileUrl));
				}
				array_push($activity, array("id" => $event -> getId(),
				"summary" => $event -> getSummary(),
				"description" => $event -> description,
				"location" => $event -> location,
				"status" => $event -> status,
				"created" => $event -> created,
				"updated" => $event -> updated,
				"creator_email" => $event -> creator -> email,
				"organizer_email" => $event -> organizer -> email,
				"start_date" => $event -> start -> date,
				"start_datetime" => $this->filtrar($event -> start -> dateTime),
				"start_timezone" => $event -> start -> timeZone,
				"end_date" => $event -> end -> date,
				"end_datetime" => $this->filtrar($event -> end -> dateTime),
				"end_timezone" => $event -> end -> timeZone,
				"recurrence" => $recurrence,
				"recurringEventId" => $event -> recurringEventId,
				"originalStartTime" => $event -> originalStartTime,
				"attendees" => $email));
			}
			$pageToken = $events -> getNextPageToken();
			if ($pageToken) {
				$optParams = array('pageToken' => $pageToken);
				$events = $service -> events -> listEvents($id_calendar, $optParams);
			} else {
				break;
			}
		}
		//print_r($activity);
		$arr = array();
		$arr2 = array();
		for ($i = 0; $i < count($activity); $i++) {
			//$activity[$i] = $this -> get_convert_hora($activity[$i], $timezone);
			$start_date = $activity[$i]['start_date'];
			$start_datetime = $activity[$i]['start_datetime'];
			$fecha = $start_datetime;
			$fecha_aux = "";
			$hora_aux = "";
			if ($fecha == null) {
				$fecha = $start_date;
				$fecha_aux = $fecha;
			} else {
				$aux = explode("T", $fecha);
				$aux2 = explode("-", $aux[1]);
				$fecha = $aux[0] . " " . $aux2[0];
				$aux3 = explode(":", $aux2[0]);
				$hora_aux = $aux3[0] . ":" . $aux3[1];
				$fecha_aux = $aux[0];
			}

			$activity[$i]['time_unix'] = strtotime($fecha);
			$activity[$i]['fecha_inicio'] = $fecha_aux;
			$activity[$i]['hora_inicio'] = $hora_aux;
			$arr[$i] = strtotime($fecha);
			$arr2[$i] = $i;
		}

		array_multisort($arr, SORT_DESC, $arr2, SORT_ASC, $activity);
		$activi2 = array();
		$activi3 = array();
		for ($i = 0; $i < count($activity); $i++) {
			$fecha_aux = $activity[$i]['fecha_inicio'];
			if (!isset($activi2[$fecha_aux])) {
				$activi2[$fecha_aux] = array();
				$activi2[$fecha_aux][] = $activity[$i];
			} else {
				$activi2[$fecha_aux][] = $activity[$i];
			}
		}
		$i = 0;
		foreach ($activi2 as $key => $value) {
			$activi3[$i] = array();
			$activi3[$i]['id'] = $key;
			$activi3[$i]['unix']=strtotime($key);
			$activi3[$i]['fecha'] = $this -> get_fecha_texto($key, $this->idioma);
			$activi3[$i]['eventos'] = $value;
			$i++;
		}
		//print_r($activi2);
		return $activi3;
	}

	public function get_actividades_fecha($token_json, $id_calendar, $timezone, $fecha_inicio, $fecha_fin,$idioma="es") {
		$token = json_decode($token_json);
		// print_r($token);
		$this -> client -> setAccessToken($token);

		$service = new Google_Service_Calendar($this -> client);
		$activity = array();
		$optParams = array();
		$optParams['showDeleted'] = false;
		$optParams['singleEvents'] = true;
		$optParams['timeZone']=$timezone;
		$optParams['timeMin'] = $fecha_inicio;
		//inicio
		$optParams['timeMax'] = $fecha_fin;
		//fin
		//$optParams['timeZone']='America/Bogota';

		// print_r([$id_calendar, $optParams]);

		$events = $service -> events -> listEvents($id_calendar, $optParams);

		while (true) {
			foreach ($events->getItems() as $event) {
				$recurrence = array();
				for ($i = 0; $i < count($event -> recurrence); $i++) {
					array_push($recurrence, $event -> recurrence[$i]);
				}
				$email = array();
				for ($i = 0; $i < count($event -> attendees); $i++) {
					array_push($email, array("email" => $event -> attendees[$i] -> email, "displayName" => $event -> attendees[$i] -> displayName, "responseStatus" => $event -> attendees[$i] -> responseStatus, "fileUrl" => $event -> attendees[$i] -> fileUrl));
				}
				array_push($activity, array("id" => $event -> getId(), "summary" => $event -> getSummary(), "description" => $event -> description, "location" => $event -> location, "status" => $event -> status, "created" => $event -> created, "updated" => $event -> updated, "creator_email" => $event -> creator -> email, "organizer_email" => $event -> organizer -> email, "start_date" => $event -> start -> date, "start_datetime" =>$this->filtrar( $event -> start -> dateTime), "start_timezone" => $event -> start -> timeZone, "end_date" => $event -> end -> date, "end_datetime" => $this->filtrar($event -> end -> dateTime), "end_timezone" => $event -> end -> timeZone, "recurrence" => $recurrence,
				"recurringEventId" => $event -> recurringEventId, "originalStartTime" => $event -> originalStartTime, "attendees" => $email));
			}
			$pageToken = $events -> getNextPageToken();
			if ($pageToken) {
				$optParams = array('pageToken' => $pageToken);
				$events = $service -> events -> listEvents($id_calendar, $optParams);
			} else {
				break;
			}
		}
		//print_r($activity);
		$arr = array();
		$arr2 = array();
		for ($i = 0; $i < count($activity); $i++) {
			//$activity[$i] = $this -> get_convert_hora($activity[$i], $timezone);
			$start_date = $activity[$i]['start_date'];
			$start_datetime = $activity[$i]['start_datetime'];
			$end_date = $activity[$i]['end_date'];
			$end_datetime = $activity[$i]['end_datetime'];
			$fecha = $start_datetime;
			$fecha_aux = "";
			$hora_aux = "";
			$fecha_end = $end_datetime;
			$fecha_aux_end = "";
			$hora_aux_end = "";
			if ($fecha == null) {
				$fecha = $start_date;
				$fecha_aux = $fecha;
			} else {
				$aux = explode("T", $fecha);
				$aux2 = explode("-", $aux[1]);
				$fecha = $aux[0] . " " . $aux2[0];
				$aux3 = explode(":", $aux2[0]);
				$hora_aux = $aux3[0] . ":" . $aux3[1];
				$fecha_aux = $aux[0];
			}
			if ($fecha_end == null) {
				$fecha_end = $end_date;
				$fecha_aux_end = $fecha_end;
			}else {
				$aux = explode("T", $fecha_end);
				$aux2 = explode("-", $aux[1]);
				$fecha_end = $aux[0] . " " . $aux2[0];
				$aux3 = explode(":", $aux2[0]);
				$hora_aux_end = $aux3[0] . ":" . $aux3[1];
				$fecha_aux_end = $aux[0];
			}

			$activity[$i]['time_unix'] = strtotime($fecha);
			$activity[$i]['fecha_inicio'] = $fecha_aux;
			$activity[$i]['hora_inicio'] = $hora_aux;
			$activity[$i]['fecha_fin'] = $fecha_aux_end;
			$activity[$i]['hora_fin'] = $hora_aux_end;
			$arr[$i] = strtotime($fecha);
			$arr2[$i] = $i;
		}

		array_multisort($arr, SORT_DESC, $arr2, SORT_ASC, $activity);
		$activi2 = array();
		$activi3 = array();
		for ($i = 0; $i < count($activity); $i++) {
			$fecha_aux = $activity[$i]['fecha_inicio'];
			if (!isset($activi2[$fecha_aux])) {
				$activi2[$fecha_aux] = array();
				$activi2[$fecha_aux][] = $activity[$i];
			} else {
				$activi2[$fecha_aux][] = $activity[$i];
			}
		}
		$i = 0;
		foreach ($activi2 as $key => $value) {
			$activi3[$i] = array();
			$activi3[$i]['id'] = $key;
			$activi3[$i]['fecha'] = $this -> get_fecha_texto($key,$this->idioma);
			$activi3[$i]['eventos'] = $value;
			$i++;
		}
		//print_r($activi2);
		return $activi3;
	}

	/*public function get_convert_hora($json, $timezone) {
		if ($json['start_timezone'] != null && $json['start_timezone'] != $timezone) {
			if ($json['start_datetime'] != null) {
				$fecha = $json['start_datetime'];
				$fecha_aux = explode("T", $fecha);
				$fecha = $fecha_aux[0];
				$hora = substr($fecha_aux[1], 0, 8);
				$sourceTimeZone = $timezone;
				if ($sourceTimeZone != "" && $sourceTimeZone != null) {
					//	echo "--".$sourceTimeZone."---";
					$targetTimeZone = $json['start_timezone'];
					$datetime = new DateTime($fecha . " " . $hora, new DateTimeZone($sourceTimeZone));
					$datetime -> setTimezone(new DateTimeZone($targetTimeZone));
					$fecha = $datetime -> format('Y-m-d\TH:i:s');
					//$fecha=str_replace(" ","T",$fecha);
					$json['start_datetime'] = $fecha;
				}

			}
		}
		if ($json['end_timezone'] != null && $json['end_timezone'] != $timezone) {
			if ($json['end_datetime'] != null) {
				$fecha = $json['end_datetime'];
				$fecha_aux = explode("T", $fecha);
				$fecha = $fecha_aux[0];
				$hora = substr($fecha_aux[1], 0, 8);
				$sourceTimeZone = $timezone;
				if ($sourceTimeZone != "" && $sourceTimeZone != null) {

					$targetTimeZone = $json['end_timezone'];
					$datetime = new DateTime($fecha . " " . $hora, new DateTimeZone($sourceTimeZone));
					$datetime -> setTimezone(new DateTimeZone($targetTimeZone));
					$fecha = $datetime -> format('Y-m-d\TH:i:s');
					//$fecha=str_replace(" ","T",$fecha);
					$json['end_datetime'] = $fecha;
				}
			}
		}
		//	print_r($json);
		return $json;
	}*/

	public function get_fecha_texto($fecha,$idioma) {
		$aux = explode("-", $fecha);
		$year = $aux[0];
		$mes = $aux[1];
		$dia = $aux[2];
		if ($mes == "01")
			$mes = $this -> modidioma -> get_etiqueta("string_enero");
		if ($mes == "02")
			$mes = $this -> modidioma -> get_etiqueta("string_febrero");
		if ($mes == "03")
			$mes = $this -> modidioma -> get_etiqueta("string_marzo");
		if ($mes == "04")
			$mes = $this -> modidioma -> get_etiqueta("string_abril");
		if ($mes == "05")
			$mes = $this -> modidioma -> get_etiqueta("string_mayo");
		if ($mes == "06")
			$mes = $this -> modidioma -> get_etiqueta("string_junio");
		if ($mes == "07")
			$mes = $this -> modidioma -> get_etiqueta("string_julio");
		if ($mes == "08")
			$mes = $this -> modidioma -> get_etiqueta("string_agosto");
		if ($mes == "09")
			$mes = $this -> modidioma -> get_etiqueta("string_septiembre");
		if ($mes == "10")
			$mes = $this -> modidioma -> get_etiqueta("string_octubre");
		if ($mes == "11")
			$mes = $this -> modidioma -> get_etiqueta("string_noviembre");
		if ($mes == "12")
			$mes = $this -> modidioma -> get_etiqueta("string_diciembre");
		if($idioma=="es"){
			return $dia . " ".$this -> modidioma -> get_etiqueta("string_de")." " . $mes . " ".$this -> modidioma -> get_etiqueta("string_del")." " . $year;
		}
		if($idioma=="en"){
			return $mes." ".$dia . ", ".$year;
		}
		return $dia . " " . $mes. " ".$year;
	}

	public function get_actividad($token_json, $id_calendar, $id_activity, $timezone) {
		$token = json_decode($token_json);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);
		$optParams = array();
		$optParams['timeZone']=$timezone;
		$event = $service -> events -> get($id_calendar, $id_activity, $optParams);
		$recurrence = array();
		for ($i = 0; $i < count($event -> recurrence); $i++) {
			array_push($recurrence, $event -> recurrence[$i]);
		}
		$email = array();
		for ($i = 0; $i < count($event -> attendees); $i++) {
			array_push($email, array("email" => $event -> attendees[$i] -> email, "displayName" => $event -> attendees[$i] -> displayName, "responseStatus" => $event -> attendees[$i] -> responseStatus, "fileUrl" => $event -> attendees[$i] -> fileUrl));
		}
		$attachments = array();
		for ($i = 0; $i < count($event -> attachments); $i++) {
			array_push($attachments, array("fileUrl" => $event -> attachments[$i] -> fileUrl, "title" => $event -> attachments[$i] -> title, "mimeType" => $event -> attachments[$i] -> mimeType, "iconLink" => $event -> attachments[$i] -> iconLink,"fileId" => $event -> attachments[$i] -> fileId));
		}
		$activity = array("id" => $event -> getId(), "summary" => $event -> getSummary(), "description" => $event -> description, "location" => $event -> location, "status" => $event -> status, "created" => $event -> created, "updated" => $event -> updated, "creator_email" => $event -> creator -> email, "organizer_email" => $event -> organizer -> email, "start_date" => $event -> start -> date, "start_datetime" =>$this->filtrar( $event -> start -> dateTime), "start_timezone" => $event -> start -> timeZone, "end_date" => $event -> end -> date, "end_datetime" => $this->filtrar($event -> end -> dateTime), "end_timezone" => $event -> end -> timeZone, "recurrence" => $recurrence, "attendees" => $email, "attachments" => $attachments);
		//$activity = $this -> get_convert_hora($activity, $timezone);
		return $activity;
	}

	public function set_actividad($token_json, $id_calendar, $json_data) {

		$token = json_decode($token_json);
		$datos = json_decode($json_data);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);

		$event = new Google_Service_Calendar_Event();
		if (isset($datos -> summary))
			$event -> setSummary($datos -> summary);
		if (isset($datos -> description))
			$event -> setDescription($datos -> description);
		if (isset($datos -> location))
			$event -> setLocation($datos -> location);

		$start = new Google_Service_Calendar_EventDateTime();
		if (isset($datos -> start_date))
			$start -> setDate($datos -> start_date);
		if (isset($datos -> start_datetime))
			$start -> setDateTime($datos -> start_datetime);
		if (isset($datos -> start_timezone))
			$start -> setTimeZone($datos -> start_timezone);
		$event -> setStart($start);

		$end = new Google_Service_Calendar_EventDateTime();
		if (isset($datos -> end_date))
			$end -> setDate($datos -> end_date);
		if (isset($datos -> end_datetime))
			$end -> setDateTime($datos -> end_datetime);
		if (isset($datos -> end_timezone))
			$end -> setTimeZone($datos -> end_timezone);
		$event -> setEnd($end);
		if (isset($datos -> attendees))
			$event -> attendees = $datos -> attendees;
		if (isset($datos -> attachments) && $datos -> attachments != "")
			$event -> attachments = $datos -> attachments;
		if (isset($datos -> creator))
			$event -> creator = $datos -> creator;

		$createdEvent = $service -> events -> insert($id_calendar, $event, array('supportsAttachments'=>true,'sendNotifications' => true));

		return $createdEvent -> getId();
	}

	public function update_actividad($token_json, $id_calendar, $id_activity, $json_data) {
		$token = json_decode($token_json);
		$datos = json_decode($json_data);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);

		$event = $service -> events -> get($id_calendar, $id_activity);
		if (isset($datos -> summary) && $datos -> summary != "")
			$event -> setSummary($datos -> summary);
		if (isset($datos -> description) && $datos -> description != "")
			$event -> setDescription($datos -> description);
		if (isset($datos -> location) && $datos -> location != "")
			$event -> setLocation($datos -> location);

		$start = new Google_Service_Calendar_EventDateTime();
		if (isset($datos -> start_date) && $datos -> start_date != "")
			$start -> setDate($datos -> start_date);
		if (isset($datos -> start_datetime) && $datos -> start_datetime != "")
			$start -> setDateTime($datos -> start_datetime);
		if (isset($datos -> start_timezone) && $datos -> start_timezone != "") {
			$start -> setTimeZone($datos -> start_timezone);
			$event -> setStart($start);
		}

		$end = new Google_Service_Calendar_EventDateTime();
		if (isset($datos -> end_date) && $datos -> end_date != "")
			$end -> setDate($datos -> end_date);
		if (isset($datos -> end_datetime) && $datos -> end_datetime != "")
			$end -> setDateTime($datos -> end_datetime);
		if (isset($datos -> end_timezone) && $datos -> end_timezone != "") {
			$end -> setTimeZone($datos -> end_timezone);
			$event -> setEnd($end);
		}

		if (isset($datos -> attendees) && $datos -> attendees != "")
			$event -> attendees = $datos -> attendees;
		if (isset($datos -> attachments) && $datos -> attachments != ""){
			$arr=array();
			for ($i=0; $i <count($datos -> attachments) ; $i++) {
				$attach = new Google_Service_Calendar_EventAttachment();
				$attach->setFileId($datos->attachments[$i]->fileId);
				$attach->setFileUrl($datos->attachments[$i]->fileUrl);
				$attach->setTitle($datos->attachments[$i]->title);
				$arr[]=$attach;
			}
			$event -> attachments = $arr;
		}
		$updatedEvent = $service -> events -> update($id_calendar, $id_activity, $event, array('supportsAttachments'=>true,'sendNotifications' => true));
		//echo $updatedEvent -> getUpdated();
	}

	public function delete_actividad($token_json, $id_calendar, $id_activity) {
		$token = json_decode($token_json);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);
		$service -> events -> delete($id_calendar, $id_activity);
	}
	public function migrar_eventos($calendarId,$events,$destination,$token_json){
		$token = json_decode($token_json,true);
		$this -> client -> setAccessToken($token);
		$service = new Google_Service_Calendar($this -> client);
		for ($i=0; $i <count($events) ; $i++) {
			$result = $service-> events -> move($calendarId,$events[$i][0],$destination);
			return $result;
		}
 }

}//FIN DE LA CLASE

/*
 $calendar_google = new GoogleCalendar();
 $token_json = json_encode('{"access_token":"ya29.GlvrA-_cNZcbMAbuG3UwxvdgyJC59-bGD8XC9xgcuKss4W884ud3VCHSGnlksfXCoRc6R9hBsTakDNzibaTOM8e7tsoMqEPpxhKcIKgH15bTZqP6P-UHYLof8vwQ","token_type":"Bearer","expires_in":3600,"refresh_token":"1\/qhr891womjY1oaRc_jUaXzl7NZkUouyQ9CJgGs1jaTM","created":1486474344}');
 $listCalendar = $calendar_google -> get_calendarios($token_json);
 echo "Calendarios:<br>";
 print_r($listCalendar);
 echo "<br><br>";

 //$listActivityCalendar = $calendar_google -> get_actividades($token_json, $listCalendar[0]['id']);
 //$activity = $calendar_google -> get_actividad($token_json, $listCalendar[0]['id'], $listActivityCalendar[6]['id']);
 //print_r($activity);
 //echo "<br><br>";

 //$json_data = '{"summary":"Evento 8","description":"Esto Es una Prueba","location":"Parque del Agua, Diagonal 32 #30A-51, Bucaramanga, Santander, Colombia","status":"confirmed","created":"2017-02-10T13:05:18.000Z","updated":"2017-02-10T13:19:31.883Z","creator_email":"edgar.herrera@systemico.co","organizer_email":"edgar.herrera@systemico.co","start_date":null,"start_datetime":"2017-02-26T08:30:00-05:00","start_timezone":null,"end_date":null,"end_datetime":"2017-02-26T09:30:00-05:00","end_timezone":null,"recurrence":[],"attendees":[{"email":"claudia.barrios@systemico.co","name":null,"responseStatus":"needsAction"},{"email":"edwin.ariza@systemico.co","name":"Edwin Ariza","responseStatus":"needsAction"},{"email":"edgar.herrera@systemico.co","name":null,"responseStatus":"accepted"},{"email":"elvis.orduz@systemico.co","name":"Elvis Damian Orduz","responseStatus":"needsAction"}]}';
 //$htmlLink = $calendar_google -> set_actividad($token_json, 'primary', $json_data);
 //echo $htmlLink;
 //echo "<br><br>";
 /*
 $json_data = '{"summary":"Evento 8 UPDATE","description":"MSP430G2553","location":"Parque del Agua, Diagonal 32 #30A-51, Bucaramanga, Santander, Colombia","status":"confirmed","created":"2017-02-10T13:05:18.000Z","updated":"2017-02-10T13:19:31.883Z","creator_email":"edgar.herrera@systemico.co","organizer_email":"edgar.herrera@systemico.co","start_date":null,"start_datetime":"2017-02-26T08:30:00-05:00","start_timezone":null,"end_date":null,"end_datetime":"2017-02-26T09:30:00-05:00","end_timezone":null,"recurrence":[],"attendees":[{"email":"claudia.barrios@systemico.co","name":null,"responseStatus":"needsAction"},{"email":"edgar.herrera@systemico.co","name":null,"responseStatus":"accepted"},{"email":"elvis.orduz@systemico.co","name":"Elvis Damian Orduz","responseStatus":"needsAction"}]}';
 $update = $calendar_google -> update_actividad($token_json, 'primary', 'lkj2vdn9d6cfr5qkov6hgq7ct4', $json_data);
 echo $update;
 echo "<br><br>";
 //$json_data='{"summary":"MSP430_Texas_Instruments", "description":"Microcontrollers", "timezone":"America/Mexico_City", "location":"Colombia"}';
 //$id_calendar = $calendar_google -> set_calendario($token_json, $json_data);
 //echo $id_calendar;
 //echo "<br><br>";

 //$json_data='{"summary":"MSP430_Texas_Instrument", "description":"Edgar Fernel", "timezone":"America/Mexico_City", "location":"Colombia"}';
 //$etag_calendar = $calendar_google -> update_calendario($token_json, $id_calendar, $json_data);
 //echo $etag_calendar;
 //echo "<br><br>";

 //for ($i = 0; $i < count($listCalendar); $i++) {
 //$listActivityCalendar = $calendar_google -> get_actividades($token_json, $listCalendar[$i][0]);
 //echo "Actividades Calendario " . $listCalendar[$i][1] . ":<br>";
 //for ($j = 0; $j < count($listActivityCalendar); $j++) {
 //echo($j + 1) . " - " . $listActivityCalendar[$j][1] . ":<br>";
 //echo "  Descripcion: " . $listActivityCalendar[$j][2] . "<br>";
 //echo "  Ubicacion:   " . $listActivityCalendar[$j][3] . "<br>";
 //echo "  Fecha:       " . $listActivityCalendar[$j][4] . "   " . $listActivityCalendar[$j][5] . " - " . $listActivityCalendar[$j][6] . "   " . $listActivityCalendar[$j][7] . "<br><br>";
 //}
 //echo "<br>";
 //}*/
?>
