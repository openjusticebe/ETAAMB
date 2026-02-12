<?php
$path = './dependencies/Mail';
require_once('config.php');
require_once('config.default.php');

// CONFIG
$to = MAIL_ADMIN;

$stamp = dechex(time());
$stamp = preg_replace('#^(\w{4})(\w+)$#','$1 $2',strtoupper($stamp));

$subject = 'etaamb remove request nr.'.$stamp;

// CHECK
$secret = getenv('FORM_KEY') ?: 'Nah-ah';
$dayKey = gmdate('Y-m-d');
$expected = hash_hmac('sha256', $dayKey, $secret);
$provided = $_POST['stamp'] ?? '';

if (!hash_equals($expected, $provided)) {
    die('Error detected');
}

// FLOW
/* Etaamb Private Life Mail Script */
$fields = array('url','terms','email','comment');

if (!isset($_POST['url']) || !isset($_POST['terms']))
	die(json_encode('error - data'));

try {
    $url     = $_POST['url'];
    $terms   = $_POST['terms'];
    $mail    = isset($_POST['email']) ? $_POST['email'] : false;
    $comment = isset($_POST['comment']) ? $_POST['comment'] : false;

    $message = 'Bonjour,

Vous recevez ce message car une personne a signalé une donnéé personelle à supprimer.

Pour anonymiser le document, connectez-vous sur un "steward" d\'Etaamb et exécutez la commande suivante :

    >  ./management/manager.php anonymise [NUMAC]


Contenu signalé :
--------------------------

Termes : '.$terms.'
Page   : '.$url.'

';

    // $message .= !empty($mail) ? "contact : $mail\n" : '';
    // $message .= !empty($mail) ? "\ncommentaire :\n$comment\n" : '';
    // $message .= "\n--------------------------\n\n\n";
    // $message .= "\n\nAdministration etaamb.openjustice.be";
    // $headers = 'From: noreply@etaamb.openjustice.be' . "\r\n" ;
    // $headers .= 'MIME-Version: 1.0' . "\r\n";
    // $headers .= 'Content-type: text/plain; charset="UTF-8"' . "\r\n";


    // mail($to,$subject,$message,$headers) or die(json_encode('error'));


    $url = "https://app.mailpace.com/api/v1/send";
    $data = [
        "from" => "noreply@etaamb.openjustice.be",
        "to" => $to,
        "subject" => $subject,
        "textbody" => $message
    ];

    $token = getenv('SMTP_USER') ?: 'Nah-ah';
    $options = [
        "http" => [
            "method"  => "POST",
            "header"  => [
                "Accept: application/json",
                "Content-Type: application/json",
                "MailPace-Server-Token: $token"
            ],
            "content" => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    echo $response;
} catch (Exception $e) {
    die('Exception catched : ' . $e->getMessage());
}

echo('ok');
