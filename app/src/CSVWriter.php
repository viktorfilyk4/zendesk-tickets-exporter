<?php

namespace App;

class CSVWriter
{
    private $filePath;
    private $file;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->file = fopen($filePath, 'w');
    }

    public function writeHeaders(array $headers)
    {
        fputcsv($this->file, $headers);
    }

    public function writeRow(array $row)
    {
        fputcsv($this->file, $row);
    }

    public function close()
    {
        fclose($this->file);
    }
}
