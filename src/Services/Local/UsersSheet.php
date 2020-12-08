<?php


namespace Osana\Challenge\Services\Local;


use PhpOffice\PhpSpreadsheet\IOFactory;

class UsersSheet extends Singleton
{
    private $fileHandle;

    protected function __construct()
    {
        $this->fileHandle = IOFactory::load(__DIR__.'/../../../data/users.csv');
    }

    public function getRange(int $begin, int $limit): array
    {
        return $this->fileHandle->getActiveSheet()->rangeToArray("A{$begin}:C{$limit}");
    }

    public function getTotalRows() : int
    {
        return $this->fileHandle->getActiveSheet()->getHighestRow();
    }

    public function setCell($cell, $value)
    {

        $this->fileHandle->getActiveSheet()->insertNewRowBefore(102)->setCellValue($cell,$value);
//        var_dump($this->fileHandle->getActiveSheet()->getCell($cell));exit;
    }

    public static function getUsers(): Singleton
    {
        return static::getInstance();
    }
}
