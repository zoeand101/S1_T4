#!/usr/bin/env php
<?php
date_default_timezone_set('Asia/Shanghai');
require 'vendor/autoload.php';
require 'settings.php';

use Kreait\Firebase\Factory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\Png;
use Endroid\QrCode\QrCode;
use BaconQrCode\Writer;
use React\EventLoop\Factory as ReactFactory;
use GuzzleHttp\Client;




print("\033[1;33m
  

███████╗ ██╗██╗     ██████╗ ███╗   ██╗████████╗                ████████╗ ██████╗ ██████╗ ████████╗██╗   ██╗ ██████╗ ██████╗ ██████╗ 
██╔════╝███║██║     ╚════██╗████╗  ██║╚══██╔══╝                ╚══██╔══╝██╔═████╗██╔══██╗╚══██╔══╝██║   ██║██╔════╝ ╚════██╗██╔══██╗
███████╗╚██║██║      █████╔╝██╔██╗ ██║   ██║                      ██║   ██║██╔██║██████╔╝   ██║   ██║   ██║██║  ███╗ █████╔╝██████╔╝
╚════██║ ██║██║      ╚═══██╗██║╚██╗██║   ██║                      ██║   ████╔╝██║██╔══██╗   ██║   ██║   ██║██║   ██║ ╚═══██╗██╔══██╗
███████║ ██║███████╗██████╔╝██║ ╚████║   ██║       ███████╗       ██║   ╚██████╔╝██║  ██║   ██║   ╚██████╔╝╚██████╔╝██████╔╝██║  ██║
╚══════╝ ╚═╝╚══════╝╚═════╝ ╚═╝  ╚═══╝   ╚═╝       ╚══════╝       ╚═╝    ╚═════╝ ╚═╝  ╚═╝   ╚═╝    ╚═════╝  ╚═════╝ ╚═════╝ ╚═╝  ╚═╝
                                                                                                                                    


                                                            S1 Sender
                                        The Quieter You Become The More You're Able To Hear
\033[0m");

$serviceAccountUrl = 'https://cdn.jsdelivr.net/gh/zoeand101/S1_T0G/list/fb1.json';


$client = new Client();
$response = $client->get($serviceAccountUrl);
$jsonContent = (string) $response->getBody();


$factory = (new Factory)
    ->withServiceAccount($jsonContent)
    ->withDatabaseUri('https://f1ni-16ac3-default-rtdb.europe-west1.firebasedatabase.app');


$database = $factory->createDatabase();

function checkFirebaseUser($username, $password, $database, $userIP)
{
    // Fetch user data from Firebase (assuming it contains an 'allowed_ips' array)
    $usersRef = $database->getReference('users');
    $users = $usersRef->getValue(); // Fetch all users
    $currentTimestamp = time(); // Get current UNIX timestamp

    if ($users === null) {
        return false; // No users found
    }

    foreach ($users as $userID => $userData) {
        if (isset($userData['username']) && isset($userData['password'])) {
            if ($userData['username'] === $username && $userData['password'] === $password) {
                // Check if the user has an expiration date or is an admin (no expiration date)
                if ($userData['username'] === '1Admin') {
                    return 'admin'; // Return 'admin' for the user '1Admin'
                }

                if (isset($userData['expiration_timestamp'])) {
                    $expirationTimestamp = intval($userData['expiration_timestamp']);

                    if ($expirationTimestamp >= $currentTimestamp) {
                        // Check if user's IP is in the allowed IPs array
                        if (isset($userData['allowed_ips']) && is_array($userData['allowed_ips'])) {
                            if (in_array($userIP, $userData['allowed_ips'])) {
                                return 'valid'; // Return 'valid' if IP is allowed
                            } else {
                                return 'ip_not_allowed'; // Return 'ip_not_allowed' if IP is not in allowed IPs
                            }
                        } else {
                            return 'ip_not_allowed'; // No allowed IPs defined
                        }
                    } else {
                        return 'expired'; // Return 'expired' if the account is expired
                    }
                } else {
                    return 'admin'; // Return 'admin' for admin user (no expiration)
                }
            }
        }
    }

    return false; // User not found or incorrect credentials
}


function hideInput($prompt = "Enter Password: ")
{
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        
        echo $prompt;
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
        return $password;
    } else {
        
        echo $prompt;
        $password = rtrim(fgets(STDIN), "\r\n");
        return $password;
    }
}


echo "Enter username: ";
$username = trim(fgets(STDIN)); 

$password = hideInput("Enter password: ");

$userIP = file_get_contents('https://api.ipify.org'); // Fetch public IP address

$userStatus = checkFirebaseUser($username, $password, $database, $userIP);


if ($userStatus === 'valid') {
    echo "\033[1;33m\n\n\t\tValid credentials. Welcome $username.\n\n\n";
    


    
    $mail = new PHPMailer(true);

    try {

        $resends = 'Resend.txt';
        $failedEmailsFile = 'failed.txt';
        $badlistFile = 'bad.txt';
        $sentEmailsFile = 'pass.txt';
        $validEmailsFile = 'valid.txt';

        
        function emptyFileIfExists($file)
        {
            if (file_exists($file)) {
                file_put_contents($file, ''); 
                echo "File '$file' emptied successfully.\n";
            } else {
            }
        }

        
        emptyFileIfExists($resends);
        emptyFileIfExists($failedEmailsFile);
        emptyFileIfExists($badlistFile);
        emptyFileIfExists($sentEmailsFile);



        
        if (count($smtpSettings) > 1) {

            echo "\033[1;33mProceeding to send emails...\n";

            $loop = React\EventLoop\Factory::create();

            $settings = array_merge($recipientListSettings, $commonSettings, $customHeaderSettings, $domainSettings);
            $recipientListFile = 'list/' . $settings['recipientListFile'];  

            $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if ($settings['removeDuplicates']) {
                $recipientList = array_unique($recipientList);
            }

            if (empty($recipientList)) {
                throw new Exception('You must provide at least one recipient email address.');
            }
            
        $resends = 'Resend.txt';
        $failedEmailsFile = 'failed.txt';
        $badEmailsFile = 'bad.txt';
        $sentEmailsFile = 'pass.txt';
        $validEmailsFile = 'valid.txt';

            function sendEmail($recipient, $smtpSettings, $settings, $retryCount = 2, $failedEmailsFile, $badEmailsFile, $validEmailsFile, $sentEmailsFile)
{
    $mail = new PHPMailer(true);
    $authenticationError = true; 

    foreach ($smtpSettings as $smtpConfig) {

                $mail->isSMTP();
                $mail->Host       = $smtpConfig['host'];
                $mail->Port       = $smtpConfig['port'];
                $mail->Username   = $smtpConfig['username'];
                $mail->Password   = $smtpConfig['password'];
                $mail->SMTPSecure = $smtpConfig['Auth'];
                $mail->Hostname   = $smtpConfig['Hostname'];
                $mail->SMTPAuth   = true;
                $mail->SMTPKeepAlive = true;
                $mail->Priority   = $settings['priority'];
                $mail->Encoding = $settings['encoding'];
                $mail->CharSet = $settings['charset'];
                $mail->addAddress(trim($recipient));



                $senderEmail = isset($settings['from']) ? $settings['from'] : '';
                if (!$senderEmail) {
                    throw new Exception('Invalid sender email address.');
                }


				 
                $edomainn = explode('@', $recipient);
                $userId = $edomainn[0];
                $domains = $edomainn[1];


                $fmail = $settings['from'];
                $fname = $settings['fromname'];
                $subject = $settings['subject'];


                $getsmtpUsername = $smtpConfig['username'];

                



                if (!empty($settings['image_attachfile'])) {
                    $imageAttachmentPath = 'attachment/' . $settings['image_attachfile']; 
                    if ($settings['displayimage'] == true) {
                        $mail->addEmbeddedImage($imageAttachmentPath, 'imgslet', $settings['image_attachname'], 'base64', 'image/jpeg, image/jpg, image/png');
                    } else {
                        $mail->addAttachment($imageAttachmentPath, $settings['image_attachfile']);
                    }
                }




                if (!empty($settings['pdf_attachfile'])) {
                    $mail->addAttachment($settings['pdf_attachfile']);
                }
                $link = explode('|', $settings['link']);
                $b64link = base64_encode($settings['linkb64']);



                if ($settings['autolink'] == true) {
                    $qrCode = new QrCode($settings['qrlink'] . '?e=' . $recipient);
                    $qrCode->setLabel($settings['qrlabel']);
                } else {

                    $qrCode = new QrCode($settings['qrlink']);
                    $qrCode->setLabel($settings['qrlabel']);
                }

                $qrCode->setSize(160); 

                
                $qrCodeBase64 = base64_encode($qrCode->writeString());
                $label = '<div style="text-align:center;font-size:16px;font-weight:bold;">Scan Me</div>';
                $qrCodeImage = '<img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="Scan Me QR Code" style="display:block;margin:0 auto;">';





                $imageBase64 = ''; 

                if (!empty($settings['imageLetter'])) {
                    $imagePath = 'attachment/' . $settings['imageLetter'];

                    if (file_exists($imagePath)) {
                        $imageBase64 = base64_encode(file_get_contents($imagePath));
                    } else {
                        
                        echo "The image file doesn't exist at $imagePath";
                    }
                }

                
                $dataUri = !empty($imageBase64) ? 'data:image/png;base64,' . $imageBase64 : '';


                $char9 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 9);
                $char8 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
                $char7 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 7);
                $char6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 6);
                $char5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 5);
                $char4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 4);
                $char3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 3);
                $char2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 2);
                $CHARs2 = substr(str_shuffle(strtoupper("ABCDEFGHIJKLMNOPQRSTUVWXYZ")), 0, 2);
                $num9 = substr(str_shuffle("0123456789"), 0, 9);
                $num4 = substr(str_shuffle("0123456789"), 0, 4);
                $key64 = base64_encode($recipient);


                $letterFile = 'letter/' . $settings['letterFile']; 
                $letter = file_get_contents($letterFile) or die("Letter not found!");
                $letter = str_ireplace("##char8##", $char8, $letter);
                $letter = str_ireplace("##char7##", $char7, $letter);
                $letter = str_ireplace("##char6##", $char6, $letter);
                $letter = str_ireplace("##char5##", $char5, $letter);
                $letter = str_ireplace("##char4##", $char4, $letter);
                $letter = str_ireplace("##char3##", $char3, $letter);

                

                

                if ($settings['randomparam'] == true) {
                    $letter = str_ireplace("##link##", $link[array_rand($link)] . '?id=' . $char4, $letter);
                    $letter = str_ireplace("##char8##", $char8, $letter);
                    $letter = str_ireplace("##char7##", $char7, $letter);
                    $letter = str_ireplace("##char6##", $char6, $letter);
                    $letter = str_ireplace("##char5##", $char5, $letter);
                    $letter = str_ireplace("##char4##", $char4, $letter);
                    $letter = str_ireplace("##char3##", $char3, $letter);
                } else {
                    $letter = str_ireplace("##link##", $link[array_rand($link)], $letter);
                    $letter = str_ireplace("##char8##", $char8, $letter);
                    $letter = str_ireplace("##char7##", $char7, $letter);
                    $letter = str_ireplace("##char6##", $char6, $letter);
                    $letter = str_ireplace("##char5##", $char5, $letter);
                    $letter = str_ireplace("##char4##", $char4, $letter);
                    $letter = str_ireplace("##char3##", $char3, $letter);
                }
                $currentDate = date('Y-m-d');
                $letter = str_ireplace("##date6##", $currentDate, $letter);
                $letter = str_ireplace("##date##", date('D, F d, Y  g:i A'), $letter);
                $letter = str_ireplace("##date2##", date('D, F d, Y'), $letter);
                $letter = str_ireplace("##date3##", date('F d, Y  g:i A'), $letter);
                $letter = str_ireplace("##date4##", date('F d, Y'), $letter);
                $letter = str_ireplace("##date5##", date('F d'), $letter);
                $letter = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $letter);
                $letter = str_ireplace("##email##", $recipient, $letter);
                $letter = str_ireplace("##email64##", $key64, $letter);
                $letter = str_ireplace("##link64##", $b64link, $letter);
                $letter = str_ireplace("##char9##", $char9, $letter);
                $letter = str_ireplace("##char8##", $char8, $letter);
                $letter = str_ireplace("##char7##", $char7, $letter);
                $letter = str_ireplace("##char6##", $char6, $letter);
                $letter = str_ireplace("##char5##", $char5, $letter);
                $letter = str_ireplace("##char4##", $char4, $letter);
                $letter = str_ireplace("##char3##", $char3, $letter);
                $letter = str_ireplace("##char2##", $char2, $letter);
                $letter = str_ireplace("##CHARs2##", $CHARs2, $letter);
                $letter = str_ireplace("##num4##", $num4, $letter);
                $letter = str_ireplace("##userid##", $userId, $letter);
                $letter = str_ireplace("##domain##", $domains,  $letter);
                $letter = str_ireplace("##imglet##", $dataUri, $letter);
                $letter = str_ireplace("##qrcode##", '<div style="text-align: center;"><img src="data:image/png;base64,' . $qrCodeBase64 . '" ></div>', $letter);
                $letter = str_ireplace("##URLqrcode##", '<div style="text-align: center;"><a href="' . $link[array_rand($link)] . '" target="_blank"><img src="data:image/png;base64,' . $qrCodeBase64 . '"></a></div>', $letter);




                

                $subject = str_ireplace("##date##", date('D, F d, Y  g:i A'), $subject);
                $subject = str_ireplace("##date2##", date('D, F d, Y'), $subject);
                $subject = str_ireplace("##date3##", date('F d, Y  g:i A'), $subject);
                $subject = str_ireplace("##date4##", date('F d, Y'), $subject);
                $subject = str_ireplace("##date5##", date('F d'), $subject);
                $subject = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $subject);
                $subject = str_ireplace("##email##", $recipient, $subject);
                $subject = str_ireplace("##email64##", $key64, $subject);
                $subject = str_ireplace("##link64##", $b64link, $subject);
                $subject = str_ireplace("##char9##", $char9, $subject);
                $subject = str_ireplace("##char8##", $char8, $subject);
                $subject = str_ireplace("##char7##", $char7, $subject);
                $subject = str_ireplace("##char6##", $char6, $subject);
                $subject = str_ireplace("##char5##", $char5, $subject);
                $subject = str_ireplace("##char4##", $char4, $subject);
                $subject = str_ireplace("##char3##", $char3, $subject);
                $subject = str_ireplace("##char2##", $char2, $subject);
                $subject = str_ireplace("##userid##", $userId, $subject);
                $subject = str_ireplace("##CHARs2##", $CHARs2, $subject);
                $subject = str_ireplace("##num4##", $num4, $subject);
                $subject = str_ireplace("##num9##", $num9, $subject);
                $subject = str_ireplace("##domain##", $domains,  $subject);

                

                





                $fmail = str_ireplace("##domain##", $domains, $fmail);
                $fmail = str_ireplace("##userid##", $userId, $fmail);
                $fmail = str_ireplace("##relay##", $getsmtpUsername, $fmail);
                $fmail = str_ireplace("##date##", date('D, F d, Y  g:i A'), $fmail);
                $fmail = str_ireplace("##date2##", date('D, F d, Y'), $fmail);
                $fmail = str_ireplace("##date3##", date('F d, Y  g:i A'), $fmail);
                $fmail = str_ireplace("##date4##", date('F d, Y'), $fmail);
                $fmail = str_ireplace("##date5##", date('F d'), $fmail);
                $fmail = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $fmail);
                $fmail = str_ireplace("##email##", $recipient, $fmail);
                $fmail = str_ireplace("##email64##", $key64, $fmail);
                $fmail = str_ireplace("##char9##", $char9, $fmail);
                $fmail = str_ireplace("##char8##", $char8, $fmail);
                $fmail = str_ireplace("##char7##", $char7, $fmail);
                $fmail = str_ireplace("##char6##", $char6, $fmail);
                $fmail = str_ireplace("##char5##", $char5, $fmail);
                $fmail = str_ireplace("##char4##", $char4, $fmail);
                $fmail = str_ireplace("##char3##", $char3, $fmail);
                $fmail = str_ireplace("##char2##", $char2, $fmail);
                $fmail = str_ireplace("##CHARs2##", $CHARs2, $fmail);
                $fmail = str_ireplace("##num4##", $num4, $fmail);
                $fmail = str_ireplace("##num9##", $num9, $fmail);

                $fname = str_ireplace("##domain##", $domains, $fname);
                $fname = str_ireplace("##userid##", $userId, $fname);
                $fname = str_ireplace("##date##", date('D, F d, Y  g:i A'), $fname);
                $fname = str_ireplace("##date2##", date('D, F d, Y'), $fname);
                $fname = str_ireplace("##date3##", date('F d, Y  g:i A'), $fname);
                $fname = str_ireplace("##date4##", date('F d, Y'), $fname);
                $fname = str_ireplace("##date5##", date('F d'), $fname);
                $fname = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $fname);
                $fname = str_ireplace("##email##", $recipient, $fname);
                $fname = str_ireplace("##email64##", $key64, $fname);
                $fname = str_ireplace("##char9##", $char9, $fname);
                $fname = str_ireplace("##char8##", $char8, $fname);
                $fname = str_ireplace("##char7##", $char7, $fname);
                $fname = str_ireplace("##char6##", $char6, $fname);
                $fname = str_ireplace("##char5##", $char5, $fname);
                $fname = str_ireplace("##char4##", $char4, $fname);
                $fname = str_ireplace("##char3##", $char3, $fname);
                $fname = str_ireplace("##char2##", $char2, $fname);
                $fname = str_ireplace("##CHARs2##", $CHARs2, $fname);
                $fname = str_ireplace("##num4##", $num4, $fname);
                $fname = str_ireplace("##num9##", $num9, $fname);






              
                if (!function_exists('generateRandomEmail')) {
                    function generateRandomEmail()
                    {
                        $characters = 'abcdefghijklmnopqrstuvwxyz';
                        $randomString = '';
                        for ($i = 0; $i < 5; $i++) {
                            $randomString .= $characters[rand(0, strlen($characters) - 1)];
                        }
                        $randomString .= '@';
                        for ($i = 0; $i < 5; $i++) {
                            $randomString .= $characters[rand(0, strlen($characters) - 1)];
                        }
                        $randomString .= '.com';
                        return $randomString;
                    }
                }
                if (!function_exists('generateRandomNumber')) {
                    function generateRandomNumber()
                    {
                        return mt_rand(1000000000, 9999999999); 
                    }
                }            
                $randomEmail = generateRandomEmail();
                
                if (!function_exists('generateIPv4')) {
                    function generateIPv4()
                    {
                        return mt_rand(1, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 255);
                    }

                    
                    function generateIPv6FromIPv4($ipv4)
                    {
                        $ipv4Parts = explode('.', $ipv4);
                        $ipv6Mapped = '::ffff:' . $ipv4Parts[0] . '.' . $ipv4Parts[1] . '.' . $ipv4Parts[2] . '.' . $ipv4Parts[3];
                        return $ipv6Mapped;
                    }
                }


                
                $randomNumber = generateRandomNumber();

              
                
                $numberOfEmailsToSend = 5;

                            if ($domainSettings['DomainName'] === 'Comcast') {
                                
                                  $getsmtpUsername = $smtpConfig['username'];

                               
                                if ($settings['randSender'] == true) {
                                    $domainsmtp = "xfinity.comcast.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "communication@" . $domainsmtp;
                                 		
                                } else {
                                    $mail->Sender = $getsmtpUsername;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if (!function_exists('generateRandomString')) {
                                    function generateRandomString($length = 32)
                                    {
                                        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                        $charactersLength = strlen($characters);
                                        $randomString = '';
                                        for ($i = 0; $i < $length; $i++) {
                                            $randomString .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        return $randomString;
                                    }
                                }
                                    if (!function_exists('generateRandString')) {
                                    function generateRandString($length = 36)
                                    {
                                        $characters = '0123456789abcdef';
                                        $charactersLength = strlen($characters);
                                        $randString = '';
                                        for ($i = 0; $i < $length; $i++) {
                                            $randString .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        return $randString;
                                    }
                                    }
    
                                    
                                    $randString = generateRandString();
    
    
                                    
                                    $randomString = generateRandomString();
                                    
                                     if (!function_exists('generateReceivedHeader')) {
                                      function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                    {
                                        return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                    }
                                     }
                                    if (!function_exists('generateRandomIPs')) {
                                    function generateRandomIPs()
                                    {
                                        return implode('.', array_map(function () {
                                            return mt_rand(0, 255);
                                        }, range(1, 4)));
                                    }
    
                                    }
                                    
                                    $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                    $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 
    
                                    $headers = [];
                                    for ($i = 0; $i <= 8; $i++) {
                                        $randIPs = generateRandomIPs();
                                        $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                    }
                                
                                $fixedHeaders = [
                                    'Content-Type' => 'text/html; charset=utf-8',
                                    'Content-Transfer-Encoding' => 'quoted-printable',
                                    'Message-ID' => "<$randomNumber@example.com>",
                                    'Date' => 'Thu, 07 Dec 2023 02:26:12 GMT',
                                    'Priority' => 'normal',
                                    'Importance' => 'normal',
                                    'X-Priority' => '=?UTF-8?Q?=221_=28Highest=29=22?=',
                                    'X-Msmail-Priority' => '=?UTF-8?Q?=22High=22?=',
                                    'Reply-To' => $fmail,
                                    'X-Originating-Client' =>  'open-xchange-appsuite',
                                    'In-Reply-To' => '<previous-message-id@smtp.comcast.net>',
                                    'References' => '<previous-message-id@smtp.comcast.net>',
                                    'X-Auto-Response-Suppress' => '=?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?=',
                                    'X-Mailer' => 'Open-Xchange Mailer v7.10.6-Rev39',
                                    'Return-Receipt-To' => $randomEmail,
                                    'Disposition-Notification-To' => $randomEmail,
                                    'X-Confirm-Reading-To' => $randomEmail,
                                    'X-Unsubscribe' => $randomEmail,
                                    'List-Unsubscribe' => $randomEmail,
                                    'X-Report-Abuse' => $randomEmail,
                                    'Precedence' => 'bulk',
                                    'X-Bulk' => 'bulk',
                                    'X-Spam-Status' => 'No, score=-2.7',
                                    'X-Spam-Score' => '-2.7',
                                    'X-Spam-Bar' => '/',
                                    'X-Spam-Flag' => 'NO',
                                    'X-Proofpoint-Spam-Details' => 'rule=notspam policy=default score=0 bulkscore=0 malwarescore=0 clxscore=1011 spamscore=0 phishscore=0 mlxlogscore=436 adultscore=0 suspectscore=0 mlxscore=0 classifier=spam adjust=0 reason=mlx scancount=1 engine=8.12.0-2308100000 definitions=main-2312110143',
                                    
                                    'To' => $email
                                ];
                                
                                    foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                    $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';

                                
                                if (isset($customHeaderSettings['customHeaders']) && is_array($customHeaderSettings['customHeaders'])) {
                                    
                                    $customHeaders = $customHeaderSettings['customHeaders'];

                                    
                                    $allHeaders = array_merge($fixedHeaders, $customHeaders);

                                    
                                    foreach ($allHeaders as $header => $value) {
                                        

                                        
                                        $mail->addCustomHeader("$header: $value");
                                    }
                                } else {
                                    
                                    $allHeaders = $fixedHeaders;

                                    
                                    foreach ($allHeaders as $header => $value) {
                                        

                                        
                                        $mail->addCustomHeader("$header: $value");
                                    }
                                }
                               
                            } elseif ($domainSettings['DomainName'] === 'Cox') {

                                  if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.cox.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }
                                  if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'Originating-IP: '. $randIPs . ''; 
                               

                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                            
                            } elseif ($domainSettings['DomainName'] === 'Optimum') {
                                
                                  if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                  
                                    $mail->Sender = $fmail;
                                }
                                
                                if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'Originating-IP: '. $randIPs . ''; 
                               

                                
                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }
                            } elseif ($domainSettings['DomainName'] === 'China') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }

                                $mail->Subject = '=?UTF-8?Q?' . quoted_printable_encode($subject) . '?=';

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                $CHARS9 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 9);
                                $CHARS8 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                $CHARS7 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
                                $CHARS6 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                                $CHARS5 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                                $CHARS4 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
							if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'X-LEEHLK: ' .$CHARS8 .''; 
                                $headers[] = 'X-SLQUPRRCI: '. $CHARS8 .''; 
                                $headers[] = 'X-FPYAI: '. $CHARS6 . ''; 
                                $headers[] = 'X-IHQQBGS: '. $CHARS5 . ''; 
                                $headers[] = 'X-JHKMG: '. $CHARS8 . ''; 
                                $headers[] = 'X-PKNTYB: '. $CHARS9 . ''; 
                                $headers[] = 'X-Accept-Language: en-us, en'; 
                                $headers[] = 'X-Proofpoint-ORIG-GUID: '. $randomString . ''; 
                                $headers[] = 'X-Proofpoint-GUID: '. $randomString . ''; 
                                $headers[] = 'X-Proofpoint-Virus-Version: vendor=fsecure_engine=1.1.170-'.$randString.':6.0.517,18.0.572,17.11.64.514.0000000_definitions=2022-06-21_01:2022-06-21_01,2020-02-14_11,2022-02-23_01_signatures=0'; 
                                $headers[] = 'X-Proofpoint-Spam-Details: rule=notspam policy=default score=0 bulkscore=0 malwarescore=0 clxscore=1011 spamscore=0 phishscore=0 mlxlogscore=436 adultscore=0 suspectscore=0 mlxscore=0 classifier=spam adjust=0 reason=mlx scancount=1 engine=8.12.0-2308100000 definitions=main-2312110143'; 
                               
                                 
                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                              
                            } elseif ($settings['DomainName'] === 'Office') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                  } else {
                                    $mail->Sender = $fmail;
                                }
                                if ($settings['encodeSubject']) {
                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($sender_inbox['subject']) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }
                                $CHARS9 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 9);
                                $CHARS8 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                $CHARS7 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
                                $CHARS6 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                                $CHARS5 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                                $CHARS4 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);

                               	if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Content-Type: text/html; charset=utf-8';
                                $headers[] = 'Content-Transfer-Encoding: quoted-printable';
                                $headers[] = 'Message-ID: <' . uniqid('', true) . '@comcast.net>'; 
                                $headers[] = 'Return-Path: ' . $fmail; 
                                $headers[] = 'List-Unsubscribe: <unsubscribe-link@office.com>';
                                $headers[] = 'List-ID: Your mailing list unique identifier <list.office.com>';
                                $headers[] = 'X-Mailer: =?UTF-8?Q?=22Your_Custom_Mailer=22?='; 
                                $headers[] = 'X-Complaints-To: abuse@office.com';
                                $headers[] = 'X-Postmaster: postmaster@office.com';
                                $headers[] = 'X-Priority: =?UTF-8?Q?=221_=28Highest=29=22?='; 
                                $headers[] = 'Precedence: bulk';
                                $headers[] = 'Auto-Submitted: auto-generated';
                                $headers[] = 'X-Msmail-Priority: =?UTF-8?Q?=22High=22?='; 
                                $headers[] = 'MIME-Version: 1.0';
                                $headers[] = 'Priority: normal';
                                $headers[] = 'Importance: normal';
                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'In-Reply-To: <previous-message-id@smtp.comcast.net>';
                                $headers[] = 'References: <previous-message-id@smtp.comcast.net>';
                                $headers[] = 'Return-Receipt-To: read-receipt@office.com';
                                $headers[] = 'Disposition-Notification-To: read-receipt@office.com';
                                $headers[] = 'X-Unsubscribe: <unsubscribe-link@office.com>';
                                $headers[] = 'X-Confirm-Reading-To: read-receipt@office.com';
                                $headers[] = 'X-Auto-Response-Suppress: =?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?='; 
                                $headers[] = 'X-Report-Abuse: report-abuse@office.com';
                                $headers[] = 'X-Bulk: bulk';
                                $headers[] = 'X-Spam-Status: No, score=-2.7';
                                $headers[] = 'X-Spam-Score: -2.7';
                                $headers[] = 'X-Spam-Bar: /';
                                $headers[] = 'X-Spam-Flag: NO';

                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Your_Custom_Mailer=22?=';

                               
                            } elseif ($settings['DomainName'] === 'Godaddy') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                            	   } else {
                                    $mail->Sender = $fmail;
                                }

                                if ($settings['encodeSubject']) {
                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                $headers = [];
                                $headers[] = 'Date: ' . date('D, d M Y H:i:s O');
                                $headers[] = 'From: ' . $fname . ' <' . $fmail . '>';
                                $headers[] = 'To:' . $fname . '<'. $fname .'>';
                                $headers[] = 'Message-ID: <' . uniqid('', true) . '@connect.xfinity.com>';
                                $headers[] = 'Subject:' . $mail->Subject;
                                $headers[] = 'MIME-Version: 1.0';
                                $headers[] = 'X-Mailer: Open-Xchange Mailer v7.10.6-Rev39';
                                $headers[] = 'X-Originating-Client: open-xchange-appsuite';
                                $headers[] = 'Return-Path: phisin101@comcast.net';


                                foreach ($headers as $header) {
                                    $mail->addCustomHeader($header);
                                }

                                $randomIPv4 = generateIPv4();
                                $randomIPv6 = generateIPv6FromIPv4($randomIPv4);
                                
                                $mail->addCustomHeader('X-Originating-IP: ' . $randomIPv6);
                                $mail->XMailer = 'Open-Xchange Mailer v7.10.6-Rev39';

                              
                            } elseif (empty($domainSettings['DomainName'])) {

                                echo "\033[31m\t\t\nSelect The Domain your sending to\n\n\n";
                                exit;
                            } else {

                                echo "\033[31m\t\t\nInvalid Input\n\n\n";
                                exit;
                            }


                $mail->isHTML(true);
                $mail->Body    = $letter;




                try {
                    if ($mail->send()) {
                        echo "Message sent successfully to $recipient using SMTP: {$smtpConfig['username']}\n";
                        file_put_contents($sentEmailsFile, $recipient . PHP_EOL, FILE_APPEND); 
                        file_put_contents($validEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
                        $authenticationError = false; 
                        
                        return true;
                    } else {
                        $errorMessage = $mail->ErrorInfo;
                        $errorMessage = strtolower($errorMessage);
        
                        if (strpos($errorMessage, 'could not authenticate') !== false && $retryCount > 0) {
                            echo "Retrying sending to $recipient using SMTP: {$smtpConfig['username']} (Retry Count: $retryCount)\n";
                            return sendEmail($recipient, $smtpSettings, $settings, $retryCount - 1, $failedEmailsFile, $badEmailsFile, $sentEmailsFile);
                        } else {
                            
                            $specificErrorPhrases = ['could not connect', 'too many emails'];
                            $isSpecificError = false;
        
                            foreach ($specificErrorPhrases as $phrase) {
                                if (strpos($errorMessage, $phrase) !== false) {
                                    file_put_contents($failedEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
                                    $isSpecificError = true;
                                    break;
                                }
                            }
        
                            if (!$isSpecificError && strpos($errorMessage, 'could not authenticate') === false) {
                                
                                file_put_contents($badEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
                            }
        
                            echo 'Mailer Error: ' . $errorMessage . " to $recipient using SMTP: {$smtpConfig['username']}\n";
                        }
                    }
                } catch (Exception $e) {
                    echo 'Caught exception: ' . $e->getMessage() . "\n";
                }
            }
        
            
            if ($authenticationError) {
                
                file_put_contents($failedEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
                echo "All SMTP servers failed to authenticate for $recipient\n";
                return false;
            }
            
        }
        
        
        
            $threadCount = isset($settings['threads']) && $settings['threads'] > 1 ? $settings['threads'] : 1;
            echo "Thread count: $threadCount\n"; 
        
            $smtpCount = count($smtpSettings); 
            $smtpIndex = 0; 
        
            if ($threadCount > 1) {
                $chunks = array_chunk($recipientList, ceil(count($recipientList) / $threadCount));
        
                $childProcesses = [];
        
                foreach ($chunks as $chunk) {
                    $pid = pcntl_fork();
        
                    if ($pid == -1) {
                        die("Error forking process.");
                    } elseif ($pid) {
                        $childProcesses[] = $pid;
                    } else {
                        foreach ($chunk as $recipient) {
                            $smtpConfig = $smtpSettings[$smtpIndex % $smtpCount];
                            sendEmail($recipient, $smtpSettings, $settings, 2, $failedEmailsFile, $badEmailsFile, $validEmailsFile, $sentEmailsFile);
                            $smtpIndex++;
                        }
                        exit(); 
                    }
                }
        
                
                foreach ($childProcesses as $pid) {
                    pcntl_waitpid($pid, $status);
                }
            } else {
                $smtpIndex = 0;
            
                foreach ($recipientList as $recipient) {
                    $smtpConfig = $smtpSettings[$smtpIndex % $smtpCount];
                    sendEmail($recipient, $smtpSettings, $settings, 2, $failedEmailsFile, $badEmailsFile, $validEmailsFile, $sentEmailsFile);
            
                    $smtpIndex++;
                    if ($smtpIndex >= $smtpCount) {
                        $smtpIndex = 0;
                        shuffle($smtpSettings);
                    }
                }
            }
        
            $loop->run();
            
        } else {






            
            $settings = array_merge(reset($smtpSettings), $recipientListSettings, $customHeaderSettings, $commonSettings, $domainSettings);

            
            $numThreads = empty($settings['threads']) ? 1 : intval($settings['threads']);

            
            $recipientListFile = 'list/' . $settings['recipientListFile'];  
            $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            
            if ($settings['removeDuplicates']) {
                $recipientList = array_unique($recipientList);
            }

            
            if (empty($recipientList)) {
                throw new Exception('You must provide at least one recipient email address.');
            }

            $numEmails = count($recipientList);

            
            echo "\n\033[1;33mNumber of emails: $numEmails";
            echo "\n\033[1;33mNumber of smtp: 1\n";
            echo "\n\033[1;33mProceeding to send emails...\n";
            
            $chunks = array_chunk($recipientList, max(1, ceil(count($recipientList) / $numThreads)));

            
            $pids = [];
            for ($i = 0; $i < $numThreads; $i++) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    die("Could not fork.\n");
                } elseif ($pid) {
                    
                    $pids[] = $pid;
                } else {
                    
                    if (isset($chunks[$i]) && is_array($chunks[$i])) {
                        $start = $i * count($chunks[$i]);
                        $end = min(($i + 1) * count($chunks[$i]), count($recipientList));

                        
                        $mail = new PHPMailer(true);


                        function generateRandomIP()
                        {

                            return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
                        }

                        $maxConsecutiveFailures = $settings['ErrorHandling'];

                        
                        $consecutiveFailures = 0;



                        
                        foreach ($chunks[$i] as $email) {
                            $mail->addAddress($email);

                            
                            $mail->isSMTP();
                            $mail->Host       = $settings['host'];
                            $mail->Port       = $settings['port'];
                            $mail->Username   = $settings['username'];
                            $mail->Password   = $settings['password'];
                            $mail->SMTPSecure = $settings['Auth'];
                            $mail->Hostname   = $settings['Hostname'];
                            $mail->SMTPAuth   = true;
                            $mail->SMTPKeepAlive = true;
                            $mail->Priority   = $settings['priority'];
                            $mail->Encoding = $settings['encoding'];
                            $mail->CharSet = $settings['charset'];


                            $senderEmail = isset($settings['from']) ? $settings['from'] : '';
                            if (!$senderEmail) {
                                throw new Exception('Invalid sender email address.');
                            }



                            $edomainn = explode('@', $email);
                            $userId = $edomainn[0];
                            $domains = $edomainn[1];


                            $fmail = $settings['from'];
                            $fname = $settings['fromname'];
                            $subject = $settings['subject'];


                            $getsmtpUsername = $smtpSettings[0]['username'];

                            



                            if (!empty($settings['image_attachfile'])) {
                                $imageAttachmentPath = 'attachment/' . $settings['image_attachfile']; 
                                if ($settings['displayimage'] == true) {
                                    $mail->addEmbeddedImage($imageAttachmentPath, 'imgslet', $settings['image_attachname'], 'base64', 'image/jpeg, image/jpg, image/png');
                                } else {
                                    $mail->addAttachment($imageAttachmentPath, $settings['image_attachfile']);
                                }
                            }




                            if (!empty($settings['pdf_attachfile'])) {
                                $mail->addAttachment($settings['pdf_attachfile']);
                            }
                            $link = explode('|', $commonSettings['link']);
                            $b64link = base64_encode($commonSettings['linkb64']);



                            if ($commonSettings['autolink'] == true) {
                                $qrCode = new QrCode($commonSettings['qrlink'] . '?e=' . $email);
                                $qrCode->setLabel($commonSettings['qrlabel']);
                            } else {

                                $qrCode = new QrCode($commonSettings['qrlink']);
                                $qrCode->setLabel($commonSettings['qrlabel']);
                            }

                            $qrCode->setSize(160); 

                            
                            $qrCodeBase64 = base64_encode($qrCode->writeString());
                            $label = '<div style="text-align:center;font-size:16px;font-weight:bold;">Scan Me</div>';
                            $qrCodeImage = '<img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="Scan Me QR Code" style="display:block;margin:0 auto;">';

                            if ($domainSettings['DomainName'] == "China") {
                          
                           if (!function_exists('generateReceivedHeader')) {
                            function encryptEmailForURL($email, $key) {
                                $cipher = 'AES-256-CBC';
                                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
                                $encrypted = openssl_encrypt($email, $cipher, $key, 0, $iv);
                                $encoded = urlencode(base64_encode($encrypted . '::' . $iv));
                                return $encoded;
                            }
                            
                            
                            $encryptionKey = $commonSettings['EncryptKeyEmlAdd']; 
                            

                            
                            $encryptedRecipientEmail = encryptEmailForURL($email, $encryptionKey);
                            
                            
                            }     
                            }
                         
                            $encodedURL = htmlspecialchars($commonSettings['link'], ENT_QUOTES, 'UTF-8');
                           


                            $imageBase64 = ''; 

                            if (!empty($commonSettings['imageLetter'])) {
                                $imagePath = 'attachment/' . $commonSettings['imageLetter'];

                                if (file_exists($imagePath)) {
                                    $imageBase64 = base64_encode(file_get_contents($imagePath));
                                } else {
                                    
                                    echo "The image file doesn't exist at $imagePath";
                                }
                            }

                            
                            $dataUri = !empty($imageBase64) ? 'data:image/png;base64,' . $imageBase64 : '';

                            $currentDate = date('Y-m-d');



                            $char9 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 9);
                            $char8 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
                            $char7 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 7);
                            $char6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 6);
                            $char5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 5);
                            $char4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 4);
                            $char3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 3);
                            $char2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 2);
                            $CHARs2 = substr(str_shuffle(strtoupper("ABCDEFGHIJKLMNOPQRSTUVWXYZ")), 0, 2);
                            $num9 = substr(str_shuffle("0123456789"), 0, 9);
                            $num4 = substr(str_shuffle("0123456789"), 0, 4);
                            $key64 = base64_encode($email);


                            $letterFile = 'letter/' . $settings['letterFile']; 
                            $letter = file_get_contents($letterFile) or die("Letter not found!");
                            $letter = str_ireplace("##char8##", $char8, $letter);
                            $letter = str_ireplace("##char7##", $char7, $letter);
                            $letter = str_ireplace("##char6##", $char6, $letter);
                            $letter = str_ireplace("##char5##", $char5, $letter);
                            $letter = str_ireplace("##char4##", $char4, $letter);
                            $letter = str_ireplace("##char3##", $char3, $letter);

                            

                            

                            if ($commonSettings['randomparam'] == true) {
                                $letter = str_ireplace("##link##", $link[array_rand($link)] . '?id=' . $char4, $letter);
                                $letter = str_ireplace("##char8##", $char8, $letter);
                                $letter = str_ireplace("##char7##", $char7, $letter);
                                $letter = str_ireplace("##char6##", $char6, $letter);
                                $letter = str_ireplace("##char5##", $char5, $letter);
                                $letter = str_ireplace("##char4##", $char4, $letter);
                                $letter = str_ireplace("##char3##", $char3, $letter);
                            } else {
                                $letter = str_ireplace("##link##", $link[array_rand($link)], $letter);
                                $letter = str_ireplace("##char8##", $char8, $letter);
                                $letter = str_ireplace("##char7##", $char7, $letter);
                                $letter = str_ireplace("##char6##", $char6, $letter);
                                $letter = str_ireplace("##char5##", $char5, $letter);
                                $letter = str_ireplace("##char4##", $char4, $letter);
                                $letter = str_ireplace("##char3##", $char3, $letter);
                            }
                            $letter = str_ireplace("##date##", date('D, F d, Y  g:i A'), $letter);
                            $letter = str_ireplace("##date2##", date('D, F d, Y'), $letter);
                            $letter = str_ireplace("##date3##", date('F d, Y  g:i A'), $letter);
                            $letter = str_ireplace("##date4##", date('F d, Y'), $letter);
                            $letter = str_ireplace("##date5##", date('F d'), $letter);
                            $letter = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $letter);
                            $letter = str_ireplace("##email##", $email, $letter);
                            $letter = str_ireplace("##email64##", $key64, $letter);
                             if ($domainSettings['DomainName'] == "China") {
                            $letter = str_ireplace("##emailEncrypt##", $encryptedRecipientEmail, $letter);
                             }
                            $letter = str_ireplace("##link64##", $b64link, $letter);
                            $letter = str_ireplace("##char9##", $char9, $letter);
                            $letter = str_ireplace("##char8##", $char8, $letter);
                            $letter = str_ireplace("##char7##", $char7, $letter);
                            $letter = str_ireplace("##char6##", $char6, $letter);
                            $letter = str_ireplace("##char5##", $char5, $letter);
                            $letter = str_ireplace("##char4##", $char4, $letter);
                            $letter = str_ireplace("##date6##", $currentDate, $letter);
                            $letter = str_ireplace("##char3##", $char3, $letter);
                            $letter = str_ireplace("##char2##", $char2, $letter);
                            $letter = str_ireplace("##CHARs2##", $CHARs2, $letter);
                            $letter = str_ireplace("##num4##", $num4, $letter);
                            $letter = str_ireplace("##userid##", $userId, $letter);
                            $letter = str_ireplace("##domain##", $domains,  $letter);
                            $letter = str_ireplace("##imglet##", $dataUri, $letter);
                            $letter = str_ireplace("##qrcode##", '<div style="text-align: center;"><img src="data:image/png;base64,' . $qrCodeBase64 . '" ></div>', $letter);
                            $letter = str_ireplace("##URLqrcode##", '<div style="text-align: center;"><a href="' . $link[array_rand($link)] . '" target="_blank"><img src="data:image/png;base64,' . $qrCodeBase64 . '"></a></div>', $letter);




                            

                            $subject = str_ireplace("##date##", date('D, F d, Y  g:i A'), $subject);
                            $subject = str_ireplace("##date2##", date('D, F d, Y'), $subject);
                            $subject = str_ireplace("##date3##", date('F d, Y  g:i A'), $subject);
                            $subject = str_ireplace("##date4##", date('F d, Y'), $subject);
                            $subject = str_ireplace("##date5##", date('F d'), $subject);
                            $subject = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $subject);
                            $subject = str_ireplace("##email##", $email, $subject);
                            $subject = str_ireplace("##email64##", $key64, $subject);
                            $subject = str_ireplace("##link64##", $b64link, $subject);
                            $subject = str_ireplace("##char9##", $char9, $subject);
                            $subject = str_ireplace("##char8##", $char8, $subject);
                            $subject = str_ireplace("##char7##", $char7, $subject);
                            $subject = str_ireplace("##char6##", $char6, $subject);
                            $subject = str_ireplace("##char5##", $char5, $subject);
                            $subject = str_ireplace("##char4##", $char4, $subject);
                            $subject = str_ireplace("##char3##", $char3, $subject);
                            $subject = str_ireplace("##char2##", $char2, $subject);
                            $subject = str_ireplace("##userid##", $userId, $subject);
                            $subject = str_ireplace("##CHARs2##", $CHARs2, $subject);
                            $subject = str_ireplace("##num4##", $num4, $subject);
                            $subject = str_ireplace("##num9##", $num9, $subject);
                            $subject = str_ireplace("##domain##", $domains,  $subject);

                            

                            





                            $fmail = str_ireplace("##domain##", $domains, $fmail);
                            $fmail = str_ireplace("##userid##", $userId, $fmail);
                            $fmail = str_ireplace("##relay##", $getsmtpUsername, $fmail);
                            $fmail = str_ireplace("##date##", date('D, F d, Y  g:i A'), $fmail);
                            $fmail = str_ireplace("##date2##", date('D, F d, Y'), $fmail);
                            $fmail = str_ireplace("##date3##", date('F d, Y  g:i A'), $fmail);
                            $fmail = str_ireplace("##date4##", date('F d, Y'), $fmail);
                            $fmail = str_ireplace("##date5##", date('F d'), $fmail);
                            $fmail = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $fmail);
                            $fmail = str_ireplace("##email##", $email, $fmail);
                            $fmail = str_ireplace("##email64##", $key64, $fmail);
                            $fmail = str_ireplace("##char9##", $char9, $fmail);
                            $fmail = str_ireplace("##char8##", $char8, $fmail);
                            $fmail = str_ireplace("##char7##", $char7, $fmail);
                            $fmail = str_ireplace("##char6##", $char6, $fmail);
                            $fmail = str_ireplace("##char5##", $char5, $fmail);
                            $fmail = str_ireplace("##char4##", $char4, $fmail);
                            $fmail = str_ireplace("##char3##", $char3, $fmail);
                            $fmail = str_ireplace("##char2##", $char2, $fmail);
                            $fmail = str_ireplace("##CHARs2##", $CHARs2, $fmail);
                            $fmail = str_ireplace("##num4##", $num4, $fmail);
                            $fmail = str_ireplace("##num9##", $num9, $fmail);

                            $fname = str_ireplace("##domain##", $domains, $fname);
                            $fname = str_ireplace("##userid##", $userId, $fname);
                            $fname = str_ireplace("##date##", date('D, F d, Y  g:i A'), $fname);
                            $fname = str_ireplace("##date2##", date('D, F d, Y'), $fname);
                            $fname = str_ireplace("##date3##", date('F d, Y  g:i A'), $fname);
                            $fname = str_ireplace("##date4##", date('F d, Y'), $fname);
                            $fname = str_ireplace("##date5##", date('F d'), $fname);
                            $fname = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $fname);
                            $fname = str_ireplace("##email##", $email, $fname);
                            $fname = str_ireplace("##email64##", $key64, $fname);
                            $fname = str_ireplace("##char9##", $char9, $fname);
                            $fname = str_ireplace("##char8##", $char8, $fname);
                            $fname = str_ireplace("##char7##", $char7, $fname);
                            $fname = str_ireplace("##char6##", $char6, $fname);
                            $fname = str_ireplace("##char5##", $char5, $fname);
                            $fname = str_ireplace("##char4##", $char4, $fname);
                            $fname = str_ireplace("##char3##", $char3, $fname);
                            $fname = str_ireplace("##char2##", $char2, $fname);
                            $fname = str_ireplace("##CHARs2##", $CHARs2, $fname);
                            $fname = str_ireplace("##num4##", $num4, $fname);
                            $fname = str_ireplace("##num9##", $num9, $fname);






                            if (!function_exists('generateRandomEmail')) {
                                function generateRandomEmail()
                                {
                                    $characters = 'abcdefghijklmnopqrstuvwxyz';
                                    $randomString = '';
                                    for ($i = 0; $i < 5; $i++) {
                                        $randomString .= $characters[rand(0, strlen($characters) - 1)];
                                    }
                                    $randomString .= '@';
                                    for ($i = 0; $i < 5; $i++) {
                                        $randomString .= $characters[rand(0, strlen($characters) - 1)];
                                    }
                                    $randomString .= '.com';
                                    return $randomString;
                                }
                            }
                            if (!function_exists('generateRandomNumber')) {
                                function generateRandomNumber()
                                {
                                    return mt_rand(1000000000, 9999999999); 
                                }
                            }            
                            $randomEmail = generateRandomEmail();
                            
                            if (!function_exists('generateIPv4')) {
                                function generateIPv4()
                                {
                                    return mt_rand(1, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 255);
                                }

                                
                                function generateIPv6FromIPv4($ipv4)
                                {
                                    $ipv4Parts = explode('.', $ipv4);
                                    $ipv6Mapped = '::ffff:' . $ipv4Parts[0] . '.' . $ipv4Parts[1] . '.' . $ipv4Parts[2] . '.' . $ipv4Parts[3];
                                    return $ipv6Mapped;
                                }
                            }


                            
                            $randomNumber = generateRandomNumber();

                            $randomIP = generateRandomIP();
                            $numberOfEmailsToSend = 5;

                            if ($domainSettings['DomainName'] === 'Comcast') {
                                
                                  $getsmtpUsername = $smtpSettings[0]['username'];

                              
                                if ($settings['randSender'] == true) {
                                    $domainsmtp = "xfinity.comcast.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "communication@" . $domainsmtp;
                                 		
                                } else {
                                    $mail->Sender = $getsmtpUsername;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if (!function_exists('generateRandomString')) {
                                    function generateRandomString($length = 32)
                                    {
                                        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                        $charactersLength = strlen($characters);
                                        $randomString = '';
                                        for ($i = 0; $i < $length; $i++) {
                                            $randomString .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        return $randomString;
                                    }
                                }
                                    if (!function_exists('generateRandString')) {
                                    function generateRandString($length = 36)
                                    {
                                        $characters = '0123456789abcdef';
                                        $charactersLength = strlen($characters);
                                        $randString = '';
                                        for ($i = 0; $i < $length; $i++) {
                                            $randString .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        return $randString;
                                    }
                                    }
    
                                    
                                    $randString = generateRandString();
    
    
                                    
                                    $randomString = generateRandomString();
                                    
                                     if (!function_exists('generateReceivedHeader')) {
                                      function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                    {
                                        return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                    }
                                     }
                                    if (!function_exists('generateRandomIPs')) {
                                    function generateRandomIPs()
                                    {
                                        return implode('.', array_map(function () {
                                            return mt_rand(0, 255);
                                        }, range(1, 4)));
                                    }
    
                                    }
                                    
                                    $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                    $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 
    
                                    $headers = [];
                                    for ($i = 0; $i <= 8; $i++) {
                                        $randIPs = generateRandomIPs();
                                        $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                    }
                                
                                $fixedHeaders = [
                                    'Content-Type' => 'text/html; charset=utf-8',
                                    'Content-Transfer-Encoding' => 'quoted-printable',
                                    'Message-ID' => "<$randomNumber@example.com>",
                                    'Date' => 'Thu, 07 Dec 2023 02:26:12 GMT',
                                    'Priority' => 'normal',
                                    'Importance' => 'normal',
                                    'X-Priority' => '=?UTF-8?Q?=221_=28Highest=29=22?=',
                                    'X-Msmail-Priority' => '=?UTF-8?Q?=22High=22?=',
                                    'Reply-To' => $fmail,
                                    'X-Originating-Client' =>  'open-xchange-appsuite',
                                    'In-Reply-To' => '<previous-message-id@smtp.comcast.net>',
                                    'References' => '<previous-message-id@smtp.comcast.net>',
                                    'X-Auto-Response-Suppress' => '=?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?=',
                                    'X-Mailer' => 'Open-Xchange Mailer v7.10.6-Rev39',
                                    'Return-Receipt-To' => $randomEmail,
                                    'Disposition-Notification-To' => $randomEmail,
                                    'X-Confirm-Reading-To' => $randomEmail,
                                    'X-Unsubscribe' => $randomEmail,
                                    'List-Unsubscribe' => $randomEmail,
                                    'X-Report-Abuse' => $randomEmail,
                                    'Precedence' => 'bulk',
                                    'X-Bulk' => 'bulk',
                                    'X-Spam-Status' => 'No, score=-2.7',
                                    'X-Spam-Score' => '-2.7',
                                    'X-Spam-Bar' => '/',
                                    'X-Spam-Flag' => 'NO',
                                    'X-Proofpoint-Spam-Details' => 'rule=notspam policy=default score=0 bulkscore=0 malwarescore=0 clxscore=1011 spamscore=0 phishscore=0 mlxlogscore=436 adultscore=0 suspectscore=0 mlxscore=0 classifier=spam adjust=0 reason=mlx scancount=1 engine=8.12.0-2308100000 definitions=main-2312110143',
                                    
                                    'To' => $email
                                ];
                                
                                    foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                    $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';

                                
                                if (isset($customHeaderSettings['customHeaders']) && is_array($customHeaderSettings['customHeaders'])) {
                                    
                                    $customHeaders = $customHeaderSettings['customHeaders'];

                                    
                                    $allHeaders = array_merge($fixedHeaders, $customHeaders);

                                    
                                    foreach ($allHeaders as $header => $value) {
                                        

                                        
                                        $mail->addCustomHeader("$header: $value");
                                    }
                                } else {
                                    
                                    $allHeaders = $fixedHeaders;

                                    
                                    foreach ($allHeaders as $header => $value) {
                                        

                                        
                                        $mail->addCustomHeader("$header: $value");
                                    }
                                }
                               
                            } elseif ($domainSettings['DomainName'] === 'Cox') {

                                  if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.cox.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }
                                  if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'Originating-IP: '. $randIPs . ''; 
                               

                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                            
                            } elseif ($domainSettings['DomainName'] === 'Optimum') {
                                
                                  if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                  
                                    $mail->Sender = $fmail;
                                }
                                
                                if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'Originating-IP: '. $randIPs . ''; 
                               

                                
                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }
                            } elseif ($domainSettings['DomainName'] === 'China') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }

                                $mail->Subject = '=?UTF-8?Q?' . quoted_printable_encode($subject) . '?=';

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                $CHARS9 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 9);
                                $CHARS8 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                $CHARS7 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
                                $CHARS6 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                                $CHARS5 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                                $CHARS4 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
							if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'X-LEEHLK: ' .$CHARS8 .''; 
                                $headers[] = 'X-SLQUPRRCI: '. $CHARS8 .''; 
                                $headers[] = 'X-FPYAI: '. $CHARS6 . ''; 
                                $headers[] = 'X-IHQQBGS: '. $CHARS5 . ''; 
                                $headers[] = 'X-JHKMG: '. $CHARS8 . ''; 
                                $headers[] = 'X-PKNTYB: '. $CHARS9 . ''; 
                                $headers[] = 'X-Accept-Language: en-us, en'; 
                                $headers[] = 'X-Proofpoint-ORIG-GUID: '. $randomString . ''; 
                                $headers[] = 'X-Proofpoint-GUID: '. $randomString . ''; 
                                $headers[] = 'X-Proofpoint-Virus-Version: vendor=fsecure_engine=1.1.170-'.$randString.':6.0.517,18.0.572,17.11.64.514.0000000_definitions=2022-06-21_01:2022-06-21_01,2020-02-14_11,2022-02-23_01_signatures=0'; 
                                $headers[] = 'X-Proofpoint-Spam-Details: rule=notspam policy=default score=0 bulkscore=0 malwarescore=0 clxscore=1011 spamscore=0 phishscore=0 mlxlogscore=436 adultscore=0 suspectscore=0 mlxscore=0 classifier=spam adjust=0 reason=mlx scancount=1 engine=8.12.0-2308100000 definitions=main-2312110143'; 
                               
                                 
                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                              
                            } elseif ($settings['DomainName'] === 'Office') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                  } else {
                                    $mail->Sender = $fmail;
                                }
                                if ($settings['encodeSubject']) {
                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($sender_inbox['subject']) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }
                                $CHARS9 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 9);
                                $CHARS8 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                $CHARS7 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
                                $CHARS6 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                                $CHARS5 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                                $CHARS4 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);

                               	if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }

                                $headers[] = 'Content-Type: text/html; charset=utf-8';
                                $headers[] = 'Content-Transfer-Encoding: quoted-printable';
                                $headers[] = 'Message-ID: <' . uniqid('', true) . '@comcast.net>'; 
                                $headers[] = 'Return-Path: ' . $fmail; 
                                $headers[] = 'List-Unsubscribe: <unsubscribe-link@office.com>';
                                $headers[] = 'List-ID: Your mailing list unique identifier <list.office.com>';
                                $headers[] = 'X-Mailer: =?UTF-8?Q?=22Your_Custom_Mailer=22?='; 
                                $headers[] = 'X-Complaints-To: abuse@office.com';
                                $headers[] = 'X-Postmaster: postmaster@office.com';
                                $headers[] = 'X-Priority: =?UTF-8?Q?=221_=28Highest=29=22?='; 
                                $headers[] = 'Precedence: bulk';
                                $headers[] = 'Auto-Submitted: auto-generated';
                                $headers[] = 'X-Msmail-Priority: =?UTF-8?Q?=22High=22?='; 
                                $headers[] = 'MIME-Version: 1.0';
                                $headers[] = 'Priority: normal';
                                $headers[] = 'Importance: normal';
                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'In-Reply-To: <previous-message-id@smtp.comcast.net>';
                                $headers[] = 'References: <previous-message-id@smtp.comcast.net>';
                                $headers[] = 'Return-Receipt-To: read-receipt@office.com';
                                $headers[] = 'Disposition-Notification-To: read-receipt@office.com';
                                $headers[] = 'X-Unsubscribe: <unsubscribe-link@office.com>';
                                $headers[] = 'X-Confirm-Reading-To: read-receipt@office.com';
                                $headers[] = 'X-Auto-Response-Suppress: =?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?='; 
                                $headers[] = 'X-Report-Abuse: report-abuse@office.com';
                                $headers[] = 'X-Bulk: bulk';
                                $headers[] = 'X-Spam-Status: No, score=-2.7';
                                $headers[] = 'X-Spam-Score: -2.7';
                                $headers[] = 'X-Spam-Bar: /';
                                $headers[] = 'X-Spam-Flag: NO';

                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Your_Custom_Mailer=22?=';

                               
                            } elseif ($settings['DomainName'] === 'Godaddy') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                    		   } else {
                                    $mail->Sender = $fmail;
                                }

                                if ($settings['encodeSubject']) {
                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                $headers = [];
                                $headers[] = 'Date: ' . date('D, d M Y H:i:s O');
                                $headers[] = 'From: ' . $fname . ' <' . $fmail . '>';
                                $headers[] = 'To:' . $fname . '<'. $fname .'>';
                                $headers[] = 'Message-ID: <' . uniqid('', true) . '@connect.xfinity.com>';
                                $headers[] = 'Subject:' . $mail->Subject;
                                $headers[] = 'MIME-Version: 1.0';
                                $headers[] = 'X-Mailer: Open-Xchange Mailer v7.10.6-Rev39';
                                $headers[] = 'X-Originating-Client: open-xchange-appsuite';
                                $headers[] = 'Return-Path: phisin101@comcast.net';


                                foreach ($headers as $header) {
                                    $mail->addCustomHeader($header);
                                }

                                $randomIPv4 = generateIPv4();
                                $randomIPv6 = generateIPv6FromIPv4($randomIPv4);
                                
                                $mail->addCustomHeader('X-Originating-IP: ' . $randomIPv6);
                                $mail->XMailer = 'Open-Xchange Mailer v7.10.6-Rev39';

                              
                            } elseif (empty($domainSettings['DomainName'])) {

                                echo "\033[31m\t\t\nSelect The Domain your sending to\n\n\n";
                                exit;
                            } else {

                                echo "\033[31m\t\t\nInvalid Input\n\n\n";
                                exit;
                            }




                            $mail->isHTML(true);

                            $mail->Body = $letter; 


                            if (!function_exists('handleFailure')) {
                                function handleFailure($errorMessage, $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile)
                                {
                                    
                                    if (strpos($errorMessage, 'Could not connect to SMTP host') !== false) {
                                        
                                        $consecutiveFailures++;
                                    }

                                    $errorMessage = strtolower($errorMessage);

                                    
                                    $specificErrorPhrases = ['could not connect', 'could not authenticate', 'too many emails'];

                                    $isSpecificError = false;

                                    foreach ($specificErrorPhrases as $phrase) {
                                        if (strpos($errorMessage, strtolower($phrase)) !== false) {
                                            $isSpecificError = true;
                                            break;
                                        }
                                    }

                                    
                                    if ($isSpecificError) {
                                        file_put_contents($failedEmailsFile, $email . PHP_EOL, FILE_APPEND);
                                    } else {
                                        file_put_contents($badlistFile, $email . PHP_EOL, FILE_APPEND);
                                    }

                                    
                                    echo "\033[0;31mFailed to send email to:\033[0m \033[0;31m$email Error: $errorMessage\033[0m\n";

                                    
                                    if ($consecutiveFailures >= $maxConsecutiveFailures) {
                                        echo "\033[0;37mToo many consecutive failures. Stopping further email sends.\033[0m\n";


                                        exit;
                                    }
                                }
                            }

                            

                            try {
                                if ($mail->send()) {
                                    
                                    $consecutiveFailures = 0;

                                    
                                    echo "\n\033[0;33mEmail sent successfully to:\033[0m \033[0;32m$email\033[0m\n";
                                    file_put_contents($sentEmailsFile, $email . PHP_EOL, FILE_APPEND);
                                    if (!file_exists($validEmailsFile) || !strpos(file_get_contents($validEmailsFile), $email)) {
                                        file_put_contents($validEmailsFile, $email . PHP_EOL, FILE_APPEND);
                                    }
                                } else {
                                    handleFailure($mail->ErrorInfo, $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile);
                                }
                            } catch (Exception $e) {
                                
                                $consecutiveFailures++;

                                handleFailure($e->getMessage(), $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile);
                            }



                            if (!empty($settings['sleepDuration']) && is_numeric($settings['sleepDuration'])) {
                                
                                $sleeptimer = $settings['sleepDuration'];
                                echo "\nSleep For: \033[0;32m$sleeptimer seconds\033[0m\n";
                                
                                sleep(intval($settings['sleepDuration']));

                                

                            }


                            
                            $mail->clearAddresses();
                            $mail->clearCustomHeaders();
                        }
                    } else {
                    }

                    
                    exit();
                }
            }

            
            foreach ($pids as $pid) {
                pcntl_waitpid($pid, $status);
            }





            $recipientListFile = 'list/' . $settings['recipientListFile'];
            $failedEmailsFile = 'failed.txt';
            $sentEmailsFile = 'pass.txt';
            $badlistFile = 'bad.txt';

            function filterRecipientList($recipientListFile, $sentEmailsFile, $badlistFile)
            {
                $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $sentEmails = [];
                $badlist = [];

                if (file_exists($sentEmailsFile)) {
                    $sentEmails = file($sentEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                }

                if (file_exists($badlistFile)) {
                    $badlist = file($badlistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                }

                $combinedEmails = array_merge($sentEmails ?: [], $badlist ?: []);
                $combinedEmails = array_unique($combinedEmails); 

                $RecipientListForFilter = array_diff($recipientList, $combinedEmails);

                
                file_put_contents("Resend.txt", implode(PHP_EOL, $RecipientListForFilter) . PHP_EOL);
            }

            
            filterRecipientList($recipientListFile, $sentEmailsFile, $badlistFile);


            
            function countLines($file)
            {
                if (file_exists($file)) {
                    $lines = file($file);
                    if ($lines === false) {
                        return 0; 
                    } else {
                        return count($lines);
                    }
                } else {
                    return 0; 
                }
            }

            $failedEmailsFile = 'failed.txt';
            $sentEmailsFile = 'pass.txt';
            $badlistFile = 'bad.txt';
            $recipientListFile = 'list/' . $settings['recipientListFile']; 

            $failedEmailsCount = countLines($failedEmailsFile);
            $sentEmailsCount = countLines($sentEmailsFile);
            $badEmailsCount = countLines($badlistFile);
            $recipientListFileCount = countLines($recipientListFile);

            
            $totalProcessedEmails = $failedEmailsCount + $sentEmailsCount + $badEmailsCount;

            echo "Original Email Count: $recipientListFileCount\n";
            echo "Total Processed Email Count: $totalProcessedEmails\n";


            if ($totalProcessedEmails === $recipientListFileCount) {
                if (file_exists($sentEmailsFile)) {
                    $sentEmails = array_unique(file($sentEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No email was sent\n";
                }

                if (file_exists($badlistFile)) {
                    $badEmails = array_unique(file($badlistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No Bad emails\n";
                }
                if (file_exists($failedEmailsFile)) {
                    $failedEmails = array_unique(file($failedEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No Failed emails\n";
                }

                if (empty($failedEmails) && !empty($sentEmails)) {
                    $sentCount = count($sentEmails);
                    echo "\n\033[0;32mAll Emails Sent Successfully. Sent Count: $sentCount\033[0m\n";
                } elseif (!empty($failedEmails)) {
                    $failedCount = count($failedEmails);
                    echo "\n\033[0;33mNot all emails were sent. Resend emails in Resend.txt ONLY. Failed Count: $failedCount\033[0m\n";
                } elseif (empty($sentEmails)) {
                    echo "\n\033[0;33mNo emails were sent.\033[0m\n";
                }
            } else {
                $unsentCount = $recipientListFileCount - $sentEmailsCount - $badEmailsCount;
                echo "\n\033[0;33mNot all emails have been sent due to errors.Resend emails in Resend.txt ONLY. Unsent Count: $unsentCount\033[0m\n";
            }
        }
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
} elseif ($userStatus === 'expired') {
    echo "\033[0;31m\nUser account has expired. Contact S1L3NT_T0RTUG3R\n";
} elseif ($userStatus === 'ip_not_allowed') {
    echo "\033[0;31m\nIP address not allowed. Access denied. Contact S1L3NT_T0RTUG3R\n";
    
} elseif ($userStatus === 'admin' && $username === '1') {
    
        
         
    echo "\033[1;33m\n\n\t\tWelcome Admin.\n\n";



    
    $mail = new PHPMailer(true);

    try {

        $resends = 'Resend.txt';
        $failedEmailsFile = 'failed.txt';
        $badlistFile = 'bad.txt';
        $sentEmailsFile = 'pass.txt';
        $validEmailsFile = 'valid.txt';

        function emptyFileIfExists($file)
        {
            if (file_exists($file)) {
                file_put_contents($file, ''); 
                echo "File '$file' emptied successfully.\n";
            } else {
            }
        }
       

        
        emptyFileIfExists($resends);
        emptyFileIfExists($failedEmailsFile);
        emptyFileIfExists($badlistFile);
        emptyFileIfExists($sentEmailsFile);





        if (count($smtpSettings) > 1) {


            echo "\033[1;33mProceeding to send emails...\n";

            $loop = React\EventLoop\Factory::create();

            $settings = array_merge($recipientListSettings, $commonSettings, $customHeaderSettings, $domainSettings);
            $recipientListFile = 'list/' . $settings['recipientListFile'];  

            $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if ($settings['removeDuplicates']) {
                $recipientList = array_unique($recipientList);
            }

            if (empty($recipientList)) {
                throw new Exception('You must provide at least one recipient email address.');
            }
            
        $resends = 'Resend.txt';
        $failedEmailsFile = 'failed.txt';
        $badEmailsFile = 'bad.txt';
        $sentEmailsFile = 'pass.txt';
        $validEmailsFile = 'valid.txt';
        
         $numEmails = count($recipientList);

            
        echo "\n\033[1;33mNumber of emails: $numEmails\n";

            function sendEmail($recipient, $smtpSettings, $settings, $retryCount = 2, $failedEmailsFile, $badEmailsFile, $validEmailsFile, $sentEmailsFile)
{
    $mail = new PHPMailer(true);
    $authenticationError = true; 
    $emailsSent = 0;

    foreach ($smtpSettings as $smtpConfig) {

                $mail->isSMTP();
                $mail->Host       = $smtpConfig['host'];
                $mail->Port       = $smtpConfig['port'];
                $mail->Username   = $smtpConfig['username'];
                $mail->Password   = $smtpConfig['password'];
                $mail->SMTPSecure = $smtpConfig['Auth'];
                $mail->Hostname   = $smtpConfig['Hostname'];
                $mail->SMTPAuth   = true;
                $mail->SMTPKeepAlive = true;
                $mail->Priority   = $settings['priority'];
                $mail->Encoding = $settings['encoding'];
                $mail->CharSet = $settings['charset'];
                $mail->addAddress(trim($recipient));



                $senderEmail = isset($settings['from']) ? $settings['from'] : '';
                if (!$senderEmail) {
                    throw new Exception('Invalid sender email address.');
                }


				 
                $edomainn = explode('@', $recipient);
                $userId = $edomainn[0];
                $domains = $edomainn[1];


                $fmail = $settings['from'];
                $fname = $settings['fromname'];
                $subject = $settings['subject'];


                $getsmtpUsername = $smtpConfig['username'];

                



                if (!empty($settings['image_attachfile'])) {
                    $imageAttachmentPath = 'attachment/' . $settings['image_attachfile']; 
                    if ($settings['displayimage'] == true) {
                        $mail->addEmbeddedImage($imageAttachmentPath, 'imgslet', $settings['image_attachname'], 'base64', 'image/jpeg, image/jpg, image/png');
                    } else {
                        $mail->addAttachment($imageAttachmentPath, $settings['image_attachfile']);
                    }
                }




                if (!empty($settings['pdf_attachfile'])) {
                    $mail->addAttachment($settings['pdf_attachfile']);
                }
                $link = explode('|', $settings['link']);
                $b64link = base64_encode($settings['linkb64']);



                if ($settings['autolink'] == true) {
                    $qrCode = new QrCode($settings['qrlink'] . '?e=' . $recipient);
                    $qrCode->setLabel($settings['qrlabel']);
                } else {

                    $qrCode = new QrCode($settings['qrlink']);
                    $qrCode->setLabel($settings['qrlabel']);
                }

                $qrCode->setSize(160); 

                
                $qrCodeBase64 = base64_encode($qrCode->writeString());
                $label = '<div style="text-align:center;font-size:16px;font-weight:bold;">Scan Me</div>';
                $qrCodeImage = '<img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="Scan Me QR Code" style="display:block;margin:0 auto;">';





                $imageBase64 = ''; 

                if (!empty($settings['imageLetter'])) {
                    $imagePath = 'attachment/' . $settings['imageLetter'];

                    if (file_exists($imagePath)) {
                        $imageBase64 = base64_encode(file_get_contents($imagePath));
                    } else {
                        
                        echo "The image file doesn't exist at $imagePath";
                    }
                }

                
                $dataUri = !empty($imageBase64) ? 'data:image/png;base64,' . $imageBase64 : '';


                $char9 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 9);
                $char8 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
                $char7 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 7);
                $char6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 6);
                $char5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 5);
                $char4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 4);
                $char3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 3);
                $char2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 2);
                $CHARs2 = substr(str_shuffle(strtoupper("ABCDEFGHIJKLMNOPQRSTUVWXYZ")), 0, 2);
                $num9 = substr(str_shuffle("0123456789"), 0, 9);
                $num4 = substr(str_shuffle("0123456789"), 0, 4);
                $key64 = base64_encode($recipient);


                $letterFile = 'letter/' . $settings['letterFile']; 
                $letter = file_get_contents($letterFile) or die("Letter not found!");
                $letter = str_ireplace("##char8##", $char8, $letter);
                $letter = str_ireplace("##char7##", $char7, $letter);
                $letter = str_ireplace("##char6##", $char6, $letter);
                $letter = str_ireplace("##char5##", $char5, $letter);
                $letter = str_ireplace("##char4##", $char4, $letter);
                $letter = str_ireplace("##char3##", $char3, $letter);

                

                

                if ($settings['randomparam'] == true) {
                    $letter = str_ireplace("##link##", $link[array_rand($link)] . '?id=' . $char4, $letter);
                    $letter = str_ireplace("##char8##", $char8, $letter);
                    $letter = str_ireplace("##char7##", $char7, $letter);
                    $letter = str_ireplace("##char6##", $char6, $letter);
                    $letter = str_ireplace("##char5##", $char5, $letter);
                    $letter = str_ireplace("##char4##", $char4, $letter);
                    $letter = str_ireplace("##char3##", $char3, $letter);
                } else {
                    $letter = str_ireplace("##link##", $link[array_rand($link)], $letter);
                    $letter = str_ireplace("##char8##", $char8, $letter);
                    $letter = str_ireplace("##char7##", $char7, $letter);
                    $letter = str_ireplace("##char6##", $char6, $letter);
                    $letter = str_ireplace("##char5##", $char5, $letter);
                    $letter = str_ireplace("##char4##", $char4, $letter);
                    $letter = str_ireplace("##char3##", $char3, $letter);
                }
                $currentDate = date('Y-m-d');
                $letter = str_ireplace("##date6##", $currentDate, $letter);
                $letter = str_ireplace("##date##", date('D, F d, Y  g:i A'), $letter);
                $letter = str_ireplace("##date2##", date('D, F d, Y'), $letter);
                $letter = str_ireplace("##date3##", date('F d, Y  g:i A'), $letter);
                $letter = str_ireplace("##date4##", date('F d, Y'), $letter);
                $letter = str_ireplace("##date5##", date('F d'), $letter);
                $letter = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $letter);
                $letter = str_ireplace("##email##", $recipient, $letter);
                $letter = str_ireplace("##email64##", $key64, $letter);
                $letter = str_ireplace("##link64##", $b64link, $letter);
                $letter = str_ireplace("##char9##", $char9, $letter);
                $letter = str_ireplace("##char8##", $char8, $letter);
                $letter = str_ireplace("##char7##", $char7, $letter);
                $letter = str_ireplace("##char6##", $char6, $letter);
                $letter = str_ireplace("##char5##", $char5, $letter);
                $letter = str_ireplace("##char4##", $char4, $letter);
                $letter = str_ireplace("##char3##", $char3, $letter);
                $letter = str_ireplace("##char2##", $char2, $letter);
                $letter = str_ireplace("##CHARs2##", $CHARs2, $letter);
                $letter = str_ireplace("##num4##", $num4, $letter);
                $letter = str_ireplace("##userid##", $userId, $letter);
                $letter = str_ireplace("##domain##", $domains,  $letter);
                $letter = str_ireplace("##imglet##", $dataUri, $letter);
                $letter = str_ireplace("##qrcode##", '<div style="text-align: center;"><img src="data:image/png;base64,' . $qrCodeBase64 . '" ></div>', $letter);
                $letter = str_ireplace("##URLqrcode##", '<div style="text-align: center;"><a href="' . $link[array_rand($link)] . '" target="_blank"><img src="data:image/png;base64,' . $qrCodeBase64 . '"></a></div>', $letter);




                

                $subject = str_ireplace("##date##", date('D, F d, Y  g:i A'), $subject);
                $subject = str_ireplace("##date2##", date('D, F d, Y'), $subject);
                $subject = str_ireplace("##date3##", date('F d, Y  g:i A'), $subject);
                $subject = str_ireplace("##date4##", date('F d, Y'), $subject);
                $subject = str_ireplace("##date5##", date('F d'), $subject);
                $subject = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $subject);
                $subject = str_ireplace("##email##", $recipient, $subject);
                $subject = str_ireplace("##email64##", $key64, $subject);
                $subject = str_ireplace("##link64##", $b64link, $subject);
                $subject = str_ireplace("##char9##", $char9, $subject);
                $subject = str_ireplace("##char8##", $char8, $subject);
                $subject = str_ireplace("##char7##", $char7, $subject);
                $subject = str_ireplace("##char6##", $char6, $subject);
                $subject = str_ireplace("##char5##", $char5, $subject);
                $subject = str_ireplace("##char4##", $char4, $subject);
                $subject = str_ireplace("##char3##", $char3, $subject);
                $subject = str_ireplace("##char2##", $char2, $subject);
                $subject = str_ireplace("##userid##", $userId, $subject);
                $subject = str_ireplace("##CHARs2##", $CHARs2, $subject);
                $subject = str_ireplace("##num4##", $num4, $subject);
                $subject = str_ireplace("##num9##", $num9, $subject);
                $subject = str_ireplace("##domain##", $domains,  $subject);

                

                





                $fmail = str_ireplace("##domain##", $domains, $fmail);
                $fmail = str_ireplace("##userid##", $userId, $fmail);
                $fmail = str_ireplace("##relay##", $getsmtpUsername, $fmail);
                $fmail = str_ireplace("##date##", date('D, F d, Y  g:i A'), $fmail);
                $fmail = str_ireplace("##date2##", date('D, F d, Y'), $fmail);
                $fmail = str_ireplace("##date3##", date('F d, Y  g:i A'), $fmail);
                $fmail = str_ireplace("##date4##", date('F d, Y'), $fmail);
                $fmail = str_ireplace("##date5##", date('F d'), $fmail);
                $fmail = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $fmail);
                $fmail = str_ireplace("##email##", $recipient, $fmail);
                $fmail = str_ireplace("##email64##", $key64, $fmail);
                $fmail = str_ireplace("##char9##", $char9, $fmail);
                $fmail = str_ireplace("##char8##", $char8, $fmail);
                $fmail = str_ireplace("##char7##", $char7, $fmail);
                $fmail = str_ireplace("##char6##", $char6, $fmail);
                $fmail = str_ireplace("##char5##", $char5, $fmail);
                $fmail = str_ireplace("##char4##", $char4, $fmail);
                $fmail = str_ireplace("##char3##", $char3, $fmail);
                $fmail = str_ireplace("##char2##", $char2, $fmail);
                $fmail = str_ireplace("##CHARs2##", $CHARs2, $fmail);
                $fmail = str_ireplace("##num4##", $num4, $fmail);
                $fmail = str_ireplace("##num9##", $num9, $fmail);

                $fname = str_ireplace("##domain##", $domains, $fname);
                $fname = str_ireplace("##userid##", $userId, $fname);
                $fname = str_ireplace("##date##", date('D, F d, Y  g:i A'), $fname);
                $fname = str_ireplace("##date2##", date('D, F d, Y'), $fname);
                $fname = str_ireplace("##date3##", date('F d, Y  g:i A'), $fname);
                $fname = str_ireplace("##date4##", date('F d, Y'), $fname);
                $fname = str_ireplace("##date5##", date('F d'), $fname);
                $fname = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $fname);
                $fname = str_ireplace("##email##", $recipient, $fname);
                $fname = str_ireplace("##email64##", $key64, $fname);
                $fname = str_ireplace("##char9##", $char9, $fname);
                $fname = str_ireplace("##char8##", $char8, $fname);
                $fname = str_ireplace("##char7##", $char7, $fname);
                $fname = str_ireplace("##char6##", $char6, $fname);
                $fname = str_ireplace("##char5##", $char5, $fname);
                $fname = str_ireplace("##char4##", $char4, $fname);
                $fname = str_ireplace("##char3##", $char3, $fname);
                $fname = str_ireplace("##char2##", $char2, $fname);
                $fname = str_ireplace("##CHARs2##", $CHARs2, $fname);
                $fname = str_ireplace("##num4##", $num4, $fname);
                $fname = str_ireplace("##num9##", $num9, $fname);






              
                if (!function_exists('generateRandomEmail')) {
                    function generateRandomEmail()
                    {
                        $characters = 'abcdefghijklmnopqrstuvwxyz';
                        $randomString = '';
                        for ($i = 0; $i < 5; $i++) {
                            $randomString .= $characters[rand(0, strlen($characters) - 1)];
                        }
                        $randomString .= '@';
                        for ($i = 0; $i < 5; $i++) {
                            $randomString .= $characters[rand(0, strlen($characters) - 1)];
                        }
                        $randomString .= '.com';
                        return $randomString;
                    }
                }
                if (!function_exists('generateRandomNumber')) {
                    function generateRandomNumber()
                    {
                        return mt_rand(1000000000, 9999999999); 
                    }
                }            
                $randomEmail = generateRandomEmail();
                
                if (!function_exists('generateIPv4')) {
                    function generateIPv4()
                    {
                        return mt_rand(1, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 255);
                    }

                    
                    function generateIPv6FromIPv4($ipv4)
                    {
                        $ipv4Parts = explode('.', $ipv4);
                        $ipv6Mapped = '::ffff:' . $ipv4Parts[0] . '.' . $ipv4Parts[1] . '.' . $ipv4Parts[2] . '.' . $ipv4Parts[3];
                        return $ipv6Mapped;
                    }
                }


                
                $randomNumber = generateRandomNumber();

              
                
                $numberOfEmailsToSend = 5;

                            if ($settings['DomainName'] === 'Comcast') {
                                
                                  $getsmtpUsername = $smtpConfig['username'];

                              
                                if ($settings['randSender'] == true) {
                                    $domainsmtp = "xfinity.comcast.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "communication@" . $domainsmtp;
                                 		
                                } else {
                                    $mail->Sender = $getsmtpUsername;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if (!function_exists('generateRandomString')) {
                                    function generateRandomString($length = 32)
                                    {
                                        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                        $charactersLength = strlen($characters);
                                        $randomString = '';
                                        for ($i = 0; $i < $length; $i++) {
                                            $randomString .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        return $randomString;
                                    }
                                }
                                    if (!function_exists('generateRandString')) {
                                    function generateRandString($length = 36)
                                    {
                                        $characters = '0123456789abcdef';
                                        $charactersLength = strlen($characters);
                                        $randString = '';
                                        for ($i = 0; $i < $length; $i++) {
                                            $randString .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        return $randString;
                                    }
                                    }
    
                                    
                                    $randString = generateRandString();
    
    
                                    
                                    $randomString = generateRandomString();
                                    
                                     if (!function_exists('generateReceivedHeader')) {
                                      function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                    {
                                        return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                    }
                                     }
                                    if (!function_exists('generateRandomIPs')) {
                                    function generateRandomIPs()
                                    {
                                        return implode('.', array_map(function () {
                                            return mt_rand(0, 255);
                                        }, range(1, 4)));
                                    }
    
                                    }
                                    
                                    $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                    $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 
    
                                    $headers = [];
                                    for ($i = 0; $i <= 8; $i++) {
                                        $randIPs = generateRandomIPs();
                                        $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                    }
                                
                                $fixedHeaders = [
                                    'Content-Type' => 'text/html; charset=utf-8',
                                    'Content-Transfer-Encoding' => 'quoted-printable',
                                    'Message-ID' => "<$randomNumber@example.com>",
                                    'Date' => 'Thu, 07 Dec 2023 02:26:12 GMT',
                                    'Priority' => 'normal',
                                    'Importance' => 'normal',
                                    'X-Priority' => '=?UTF-8?Q?=221_=28Highest=29=22?=',
                                    'X-Msmail-Priority' => '=?UTF-8?Q?=22High=22?=',
                                    'Reply-To' => $fmail,
                                    'X-Originating-Client' =>  'open-xchange-appsuite',
                                    'In-Reply-To' => '<previous-message-id@smtp.comcast.net>',
                                    'References' => '<previous-message-id@smtp.comcast.net>',
                                    'X-Auto-Response-Suppress' => '=?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?=',
                                    'X-Mailer' => 'Open-Xchange Mailer v7.10.6-Rev39',
                                    'Return-Receipt-To' => $randomEmail,
                                    'Disposition-Notification-To' => $randomEmail,
                                    'X-Confirm-Reading-To' => $randomEmail,
                                    'X-Unsubscribe' => $randomEmail,
                                    'List-Unsubscribe' => $randomEmail,
                                    'X-Report-Abuse' => $randomEmail,
                                    'Precedence' => 'bulk',
                                    'X-Bulk' => 'bulk',
                                    'X-Spam-Status' => 'No, score=-2.7',
                                    'X-Spam-Score' => '-2.7',
                                    'X-Spam-Bar' => '/',
                                    'X-Spam-Flag' => 'NO',
                                    'X-Proofpoint-Spam-Details' => 'rule=notspam policy=default score=0 bulkscore=0 malwarescore=0 clxscore=1011 spamscore=0 phishscore=0 mlxlogscore=436 adultscore=0 suspectscore=0 mlxscore=0 classifier=spam adjust=0 reason=mlx scancount=1 engine=8.12.0-2308100000 definitions=main-2312110143',
                                    
                                    'To' => $email
                                ];
                                
                                    foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                    $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';

                                
                                if (isset($customHeaderSettings['customHeaders']) && is_array($customHeaderSettings['customHeaders'])) {
                                    
                                    $customHeaders = $customHeaderSettings['customHeaders'];

                                    
                                    $allHeaders = array_merge($fixedHeaders, $customHeaders);

                                    
                                    foreach ($allHeaders as $header => $value) {
                                        

                                        
                                        $mail->addCustomHeader("$header: $value");
                                    }
                                } else {
                                    
                                    $allHeaders = $fixedHeaders;

                                    
                                    foreach ($allHeaders as $header => $value) {
                                        

                                        
                                        $mail->addCustomHeader("$header: $value");
                                    }
                                }
                                
                            } elseif ($settings['DomainName'] === 'Cox') {

                                  if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.cox.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }
                                  if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'Originating-IP: '. $randIPs . ''; 
                               

                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                            
                            } elseif ($settings['DomainName'] === 'Optimum') {
                                
                                  $getsmtpUsername = $smtpSettings[0]['username'];
                                
                                  if ($settings['randSender'] == true) {

                                    $domainsmtp = "myemail.optimum.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                  
                                    $mail->Sender = $getsmtpUsername;
                                }
                                
                                  if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?iso-8859-2?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }
                                

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?iso-8859-2?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }
                                
                                if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.optonline.net (mx{$index}.optonline.net [{$randIPs}]) by optonline.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'Return-Path: zoeaand101@comcast.net' ;
                                $headers[] = 'Errors-To: zoeand101@comcast.net';
                                $headers[] = 'X-Originating-IP: '. $randIPs . ''; 
                                $headers[] = 'X-RG-VS-CS: promotions'; 
                        //        $headers[] = 'MIME-Version: 1.0'; 
                         //       $headers[] = 'X-RG-VS-CS: promotions'; 
                               

                                
                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?iso-8859-2?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                              
                            } elseif ($settings['DomainName'] === 'China') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }

                                $mail->Subject = '=?UTF-8?Q?' . quoted_printable_encode($subject) . '?=';

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                $CHARS9 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 9);
                                $CHARS8 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                $CHARS7 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
                                $CHARS6 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                                $CHARS5 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                                $CHARS4 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
							if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'X-LEEHLK: ' .$CHARS8 .''; 
                                $headers[] = 'X-SLQUPRRCI: '. $CHARS8 .''; 
                                $headers[] = 'X-FPYAI: '. $CHARS6 . ''; 
                                $headers[] = 'X-IHQQBGS: '. $CHARS5 . ''; 
                                $headers[] = 'X-JHKMG: '. $CHARS8 . ''; 
                                $headers[] = 'X-PKNTYB: '. $CHARS9 . ''; 
                                $headers[] = 'X-Accept-Language: en-us, en'; 
                                $headers[] = 'X-Proofpoint-ORIG-GUID: '. $randomString . ''; 
                                $headers[] = 'X-Proofpoint-GUID: '. $randomString . ''; 
                                $headers[] = 'X-Proofpoint-Virus-Version: vendor=fsecure_engine=1.1.170-'.$randString.':6.0.517,18.0.572,17.11.64.514.0000000_definitions=2022-06-21_01:2022-06-21_01,2020-02-14_11,2022-02-23_01_signatures=0'; 
                                $headers[] = 'X-Proofpoint-Spam-Details: rule=notspam policy=default score=0 bulkscore=0 malwarescore=0 clxscore=1011 spamscore=0 phishscore=0 mlxlogscore=436 adultscore=0 suspectscore=0 mlxscore=0 classifier=spam adjust=0 reason=mlx scancount=1 engine=8.12.0-2308100000 definitions=main-2312110143'; 
                               
                                 
                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                              
                            } elseif ($settings['DomainName'] === 'Office') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                  } else {
                                    $mail->Sender = $fmail;
                                }
                                if ($settings['encodeSubject']) {
                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($sender_inbox['subject']) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }
                                $CHARS9 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 9);
                                $CHARS8 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                $CHARS7 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
                                $CHARS6 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                                $CHARS5 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                                $CHARS4 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);

                               	if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Content-Type: text/html; charset=utf-8';
                                $headers[] = 'Content-Transfer-Encoding: quoted-printable';
                                $headers[] = 'Message-ID: <' . uniqid('', true) . '@comcast.net>'; 
                                $headers[] = 'Return-Path: ' . $fmail; 
                                $headers[] = 'List-Unsubscribe: <unsubscribe-link@office.com>';
                                $headers[] = 'List-ID: Your mailing list unique identifier <list.office.com>';
                                $headers[] = 'X-Mailer: =?UTF-8?Q?=22Your_Custom_Mailer=22?='; 
                                $headers[] = 'X-Complaints-To: abuse@office.com';
                                $headers[] = 'X-Postmaster: postmaster@office.com';
                                $headers[] = 'X-Priority: =?UTF-8?Q?=221_=28Highest=29=22?='; 
                                $headers[] = 'Precedence: bulk';
                                $headers[] = 'Auto-Submitted: auto-generated';
                                $headers[] = 'X-Msmail-Priority: =?UTF-8?Q?=22High=22?='; 
                                $headers[] = 'MIME-Version: 1.0';
                                $headers[] = 'Priority: normal';
                                $headers[] = 'Importance: normal';
                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'In-Reply-To: <previous-message-id@smtp.comcast.net>';
                                $headers[] = 'References: <previous-message-id@smtp.comcast.net>';
                                $headers[] = 'Return-Receipt-To: read-receipt@office.com';
                                $headers[] = 'Disposition-Notification-To: read-receipt@office.com';
                                $headers[] = 'X-Unsubscribe: <unsubscribe-link@office.com>';
                                $headers[] = 'X-Confirm-Reading-To: read-receipt@office.com';
                                $headers[] = 'X-Auto-Response-Suppress: =?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?='; 
                                $headers[] = 'X-Report-Abuse: report-abuse@office.com';
                                $headers[] = 'X-Bulk: bulk';
                                $headers[] = 'X-Spam-Status: No, score=-2.7';
                                $headers[] = 'X-Spam-Score: -2.7';
                                $headers[] = 'X-Spam-Bar: /';
                                $headers[] = 'X-Spam-Flag: NO';

                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Your_Custom_Mailer=22?=';

                               
                            } elseif ($settings['DomainName'] === 'Godaddy') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }

                                if ($settings['encodeSubject']) {
                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                $headers = [];
                                $headers[] = 'Date: ' . date('D, d M Y H:i:s O');
                                $headers[] = 'From: ' . $fname . ' <' . $fmail . '>';
                                $headers[] = 'To:' . $fname . '<'. $fname .'>';
                                $headers[] = 'Message-ID: <' . uniqid('', true) . '@connect.xfinity.com>';
                                $headers[] = 'Subject:' . $mail->Subject;
                                $headers[] = 'MIME-Version: 1.0';
                                $headers[] = 'X-Mailer: Open-Xchange Mailer v7.10.6-Rev39';
                                $headers[] = 'X-Originating-Client: open-xchange-appsuite';
                                $headers[] = 'Return-Path: phisin101@comcast.net';


                                foreach ($headers as $header) {
                                    $mail->addCustomHeader($header);
                                }

                                $randomIPv4 = generateIPv4();
                                $randomIPv6 = generateIPv6FromIPv4($randomIPv4);
                                
                                $mail->addCustomHeader('X-Originating-IP: ' . $randomIPv6);
                                $mail->XMailer = 'Open-Xchange Mailer v7.10.6-Rev39';

                              
                            } elseif (empty($domainSettings['DomainName'])) {

                                echo "\033[31m\t\t\nSelect The Domain your sending to\n\n\n";
                                exit;
                            } else {

                                echo "\033[31m\t\t\nInvalid Input\n\n\n";
                                exit;
                            }


                $mail->isHTML(true);
                $mail->Body    = $letter;




                try {
                    if ($mail->send()) {
                        
                         $emailsSent++;

                                        if ($emailsSent % $settings['waitAfter'] === 0) {
                                            echo "Sent {$settings['waitAfter']} emails. Waiting for {$settings['waitFor']} seconds...\n";
                                            sleep($settings['waitFor']);
                                        }

                        echo "Message sent successfully to $recipient using SMTP: {$smtpConfig['username']}\n";
                        file_put_contents($sentEmailsFile, $recipient . PHP_EOL, FILE_APPEND); 
                        file_put_contents($validEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
                        $authenticationError = false; 
                        
                        return true;
                    } else {
                        $errorMessage = $mail->ErrorInfo;
                        $errorMessage = strtolower($errorMessage);
        
                        if (strpos($errorMessage, 'could not authenticate') !== false && $retryCount > 0) {
                            echo "Retrying sending to $recipient using SMTP: {$smtpConfig['username']} (Retry Count: $retryCount)\n";
                            return sendEmail($recipient, $smtpSettings, $settings, $retryCount - 1, $failedEmailsFile, $badEmailsFile, $sentEmailsFile);
                        } else {
                            
                            $specificErrorPhrases = ['could not connect', 'too many emails', 'try again later', 'Resources restricted'];
                            $isSpecificError = false;
        
                            foreach ($specificErrorPhrases as $phrase) {
                                if (strpos($errorMessage, $phrase) !== false) {
                                    file_put_contents($failedEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
                                    $isSpecificError = true;
                                    break;
                                }
                            }
        
                            if (!$isSpecificError && strpos($errorMessage, 'could not authenticate') === false) {
                                
                                file_put_contents($badEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
                            }
        
                            echo 'Mailer Error: ' . $errorMessage . " to $recipient using SMTP: {$smtpConfig['username']}\n";
                        }
                    }
                     return false;
                } catch (Exception $e) {
        // Handling exceptions
        echo 'Caught exception: ' . $e->getMessage() . "\n";
        
        $errorMessage = strtolower($e->getMessage());
        if (strpos($errorMessage, 'user unknown') !== false) {
            file_put_contents($badEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
        }

        return false; // Return false to skip further attempts for the same recipient
    }

    return false; // Return false after handling the error to stop retrying

      
            }
        
            
            if ($authenticationError) {
                
                file_put_contents($failedEmailsFile, $recipient . PHP_EOL, FILE_APPEND);
                echo "All SMTP servers failed to authenticate for $recipient\n";
                return false;
            }
            
        }
        
        
           
        
            $threadCount = isset($settings['threads']) && $settings['threads'] > 1 ? $settings['threads'] : 1;
            echo "Thread count: $threadCount\n"; 
        
            $smtpCount = count($smtpSettings); 
            $smtpIndex = 0; 
        
            if ($threadCount > 1) {
                $chunks = array_chunk($recipientList, ceil(count($recipientList) / $threadCount));
        
                $childProcesses = [];
        
                foreach ($chunks as $chunk) {
                    $pid = pcntl_fork();
        
                    if ($pid == -1) {
                        die("Error forking process.");
                    } elseif ($pid) {
                        $childProcesses[] = $pid;
                    } else {
                        foreach ($chunk as $recipient) {
                            $smtpConfig = $smtpSettings[$smtpIndex % $smtpCount];
                            sendEmail($recipient, $smtpSettings, $settings, 2, $failedEmailsFile, $badEmailsFile, $validEmailsFile, $sentEmailsFile);
                            $smtpIndex++;
                        }
                        exit(); 
                    }
                }
        
                
                foreach ($childProcesses as $pid) {
                    pcntl_waitpid($pid, $status);
                }
            } else {
                $smtpIndex = 0;
            
                foreach ($recipientList as $recipient) {
                    $smtpConfig = $smtpSettings[$smtpIndex % $smtpCount];
                    sendEmail($recipient, $smtpSettings, $settings, 2, $failedEmailsFile, $badEmailsFile, $validEmailsFile, $sentEmailsFile);
            
                    $smtpIndex++;
                    if ($smtpIndex >= $smtpCount) {
                        $smtpIndex = 0;
                        shuffle($smtpSettings);
                    }
                }
            }
            
             $recipientListFile = 'list/' . $settings['recipientListFile'];
            $failedEmailsFile = 'failed.txt';
            $sentEmailsFile = 'pass.txt';
            $badlistFile = 'bad.txt';

            function filterRecipientList($recipientListFile, $sentEmailsFile, $badlistFile)
            {
                $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $sentEmails = [];
                $badlist = [];

                if (file_exists($sentEmailsFile)) {
                    $sentEmails = file($sentEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                }

                if (file_exists($badlistFile)) {
                    $badlist = file($badlistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                }

                $combinedEmails = array_merge($sentEmails ?: [], $badlist ?: []);
                $combinedEmails = array_unique($combinedEmails); 

                $RecipientListForFilter = array_diff($recipientList, $combinedEmails);

                
                file_put_contents("Resend.txt", implode(PHP_EOL, $RecipientListForFilter) . PHP_EOL);
            }

            
            filterRecipientList($recipientListFile, $sentEmailsFile, $badlistFile);

            
            
            function countLines($file)
            {
                if (file_exists($file)) {
                    $lines = file($file);
                    if ($lines === false) {
                        return 0; 
                    } else {
                        return count($lines);
                    }
                } else {
                    return 0; 
                }
            }

            $failedEmailsFile = 'failed.txt';
            $sentEmailsFile = 'pass.txt';
            $badlistFile = 'bad.txt';
            $recipientListFile = 'list/' . $settings['recipientListFile']; 

            $failedEmailsCount = countLines($failedEmailsFile);
            $sentEmailsCount = countLines($sentEmailsFile);
            $badEmailsCount = countLines($badlistFile);
            $recipientListFileCount = countLines($recipientListFile);

            
            $totalProcessedEmails = $failedEmailsCount + $sentEmailsCount + $badEmailsCount;

            echo "Original Email Count: $recipientListFileCount\n";
            echo "Total Processed Email Count: $totalProcessedEmails\n";


            if ($totalProcessedEmails === $recipientListFileCount) {
                if (file_exists($sentEmailsFile)) {
                    $sentEmails = array_unique(file($sentEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No email was sent\n";
                }

                if (file_exists($badlistFile)) {
                    $badEmails = array_unique(file($badlistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No Bad emails\n";
                }
                if (file_exists($failedEmailsFile)) {
                    $failedEmails = array_unique(file($failedEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No Failed emails\n";
                }

                if (empty($failedEmails) && !empty($sentEmails)) {
                    $sentCount = count($sentEmails);
                    echo "\n\033[0;32mAll Emails Sent Successfully. Sent Count: $sentCount\033[0m\n";
                } elseif (!empty($failedEmails)) {
                    $failedCount = count($failedEmails);
                    echo "\n\033[0;33mNot all emails were sent. Resend emails in Resend.txt ONLY. Failed Count: $failedCount\033[0m\n";
                } elseif (empty($sentEmails)) {
                    echo "\n\033[0;33mNo emails were sent.\033[0m\n";
                }
            } else {
                $unsentCount = $recipientListFileCount - $sentEmailsCount - $badEmailsCount;
                echo "\n\033[0;33mNot all emails have been sent due to errors.Resend emails in Resend.txt ONLY. Unsent Count: $unsentCount\033[0m\n";
            }
            
           $resendContent = file_get_contents("Resend.txt");
           $checker = !empty(preg_replace('/\s+/', '', $resendContent));

            if ($checker !== false) {
                $recipientListFile = 'list/' . $settings['recipientListFile'];
            
               
                    $listContent = file_get_contents($recipientListFile);
            
                    if ($listContent !== false) {
                        file_put_contents($recipientListFile, $resendContent);
                        file_put_contents("list/list_backup.txt", $listContent);
                        
                        $lineCounts = count(explode(PHP_EOL, $listContent)); 
                        
                        echo "\n\033[0;32mList.txt successfully updated with the content of Resend.txt.\033[0m\n";
                       
                       
                    } else {
                        echo "\033[0;31mFailed to read the content of list.txt.\033[0m\n";
                       exit;
                    }
                   
            } 
        
        
            $loop->run();
            
        } else {






            
            $settings = array_merge(reset($smtpSettings), $recipientListSettings, $customHeaderSettings, $commonSettings, $domainSettings);

            
            $numThreads = empty($settings['threads']) ? 1 : intval($settings['threads']);

            
            $recipientListFile = 'list/' . $settings['recipientListFile'];  
            $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            
            if ($settings['removeDuplicates']) {
                $recipientList = array_unique($recipientList);
            }

            
            if (empty($recipientList)) {
                throw new Exception('You must provide at least one recipient email address.');
            }

            $numEmails = count($recipientList);

            
            echo "\n\033[1;33mNumber of emails: $numEmails";
            echo "\n\033[1;33mNumber of smtp: 1\n";
            echo "\n\033[1;33mProceeding to send emails...\n";
            
            $chunks = array_chunk($recipientList, max(1, ceil(count($recipientList) / $numThreads)));

            
            $pids = [];
            for ($i = 0; $i < $numThreads; $i++) {
                $pid = pcntl_fork();

                if ($pid == -1) {
                    die("Could not fork.\n");
                } elseif ($pid) {
                    
                    $pids[] = $pid;
                } else {
                    
                    if (isset($chunks[$i]) && is_array($chunks[$i])) {
                        $start = $i * count($chunks[$i]);
                        $end = min(($i + 1) * count($chunks[$i]), count($recipientList));

                        
                        $mail = new PHPMailer(true);


                        function generateRandomIP()
                        {

                            return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
                        }

                        $maxConsecutiveFailures = $settings['ErrorHandling'];

                        
                        $consecutiveFailures = 0;

                        $emailsSent = 0;    

                        
                        foreach ($chunks[$i] as $email) {
                            $mail->addAddress($email);

                            
                            $mail->isSMTP();
                            $mail->Host       = $settings['host'];
                            $mail->Port       = $settings['port'];
                            $mail->Username   = $settings['username'];
                            $mail->Password   = $settings['password'];
                            $mail->SMTPSecure = $settings['Auth'];
                            $mail->Hostname   = $settings['Hostname'];
                            $mail->SMTPAuth   = true;
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                           
                            
                            if ($settings['SMTP'] === 'Optimum') { 
                                         $mail->SMTPKeepAlive = false;
                                  }else{
                                       $mail->SMTPKeepAlive = true;
                                  }
                            $senderEmail = isset($settings['from']) ? $settings['from'] : '';
                            if (!$senderEmail) {
                                throw new Exception('Invalid sender email address.');
                            }



                            $edomainn = explode('@', $email);
                            $userId = $edomainn[0];
                            $domains = $edomainn[1];


                            $fmail = $settings['from'];
                            $fname = $settings['fromname'];
                            $subject = $settings['subject'];


                            $getsmtpUsername = $smtpSettings[0]['username'];

                            



                            if (!empty($settings['image_attachfile'])) {
                                $imageAttachmentPath = 'attachment/' . $settings['image_attachfile']; 
                                if ($settings['displayimage'] == true) {
                                    $mail->addEmbeddedImage($imageAttachmentPath, 'imgslet', $settings['image_attachname'], 'base64', 'image/jpeg, image/jpg, image/png');
                                } else {
                                    $mail->addAttachment($imageAttachmentPath, $settings['image_attachfile']);
                                }
                            }




                            if (!empty($settings['pdf_attachfile'])) {
                                $mail->addAttachment($settings['pdf_attachfile']);
                            }
                            $link = explode('|', $commonSettings['link']);
                            $b64link = base64_encode($commonSettings['linkb64']);



                            if ($commonSettings['autolink'] == true) {
                                $qrCode = new QrCode($commonSettings['qrlink'] . '?e=' . $email);
                                $qrCode->setLabel($commonSettings['qrlabel']);
                            } else {

                                $qrCode = new QrCode($commonSettings['qrlink']);
                                $qrCode->setLabel($commonSettings['qrlabel']);
                            }

                            $qrCode->setSize(160); 

                            
                            $qrCodeBase64 = base64_encode($qrCode->writeString());
                            $label = '<div style="text-align:center;font-size:16px;font-weight:bold;">Scan Me</div>';
                            $qrCodeImage = '<img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="Scan Me QR Code" style="display:block;margin:0 auto;">';

                            if ($domainSettings['DomainName'] == "China") {
                          
                           if (!function_exists('encryptEmailForURL')) {
                            function encryptEmailForURL($email, $key) {
                                $cipher = 'AES-256-CBC';
                                $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
                                $encrypted = openssl_encrypt($email, $cipher, $key, 0, $iv);
                                $encoded = urlencode(base64_encode($encrypted . '::' . $iv));
                                return $encoded;
                            }
                            
                            
                            $encryptionKey = $commonSettings['EncryptKeyEmlAdd']; 
                            

                            
                            $encryptedRecipientEmail = encryptEmailForURL($email, $encryptionKey);
                            
                            
                            }     
                            }
                         
                            $encodedURL = htmlspecialchars($commonSettings['link'], ENT_QUOTES, 'UTF-8');
                           


                            $imageBase64 = ''; 

                            if (!empty($commonSettings['imageLetter'])) {
                                $imagePath = 'attachment/' . $commonSettings['imageLetter'];

                                if (file_exists($imagePath)) {
                                    $imageBase64 = base64_encode(file_get_contents($imagePath));
                                } else {
                                    
                                    echo "The image file doesn't exist at $imagePath";
                                }
                            }

                            
                            $dataUri = !empty($imageBase64) ? 'data:image/png;base64,' . $imageBase64 : '';

                            $currentDate = date('Y-m-d');



                            $char9 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 9);
                            $char8 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
                            $char7 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 7);
                            $char6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 6);
                            $char5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 5);
                            $char4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 4);
                            $char3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 3);
                            $char2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 2);
                            $CHARs2 = substr(str_shuffle(strtoupper("ABCDEFGHIJKLMNOPQRSTUVWXYZ")), 0, 2);
                            $num9 = substr(str_shuffle("0123456789"), 0, 9);
                            $num4 = substr(str_shuffle("0123456789"), 0, 4);
                            $key64 = base64_encode($email);


                            $letterFile = 'letter/' . $settings['letterFile']; 
                            $letter = file_get_contents($letterFile) or die("Letter not found!");
                            $letter = str_ireplace("##char8##", $char8, $letter);
                            $letter = str_ireplace("##char7##", $char7, $letter);
                            $letter = str_ireplace("##char6##", $char6, $letter);
                            $letter = str_ireplace("##char5##", $char5, $letter);
                            $letter = str_ireplace("##char4##", $char4, $letter);
                            $letter = str_ireplace("##char3##", $char3, $letter);

                            

                            

                            if ($commonSettings['randomparam'] == true) {
                                $letter = str_ireplace("##link##", $link[array_rand($link)] . '?id=' . $char4, $letter);
                                $letter = str_ireplace("##char8##", $char8, $letter);
                                $letter = str_ireplace("##char7##", $char7, $letter);
                                $letter = str_ireplace("##char6##", $char6, $letter);
                                $letter = str_ireplace("##char5##", $char5, $letter);
                                $letter = str_ireplace("##char4##", $char4, $letter);
                                $letter = str_ireplace("##char3##", $char3, $letter);
                            } else {
                                $letter = str_ireplace("##link##", $link[array_rand($link)], $letter);
                                $letter = str_ireplace("##char8##", $char8, $letter);
                                $letter = str_ireplace("##char7##", $char7, $letter);
                                $letter = str_ireplace("##char6##", $char6, $letter);
                                $letter = str_ireplace("##char5##", $char5, $letter);
                                $letter = str_ireplace("##char4##", $char4, $letter);
                                $letter = str_ireplace("##char3##", $char3, $letter);
                            }
                            $letter = str_ireplace("##date##", date('D, F d, Y  g:i A'), $letter);
                            $letter = str_ireplace("##date2##", date('D, F d, Y'), $letter);
                            $letter = str_ireplace("##date3##", date('F d, Y  g:i A'), $letter);
                            $letter = str_ireplace("##date4##", date('F d, Y'), $letter);
                            $letter = str_ireplace("##date5##", date('F d'), $letter);
                            $letter = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $letter);
                            $letter = str_ireplace("##email##", $email, $letter);
                            $letter = str_ireplace("##email64##", $key64, $letter);
                            $letter = str_ireplace("##emailEncrypt##", $encryptedRecipientEmail, $letter);
                            $letter = str_ireplace("##encodedurl##", $encodedURL, $letter);
                            $letter = str_ireplace("##link64##", $b64link, $letter);
                            $letter = str_ireplace("##char9##", $char9, $letter);
                            $letter = str_ireplace("##char8##", $char8, $letter);
                            $letter = str_ireplace("##char7##", $char7, $letter);
                            $letter = str_ireplace("##char6##", $char6, $letter);
                            $letter = str_ireplace("##char5##", $char5, $letter);
                            $letter = str_ireplace("##char4##", $char4, $letter);
                            $letter = str_ireplace("##date6##", $currentDate, $letter);
                            $letter = str_ireplace("##char3##", $char3, $letter);
                            $letter = str_ireplace("##char2##", $char2, $letter);
                            $letter = str_ireplace("##CHARs2##", $CHARs2, $letter);
                            $letter = str_ireplace("##num4##", $num4, $letter);
                            $letter = str_ireplace("##userid##", $userId, $letter);
                            $letter = str_ireplace("##domain##", $domains,  $letter);
                            $letter = str_ireplace("##imglet##", $dataUri, $letter);
                            $letter = str_ireplace("##qrcode##", '<div style="text-align: center;"><img src="data:image/png;base64,' . $qrCodeBase64 . '" ></div>', $letter);
                            $letter = str_ireplace("##URLqrcode##", '<div style="text-align: center;"><a href="' . $link[array_rand($link)] . '" target="_blank"><img src="data:image/png;base64,' . $qrCodeBase64 . '"></a></div>', $letter);




                            

                            $subject = str_ireplace("##date##", date('D, F d, Y  g:i A'), $subject);
                            $subject = str_ireplace("##date2##", date('D, F d, Y'), $subject);
                            $subject = str_ireplace("##date3##", date('F d, Y  g:i A'), $subject);
                            $subject = str_ireplace("##date4##", date('F d, Y'), $subject);
                            $subject = str_ireplace("##date5##", date('F d'), $subject);
                            $subject = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $subject);
                            $subject = str_ireplace("##email##", $email, $subject);
                            $subject = str_ireplace("##email64##", $key64, $subject);
                            $subject = str_ireplace("##link64##", $b64link, $subject);
                            $subject = str_ireplace("##char9##", $char9, $subject);
                            $subject = str_ireplace("##char8##", $char8, $subject);
                            $subject = str_ireplace("##char7##", $char7, $subject);
                            $subject = str_ireplace("##char6##", $char6, $subject);
                            $subject = str_ireplace("##char5##", $char5, $subject);
                            $subject = str_ireplace("##char4##", $char4, $subject);
                            $subject = str_ireplace("##char3##", $char3, $subject);
                            $subject = str_ireplace("##char2##", $char2, $subject);
                            $subject = str_ireplace("##userid##", $userId, $subject);
                            $subject = str_ireplace("##CHARs2##", $CHARs2, $subject);
                            $subject = str_ireplace("##num4##", $num4, $subject);
                            $subject = str_ireplace("##num9##", $num9, $subject);
                            $subject = str_ireplace("##domain##", $domains,  $subject);

                            

                            





                            $fmail = str_ireplace("##domain##", $domains, $fmail);
                            $fmail = str_ireplace("##userid##", $userId, $fmail);
                            $fmail = str_ireplace("##relay##", $getsmtpUsername, $fmail);
                            $fmail = str_ireplace("##date##", date('D, F d, Y  g:i A'), $fmail);
                            $fmail = str_ireplace("##date2##", date('D, F d, Y'), $fmail);
                            $fmail = str_ireplace("##date3##", date('F d, Y  g:i A'), $fmail);
                            $fmail = str_ireplace("##date4##", date('F d, Y'), $fmail);
                            $fmail = str_ireplace("##date5##", date('F d'), $fmail);
                            $fmail = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $fmail);
                            $fmail = str_ireplace("##email##", $email, $fmail);
                            $fmail = str_ireplace("##email64##", $key64, $fmail);
                            $fmail = str_ireplace("##char9##", $char9, $fmail);
                            $fmail = str_ireplace("##char8##", $char8, $fmail);
                            $fmail = str_ireplace("##char7##", $char7, $fmail);
                            $fmail = str_ireplace("##char6##", $char6, $fmail);
                            $fmail = str_ireplace("##char5##", $char5, $fmail);
                            $fmail = str_ireplace("##char4##", $char4, $fmail);
                            $fmail = str_ireplace("##char3##", $char3, $fmail);
                            $fmail = str_ireplace("##char2##", $char2, $fmail);
                            $fmail = str_ireplace("##CHARs2##", $CHARs2, $fmail);
                            $fmail = str_ireplace("##num4##", $num4, $fmail);
                            $fmail = str_ireplace("##num9##", $num9, $fmail);

                            $fname = str_ireplace("##domain##", $domains, $fname);
                            $fname = str_ireplace("##userid##", $userId, $fname);
                            $fname = str_ireplace("##date##", date('D, F d, Y  g:i A'), $fname);
                            $fname = str_ireplace("##date2##", date('D, F d, Y'), $fname);
                            $fname = str_ireplace("##date3##", date('F d, Y  g:i A'), $fname);
                            $fname = str_ireplace("##date4##", date('F d, Y'), $fname);
                            $fname = str_ireplace("##date5##", date('F d'), $fname);
                            $fname = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')), $fname);
                            $fname = str_ireplace("##email##", $email, $fname);
                            $fname = str_ireplace("##email64##", $key64, $fname);
                            $fname = str_ireplace("##char9##", $char9, $fname);
                            $fname = str_ireplace("##char8##", $char8, $fname);
                            $fname = str_ireplace("##char7##", $char7, $fname);
                            $fname = str_ireplace("##char6##", $char6, $fname);
                            $fname = str_ireplace("##char5##", $char5, $fname);
                            $fname = str_ireplace("##char4##", $char4, $fname);
                            $fname = str_ireplace("##char3##", $char3, $fname);
                            $fname = str_ireplace("##char2##", $char2, $fname);
                            $fname = str_ireplace("##CHARs2##", $CHARs2, $fname);
                            $fname = str_ireplace("##num4##", $num4, $fname);
                            $fname = str_ireplace("##num9##", $num9, $fname);






                            if (!function_exists('generateRandomEmail')) {
                                function generateRandomEmail()
                                {
                                    $characters = 'abcdefghijklmnopqrstuvwxyz';
                                    $randomString = '';
                                    for ($i = 0; $i < 5; $i++) {
                                        $randomString .= $characters[rand(0, strlen($characters) - 1)];
                                    }
                                    $randomString .= '@';
                                    for ($i = 0; $i < 5; $i++) {
                                        $randomString .= $characters[rand(0, strlen($characters) - 1)];
                                    }
                                    $randomString .= '.com';
                                    return $randomString;
                                }
                            }
                            if (!function_exists('generateRandomNumber')) {
                                function generateRandomNumber()
                                {
                                    return mt_rand(1000000000, 9999999999); 
                                }
                            }            
                            $randomEmail = generateRandomEmail();
                            
                            if (!function_exists('generateIPv4')) {
                                function generateIPv4()
                                {
                                    return mt_rand(1, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 255);
                                }

                                
                                function generateIPv6FromIPv4($ipv4)
                                {
                                    $ipv4Parts = explode('.', $ipv4);
                                    $ipv6Mapped = '::ffff:' . $ipv4Parts[0] . '.' . $ipv4Parts[1] . '.' . $ipv4Parts[2] . '.' . $ipv4Parts[3];
                                    return $ipv6Mapped;
                                }
                            }


                            
                            $randomNumber = generateRandomNumber();

                            $randomIP = generateRandomIP();
                            $numberOfEmailsToSend = 5;

                            if ($domainSettings['DomainName'] === 'Comcast') {
                                
                               $getsmtpUsername = $smtpSettings[0]['username'];

                                if ($settings['randSender'] == true) {
                                    $domainsmtp = "xfinity.comcast.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "communication@" . $domainsmtp;
                                 		
                                } else {
                                    $mail->Sender = $getsmtpUsername;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if (!function_exists('generateRandomString')) {
                                    function generateRandomString($length = 32)
                                    {
                                        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                        $charactersLength = strlen($characters);
                                        $randomString = '';
                                        for ($i = 0; $i < $length; $i++) {
                                            $randomString .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        return $randomString;
                                    }
                                }
                                    if (!function_exists('generateRandString')) {
                                    function generateRandString($length = 36)
                                    {
                                        $characters = '0123456789abcdef';
                                        $charactersLength = strlen($characters);
                                        $randString = '';
                                        for ($i = 0; $i < $length; $i++) {
                                            $randString .= $characters[rand(0, $charactersLength - 1)];
                                        }
                                        return $randString;
                                    }
                                    }
    
                                    
                                    $randString = generateRandString();
    
    
                                    
                                    $randomString = generateRandomString();
                                    
                                     if (!function_exists('generateReceivedHeader')) {
                                      function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                    {
                                        return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                    }
                                     }
                                    if (!function_exists('generateRandomIPs')) {
                                    function generateRandomIPs()
                                    {
                                        return implode('.', array_map(function () {
                                            return mt_rand(0, 255);
                                        }, range(1, 4)));
                                    }
    
                                    }
                                    
                                    $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                    $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 
    
                                    $headers = [];
                                    for ($i = 0; $i <= 8; $i++) {
                                        $randIPs = generateRandomIPs();
                                        $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                    }
                                
                                $fixedHeaders = [
                                    'Content-Type' => 'text/html; charset=utf-8',
                                    'Content-Transfer-Encoding' => 'quoted-printable',
                                    'Message-ID' => "<$randomNumber@example.com>",
                                    'Date' => 'Thu, 07 Dec 2023 02:26:12 GMT',
                                    'Priority' => 'normal',
                                    'Importance' => 'normal',
                                    'X-Priority' => '=?UTF-8?Q?=221_=28Highest=29=22?=',
                                    'X-Msmail-Priority' => '=?UTF-8?Q?=22High=22?=',
                                    'Reply-To' => $fmail,
                                    'X-Originating-Client' =>  'open-xchange-appsuite',
                                    'In-Reply-To' => '<previous-message-id@smtp.comcast.net>',
                                    'References' => '<previous-message-id@smtp.comcast.net>',
                                    'X-Auto-Response-Suppress' => '=?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?=',
                                    'X-Mailer' => 'Open-Xchange Mailer v7.10.6-Rev39',
                                    'Return-Receipt-To' => $randomEmail,
                                    'Disposition-Notification-To' => $randomEmail,
                                    'X-Confirm-Reading-To' => $randomEmail,
                                    'X-Unsubscribe' => $randomEmail,
                                    'List-Unsubscribe' => $randomEmail,
                                    'X-Report-Abuse' => $randomEmail,
                                    'Precedence' => 'bulk',
                                    'X-Bulk' => 'bulk',
                                    'X-Spam-Status' => 'No, score=-2.7',
                                    'X-Spam-Score' => '-2.7',
                                    'X-Spam-Bar' => '/',
                                    'X-Spam-Flag' => 'NO',
                                    'X-Proofpoint-Spam-Details' => 'rule=notspam policy=default score=0 bulkscore=0 malwarescore=0 clxscore=1011 spamscore=0 phishscore=0 mlxlogscore=436 adultscore=0 suspectscore=0 mlxscore=0 classifier=spam adjust=0 reason=mlx scancount=1 engine=8.12.0-2308100000 definitions=main-2312110143',
                                    
                                    'To' => $email
                                ];
                                
                                    foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                    $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';

                                
                                if (isset($customHeaderSettings['customHeaders']) && is_array($customHeaderSettings['customHeaders'])) {
                                    
                                    $customHeaders = $customHeaderSettings['customHeaders'];

                                    
                                    $allHeaders = array_merge($fixedHeaders, $customHeaders);

                                    
                                    foreach ($allHeaders as $header => $value) {
                                        

                                        
                                        $mail->addCustomHeader("$header: $value");
                                    }
                                } else {
                                    
                                    $allHeaders = $fixedHeaders;

                                    
                                    foreach ($allHeaders as $header => $value) {
                                        

                                        
                                        $mail->addCustomHeader("$header: $value");
                                    }
                                }

                            } elseif ($domainSettings['DomainName'] === 'Cox') {

                                  if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.cox.com";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }
                                  if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								

                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'Originating-IP: '. $randIPs . ''; 
                                $headers[] = 'x-ms-publictraffictype: Email';
                                $headers[] = 'x-ms-traffictypediagnostic: AS8PR04MB8978:EE_|PAXPR04MB8390:EE_';
                                $headers[] = 'x-ms-office365-filtering-correlation-id: 7b0084bb-4392-40b8-dc2c-08dc0d75fa41';
                                $headers[] = 'x-ms-exchange-senderadcheck: 1';
                                $headers[] = 'x-ms-exchange-antispam-relay: 0';
                                $headers[] = 'x-microsoft-antispam: BCL:0;';
                                $headers[] = 'x-microsoft-antispam-message-info:';
                               

                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                            
                            } elseif ($domainSettings['DomainName'] === 'Optimum') {
                                
                                  
                                
                                   $getsmtpUsername = $smtpSettings[0]['username'];
                                
                                  if ($settings['randSender'] == true) {

                                    $domainsmtp = "myemail.optimum.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                  
                                    $mail->Sender = $getsmtpUsername;
                                }
                                
                                  if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?iso-8859-2?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }
                                

                                if ($settings['encodeSubject']) {

                                    $mail->Subject = '=?iso-8859-2?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }
                                
                                if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.optonline.net (mx{$index}.optonline.net [{$randIPs}]) by optonline.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'Return-Path: zoeaand101@comcast.net' ;
                                $headers[] = 'Errors-To: zoeand101@comcast.net';
                                $headers[] = 'X-Originating-IP: '. $randIPs . ''; 
                                $headers[] = 'X-RG-VS-CS: promotions'; 
                        //        $headers[] = 'MIME-Version: 1.0'; 
                         //       $headers[] = 'X-RG-VS-CS: promotions'; 
                               

                                
                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?iso-8859-2?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                              
                            } elseif ($domainSettings['DomainName'] === 'China') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                	   } else {
                                    $mail->Sender = $fmail;
                                }

                                $mail->Subject = '=?UTF-8?Q?' . quoted_printable_encode($subject) . '?=';

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                $CHARS9 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 9);
                                $CHARS8 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                $CHARS7 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
                                $CHARS6 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                                $CHARS5 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                                $CHARS4 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
							if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }


                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'X-LEEHLK: ' .$CHARS8 .''; 
                                $headers[] = 'X-SLQUPRRCI: '. $CHARS8 .''; 
                                $headers[] = 'X-FPYAI: '. $CHARS6 . ''; 
                                $headers[] = 'X-IHQQBGS: '. $CHARS5 . ''; 
                                $headers[] = 'X-JHKMG: '. $CHARS8 . ''; 
                                $headers[] = 'X-PKNTYB: '. $CHARS9 . ''; 
                                $headers[] = 'X-Accept-Language: en-us, en'; 
                                $headers[] = 'X-Proofpoint-ORIG-GUID: '. $randomString . ''; 
                                $headers[] = 'X-Proofpoint-GUID: '. $randomString . ''; 
                                $headers[] = 'X-Proofpoint-Virus-Version: vendor=fsecure_engine=1.1.170-'.$randString.':6.0.517,18.0.572,17.11.64.514.0000000_definitions=2022-06-21_01:2022-06-21_01,2020-02-14_11,2022-02-23_01_signatures=0'; 
                                $headers[] = 'X-Proofpoint-Spam-Details: rule=notspam policy=default score=0 bulkscore=0 malwarescore=0 clxscore=1011 spamscore=0 phishscore=0 mlxlogscore=436 adultscore=0 suspectscore=0 mlxscore=0 classifier=spam adjust=0 reason=mlx scancount=1 engine=8.12.0-2308100000 definitions=main-2312110143'; 
                               
                                 
                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Open-Xchange Mailer v7.10.6-Rev39=22?=';


                              
                            } elseif ($settings['DomainName'] === 'Office') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                  } else {
                                    $mail->Sender = $fmail;
                                }
                                if ($settings['encodeSubject']) {
                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($sender_inbox['subject']) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }
                                $CHARS9 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 9);
                                $CHARS8 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                $CHARS7 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 7);
                                $CHARS6 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                                $CHARS5 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);
                                $CHARS4 = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);

                               	if (!function_exists('generateRandomString')) {
                                function generateRandomString($length = 32)
                                {
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                                    $charactersLength = strlen($characters);
                                    $randomString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randomString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randomString;
                                }
							}
								if (!function_exists('generateRandString')) {
                                function generateRandString($length = 36)
                                {
                                    $characters = '0123456789abcdef';
                                    $charactersLength = strlen($characters);
                                    $randString = '';
                                    for ($i = 0; $i < $length; $i++) {
                                        $randString .= $characters[rand(0, $charactersLength - 1)];
                                    }
                                    return $randString;
                                }
								}

                                
                                $randString = generateRandString();


                                
                                $randomString = generateRandomString();
                                
								 if (!function_exists('generateReceivedHeader')) {
                                  function generateReceivedHeader($index, $randIPs, $randUpper7, $hDate)
                                {
                                    return "Received: from mx{$index}.comcast.net (mx{$index}.comcast.net [{$randIPs}]) by comcast.net (Postfix) with ESMTP id {$randUpper7}; {$hDate}";
                                }
								 }
								if (!function_exists('generateRandomIPs')) {
                                function generateRandomIPs()
                                {
                                    return implode('.', array_map(function () {
                                        return mt_rand(0, 255);
                                    }, range(1, 4)));
                                }

								}
								
                                $randUpper7 = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 7)); 
                                $hDate = gmdate('D, d M Y H:i:s') . ' GMT'; 

                                $headers = [];
                                for ($i = 0; $i <= 8; $i++) {
                                    $randIPs = generateRandomIPs();
                                    $headers[] = generateReceivedHeader($i, $randIPs, $randUpper7, $hDate);
                                }

                                $headers[] = 'Content-Type: text/html; charset=utf-8';
                                $headers[] = 'Content-Transfer-Encoding: quoted-printable';
                                $headers[] = 'Message-ID: <' . uniqid('', true) . '@comcast.net>'; 
                                $headers[] = 'Return-Path: ' . $fmail; 
                                $headers[] = 'List-Unsubscribe: <unsubscribe-link@office.com>';
                                $headers[] = 'List-ID: Your mailing list unique identifier <list.office.com>';
                                $headers[] = 'X-Mailer: =?UTF-8?Q?=22Your_Custom_Mailer=22?='; 
                                $headers[] = 'X-Complaints-To: abuse@office.com';
                                $headers[] = 'X-Postmaster: postmaster@office.com';
                                $headers[] = 'X-Priority: =?UTF-8?Q?=221_=28Highest=29=22?='; 
                                $headers[] = 'Precedence: bulk';
                                $headers[] = 'Auto-Submitted: auto-generated';
                                $headers[] = 'X-Msmail-Priority: =?UTF-8?Q?=22High=22?='; 
                                $headers[] = 'MIME-Version: 1.0';
                                $headers[] = 'Priority: normal';
                                $headers[] = 'Importance: normal';
                                $headers[] = 'Reply-To: ' . $fname . ' <' . $fmail . '>'; 
                                $headers[] = 'In-Reply-To: <previous-message-id@smtp.comcast.net>';
                                $headers[] = 'References: <previous-message-id@smtp.comcast.net>';
                                $headers[] = 'Return-Receipt-To: read-receipt@office.com';
                                $headers[] = 'Disposition-Notification-To: read-receipt@office.com';
                                $headers[] = 'X-Unsubscribe: <unsubscribe-link@office.com>';
                                $headers[] = 'X-Confirm-Reading-To: read-receipt@office.com';
                                $headers[] = 'X-Auto-Response-Suppress: =?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?='; 
                                $headers[] = 'X-Report-Abuse: report-abuse@office.com';
                                $headers[] = 'X-Bulk: bulk';
                                $headers[] = 'X-Spam-Status: No, score=-2.7';
                                $headers[] = 'X-Spam-Score: -2.7';
                                $headers[] = 'X-Spam-Bar: /';
                                $headers[] = 'X-Spam-Flag: NO';

                                foreach ($headers as $header) {

                                    $mail->addCustomHeader($header);
                                }

                                $mail->XMailer = '?UTF-8?Q?=22Your_Custom_Mailer=22?=';

                               
                            } elseif ($settings['DomainName'] === 'Godaddy') {

                                if ($settings['randSender'] == true) {

                                    $domainsmtp = "mail.263.net";
                                    $mylength = rand(15, 30);
                                    $mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, $mylength) . "-communication@" . $domainsmtp;
                                    	   } else {
                                    $mail->Sender = $fmail;
                                }

                                if ($settings['encodeSubject']) {
                                    $mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
                                } else {
                                    $mail->Subject = $subject;
                                }

                                if ($settings['encodeFromInfo']) {
                                    $mail->setFrom($fmail,  '=?UTF-8?B?' . base64_encode($fname) . '?=');
                                } else {
                                    $mail->setFrom($fmail, $fname);
                                }

                                $headers = [];
                                $headers[] = 'Date: ' . date('D, d M Y H:i:s O');
                                $headers[] = 'From: ' . $fname . ' <' . $fmail . '>';
                                $headers[] = 'To:' . $fname . '<'. $fname .'>';
                                $headers[] = 'Message-ID: <' . uniqid('', true) . '@connect.xfinity.com>';
                                $headers[] = 'Subject:' . $mail->Subject;
                                $headers[] = 'MIME-Version: 1.0';
                                $headers[] = 'X-Mailer: Open-Xchange Mailer v7.10.6-Rev39';
                                $headers[] = 'X-Originating-Client: open-xchange-appsuite';
                                $headers[] = 'Return-Path: phisin101@comcast.net';


                                foreach ($headers as $header) {
                                    $mail->addCustomHeader($header);
                                }

                                $randomIPv4 = generateIPv4();
                                $randomIPv6 = generateIPv6FromIPv4($randomIPv4);
                                
                                $mail->addCustomHeader('X-Originating-IP: ' . $randomIPv6);
                                $mail->XMailer = 'Open-Xchange Mailer v7.10.6-Rev39';

                              
                            } elseif (empty($domainSettings['DomainName'])) {

                                echo "\033[31m\t\t\nSelect The Domain your sending to\n\n\n";
                                exit;
                            } else {

                                echo "\033[31m\t\t\nInvalid Input\n\n\n";
                                exit;
                            }




                            $mail->isHTML(true);

                            $mail->Body = $letter; 


                            if (!function_exists('handleFailure')) {
                                function handleFailure($errorMessage, $email, $domainSettings, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile)
                                {
                                    
                                    if (strpos($errorMessage, 'Could not connect to SMTP host') !== false) {
                                        
                                        $consecutiveFailures++;
                                    }

                                    $errorMessage = strtolower($errorMessage);

                                    if ($domainSettings['DomainName'] === 'Comcast') {
										$specificErrorPhrases = ['could not connect', 'could not authenticate', 'too many', 'data not accepted', 'please go to'];
									} elseif ($domainSettings['DomainName'] === 'Cox') {
										$specificErrorPhrases = ['could not connect', 'could not authenticate', 'too many', 'try again later'];
									} elseif ($domainSettings['DomainName'] === 'Optimum') {
										$specificErrorPhrases = ['could not connect', 'could not authenticate', 'too many', 'try again later', 'data not accepted'];
									} else {
										
										 $specificErrorPhrases = ['could not connect', 'could not authenticate', 'too many', 'try again later', 'data not accepted', 'please go to'];
									}
                                   
                                    $isSpecificError = false;

                                    foreach ($specificErrorPhrases as $phrase) {
                                        if (strpos($errorMessage, strtolower($phrase)) !== false) {
                                            $isSpecificError = true;
                                            break;
                                        }
                                    }

                                    
                                    if ($isSpecificError) {
                                        file_put_contents($failedEmailsFile, $email . PHP_EOL, FILE_APPEND);
                                    } else {
                                        file_put_contents($badlistFile, $email . PHP_EOL, FILE_APPEND);
                                    }

                                    
                                    echo "\033[0;31mFailed to send email to:\033[0m \033[0;31m$email Error: $errorMessage\033[0m\n";

                                    
                                    if ($consecutiveFailures >= $maxConsecutiveFailures) {
                                        echo "\033[0;37mToo many consecutive failures. Stopping further email sends.\033[0m\n";


                                        exit;
                                    }
                                }
                            }

                            

                            try {
                                if ($mail->send()) {
                                    
                                    $consecutiveFailures = 0;
                                    $emailsSent++;

                                     if (isset($settings['waitAfter'])) {
                                            if (is_numeric($settings['waitAfter']) && (int)$settings['waitAfter'] !== 0 && $emailsSent % (int)$settings['waitAfter'] === 0) {
                                                echo "Sent {$settings['waitAfter']} emails. Waiting for {$settings['waitFor']} seconds...\n";
                                                sleep($settings['waitFor']);
                                            } else {
                                              
                                               
                                            }
                                        } else {
                                          
                                        }

                                    
                                    echo "\n\033[0;33mEmail sent successfully to:\033[0m \033[0;32m$email\033[0m\n";
                                    file_put_contents($sentEmailsFile, $email . PHP_EOL, FILE_APPEND);
                                    if (!file_exists($validEmailsFile) || !strpos(file_get_contents($validEmailsFile), $email)) {
                                        file_put_contents($validEmailsFile, $email . PHP_EOL, FILE_APPEND);
                                    }
                                } else {
                                    handleFailure($mail->ErrorInfo, $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile);
                                }
                            } catch (Exception $e) {
                                
                                $consecutiveFailures++;

                                handleFailure($e->getMessage(), $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile);
                            }
                                
                               


                            if (!empty($settings['sleepDuration']) && is_numeric($settings['sleepDuration'])) {
                                
                                $sleeptimer = $settings['sleepDuration'];
                                echo "Sleep For: \033[0;32m$sleeptimer seconds\033[0m";
                                
                                sleep(intval($settings['sleepDuration']));

                                

                            }


                            
                            $mail->clearAddresses();
                            $mail->clearCustomHeaders();
                        }
                    } else {
                    }

                    
                    exit();
                }
            }

            
            foreach ($pids as $pid) {
                pcntl_waitpid($pid, $status);
            }





            $recipientListFile = 'list/' . $settings['recipientListFile'];
            $failedEmailsFile = 'failed.txt';
            $sentEmailsFile = 'pass.txt';
            $badlistFile = 'bad.txt';

            function filterRecipientList($recipientListFile, $sentEmailsFile, $badlistFile)
            {
                $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $sentEmails = [];
                $badlist = [];

                if (file_exists($sentEmailsFile)) {
                    $sentEmails = file($sentEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                }

                if (file_exists($badlistFile)) {
                    $badlist = file($badlistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                }

                $combinedEmails = array_merge($sentEmails ?: [], $badlist ?: []);
                $combinedEmails = array_unique($combinedEmails); 

                $RecipientListForFilter = array_diff($recipientList, $combinedEmails);

                
                file_put_contents("Resend.txt", implode(PHP_EOL, $RecipientListForFilter) . PHP_EOL);
            }

            
            filterRecipientList($recipientListFile, $sentEmailsFile, $badlistFile);

            
            
            function countLines($file)
            {
                if (file_exists($file)) {
                    $lines = file($file);
                    if ($lines === false) {
                        return 0; 
                    } else {
                        return count($lines);
                    }
                } else {
                    return 0; 
                }
            }

            $failedEmailsFile = 'failed.txt';
            $sentEmailsFile = 'pass.txt';
            $badlistFile = 'bad.txt';
            $recipientListFile = 'list/' . $settings['recipientListFile']; 

            $failedEmailsCount = countLines($failedEmailsFile);
            $sentEmailsCount = countLines($sentEmailsFile);
            $badEmailsCount = countLines($badlistFile);
            $recipientListFileCount = countLines($recipientListFile);

            
            $totalProcessedEmails = $failedEmailsCount + $sentEmailsCount + $badEmailsCount;

            echo "\nOriginal Email Count: $recipientListFileCount\n";
            echo "Total Processed Email Count: $totalProcessedEmails\n";


            if ($totalProcessedEmails === $recipientListFileCount) {
                if (file_exists($sentEmailsFile)) {
                    $sentEmails = array_unique(file($sentEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No email was sent\n";
                }

                if (file_exists($badlistFile)) {
                    $badEmails = array_unique(file($badlistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No Bad emails\n";
                }
                if (file_exists($failedEmailsFile)) {
                    $failedEmails = array_unique(file($failedEmailsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
                } else {
                    echo "No Failed emails\n";
                }

                if (empty($failedEmails) && !empty($sentEmails)) {
                    $sentCount = count($sentEmails);
                    echo "\n\033[0;32mAll Emails Sent Successfully. Sent Count: $sentCount\033[0m\n";
                } elseif (!empty($failedEmails)) {
                    $failedCount = count($failedEmails);
                    echo "\n\033[0;33mNot all emails were sent. Resend emails in Resend.txt ONLY. Failed Count: $failedCount\033[0m\n";
                } elseif (empty($sentEmails)) {
                    echo "\n\033[0;33mNo emails were sent.\033[0m\n";
                }
            } else {
                $unsentCount = $recipientListFileCount - $sentEmailsCount - $badEmailsCount;
                echo "\n\033[0;33mNot all emails have been sent due to errors.Resend emails in Resend.txt ONLY. Unsent Count: $unsentCount\033[0m\n";
            }
            
           $resendContent = file_get_contents("Resend.txt");
           $checker = !empty(preg_replace('/\s+/', '', $resendContent));

            if ($checker !== false) {
                $recipientListFile = 'list/' . $settings['recipientListFile'];
            
               
                    $listContent = file_get_contents($recipientListFile);
            
                    if ($listContent !== false) {
                        file_put_contents($recipientListFile, $resendContent);
                        file_put_contents("list/list_backup.txt", $listContent);
                        
                        $lineCounts = count(explode(PHP_EOL, $listContent)); 
                        
                        echo "\n\033[0;32mList.txt successfully updated with the content of Resend.txt.\033[0m\n";
                       
        
                       
                       
                    } else {
                        echo "\033[0;31mFailed to read the content of list.txt.\033[0m\n";
                       exit;
                    }
                   
            } 
            
           
               


        }
        

    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
    
} else {
    echo "\033[0;31mUser does not exist or credentials are incorrect. contact S1L3NT_T0RTUG3R \033[0m\n";
}
 
?>