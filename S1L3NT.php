#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';
require 'settings.php';

use Kreait\Firebase\Factory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
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
\033[0m");
// Rest of your script...
$serviceAccountUrl = 'https://cdn.jsdelivr.net/gh/zoeand101/S1_T0G/list/fb1.json';

// Use GuzzleHttp client to fetch the contents of the JSON file
$client = new Client();
$response = $client->get($serviceAccountUrl);
$jsonContent = (string) $response->getBody();

// Initialize Firebase
$factory = (new Factory)
    ->withServiceAccount($jsonContent)
    ->withDatabaseUri('https://f1ni-16ac3-default-rtdb.europe-west1.firebasedatabase.app');

$database = $factory->createDatabase();

function checkFirebaseUser($username, $password, $database) {
    $usersRef = $database->getReference('users');

    $users = $usersRef->getValue(); // Fetch all users

    if ($users === null) {
        return false; // No users found
    }
    $currentTimestamp = time(); // Get current UNIX timestamp
    

    foreach ($users as $userID => $userData) {
        if (isset($userData['username']) && isset($userData['password'])) {
            if ($userData['username'] === $username && $userData['password'] === $password) {
                // Check if the user has an expiration date or is an admin (no expiration date)
                if (isset($userData['expiration_timestamp'])) {
                $expirationTimestamp = intval($userData['expiration_timestamp']);

                    if ($expirationTimestamp >= $currentTimestamp) {
                        return 'valid'; // Return a specific value indicating valid and not expired
                    } else {
                        return 'expired'; // Return a specific value indicating the account is expired
                    }
                } else {
                    return 'admin'; // Return a specific value indicating an admin user
                }
            }
        }
    }

    return false; // User not found or incorrect credentials
}

// Function to hide password input
function hideInput($prompt = "Enter Password: ")
{
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        // For non-Windows systems
        echo $prompt;
        system('stty -echo');
        $password = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
        return $password;
    } else {
        // For Windows systems
        echo $prompt;
        $password = rtrim(fgets(STDIN), "\r\n");
        return $password;
    }
}

// Prompt for user input
echo "Enter username: ";
$username = trim(fgets(STDIN)); // Read user input for username

$password = hideInput("Enter password: ");

$userStatus = checkFirebaseUser($username, $password, $database);

