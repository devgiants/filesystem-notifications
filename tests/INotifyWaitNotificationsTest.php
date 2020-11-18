<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use Calcinai\Rubberneck\Observer;
use React\EventLoop\LoopInterface;
use Calcinai\Rubberneck\Driver\Drivers;

class INotifyWaitNotificationsTest extends TestCase
{
    const DATA_FOLDER = '/tmp/data/';
    const LOG_FOLDER = '/tmp/log/';
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
        $log->pushHandler(
            new RotatingFileHandler(
                static::LOG_FOLDER . "filesystem_notifications.log",
                Logger::DEBUG)
        );

        exec(sprintf('rm -rf %s', static::DATA_FOLDER));
        mkdir(static::DATA_FOLDER, 0775,true);

        $this->loop     = Factory::create();
        $this->observer = new Observer(
            $this->loop,
            $log,
            Drivers::getList()['INOTIFY_WAIT']
        );

        parent::setUp();
    }

    /**
     * @covers Calcinai\Rubberneck\Driver\Filesystem
     * @covers Calcinai\Rubberneck\Observer
     * @covers Calcinai\Rubberneck\Driver\AbstractDriver
     * @covers Calcinai\Rubberneck\Driver\InotifyWait
     * @covers Calcinai\Rubberneck\Driver\Drivers
     *
     */
    public function testEvents()
    {

        $this->loop->addTimer(1, function () {
            touch(static::DATA_FOLDER . 'creation_test.txt');
        });

        $this->loop->addTimer(2, function () {
            file_put_contents(static::DATA_FOLDER . 'creation_test.txt', 'my_data');
        });

        $this->loop->addTimer(3, function () {
            unlink(static::DATA_FOLDER . 'creation_test.txt');
        });

        $this->loop->addTimer(5, function() {
            $this->loop->stop();
        });


        $this->observer->onCreate(function ($fileName) {
            $this->assertFileExists(static::DATA_FOLDER . $fileName);
            $this->assertEquals(static::DATA_FOLDER . 'creation_test.txt', static::DATA_FOLDER . $fileName);
        });


        $this->observer->onModify(function ($fileName) {
            $this->assertEquals('my_data', file_get_contents(static::DATA_FOLDER . $fileName));
        });

        $this->observer->onDelete(function ($fileName) {
            $this->assertFileDoesNotExist(static::DATA_FOLDER . $fileName);
        });


        $this->observer->watch(static::DATA_FOLDER);
        $this->loop->run();
    }


    public function tearDown(): void
    {
        exec(sprintf('rm -rf %s', static::DATA_FOLDER));
        unset($this->loop);
        unset($this->observer);
        parent::tearDown();
    }
}