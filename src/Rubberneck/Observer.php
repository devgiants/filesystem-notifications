<?php

/**
 * @package    rubberneck
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Rubberneck;

use Calcinai\Rubberneck\Driver\Drivers;
use Calcinai\Rubberneck\Exception\DriverDoesNotExistException;
use Calcinai\Rubberneck\Exception\DriverNotAvailableException;
use Evenement\EventEmitterTrait;
use Monolog\Logger;
use React\EventLoop\LoopInterface;
use Calcinai\Rubberneck\Driver\DriverInterface;

class Observer {

    use EventEmitterTrait;

    const EVENT_CREATE = 'create';
    const EVENT_MODIFY = 'modify';
    const EVENT_DELETE = 'delete';

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Driver\DriverInterface
     */
    private $driver;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * Observer constructor.
     *
     * @param LoopInterface $loop
     *
     * @throws \Exception
     */
    public function __construct(LoopInterface $loop, Logger $logger, ?string $driverClass = null) {

        $this->loop = $loop;
        $this->logger = $logger;
        if($driverClass !== null) {
            if(!in_array($driverClass, Drivers::getList())) {
                $this->logger->addDebug("Driver provided does not exist : {$driverClass}");
                throw new DriverDoesNotExistException("Driver provided does not exist : {$driverClass}");
            }
        } else {
            $driverClass = $this->getBestDriver();
        }
        $this->logger->addDebug("Driver selected : {$driverClass}");
        $this->driver = new $driverClass($this, $logger);
    }


    public function watch($path) {
        $this->logger->addDebug("Start watch process for {$path}");
        $this->driver->watch($path);
    }


    public function getSubscribedEvents(){
        return array_keys($this->listeners);
    }


    public function getLoop() {
        return $this->loop;
    }


    public function getBestDriver(){

        foreach(Drivers::getList() as $driver){
            if($driver::hasDependencies()){
                return $driver;
            }
        }

        $this->logger->addError("No drivers available");
        // Should never happen since the file poll can always work.
        throw new DriverNotAvailableException('No drivers available');
    }


    public function onCreate($callback) {
        $this->on(self::EVENT_CREATE, $callback);
    }

    public function onModify($callback) {
        $this->on(self::EVENT_MODIFY, $callback);
    }

    public function onDelete($callback) {
        $this->on(self::EVENT_DELETE, $callback);
    }

}