if ($userStatus === 'valid') {
    echo "\033[1;33m\n\n\t\tValid credentials. Welcome $username.\n\n\n";
 // Continue with the rest of your script

    // Create a PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        
            $resends = 'resend.txt';
            $failedEmailsFile = 'failed.txt';
            $badlistFile = 'bad.txt';
            $sentEmailsFile = 'pass.txt';
            $validEmailsFile = 'valid.txt';
            
            // Function to empty files if they exist
            function emptyFileIfExists($file) {
                if (file_exists($file)) {
                    file_put_contents($file, ''); // Empty the file
                    echo "File '$file' emptied successfully.\n";
                } else {
                   
                }
            }
            
            // Empty the files if they exist
            emptyFileIfExists($resends);
            emptyFileIfExists($failedEmailsFile);
            emptyFileIfExists($badlistFile);
            emptyFileIfExists($sentEmailsFile);



        // Check if there are multiple SMTP configurations
     if (count($smtpSettings) > 1) {
		 
		 echo "\033[1;33mProceeding to send emails...\n";
         
$loop = React\EventLoop\Factory::create();
  
$settings = array_merge($recipientListSettings, $commonSettings, $customHeaderSettings);
$recipientListFile = 'list/' . $settings['recipientListFile'];  // Update the path as needed

$recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($settings['removeDuplicates']) {
    $recipientList = array_unique($recipientList);
}

if (empty($recipientList)) {
    throw new Exception('You must provide at least one recipient email address.');
}

function sendEmail($recipient, $smtpConfig, $retryCount = 2) {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = $smtpConfig['host'];
    $mail->Port       = $smtpConfig['port'];
    $mail->Username   = $smtpConfig['username'];
    $mail->Password   = $smtpConfig['password'];
    $mail->SMTPSecure = $smtpConfig['Auth'];
    $mail->Hostname   = $smtpConfig['Hostname'];
    $mail->SMTPAuth   = true;
    $mail->SMTPKeepAlive = true;
    $mail->Priority   = $smtpConfig['priority'];
    $mail->Encoding = $smtpConfig['encoding'];
    $mail->CharSet = $smtpConfig['charset'];
    $mail->addAddress(trim($recipient));
    
    
    
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
                         if ($settings['randSender'] == true) {
                        $domainsmtp = "xfinity.comcast.net";
                    	$mylength = rand(15,30);
                    	$mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'),1,$mylength)."communication@".$domainsmtp;
            //			
                    } else {
                       $mail->Sender = $fmail;
                    }
                        // Attachments
                        
                        
                        
                        if (!empty($settings['image_attachfile'])) {
                            $imageAttachmentPath = 'attachment/' . $settings['image_attachfile']; // Update the path as needed
                             if ($settings['displayimage'] == true) {
                             $mail->addEmbeddedImage($imageAttachmentPath, 'imgslet', $settings['image_attachname'], 'base64', 'image/jpeg, image/jpg, image/png');
                             }else{
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
                      	    
		        	    }else{
		    	    
		    	            $qrCode = new QrCode($commonSettings['qrlink']);
                            $qrCode->setLabel($commonSettings['qrlabel']);
                        
		            	}
				    
                             $qrCode->setSize(160); // Set the size of the QR code

                                // Get QR code image data as base64
                            $qrCodeBase64 = base64_encode($qrCode->writeString());
                            $label = '<div style="text-align:center;font-size:16px;font-weight:bold;">Scan Me</div>';
                            $qrCodeImage = '<img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="Scan Me QR Code" style="display:block;margin:0 auto;">';
            				
				
				
                               
                        
                       $imageBase64 = ''; // Initialize $imageBase64 variable

                        if (!empty($commonSettings['imageLetter'])) {
                            $imagePath = 'attachment/' . $commonSettings['imageLetter'];
                        
                            if (file_exists($imagePath)) {
                                $imageBase64 = base64_encode(file_get_contents($imagePath));
                            } else {
                                // Handle case when the file doesn't exist
                                echo "The image file doesn't exist at $imagePath";
                            }
                        }
                        
                        // Use $imageBase64 as needed, ensuring it contains valid data
                        $dataUri = !empty($imageBase64) ? 'data:image/png;base64,' . $imageBase64 : '';
                        
                                               
                        $char9 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,9);
        				$char8 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,8);
        				$char7 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,7);
        				$char6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,6);
        				$char5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,5);
        				$char4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,4);
        				$char3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,3);
        				$char2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,2);
        				$CHARs2 = substr(str_shuffle(strtoupper("ABCDEFGHIJKLMNOPQRSTUVWXYZ")),0,2);
        				$num9 = substr(str_shuffle("0123456789"),0,9);
        				$num4 = substr(str_shuffle("0123456789"),0,4);
        				$key64 = base64_encode($email);
        			
        
                                $letterFile = 'letter/' . $settings['letterFile']; // Update the path as needed
                                $letter = file_get_contents($letterFile) or die("Letter not found!");
                                $letter = str_ireplace("##char8##", $char8, $letter);
                                $letter = str_ireplace("##char7##", $char7, $letter);
                                $letter = str_ireplace("##char6##", $char6, $letter);
                                $letter = str_ireplace("##char5##", $char5, $letter);
                                $letter = str_ireplace("##char4##", $char4, $letter);
                                $letter = str_ireplace("##char3##", $char3, $letter);
                        
                                // ... (continue with your existing code)
                        
                        // Additional randomization features
                        
				          if ($commonSettings['randomparam'] == true) {
            		    		$letter = str_ireplace("##link##", $link[array_rand($link)].'?id='.generatestring('mix', 8, 'normal'), $letter);
            					$letter = str_ireplace("##char8##", $char8, $letter);
            					$letter = str_ireplace("##char7##", $char7, $letter);
            					$letter = str_ireplace("##char6##", $char6, $letter);
            					$letter = str_ireplace("##char5##", $char5, $letter);
            					$letter = str_ireplace("##char4##", $char4, $letter);
            					$letter = str_ireplace("##char3##", $char3, $letter);
            		            	}else{
            		    		$letter = str_ireplace("##link##", $link[array_rand($link)], $letter);
            		    		$letter = str_ireplace("##char8##", $char8, $letter);
            					$letter = str_ireplace("##char7##", $char7, $letter);
            					$letter = str_ireplace("##char6##", $char6, $letter);
            					$letter = str_ireplace("##char5##", $char5, $letter);
            					$letter = str_ireplace("##char4##", $char4, $letter);
            					$letter = str_ireplace("##char3##", $char3, $letter);
            					
            		    	}
                		    	$letter = str_ireplace("##date##", date('D, F d, Y  g:i A') , $letter);
                                $letter = str_ireplace("##date2##", date('D, F d, Y') , $letter);
                                $letter = str_ireplace("##date3##", date('F d, Y  g:i A') , $letter);
                                $letter = str_ireplace("##date4##", date('F d, Y') , $letter);
                				$letter = str_ireplace("##date5##", date('F d') , $letter);
                				$letter = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $letter);
                				$letter = str_ireplace("##email##", $email , $letter);
                				$letter = str_ireplace("##email64##", $key64 , $letter);
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
        
                	


                       // Replace placeholders in the subject with the current date
                        
                                $subject = str_ireplace("##date##", date('D, F d, Y  g:i A') , $subject);
                                $subject = str_ireplace("##date2##", date('D, F d, Y') , $subject);
                                $subject = str_ireplace("##date3##", date('F d, Y  g:i A') , $subject);
                                $subject = str_ireplace("##date4##", date('F d, Y') , $subject);
                				$subject = str_ireplace("##date5##", date('F d') , $subject);
                				$subject = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $subject);
                				$subject = str_ireplace("##email##", $email , $subject);
                				$subject = str_ireplace("##email64##", $key64 , $subject);
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
                    
                        // Set the subject
                       
                             // Check if the sender's email is valid
                        
			
                               
		       	        
                                
                                $fmail = str_ireplace("##domain##", $domains, $fmail);
                                $fmail = str_ireplace("##userid##", $userId, $fmail);
                                $fmail = str_ireplace("##relay##", $getsmtpUsername, $fmail);
                                $fmail = str_ireplace("##date##", date('D, F d, Y  g:i A') , $fmail);
                                $fmail = str_ireplace("##date2##", date('D, F d, Y') , $fmail);
                                $fmail = str_ireplace("##date3##", date('F d, Y  g:i A') , $fmail);
                                $fmail = str_ireplace("##date4##", date('F d, Y') , $fmail);
                				$fmail = str_ireplace("##date5##", date('F d') , $fmail);
                				$fmail = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $fmail);
                				$fmail = str_ireplace("##email##", $email , $fmail);
                				$fmail = str_ireplace("##email64##", $key64 , $fmail);
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
                                $fname = str_ireplace("##date##", date('D, F d, Y  g:i A') , $fname);
                                $fname = str_ireplace("##date2##", date('D, F d, Y') , $fname);
                                $fname = str_ireplace("##date3##", date('F d, Y  g:i A') , $fname);
                                $fname = str_ireplace("##date4##", date('F d, Y') , $fname);
                				$fname = str_ireplace("##date5##", date('F d') , $fname);
                				$fname = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $fname);
                				$fname = str_ireplace("##email##", $email , $fname);
                				$fname = str_ireplace("##email64##", $key64 , $fname);
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
                        
                  
                      	if (!function_exists('generateRandomEmail')) {
                                  function generateRandomEmail() {
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
                                 function generateRandomNumber() {
                                                    return mt_rand(1000000000, 9999999999); // Random number between 100000 and 999999
                                                }
                                                
                                    }            // Generate a random email
                                                $randomEmail = generateRandomEmail();
                                                
                                                // Generate a random number
                                                $randomNumber = generateRandomNumber();
                                                
                                                $randomIP = generateRandomIP();
                      	 
                                    



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
                                                    'In-Reply-To' => '<previous-message-id@smtp.comcast.net>',
                                                    'References' => '<previous-message-id@smtp.comcast.net>',
                                                    'X-Auto-Response-Suppress' => '=?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?=', 
                                                    'X-Mailer' => '=?UTF-8?Q?=22Your_Custom_Mailer=22?=',
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
                                             //       'X-Originating-IP' => $randomIP,
                                                    'To' => $email
                                                ];
                                                      
                                                
                                                            // Check if the customHeaders key exists and is an array
                            if (isset($customHeaderSettings['customHeaders']) && is_array($customHeaderSettings['customHeaders'])) {
                                // Retrieve the custom headers
                                $customHeaders = $customHeaderSettings['customHeaders'];
                            
                                // Merge fixed headers with custom headers
                                $allHeaders = array_merge($fixedHeaders, $customHeaders);
                            
                                // Loop through all merged headers
                                foreach ($allHeaders as $header => $value) {
                                    // Use $header and $value here as needed
                                   
                                    // For example, adding headers to PHPMailer
                                    $mail->addCustomHeader("$header: $value");
                                }
                            } else {
                                // If custom headers are not properly defined, use only the fixed headers
                                $allHeaders = $fixedHeaders;
                            
                                // Loop through fixed headers
                                foreach ($allHeaders as $header => $value) {
                                    // Use $header and $value here as needed
                                  
                                    // For example, adding headers to PHPMailer
                                    $mail->addCustomHeader("$header: $value");
                                }
                            }
                        
                        $mail->isHTML(true);
                        $mail->Body    = $letter;
     
                       
                        

    try {
        if ($mail->send()) {
            echo "Message sent successfully to $recipient using SMTP: {$smtpConfig['username']}\n";
            return true;
        } else {
            echo 'Mailer Error: ' . $mail->ErrorInfo . " to $recipient using SMTP: {$smtpConfig['username']}\n";
            if ($retryCount > 0) {
                echo "Retrying sending to $recipient using SMTP: {$smtpConfig['username']} (Retry Count: $retryCount)\n";
                return sendEmail($recipient, $smtpConfig, $retryCount - 1);
            } else {
                echo "Failed to send to $recipient after retries using SMTP: {$smtpConfig['username']}\n";
                return false;
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ' . $e->getMessage() . "\n";
        return false;
    }
}



$threadCount = isset($settings['threads']) && $settings['threads'] > 1 ? $settings['threads'] : 1;
echo "Thread count: $threadCount\n"; // Echo the thread count

$smtpCount = count($smtpSettings); // Initialize $smtpCount with the count of SMTP configurations
$smtpIndex = 0; // Initialize $smtpIndex

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
                sendEmail($recipient, $smtpConfig);
                $smtpIndex++;
            }
            exit(); // Exit the child process after sending emails in the chunk
        }
    }

    // Wait for child processes to finish
    foreach ($childProcesses as $pid) {
        pcntl_waitpid($pid, $status);
    }
} else {
    // Single-threaded logic as before
    foreach ($recipientList as $recipient) {
        $smtpConfig = $smtpSettings[$smtpIndex % $smtpCount];
        sendEmail($recipient, $smtpConfig);
        $smtpIndex++;
    }
}

