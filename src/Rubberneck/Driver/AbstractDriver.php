<?php
/**
 * @package    calcinai/rubberneck
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Rubberneck\Driver;

use Calcinai\Rubberneck\Observer;
use Monolog\Logger;

abstract class AbstractDriver {

    /**
     * @var Observer $observer
     */
    protected $observer;

    protected $logger;

    public function __construct(Observer $observer, Logger $logger) {
        $this->observer = $observer;
        $this->logger = $logger;
    }
}