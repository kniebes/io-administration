<?php declare(strict_types=1);

namespace App\Service\ErrorLogger;

use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;

class EmailNotification implements ErrorLoggerInterface
{
    public function log(string $subject, string $message, ?string $type = 'info'): void
    {
        $header = 'From: notification@kniebes.com' . "\r\n" .
            'Reply-To: notification@kniebes.com' . "\r\n" .
            'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
        $message .= PHP_EOL;
        foreach ([
                     'HTTP_USER_AGENT',
                     'HTTP_REFERER',
                     'REQUEST_URI',
                     'GEOIP_POSTAL_CODE',
                     'GEOIP_AREA_CODE',
                     'GEOIP_METRO_CODE',
                     'GEOIP_DMA_CODE',
                     'GEOIP_CITY',
                     'GEOIP_REGION_NAME',
                     'GEOIP_REGION',
                     'GEOIP_COUNTRY_NAME',
                     'GEOIP_COUNTRY_CODE',
                     'GEOIP_CONTINENT_CODE',
                 ] as $key) {
            if (!empty($_SERVER[$key])) {
                $message .= PHP_EOL.sprintf('%-20s : %s', $key, $_SERVER[$key]);
            }
        }

        mail('m@kniebes.io', sprintf('[%s] %s', $type, $subject), $message, $header);
    }

}
