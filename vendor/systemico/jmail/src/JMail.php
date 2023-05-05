<?php
namespace Systemico;

use Mailgun\Mailgun;
use \Mailjet\Resources;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;


/**
 * Clase encargada del envió masivo de correos.
 */
class JMail{
    private $api_key;
    private $domain;
    private $sender;
    private $sender_password="";
    private $service;
    private $name="";
    private $name_to="";
    public static $MAILGUN='MAILGUN';
    public static $MAILJET='MAILJET';
    public static $PHPMAILER='PHPMAILER';
    public static $DEBUG= false;
  /**
   * Permite la definición de credenciales requeridas para la operación del la librería.
   * @param $api Key del servicio a utilizar, puede ser array
   * @param $domain Dominio al que está vinculada la petición.
   * @param $sender Información de quien remite el correo, y que aparecerá como remisor.
   * @param $service --> Tipo de servicio a utilizar JMail::$MAILGUN
   * @param $name Nombre de la cuenta que remite el correo.
   */
    public function credentials($api_key, $domain, $sender, $service, $name="", $name_to="", $debug=false){
      $this->api_key=$api_key;
      $this->domain=$domain;
      $this->sender=$sender;
      $this->service=$service;
      $this->name=$name;
      $this->name_to=$name_to;
      JMail::$DEBUG=$debug;
    }
  /**
   * Permite la definición de credenciales requeridas para la operación del la librería.
   * @param $api Key del servicio a utilizar, puede ser array
   * @param $domain Dominio al que está vinculada la petición.
   * @param $sender Información de quien remite el correo, y que aparecerá como remisor.
   * @param $service --> Tipo de servicio a utilizar JMail::$MAILGUN
   * @param $name Nombre de la cuenta que remite el correo.
   */
  public function credentials_mailer($sender, $sender_password, $name="", $name_to="", $debug=false){
    $this->sender = $sender;
    $this->sender_password = $sender_password;
    $this->service = JMail::$PHPMAILER;
    $this->name = $name;
    $this->name_to = $name_to;
    JMail::$DEBUG=$debug;
  }
  /**
   * @param $email_to Destinatario del mensaje.
   * @param $subject  Asunto del mensaje
   * @param $content Contenido del mensaje
   * @param string $altbody Mensaje altbody, aparece en el preview del mensaje
   * @param string $name Nombre del remitente en caso que se quiera personalizar como Carlos Ariza <carlos.ariza@systemico.co>
   * @param string $tag Etiqueta para marcar el correo.
   * @throws ErrorException
   * @throws \Mailgun\Messages\Exceptions\MissingRequiredMIMEParameters
   */
    public function send($email_to,$subject,$content,$altbody="", $tag=""){
      if($this->service == JMail::$MAILGUN){
        $this->send_mailgun($email_to,$subject,$content,$altbody,$tag);
      }
      if($this->service == JMail::$MAILJET){
        $this->send_mailjet($email_to,$subject,$content,$altbody,$tag);
      }
      if($this->service == JMail::$PHPMAILER){
       $this->send_mailer($email_to, $subject, $content, $altbody, $tag);
      }
    }

  /**
   * @param $email_to Destinatario del mensaje.
   * @param $subject  Asunto del mensaje
   * @param $content Contenido del mensaje
   * @param string $altbody Mensaje altbody, aparece en el preview del mensaje
   * @param string $name Nombre del remitente en caso que se quiera personalizar como Carlos Ariza <carlos.ariza@systemico.co>
   * @param string $tag Etiqueta para marcar el correo.
   */
    public function send_mailgun($email_to,$subject,$content,$altbody="", $tag=""){
      if (JMail::$DEBUG)
        echo "Enviando con Mailgun";
      $mg = Mailgun::create($this->api_key);
      $domain = $this->domain;
      $params = array(
        'from'    => $this->name.' <'.$this->sender.'>',
        'to'      => $email_to,
        'subject' => $subject,
        'text'    => $altbody,
        'html'    => $content
      );
      $mg->messages()->send($domain, $params);
    }
  /**
   * @param $email_to Destinatario del mensaje.
   * @param $subject  Asunto del mensaje
   * @param $content Contenido del mensaje
   * @param string $altbody Mensaje altbody, aparece en el preview del mensaje
   * @param string $name Nombre del remitente en caso que se quiera personalizar como Carlos Ariza <carlos.ariza@systemico.co>
   * @param string $tag Etiqueta para marcar el correo.
   */
  public function send_mailjet($email_to,$subject,$content,$altbody="", $tag=""){
    if (JMail::$DEBUG)
      echo "Enviando con MailJet";
    $mj = new \Mailjet\Client($this->api_key[0],$this->api_key[1],true,['version' => 'v3.1']);
    $body = [
      'Messages' => [
        [
          'From' => [
            'Email' => $this->sender,
            'Name' => $this->name
          ],
          'To' => [
            [
              'Email' => $email_to,
              'Name' => $this->name_to
            ]
          ],
          'Subject' => $subject,
          'TextPart' => $altbody,
          'HTMLPart' => $content,
          'CustomID' => $tag
        ]
      ]
    ];
    $response = $mj->post(Resources::$Email, ['body' => $body]);
    $response->success() && var_dump($response->getData());
  }
  /**
   * @param $email_to Destinatario del mensaje.
   * @param $subject  Asunto del mensaje
   * @param $content Contenido del mensaje
   * @param string $altbody Mensaje altbody, aparece en el preview del mensaje
   * @param string $name Nombre del remitente en caso que se quiera personalizar como Carlos Ariza <carlos.ariza@systemico.co>
   * @param string $tag Etiqueta para marcar el correo.
   */
  public function send_mailer($email_to,$subject,$content,$altbody="", $tag=""){
    if (JMail::$DEBUG)
      echo "Sending Email with PHPMailer to ($email_to) the subject ($subject)...";
    //Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer(true);
    try {
      //Server settings
      $mail->SMTPDebug = SMTP::DEBUG_OFF;                      //Enable verbose debug output
      $mail->isSMTP();
      $mail->CharSet = 'UTF-8';
      //Send using SMTP
      $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
      $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
      $mail->Username   = $this->sender;                     //SMTP username
      $mail->Password   = $this->sender_password;                               //SMTP password
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
      $mail->Port       = 587;                                    //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

      //Recipients
      $mail->setFrom($this->sender, $this->name);
      $mail->addAddress($email_to, $this->name_to);     //Add a recipient

      //Content
      $mail->isHTML(true);                                  //Set email format to HTML
      $mail->Subject = $subject;
      $mail->Body    = $content;
      $mail->AltBody = $altbody;

      $mail->send();
    } catch (Exception $e) {
      echo "Message could not be sent. PHP Mailer Error: {$mail->ErrorInfo}";
    }
  }
}
