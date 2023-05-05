# JMail
Micro library to send email notifications using different platforms like Mailgun, MailJet and others.

## Example with Mailgun
    require_once (__DIR__."/../vendor/autoload.php");

    use Systemico\JMail;

    $jmail= new JMail();
    $jmail->credentials('[PRIVATE_API_KEY]', '[DOMAIN]', '[SENDER]',JMail::$MAILGUN,'[NAME_SENDER]');
    $jmail->send('[EMAIL_TO]','Hello World!','My firts Email HTML','My first mail TEXT');

## Example with MailJet
    require_once (__DIR__."/../vendor/autoload.php");

    use Systemico\JMail;

    $jmail= new JMail();
    // Mensaje con MailJet
    $jmail->credentials(array('[API_KEY]','[SECRET_API_KEY]'), '[DOMAIN]', '[SENDER]',JMail::$MAILJET,'[NAME_SENDER]');
    $jmail->send('[EMAIL_TO]','Hello World!','My firts Email HTML','My first mail TEXT');

## Example with PHPMailer
    require_once (__DIR__."/../vendor/autoload.php");

    use Systemico\JMail;

    $jmail= new JMail();
    // Mensaje con PHPMailer
    $jmail->credentials_mailer('[EMAIL_FROM]', '[EMAIL_FROM_PASSWORD]', '[NAME]', 'NAME_TO');
    $jmail->send('[EMAIL_TO]','Hello World!','My firts Email HTML','My first mail TEXT');

## Contributors
Edwin Ariza <me@edwinariza.com>
