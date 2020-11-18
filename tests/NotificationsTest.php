<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use Calcinai\Rubberneck\Observer;
use React\EventLoop\LoopInterface;

class NotificationsTest extends TestCase
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Observer
     */
    private $observer;

    public function setUp(): void
    {

        $log = new Logger('filesystem_notifications');
        $log->pushHandler(new RotatingFileHandler(  "/tmp/filesystem_notifications.log", Logger::DEBUG));

        $this->loop     = Factory::create();
        $this->observer = new Observer($this->loop, $log);

        parent::setUp();
    }

    /**
     * @covers Calcinai\Rubberneck\Driver\Filesystem
     * @covers Calcinai\Rubberneck\Observer
     * @covers Calcinai\Rubberneck\Driver\AbstractDriver
     * @covers Calcinai\Rubberneck\Driver\InotifyWait
     * @covers Calcinai\Rubberneck\Driver\DriverInterface
     *
     */
    public function testEvents()
    {
        

        $this->loop->addTimer(1, function () {
            touch('/tmp/creation_test.txt');
        });

        $this->loop->addTimer(2, function () {
            file_put_contents('/tmp/creation_test.txt', 'my_data');
        });

        $this->loop->addTimer(3, function () {
            unlink('/tmp/creation_test.txt');
        });


        $this->loop->addTimer(4, function () {
            $this->loop->stop();
        });

        $this->observer->onCreate(function ($fileName) {
            $this->assertFileExists($fileName);
            $this->assertEquals('/tmp/creation_test.txt', $fileName);
        });


        $this->observer->onModify(function ($file_name) {
            $this->assertEquals('my_data', file_get_contents($file_name));
        });

        $this->observer->onDelete(function ($file_name) {
            $this->assertFileDoesNotExist($file_name);
        });


        $this->observer->watch('/tmp/*.txt');
        $this->loop->run();
    }


    public function tearDown(): void
    {
        if(file_exists('/tmp/creation_test.txt')) {
            unlink('/tmp/creation_test.txt');
        }
        unset($this->loop);
        unset($this->observer);
        parent::tearDown();
    }
}