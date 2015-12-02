.<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Mailgun\Mailgun;

$mgClient = new Mailgun('key');
$domain = "sandboxd22a3870f1714d69a0c4ae1b76b4a962.mailgun.org";

$activation_email_content = file_get_contents("./emailTemplates/activation-email.html");

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('activation-email', false, false, false, false);
echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function($msg) use ($mgClient, $domain, $activation_email_content) {
    echo "#Received->\n";
    $data = json_decode($msg->body, true);
    echo "#Processed->\n";

    $to_email = $data['email'];
    $activation_code = $data['activationCode'];

    //Replave activation code variable in the email template
    $activation_email_content = str_replace('{activation_code}', $activation_code, $activation_email_content);
    $result = $mgClient->sendMessage($domain, array(
       'from'    => "StudentShopper <$domain>",
       'to'      => "New User <$to_email>",
       'subject' => 'StudentShopper Account Activation',
       'html'    => $activation_email_content
    ));
    echo "#Email has been sent->\n";
};

$channel->basic_consume('activation-email', '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

//Close connection
$channel->close();
$connection->close();
