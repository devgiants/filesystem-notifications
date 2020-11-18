<?php

/**
 * @package    rubberneck
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Rubberneck;

use Evenement\EventEmitterTrait;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Calcinai\Rubberneck\Driver;
use Calcinai\Logger\FilesystemNotificationsLogger;

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
     * @var Logger
     */
    protected $logger;

    /**
     * List of available drivers in order of preference
     *
     * @var Driver\DriverInterface[]
     */
    static $drivers = [
        Driver\InotifyWait::class,
        Driver\Filesystem::class
    ];

    /**
     * Observer constructor.
     *
     * @param LoopInterface $loop
     *
     * @throws \Exception
     */
    public function __construct(LoopInterface $loop, Logger $logger) {

        $this->loop = $loop;
        $this->logger = $logger;
        $driverClass = $this->getBestDriver();
        $this->driver = new $driverClass($this, $logger);
    }


    public function watch($path) {
        $this->driver->watch($path);
    }


    public function getSubscribedEvents(){
        return array_keys($this->listeners);
    }


    public function getLoop() {
        return $this->loop;
    }


    public function getBestDriver(){

        foreach(self::$drivers as $driver){
            if($driver::hasDependencies()){
                $this->logger->addDebug("Driver selected : {$driver}");
                return $driver;
            }
        }

        $this->logger->addError("No drivers available");
        // Should never happen since the file poll can always work.
        throw new \Exception('No drivers available');
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