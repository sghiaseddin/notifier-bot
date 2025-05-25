<?php
/* 
 * check_calendar.php
 * Loops over current month + next 2 months
 * Sends POST requests to MNE calendar API
 * Sends email alert if response is not 200 or not {"data":{}}
 * Stores all responses in response.log
 */

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If not using Composer, include manually:
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

$debug = ($_ENV['DEBUG'] ?? 'false') === 'true';
$test = ($_ENV['TEST_MODE'] ?? 'false') === 'true';
$payload_url = $_ENV['PAYLOAD_URL'] ?? '';
$test_url = $_ENV['TEST_URL'] ?? '';
$logFile = __DIR__ . '/' . ($_ENV['LOG_FILE'] ?? 'response.log');

date_default_timezone_set($_ENV['TIMEZONE']);
$currentMonth = (int)date('n') - 1; // zero-indexed
$currentYear = (int)date('Y');

for ($i = 0; $i < 3; $i++) {
    $month = ($currentMonth + $i) % 12;
    $year = $currentYear + intdiv($currentMonth + $i, 12);
    $logEntry = checkMonth($month, $year);
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

function checkMonth($mes, $ano) {
    global $debug;
    global $test;
    global $test_url;
    global $payload_url;

    $url = $test ? $test_url : $payload_url;

    $headers = [
        'Content-Type: application/json',
        'X-CSRF-Token: ' . $_ENV['CSRF_TOKEN']
    ];
    $payload = json_encode([
        'mes' => $mes,
        'ano' => $ano,
        '_req' => ''
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HEADER => true
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headersText = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    $month = $mes + 1;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] Month: $month, Year: $ano\nStatus: $httpCode\nHeaders:\n$headersText\nBody:\n$body\n---------------------\n";

    // Decode response
    $json = json_decode($body, true);
    $shouldNotify = false;
    $reason = "";

    if ($httpCode !== 200) {
        $shouldNotify = true;
        $reason = "HTTP Error $httpCode";
    } elseif (!isset($json['data']) || !empty($json['data'])) {
        $shouldNotify = true;
        $reason = 'Non-empty or invalid "data"';
    } elseif ($debug) {
        $shouldNotify = true;
        $reason = 'Debug Mode ON';
    }

    if ($shouldNotify) {
        sendEmailAlert($httpCode, $reason, $logEntry);
    }

    return $logEntry;
}

function sendEmailAlert($statusCode, $reason, $logContent) {
    $mail = new PHPMailer(true);

    try {
        // SMTP CONFIGURATION
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = $_ENV['SMTP_SECURE']; // 'tls' or 'ssl'
        $mail->Port = (int)$_ENV['SMTP_PORT'];
        
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom($_ENV['EMAIL_FROM'], $_ENV['EMAIL_FROM_NAME']);
        $mail->addAddress($_ENV['EMAIL_TO']);

        $mail->Subject = "Alert: $reason (Status $statusCode)";
        $mail->isHTML(true);
        $mail->Body = nl2br(htmlspecialchars($logContent));

        $mail->send();
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
    }
}
