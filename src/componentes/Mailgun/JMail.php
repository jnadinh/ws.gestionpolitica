<?php
require_once dirname(__FILE__).'/vendor/autoload.php';
use Mailgun\Mailgun;
class JMail{
    public static function enviar($email,$subject,$content,$altbody="",$destinos="",$name="", $tag=""){

        $mg = new Mailgun(Variables::$apikeyMailgun);
		$domain = Variables::$dominioMailgun;
        try{
             $result = $mg->sendMessage($domain, array(
                'from'      => Variables::$nombreRemite."<".Variables::$correoRemite.">",
                'to'        => $email,
                'subject'   => $subject,
                'html'      => $content,
                'text'      => $content)); 
            return $result;
        } catch(ErrorException $e) {
            return $e;
        }
    }

    public static function send($subject, $content, $email, $name, $tag="Cobros", $conthtml, $attach = array(), $cc = array()) {
        if(isset(Variables::$ENVDEV) && Variables::$ENVDEV)
            $email=Variables::$EMAIL_REP;

        $mg = new Mailgun(Variables::$apikeyMailgun);
        $domain = Variables::$dominioMailgun;
        try{
            $cc = (array)$cc;
            $cc = (count($cc) > 0) ?implode(', ', $cc) : '';
			$result = $mg->sendMessage($domain, array(
                'from'      =>'Ciatel <soporte@ciatel.com>',
                'to'      => $email,
                'subject' => $subject,
                'html'    => $conthtml,
                'text'    => $content
            ), array(
                'attachment' => array($attach)
            ));
            return $result;
        } catch(ErrorException $e) {
            return $e;
        }
    }
}
?>