$loop->run();

} else {
            // Use the only available SMTP configuration
            $settings = array_merge(reset($smtpSettings), $recipientListSettings, $customHeaderSettings, $commonSettings);

            // Use the number of threads specified in commonSettings or default to 1 if not provided
            $numThreads = empty($settings['threads']) ? 1 : intval($settings['threads']);

            // Load recipient list from file
            $recipientListFile = 'list/' . $settings['recipientListFile'];  // Update the path as needed
            $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Remove duplicates if specified
            if ($settings['removeDuplicates']) {
                $recipientList = array_unique($recipientList);
            }

            // Check if recipient list is empty or has at least one recipient
            if (empty($recipientList)) {
                throw new Exception('You must provide at least one recipient email address.');
            }
             
             $numEmails = count($recipientList);
                
                // Echo the number of emails
                echo "\n\033[1;33mNumber of emails: $numEmails";
                 echo "\n\033[1;33mNumber of smtp: 1\n";
                 echo "\n\033[1;33mProceeding to send emails...\n";
            // Divide the recipient list into chunks based on the number of threads
           $chunks = array_chunk($recipientList, max(1, ceil(count($recipientList) / $numThreads)));

// Create a separate process for each thread
            $pids = [];
            for ($i = 0; $i < $numThreads; $i++) {
                $pid = pcntl_fork();
            
                if ($pid == -1) {
                    die("Could not fork.\n");
                } elseif ($pid) {
                    // Parent process
                    $pids[] = $pid;
                } else {
                    // Child process
                    if (isset($chunks[$i]) && is_array($chunks[$i])) {
                        $start = $i * count($chunks[$i]);
                        $end = min(($i + 1) * count($chunks[$i]), count($recipientList));
            
                        // Load a new instance of PHPMailer in each thread
                        $mail = new PHPMailer(true);
                                
                                
         function generateRandomIP() {
             
                    return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
            
             }
             
             $maxConsecutiveFailures = $settings['ErrorHandling'];

// Counter to track consecutive failures
            $consecutiveFailures = 0;
            
         
            
                   // Load email addresses from the recipient list
            foreach ($chunks[$i] as $email) {
                        $mail->addAddress($email);
                
                         // Server settings
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
                         if ($settings['randSender'] == true) {
                        $domainsmtp = "xfinity.comcast.net";
                    	$mylength = rand(15,30);
                    	$mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'),1,$mylength)."communication@".$domainsmtp;
            //			
                    } else {
                       $mail->Sender = $fmail;
                    }
                        // Attachments
                        
                        
                        
                        if (!empty($settings['image_attachfile'])) {
                            $imageAttachmentPath = 'attachment/' . $settings['image_attachfile']; // Update the path as needed
                             if ($settings['displayimage'] == true) {
                             $mail->addEmbeddedImage($imageAttachmentPath, 'imgslet', $settings['image_attachname'], 'base64', 'image/jpeg, image/jpg, image/png');
                             }else{
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
                      	    
		        	    }else{
		    	    
		    	            $qrCode = new QrCode($commonSettings['qrlink']);
                            $qrCode->setLabel($commonSettings['qrlabel']);
                        
		            	}
				    
                             $qrCode->setSize(160); // Set the size of the QR code

                                // Get QR code image data as base64
                            $qrCodeBase64 = base64_encode($qrCode->writeString());
                            $label = '<div style="text-align:center;font-size:16px;font-weight:bold;">Scan Me</div>';
                            $qrCodeImage = '<img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="Scan Me QR Code" style="display:block;margin:0 auto;">';
            				
				
				
                               
                        
                       $imageBase64 = ''; // Initialize $imageBase64 variable

                        if (!empty($commonSettings['imageLetter'])) {
                            $imagePath = 'attachment/' . $commonSettings['imageLetter'];
                        
                            if (file_exists($imagePath)) {
                                $imageBase64 = base64_encode(file_get_contents($imagePath));
                            } else {
                                // Handle case when the file doesn't exist
                                echo "The image file doesn't exist at $imagePath";
                            }
                        }
                        
                        // Use $imageBase64 as needed, ensuring it contains valid data
                        $dataUri = !empty($imageBase64) ? 'data:image/png;base64,' . $imageBase64 : '';
                        
                                               
                        $char9 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,9);
        				$char8 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,8);
        				$char7 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,7);
        				$char6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,6);
        				$char5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,5);
        				$char4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,4);
        				$char3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,3);
        				$char2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,2);
        				$CHARs2 = substr(str_shuffle(strtoupper("ABCDEFGHIJKLMNOPQRSTUVWXYZ")),0,2);
        				$num9 = substr(str_shuffle("0123456789"),0,9);
        				$num4 = substr(str_shuffle("0123456789"),0,4);
        				$key64 = base64_encode($email);
        			
        
                                $letterFile = 'letter/' . $settings['letterFile']; // Update the path as needed
                                $letter = file_get_contents($letterFile) or die("Letter not found!");
                                $letter = str_ireplace("##char8##", $char8, $letter);
                                $letter = str_ireplace("##char7##", $char7, $letter);
                                $letter = str_ireplace("##char6##", $char6, $letter);
                                $letter = str_ireplace("##char5##", $char5, $letter);
                                $letter = str_ireplace("##char4##", $char4, $letter);
                                $letter = str_ireplace("##char3##", $char3, $letter);
                        
                                // ... (continue with your existing code)
                        
                        // Additional randomization features
                        
				          if ($commonSettings['randomparam'] == true) {
            		    		$letter = str_ireplace("##link##", $link[array_rand($link)].'?id='.generatestring('mix', 8, 'normal'), $letter);
            					$letter = str_ireplace("##char8##", $char8, $letter);
            					$letter = str_ireplace("##char7##", $char7, $letter);
            					$letter = str_ireplace("##char6##", $char6, $letter);
            					$letter = str_ireplace("##char5##", $char5, $letter);
            					$letter = str_ireplace("##char4##", $char4, $letter);
            					$letter = str_ireplace("##char3##", $char3, $letter);
            		            	}else{
            		    		$letter = str_ireplace("##link##", $link[array_rand($link)], $letter);
            		    		$letter = str_ireplace("##char8##", $char8, $letter);
            					$letter = str_ireplace("##char7##", $char7, $letter);
            					$letter = str_ireplace("##char6##", $char6, $letter);
            					$letter = str_ireplace("##char5##", $char5, $letter);
            					$letter = str_ireplace("##char4##", $char4, $letter);
            					$letter = str_ireplace("##char3##", $char3, $letter);
            					
            		    	}
                		    	$letter = str_ireplace("##date##", date('D, F d, Y  g:i A') , $letter);
                                $letter = str_ireplace("##date2##", date('D, F d, Y') , $letter);
                                $letter = str_ireplace("##date3##", date('F d, Y  g:i A') , $letter);
                                $letter = str_ireplace("##date4##", date('F d, Y') , $letter);
                				$letter = str_ireplace("##date5##", date('F d') , $letter);
                				$letter = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $letter);
                				$letter = str_ireplace("##email##", $email , $letter);
                				$letter = str_ireplace("##email64##", $key64 , $letter);
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
        
                	


                       // Replace placeholders in the subject with the current date
                        
                                $subject = str_ireplace("##date##", date('D, F d, Y  g:i A') , $subject);
                                $subject = str_ireplace("##date2##", date('D, F d, Y') , $subject);
                                $subject = str_ireplace("##date3##", date('F d, Y  g:i A') , $subject);
                                $subject = str_ireplace("##date4##", date('F d, Y') , $subject);
                				$subject = str_ireplace("##date5##", date('F d') , $subject);
                				$subject = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $subject);
                				$subject = str_ireplace("##email##", $email , $subject);
                				$subject = str_ireplace("##email64##", $key64 , $subject);
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
                    
                        // Set the subject
                       
                             // Check if the sender's email is valid
                        
			
                               
		       	        
                                
                                $fmail = str_ireplace("##domain##", $domains, $fmail);
                                $fmail = str_ireplace("##userid##", $userId, $fmail);
                                $fmail = str_ireplace("##relay##", $getsmtpUsername, $fmail);
                                $fmail = str_ireplace("##date##", date('D, F d, Y  g:i A') , $fmail);
                                $fmail = str_ireplace("##date2##", date('D, F d, Y') , $fmail);
                                $fmail = str_ireplace("##date3##", date('F d, Y  g:i A') , $fmail);
                                $fmail = str_ireplace("##date4##", date('F d, Y') , $fmail);
                				$fmail = str_ireplace("##date5##", date('F d') , $fmail);
                				$fmail = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $fmail);
                				$fmail = str_ireplace("##email##", $email , $fmail);
                				$fmail = str_ireplace("##email64##", $key64 , $fmail);
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
                                $fname = str_ireplace("##date##", date('D, F d, Y  g:i A') , $fname);
                                $fname = str_ireplace("##date2##", date('D, F d, Y') , $fname);
                                $fname = str_ireplace("##date3##", date('F d, Y  g:i A') , $fname);
                                $fname = str_ireplace("##date4##", date('F d, Y') , $fname);
                				$fname = str_ireplace("##date5##", date('F d') , $fname);
                				$fname = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $fname);
                				$fname = str_ireplace("##email##", $email , $fname);
                				$fname = str_ireplace("##email64##", $key64 , $fname);
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
                        
                  
                      	if (!function_exists('generateRandomEmail')) {
                                  function generateRandomEmail() {
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
                                 function generateRandomNumber() {
                                                    return mt_rand(1000000000, 9999999999); // Random number between 100000 and 999999
                                                }
                                                
                                    }            // Generate a random email
                                                $randomEmail = generateRandomEmail();
                                                
                                                // Generate a random number
                                                $randomNumber = generateRandomNumber();
                                                
                                                $randomIP = generateRandomIP();
                      	 
                                    



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
                                                    'In-Reply-To' => '<previous-message-id@smtp.comcast.net>',
                                                    'References' => '<previous-message-id@smtp.comcast.net>',
                                                    'X-Auto-Response-Suppress' => '=?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?=', 
                                                    'X-Mailer' => '=?UTF-8?Q?=22Your_Custom_Mailer=22?=',
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
                                             //       'X-Originating-IP' => $randomIP,
                                                    'To' => $email
                                                ];
                                                      
                                                
                                                            // Check if the customHeaders key exists and is an array
                            if (isset($customHeaderSettings['customHeaders']) && is_array($customHeaderSettings['customHeaders'])) {
                                // Retrieve the custom headers
                                $customHeaders = $customHeaderSettings['customHeaders'];
                            
                                // Merge fixed headers with custom headers
                                $allHeaders = array_merge($fixedHeaders, $customHeaders);
                            
                                // Loop through all merged headers
                                foreach ($allHeaders as $header => $value) {
                                    // Use $header and $value here as needed
                                   
                                    // For example, adding headers to PHPMailer
                                    $mail->addCustomHeader("$header: $value");
                                }
                            } else {
                                // If custom headers are not properly defined, use only the fixed headers
                                $allHeaders = $fixedHeaders;
                            
                                // Loop through fixed headers
                                foreach ($allHeaders as $header => $value) {
                                    // Use $header and $value here as needed
                                  
                                    // For example, adding headers to PHPMailer
                                    $mail->addCustomHeader("$header: $value");
                                }
                            }
                        
                        $mail->isHTML(true);
                        
                        $mail->Body = $letter; // Set the content of your email
                    
                                            
                        if (!function_exists('handleFailure')) {
                               function handleFailure($errorMessage, $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile) {
                        // Check if the error message contains "Could not connect to SMTP host"
                        if (strpos($errorMessage, 'Could not connect to SMTP host') !== false) {
                            // Increment the consecutive failures counter
                            $consecutiveFailures++;
                        }
                    
                        $errorMessage = strtolower($errorMessage);
                        
                        // Check if the error message contains specific phrases
                        $specificErrorPhrases = ['could not connect', 'could not authenticate','too many emails'];
                        
                        $isSpecificError = false;
                        
                        foreach ($specificErrorPhrases as $phrase) {
                            if (strpos($errorMessage, strtolower($phrase)) !== false) {
                                $isSpecificError = true;
                                break;
                            }
                        }
                        
                        // Log the email to the failedEmailsFile if it's a specific error, otherwise log it to the badlistFile
                        if ($isSpecificError) {
                            file_put_contents($failedEmailsFile, $email . PHP_EOL, FILE_APPEND);
                        } else {
                            file_put_contents($badlistFile, $email . PHP_EOL, FILE_APPEND);
                        }

                        // Print the email in red for failure
                        echo "\033[0;31mFailed to send email to:\033[0m \033[0;31m$email Error: $errorMessage\033[0m\n";
                    
                        // Check if consecutive failures have reached the limit
                        if ($consecutiveFailures >= $maxConsecutiveFailures) {
                            echo "\033[0;37mToo many consecutive failures. Stopping further email sends.\033[0m\n";
                    
                          
                            exit;
                        }
                    }
                            
                        }
                    
                    // Example usage within your main code with try-catch blocks
                    
                    try {
                        if ($mail->send()) {
                            // Reset the consecutive failures counter on successful email send
                            $consecutiveFailures = 0;
                    
                            // Print the email in green for success
                            echo "\n\033[0;33mEmail sent successfully to:\033[0m \033[0;32m$email\033[0m\n";
                            file_put_contents($sentEmailsFile, $email . PHP_EOL, FILE_APPEND);
                           if (!file_exists($validEmailsFile) || !strpos(file_get_contents($validEmailsFile), $email)) {
                                file_put_contents($validEmailsFile, $email . PHP_EOL, FILE_APPEND);
                            }
                        } else {
                            handleFailure($mail->ErrorInfo, $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile);
                        }
                    } catch (Exception $e) {
                        // Handle exceptions and increment the consecutive failures counter
                        $consecutiveFailures++;
                    
                        handleFailure($e->getMessage(), $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile);
                    }
                                            
                                            
                                            
                        if (!empty($settings['sleepDuration']) && is_numeric($settings['sleepDuration'])) {
                        // Retrieve sleep duration from settings
                        $sleeptimer = $settings['sleepDuration'];
                        echo "\nSleep For: \033[0;32m$sleeptimer seconds\033[0m\n";
                        // Use usleep to sleep for the specified duration in microseconds
                        sleep(intval($settings['sleepDuration']));
                    
                        // Output the sleep duration in green
                        
                    }
                    

                        // Clear recipients for the next iteration
                        $mail->clearAddresses();
                        $mail->clearCustomHeaders();
                        
                
                    }
                    
         
                    
                   } else {
    
}
               
                    // Exit the child process
                    exit();
                         
                }
                

                
            }

            // Wait for all child processes to finish
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
                        $combinedEmails = array_unique($combinedEmails); // Remove duplicates
                    
                        $RecipientListForFilter = array_diff($recipientList, $combinedEmails);
                    
                        // Save the new filtered recipient list to "resend.txt"
                        file_put_contents("resend.txt", implode(PHP_EOL, $RecipientListForFilter) . PHP_EOL);
                    }
                    
                    // Call function to filter recipient list
                    filterRecipientList($recipientListFile, $sentEmailsFile, $badlistFile);
                    
                    
                    // Function to count unique lines in a file
                    function countLines($file)
                    {
                        if (file_exists($file)) {
                            $lines = file($file);
                            if ($lines === false) {
                                return 0; // Unable to read the file
                            } else {
                                return count($lines);
                            }
                        } else {
                            return 0; // File doesn't exist
                        }
                    }
                    
                    $failedEmailsFile = 'failed.txt';
                    $sentEmailsFile = 'pass.txt';
                    $badlistFile = 'bad.txt';
                    $recipientListFile = 'list/' . $settings['recipientListFile']; // Update the path as needed
                    
                    $failedEmailsCount = countLines($failedEmailsFile);
                    $sentEmailsCount = countLines($sentEmailsFile);
                    $badEmailsCount = countLines($badlistFile);
                    $recipientListFileCount = countLines($recipientListFile);
                    
                    // Calculate the total processed emails
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
                            echo "\n\033[0;33mNot all emails were sent. Check failed.txt file for details. Failed Count: $failedCount\033[0m\n";
                        } elseif (empty($sentEmails)) {
                            echo "\n\033[0;33mNo emails were sent.\033[0m\n";
                        }
                    } else {
                        $unsentCount = $recipientListFileCount - $sentEmailsCount - $badEmailsCount;
                        echo "\n\033[0;33mNot all emails have been sent due to errors. Unsent Count: $unsentCount\033[0m\n";
                    }



            
            
        }
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
    
} elseif ($userStatus === 'expired') {
    echo "User account has expired. Contact S1L3NT_T0RTUG3R\n";
} elseif ($userStatus === 'admin') {
    echo "\033[1;33m\n\n\t\tWelcome Admin.\n\n";
    
    
    
    // Create a PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        
            $resends = 'resend.txt';
            $failedEmailsFile = 'failed.txt';
            $badlistFile = 'bad.txt';
            $sentEmailsFile = 'pass.txt';
            $validEmailsFile = 'valid.txt';
            
            // Function to empty files if they exist
            function emptyFileIfExists($file) {
                if (file_exists($file)) {
                    file_put_contents($file, ''); // Empty the file
                    echo "File '$file' emptied successfully.\n";
                } else {
                   
                }
            }
            
            // Empty the files if they exist
            emptyFileIfExists($resends);
            emptyFileIfExists($failedEmailsFile);
            emptyFileIfExists($badlistFile);
            emptyFileIfExists($sentEmailsFile);



        // Check if there are multiple SMTP configurations
     if (count($smtpSettings) > 1) {
		 
		 echo "\033[1;33mProceeding to send emails...\n";
         
$loop = React\EventLoop\Factory::create();
  
$settings = array_merge($recipientListSettings, $commonSettings, $customHeaderSettings);
$recipientListFile = 'list/' . $settings['recipientListFile'];  // Update the path as needed

$recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($settings['removeDuplicates']) {
    $recipientList = array_unique($recipientList);
}

if (empty($recipientList)) {
    throw new Exception('You must provide at least one recipient email address.');
}

function sendEmail($recipient, $smtpConfig, $retryCount = 2) {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = $smtpConfig['host'];
    $mail->Port       = $smtpConfig['port'];
    $mail->Username   = $smtpConfig['username'];
    $mail->Password   = $smtpConfig['password'];
    $mail->SMTPSecure = $smtpConfig['Auth'];
    $mail->Hostname   = $smtpConfig['Hostname'];
    $mail->SMTPAuth   = true;
    $mail->SMTPKeepAlive = true;
    $mail->Priority   = $smtpConfig['priority'];
    $mail->Encoding = $smtpConfig['encoding'];
    $mail->CharSet = $smtpConfig['charset'];
    $mail->addAddress(trim($recipient));
    
    
    
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
                         if ($settings['randSender'] == true) {
                        $domainsmtp = "xfinity.comcast.net";
                    	$mylength = rand(15,30);
                    	$mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'),1,$mylength)."communication@".$domainsmtp;
            //			
                    } else {
                       $mail->Sender = $fmail;
                    }
                        // Attachments
                        
                        
                        
                        if (!empty($settings['image_attachfile'])) {
                            $imageAttachmentPath = 'attachment/' . $settings['image_attachfile']; // Update the path as needed
                             if ($settings['displayimage'] == true) {
                             $mail->addEmbeddedImage($imageAttachmentPath, 'imgslet', $settings['image_attachname'], 'base64', 'image/jpeg, image/jpg, image/png');
                             }else{
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
                      	    
		        	    }else{
		    	    
		    	            $qrCode = new QrCode($commonSettings['qrlink']);
                            $qrCode->setLabel($commonSettings['qrlabel']);
                        
		            	}
				    
                             $qrCode->setSize(160); // Set the size of the QR code

                                // Get QR code image data as base64
                            $qrCodeBase64 = base64_encode($qrCode->writeString());
                            $label = '<div style="text-align:center;font-size:16px;font-weight:bold;">Scan Me</div>';
                            $qrCodeImage = '<img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="Scan Me QR Code" style="display:block;margin:0 auto;">';
            				
				
				
                               
                        
                       $imageBase64 = ''; // Initialize $imageBase64 variable

                        if (!empty($commonSettings['imageLetter'])) {
                            $imagePath = 'attachment/' . $commonSettings['imageLetter'];
                        
                            if (file_exists($imagePath)) {
                                $imageBase64 = base64_encode(file_get_contents($imagePath));
                            } else {
                                // Handle case when the file doesn't exist
                                echo "The image file doesn't exist at $imagePath";
                            }
                        }
                        
                        // Use $imageBase64 as needed, ensuring it contains valid data
                        $dataUri = !empty($imageBase64) ? 'data:image/png;base64,' . $imageBase64 : '';
                        
                                               
                        $char9 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,9);
        				$char8 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,8);
        				$char7 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,7);
        				$char6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,6);
        				$char5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,5);
        				$char4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,4);
        				$char3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,3);
        				$char2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,2);
        				$CHARs2 = substr(str_shuffle(strtoupper("ABCDEFGHIJKLMNOPQRSTUVWXYZ")),0,2);
        				$num9 = substr(str_shuffle("0123456789"),0,9);
        				$num4 = substr(str_shuffle("0123456789"),0,4);
        				$key64 = base64_encode($email);
        			
        
                                $letterFile = 'letter/' . $settings['letterFile']; // Update the path as needed
                                $letter = file_get_contents($letterFile) or die("Letter not found!");
                                $letter = str_ireplace("##char8##", $char8, $letter);
                                $letter = str_ireplace("##char7##", $char7, $letter);
                                $letter = str_ireplace("##char6##", $char6, $letter);
                                $letter = str_ireplace("##char5##", $char5, $letter);
                                $letter = str_ireplace("##char4##", $char4, $letter);
                                $letter = str_ireplace("##char3##", $char3, $letter);
                        
                                // ... (continue with your existing code)
                        
                        // Additional randomization features
                        
				          if ($commonSettings['randomparam'] == true) {
            		    		$letter = str_ireplace("##link##", $link[array_rand($link)].'?id='.generatestring('mix', 8, 'normal'), $letter);
            					$letter = str_ireplace("##char8##", $char8, $letter);
            					$letter = str_ireplace("##char7##", $char7, $letter);
            					$letter = str_ireplace("##char6##", $char6, $letter);
            					$letter = str_ireplace("##char5##", $char5, $letter);
            					$letter = str_ireplace("##char4##", $char4, $letter);
            					$letter = str_ireplace("##char3##", $char3, $letter);
            		            	}else{
            		    		$letter = str_ireplace("##link##", $link[array_rand($link)], $letter);
            		    		$letter = str_ireplace("##char8##", $char8, $letter);
            					$letter = str_ireplace("##char7##", $char7, $letter);
            					$letter = str_ireplace("##char6##", $char6, $letter);
            					$letter = str_ireplace("##char5##", $char5, $letter);
            					$letter = str_ireplace("##char4##", $char4, $letter);
            					$letter = str_ireplace("##char3##", $char3, $letter);
            					
            		    	}
                		    	$letter = str_ireplace("##date##", date('D, F d, Y  g:i A') , $letter);
                                $letter = str_ireplace("##date2##", date('D, F d, Y') , $letter);
                                $letter = str_ireplace("##date3##", date('F d, Y  g:i A') , $letter);
                                $letter = str_ireplace("##date4##", date('F d, Y') , $letter);
                				$letter = str_ireplace("##date5##", date('F d') , $letter);
                				$letter = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $letter);
                				$letter = str_ireplace("##email##", $email , $letter);
                				$letter = str_ireplace("##email64##", $key64 , $letter);
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
        
                	


                       // Replace placeholders in the subject with the current date
                        
                                $subject = str_ireplace("##date##", date('D, F d, Y  g:i A') , $subject);
                                $subject = str_ireplace("##date2##", date('D, F d, Y') , $subject);
                                $subject = str_ireplace("##date3##", date('F d, Y  g:i A') , $subject);
                                $subject = str_ireplace("##date4##", date('F d, Y') , $subject);
                				$subject = str_ireplace("##date5##", date('F d') , $subject);
                				$subject = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $subject);
                				$subject = str_ireplace("##email##", $email , $subject);
                				$subject = str_ireplace("##email64##", $key64 , $subject);
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
                    
                        // Set the subject
                       
                             // Check if the sender's email is valid
                        
			
                               
		       	        
                                
                                $fmail = str_ireplace("##domain##", $domains, $fmail);
                                $fmail = str_ireplace("##userid##", $userId, $fmail);
                                $fmail = str_ireplace("##relay##", $getsmtpUsername, $fmail);
                                $fmail = str_ireplace("##date##", date('D, F d, Y  g:i A') , $fmail);
                                $fmail = str_ireplace("##date2##", date('D, F d, Y') , $fmail);
                                $fmail = str_ireplace("##date3##", date('F d, Y  g:i A') , $fmail);
                                $fmail = str_ireplace("##date4##", date('F d, Y') , $fmail);
                				$fmail = str_ireplace("##date5##", date('F d') , $fmail);
                				$fmail = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $fmail);
                				$fmail = str_ireplace("##email##", $email , $fmail);
                				$fmail = str_ireplace("##email64##", $key64 , $fmail);
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
                                $fname = str_ireplace("##date##", date('D, F d, Y  g:i A') , $fname);
                                $fname = str_ireplace("##date2##", date('D, F d, Y') , $fname);
                                $fname = str_ireplace("##date3##", date('F d, Y  g:i A') , $fname);
                                $fname = str_ireplace("##date4##", date('F d, Y') , $fname);
                				$fname = str_ireplace("##date5##", date('F d') , $fname);
                				$fname = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $fname);
                				$fname = str_ireplace("##email##", $email , $fname);
                				$fname = str_ireplace("##email64##", $key64 , $fname);
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
                        
                  
                      	if (!function_exists('generateRandomEmail')) {
                                  function generateRandomEmail() {
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
                                 function generateRandomNumber() {
                                                    return mt_rand(1000000000, 9999999999); // Random number between 100000 and 999999
                                                }
                                                
                                    }            // Generate a random email
                                                $randomEmail = generateRandomEmail();
                                                
                                                // Generate a random number
                                                $randomNumber = generateRandomNumber();
                                                
                                                $randomIP = generateRandomIP();
                      	 
                                    



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
                                                    'In-Reply-To' => '<previous-message-id@smtp.comcast.net>',
                                                    'References' => '<previous-message-id@smtp.comcast.net>',
                                                    'X-Auto-Response-Suppress' => '=?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?=', 
                                                    'X-Mailer' => '=?UTF-8?Q?=22Your_Custom_Mailer=22?=',
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
                                             //       'X-Originating-IP' => $randomIP,
                                                    'To' => $email
                                                ];
                                                      
                                                
                                                            // Check if the customHeaders key exists and is an array
                            if (isset($customHeaderSettings['customHeaders']) && is_array($customHeaderSettings['customHeaders'])) {
                                // Retrieve the custom headers
                                $customHeaders = $customHeaderSettings['customHeaders'];
                            
                                // Merge fixed headers with custom headers
                                $allHeaders = array_merge($fixedHeaders, $customHeaders);
                            
                                // Loop through all merged headers
                                foreach ($allHeaders as $header => $value) {
                                    // Use $header and $value here as needed
                                   
                                    // For example, adding headers to PHPMailer
                                    $mail->addCustomHeader("$header: $value");
                                }
                            } else {
                                // If custom headers are not properly defined, use only the fixed headers
                                $allHeaders = $fixedHeaders;
                            
                                // Loop through fixed headers
                                foreach ($allHeaders as $header => $value) {
                                    // Use $header and $value here as needed
                                  
                                    // For example, adding headers to PHPMailer
                                    $mail->addCustomHeader("$header: $value");
                                }
                            }
                        
                        $mail->isHTML(true);
                        $mail->Body    = $letter;
     
                       
                        

    try {
        if ($mail->send()) {
            echo "Message sent successfully to $recipient using SMTP: {$smtpConfig['username']}\n";
            return true;
        } else {
            echo 'Mailer Error: ' . $mail->ErrorInfo . " to $recipient using SMTP: {$smtpConfig['username']}\n";
            if ($retryCount > 0) {
                echo "Retrying sending to $recipient using SMTP: {$smtpConfig['username']} (Retry Count: $retryCount)\n";
                return sendEmail($recipient, $smtpConfig, $retryCount - 1);
            } else {
                echo "Failed to send to $recipient after retries using SMTP: {$smtpConfig['username']}\n";
                return false;
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ' . $e->getMessage() . "\n";
        return false;
    }
}



$threadCount = isset($settings['threads']) && $settings['threads'] > 1 ? $settings['threads'] : 1;
echo "Thread count: $threadCount\n"; // Echo the thread count

$smtpCount = count($smtpSettings); // Initialize $smtpCount with the count of SMTP configurations
$smtpIndex = 0; // Initialize $smtpIndex

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
                sendEmail($recipient, $smtpConfig);
                $smtpIndex++;
            }
            exit(); // Exit the child process after sending emails in the chunk
        }
    }

    // Wait for child processes to finish
    foreach ($childProcesses as $pid) {
        pcntl_waitpid($pid, $status);
    }
} else {
    // Single-threaded logic as before
    foreach ($recipientList as $recipient) {
        $smtpConfig = $smtpSettings[$smtpIndex % $smtpCount];
        sendEmail($recipient, $smtpConfig);
        $smtpIndex++;
    }
}

