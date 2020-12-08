<?php


namespace Osana\Challenge\Services\Local;


use PhpOffice\PhpSpreadsheet\IOFactory;

class ProfilesSheet extends Singleton
{
    private $fileHandle;

    protected function __construct()
    {
        $this->fileHandle = IOFactory::load(__DIR__.'/../../../data/profiles.csv');
    }

    public function getRange(int $begin, int $limit): array
    {
        return $this->fileHandle->getActiveSheet()->rangeToArray("A{$begin}:D{$limit}");
    }

    public function setCell($cell, $value)
    {
        $this->fileHandle->getActiveSheet()->getCell($cell)->setValue($value);
    }

    public static function getProfiles(): Singleton
    {
        return static::getInstance();
    }
}
