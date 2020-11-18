<?php


namespace Calcinai\Rubberneck\Driver;


final class Drivers
{
    public const INOTIFY_WAIT = InotifyWait::class;
    public const FILESYSTEM = Filesystem::class;

    /**
     * @return array
     */
    public static function getList(): array {
        return  (new \ReflectionClass(__CLASS__))->getConstants();
    }
}