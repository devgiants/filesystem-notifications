<?php

/**
 * @package    rubberneck
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Rubberneck\Driver;

use Calcinai\Rubberneck\Observer;
use Monolog\Logger;

interface DriverInterface {
    public function __construct(Observer $observer, Logger $logger);
    public function watch($path);
    public static function hasDependencies();
}