<?php


namespace App\Calendars;


abstract class AbstractCalendar
{

    protected $lastError = null;

    /**
     * @return null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param null $lastError
     */
    public function setLastError($lastError): void
    {
        $this->lastError = $lastError;
    }



}
