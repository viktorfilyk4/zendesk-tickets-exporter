<?php declare(strict_types=1);

namespace App;

class CSVWriter
{
    private string $filePath;
    private $filePointer;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->filePointer = fopen($filePath, 'w');
    }

    public function writeHeaders(array $headers)
    {
        fputcsv($this->filePointer, $headers);
    }

    public function writeRow(array $row)
    {
        fputcsv($this->filePointer, $row);
    }

    public function close()
    {
        fclose($this->filePointer);
    }
}
