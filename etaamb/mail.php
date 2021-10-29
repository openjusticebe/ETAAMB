<?php
$path = './dependencies/Mail';
require_once('config.php');
require_once('config.default.php');

// CONFIG
$to = MAIL_ADMIN;

$stamp = dechex(time());
$stamp = preg_replace('#^(\w{4})(\w+)$#','$1 $2',strtoupper($stamp));

$subject = 'etaamb remove request nr.'.$stamp;

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

Vous recevez ce message car une personne à signalé une donnéé personelle à supprimer.

Pour anonymiser le document, connectez-vous sur un "steward" d\'Etaamb et exécutez la commande suivante :

    >  ./management/manager.php anonymise [NUMAC]


Contenu signalé :
--------------------------

Termes   : '.$terms.'
Adresse  : '.$url.'

';

    $message .= !empty($mail) ? "contact : $mail\n" : '';
    $message .= !empty($mail) ? "\ncommentaire :\n$comment\n" : '';
    $message .= '\n--------------------------\n\n\n';
    $message .= "\n\nAdministration etaamb.openjustice.be";

    $headers = 'From: noreply@openjustice.be' . "\r\n" ;
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/plain; charset="UTF-8"' . "\r\n";


    mail($to,$subject,$message,$headers) or die(json_encode('error'));
} catch (Exception $e) {
    die('Exception catched : ' . $e->getMessage());
}

echo('ok');