$loop->run();

} else {
            // Use the only available SMTP configuration
            $settings = array_merge(reset($smtpSettings), $recipientListSettings, $customHeaderSettings, $commonSettings);

            // Use the number of threads specified in commonSettings or default to 1 if not provided
            $numThreads = empty($settings['threads']) ? 1 : intval($settings['threads']);

            // Load recipient list from file
            $recipientListFile = 'list/' . $settings['recipientListFile'];  // Update the path as needed
            $recipientList = file($recipientListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Remove duplicates if specified
            if ($settings['removeDuplicates']) {
                $recipientList = array_unique($recipientList);
            }

            // Check if recipient list is empty or has at least one recipient
            if (empty($recipientList)) {
                throw new Exception('You must provide at least one recipient email address.');
            }
             
             $numEmails = count($recipientList);
                
                // Echo the number of emails
                echo "\n\033[1;33mNumber of emails: $numEmails";
                 echo "\n\033[1;33mNumber of smtp: 1\n";
                 echo "\n\033[1;33mProceeding to send emails...\n";
            // Divide the recipient list into chunks based on the number of threads
           $chunks = array_chunk($recipientList, max(1, ceil(count($recipientList) / $numThreads)));

// Create a separate process for each thread
            $pids = [];
            for ($i = 0; $i < $numThreads; $i++) {
                $pid = pcntl_fork();
            
                if ($pid == -1) {
                    die("Could not fork.\n");
                } elseif ($pid) {
                    // Parent process
                    $pids[] = $pid;
                } else {
                    // Child process
                    if (isset($chunks[$i]) && is_array($chunks[$i])) {
                        $start = $i * count($chunks[$i]);
                        $end = min(($i + 1) * count($chunks[$i]), count($recipientList));
            
                        // Load a new instance of PHPMailer in each thread
                        $mail = new PHPMailer(true);
                                
                                
         function generateRandomIP() {
             
                    return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255);
            
             }
             
             $maxConsecutiveFailures = $settings['ErrorHandling'];

// Counter to track consecutive failures
            $consecutiveFailures = 0;
            
         
            
                   // Load email addresses from the recipient list
            foreach ($chunks[$i] as $email) {
                        $mail->addAddress($email);
                
                         // Server settings
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
                         if ($settings['randSender'] == true) {
                        $domainsmtp = "xfinity.comcast.net";
                    	$mylength = rand(15,30);
                    	$mail->Sender = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'),1,$mylength)."communication@".$domainsmtp;
            //			
                    } else {
                       $mail->Sender = $fmail;
                    }
                        // Attachments
                        
                        
                        
                        if (!empty($settings['image_attachfile'])) {
                            $imageAttachmentPath = 'attachment/' . $settings['image_attachfile']; // Update the path as needed
                             if ($settings['displayimage'] == true) {
                             $mail->addEmbeddedImage($imageAttachmentPath, 'imgslet', $settings['image_attachname'], 'base64', 'image/jpeg, image/jpg, image/png');
                             }else{
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
                      	    
		        	    }else{
		    	    
		    	            $qrCode = new QrCode($commonSettings['qrlink']);
                            $qrCode->setLabel($commonSettings['qrlabel']);
                        
		            	}
				    
                             $qrCode->setSize(160); // Set the size of the QR code

                                // Get QR code image data as base64
                            $qrCodeBase64 = base64_encode($qrCode->writeString());
                            $label = '<div style="text-align:center;font-size:16px;font-weight:bold;">Scan Me</div>';
                            $qrCodeImage = '<img src="data:image/png;base64,' . $qrCodeBase64 . '" alt="Scan Me QR Code" style="display:block;margin:0 auto;">';
            				
				
				
                               
                        
                       $imageBase64 = ''; // Initialize $imageBase64 variable

                        if (!empty($commonSettings['imageLetter'])) {
                            $imagePath = 'attachment/' . $commonSettings['imageLetter'];
                        
                            if (file_exists($imagePath)) {
                                $imageBase64 = base64_encode(file_get_contents($imagePath));
                            } else {
                                // Handle case when the file doesn't exist
                                echo "The image file doesn't exist at $imagePath";
                            }
                        }
                        
                        // Use $imageBase64 as needed, ensuring it contains valid data
                        $dataUri = !empty($imageBase64) ? 'data:image/png;base64,' . $imageBase64 : '';
                        
                                               
                        $char9 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,9);
        				$char8 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,8);
        				$char7 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,7);
        				$char6 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,6);
        				$char5 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,5);
        				$char4 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,4);
        				$char3 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,3);
        				$char2 = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"),0,2);
        				$CHARs2 = substr(str_shuffle(strtoupper("ABCDEFGHIJKLMNOPQRSTUVWXYZ")),0,2);
        				$num9 = substr(str_shuffle("0123456789"),0,9);
        				$num4 = substr(str_shuffle("0123456789"),0,4);
        				$key64 = base64_encode($email);
        			
        
                                $letterFile = 'letter/' . $settings['letterFile']; // Update the path as needed
                                $letter = file_get_contents($letterFile) or die("Letter not found!");
                                $letter = str_ireplace("##char8##", $char8, $letter);
                                $letter = str_ireplace("##char7##", $char7, $letter);
                                $letter = str_ireplace("##char6##", $char6, $letter);
                                $letter = str_ireplace("##char5##", $char5, $letter);
                                $letter = str_ireplace("##char4##", $char4, $letter);
                                $letter = str_ireplace("##char3##", $char3, $letter);
                        
                                // ... (continue with your existing code)
                        
                        // Additional randomization features
                        
				          if ($commonSettings['randomparam'] == true) {
            		    		$letter = str_ireplace("##link##", $link[array_rand($link)].'?id='.generatestring('mix', 8, 'normal'), $letter);
            					$letter = str_ireplace("##char8##", $char8, $letter);
            					$letter = str_ireplace("##char7##", $char7, $letter);
            					$letter = str_ireplace("##char6##", $char6, $letter);
            					$letter = str_ireplace("##char5##", $char5, $letter);
            					$letter = str_ireplace("##char4##", $char4, $letter);
            					$letter = str_ireplace("##char3##", $char3, $letter);
            		            	}else{
            		    		$letter = str_ireplace("##link##", $link[array_rand($link)], $letter);
            		    		$letter = str_ireplace("##char8##", $char8, $letter);
            					$letter = str_ireplace("##char7##", $char7, $letter);
            					$letter = str_ireplace("##char6##", $char6, $letter);
            					$letter = str_ireplace("##char5##", $char5, $letter);
            					$letter = str_ireplace("##char4##", $char4, $letter);
            					$letter = str_ireplace("##char3##", $char3, $letter);
            					
            		    	}
                		    	$letter = str_ireplace("##date##", date('D, F d, Y  g:i A') , $letter);
                                $letter = str_ireplace("##date2##", date('D, F d, Y') , $letter);
                                $letter = str_ireplace("##date3##", date('F d, Y  g:i A') , $letter);
                                $letter = str_ireplace("##date4##", date('F d, Y') , $letter);
                				$letter = str_ireplace("##date5##", date('F d') , $letter);
                				$letter = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $letter);
                				$letter = str_ireplace("##email##", $email , $letter);
                				$letter = str_ireplace("##email64##", $key64 , $letter);
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
        
                	


                       // Replace placeholders in the subject with the current date
                        
                                $subject = str_ireplace("##date##", date('D, F d, Y  g:i A') , $subject);
                                $subject = str_ireplace("##date2##", date('D, F d, Y') , $subject);
                                $subject = str_ireplace("##date3##", date('F d, Y  g:i A') , $subject);
                                $subject = str_ireplace("##date4##", date('F d, Y') , $subject);
                				$subject = str_ireplace("##date5##", date('F d') , $subject);
                				$subject = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $subject);
                				$subject = str_ireplace("##email##", $email , $subject);
                				$subject = str_ireplace("##email64##", $key64 , $subject);
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
                    
                        // Set the subject
                       
                             // Check if the sender's email is valid
                        
			
                               
		       	        
                                
                                $fmail = str_ireplace("##domain##", $domains, $fmail);
                                $fmail = str_ireplace("##userid##", $userId, $fmail);
                                $fmail = str_ireplace("##relay##", $getsmtpUsername, $fmail);
                                $fmail = str_ireplace("##date##", date('D, F d, Y  g:i A') , $fmail);
                                $fmail = str_ireplace("##date2##", date('D, F d, Y') , $fmail);
                                $fmail = str_ireplace("##date3##", date('F d, Y  g:i A') , $fmail);
                                $fmail = str_ireplace("##date4##", date('F d, Y') , $fmail);
                				$fmail = str_ireplace("##date5##", date('F d') , $fmail);
                				$fmail = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $fmail);
                				$fmail = str_ireplace("##email##", $email , $fmail);
                				$fmail = str_ireplace("##email64##", $key64 , $fmail);
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
                                $fname = str_ireplace("##date##", date('D, F d, Y  g:i A') , $fname);
                                $fname = str_ireplace("##date2##", date('D, F d, Y') , $fname);
                                $fname = str_ireplace("##date3##", date('F d, Y  g:i A') , $fname);
                                $fname = str_ireplace("##date4##", date('F d, Y') , $fname);
                				$fname = str_ireplace("##date5##", date('F d') , $fname);
                				$fname = str_ireplace("##48hrs##", date('F j, Y', strtotime('+48 hours')) , $fname);
                				$fname = str_ireplace("##email##", $email , $fname);
                				$fname = str_ireplace("##email64##", $key64 , $fname);
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
                        
                  
                      	if (!function_exists('generateRandomEmail')) {
                                  function generateRandomEmail() {
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
                                 function generateRandomNumber() {
                                                    return mt_rand(1000000000, 9999999999); // Random number between 100000 and 999999
                                                }
                                                
                                    }            // Generate a random email
                                                $randomEmail = generateRandomEmail();
                                                
                                                // Generate a random number
                                                $randomNumber = generateRandomNumber();
                                                
                                                $randomIP = generateRandomIP();
                      	 
                                    



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
                                                    'In-Reply-To' => '<previous-message-id@smtp.comcast.net>',
                                                    'References' => '<previous-message-id@smtp.comcast.net>',
                                                    'X-Auto-Response-Suppress' => '=?UTF-8?Q?=22OOF=2C_DR=2C_RN=2C_NRN=2C_AutoReply?= =?UTF-8?Q?=22?=', 
                                                    'X-Mailer' => '=?UTF-8?Q?=22Your_Custom_Mailer=22?=',
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
                                             //       'X-Originating-IP' => $randomIP,
                                                    'To' => $email
                                                ];
                                                      
                                                
                                                            // Check if the customHeaders key exists and is an array
                            if (isset($customHeaderSettings['customHeaders']) && is_array($customHeaderSettings['customHeaders'])) {
                                // Retrieve the custom headers
                                $customHeaders = $customHeaderSettings['customHeaders'];
                            
                                // Merge fixed headers with custom headers
                                $allHeaders = array_merge($fixedHeaders, $customHeaders);
                            
                                // Loop through all merged headers
                                foreach ($allHeaders as $header => $value) {
                                    // Use $header and $value here as needed
                                   
                                    // For example, adding headers to PHPMailer
                                    $mail->addCustomHeader("$header: $value");
                                }
                            } else {
                                // If custom headers are not properly defined, use only the fixed headers
                                $allHeaders = $fixedHeaders;
                            
                                // Loop through fixed headers
                                foreach ($allHeaders as $header => $value) {
                                    // Use $header and $value here as needed
                                  
                                    // For example, adding headers to PHPMailer
                                    $mail->addCustomHeader("$header: $value");
                                }
                            }
                        
                        $mail->isHTML(true);
                        
                        $mail->Body = $letter; // Set the content of your email
                    
                                            
                        if (!function_exists('handleFailure')) {
                               function handleFailure($errorMessage, $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile) {
                        // Check if the error message contains "Could not connect to SMTP host"
                        if (strpos($errorMessage, 'Could not connect to SMTP host') !== false) {
                            // Increment the consecutive failures counter
                            $consecutiveFailures++;
                        }
                    
                        $errorMessage = strtolower($errorMessage);
                        
                        // Check if the error message contains specific phrases
                        $specificErrorPhrases = ['could not connect', 'could not authenticate','too many emails'];
                        
                        $isSpecificError = false;
                        
                        foreach ($specificErrorPhrases as $phrase) {
                            if (strpos($errorMessage, strtolower($phrase)) !== false) {
                                $isSpecificError = true;
                                break;
                            }
                        }
                        
                        // Log the email to the failedEmailsFile if it's a specific error, otherwise log it to the badlistFile
                        if ($isSpecificError) {
                            file_put_contents($failedEmailsFile, $email . PHP_EOL, FILE_APPEND);
                        } else {
                            file_put_contents($badlistFile, $email . PHP_EOL, FILE_APPEND);
                        }

                        // Print the email in red for failure
                        echo "\033[0;31mFailed to send email to:\033[0m \033[0;31m$email Error: $errorMessage\033[0m\n";
                    
                        // Check if consecutive failures have reached the limit
                        if ($consecutiveFailures >= $maxConsecutiveFailures) {
                            echo "\033[0;37mToo many consecutive failures. Stopping further email sends.\033[0m\n";
                    
                          
                            exit;
                        }
                    }
                            
                        }
                    
                    // Example usage within your main code with try-catch blocks
                    
                    try {
                        if ($mail->send()) {
                            // Reset the consecutive failures counter on successful email send
                            $consecutiveFailures = 0;
                    
                            // Print the email in green for success
                            echo "\n\033[0;33mEmail sent successfully to:\033[0m \033[0;32m$email\033[0m\n";
                            file_put_contents($sentEmailsFile, $email . PHP_EOL, FILE_APPEND);
                           if (!file_exists($validEmailsFile) || !strpos(file_get_contents($validEmailsFile), $email)) {
                                file_put_contents($validEmailsFile, $email . PHP_EOL, FILE_APPEND);
                            }
                        } else {
                            handleFailure($mail->ErrorInfo, $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile);
                        }
                    } catch (Exception $e) {
                        // Handle exceptions and increment the consecutive failures counter
                        $consecutiveFailures++;
                    
                        handleFailure($e->getMessage(), $email, $consecutiveFailures, $maxConsecutiveFailures, $failedEmailsFile, $badlistFile, $recipientList, $sentEmailsFile);
                    }
                                            
                                            
                                            
                        if (!empty($settings['sleepDuration']) && is_numeric($settings['sleepDuration'])) {
                        // Retrieve sleep duration from settings
                        $sleeptimer = $settings['sleepDuration'];
                        echo "\nSleep For: \033[0;32m$sleeptimer seconds\033[0m\n";
                        // Use usleep to sleep for the specified duration in microseconds
                        sleep(intval($settings['sleepDuration']));
                    
                        // Output the sleep duration in green
                        
                    }
                    

                        // Clear recipients for the next iteration
                        $mail->clearAddresses();
                        $mail->clearCustomHeaders();
                        
                
                    }
                    
         
                    
                   } else {
    
}
               
                    // Exit the child process
                    exit();
                         
                }
                

                
            }

            // Wait for all child processes to finish
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
                        $combinedEmails = array_unique($combinedEmails); // Remove duplicates
                    
                        $RecipientListForFilter = array_diff($recipientList, $combinedEmails);
                    
                        // Save the new filtered recipient list to "resend.txt"
                        file_put_contents("resend.txt", implode(PHP_EOL, $RecipientListForFilter) . PHP_EOL);
                    }
                    
                    // Call function to filter recipient list
                    filterRecipientList($recipientListFile, $sentEmailsFile, $badlistFile);
                    
                    
                    // Function to count unique lines in a file
                    function countLines($file)
                    {
                        if (file_exists($file)) {
                            $lines = file($file);
                            if ($lines === false) {
                                return 0; // Unable to read the file
                            } else {
                                return count($lines);
                            }
                        } else {
                            return 0; // File doesn't exist
                        }
                    }
                    
                    $failedEmailsFile = 'failed.txt';
                    $sentEmailsFile = 'pass.txt';
                    $badlistFile = 'bad.txt';
                    $recipientListFile = 'list/' . $settings['recipientListFile']; // Update the path as needed
                    
                    $failedEmailsCount = countLines($failedEmailsFile);
                    $sentEmailsCount = countLines($sentEmailsFile);
                    $badEmailsCount = countLines($badlistFile);
                    $recipientListFileCount = countLines($recipientListFile);
                    
                    // Calculate the total processed emails
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
                            echo "\n\033[0;33mNot all emails were sent. Check failed.txt file for details. Failed Count: $failedCount\033[0m\n";
                        } elseif (empty($sentEmails)) {
                            echo "\n\033[0;33mNo emails were sent.\033[0m\n";
                        }
                    } else {
                        $unsentCount = $recipientListFileCount - $sentEmailsCount - $badEmailsCount;
                        echo "\n\033[0;33mNot all emails have been sent due to errors. Unsent Count: $unsentCount\033[0m\n";
                    }



            
            
        }
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
    
    
    
} else {
    echo "\033[0;31mUser does not exist or credentials are incorrect. contact S1L3NT_T0RTUG3R \033[0m\n";
}
?>
