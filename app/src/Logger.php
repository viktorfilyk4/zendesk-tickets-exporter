<?php declare(strict_types=1);

namespace App;

class Logger {
    public static function log(string $message, bool $disableFlush = false): void
    {
        echo $message . "<br>";
        if (!$disableFlush) {
            flush();
            ob_flush();
        }
    }
}