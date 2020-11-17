<?php

use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use Calcinai\Rubberneck\Observer;

class NotificationsTest extends TestCase
{
    /**
     * @covers Calcinai\Rubberneck\Driver\Filesystem
     * @covers Calcinai\Rubberneck\Observer
     * @covers Calcinai\Rubberneck\Driver\AbstractDriver
     * @covers Calcinai\Rubberneck\Driver\InotifyWait
     *
     */
    public function testEvents()
    {

        $loop     = Factory::create();
        $observer = new Observer($loop);

        $loop->addTimer(1, function () use ($loop) {
            touch('/tmp/creation_test.txt');
        });

        $loop->addTimer(2, function () use ($loop) {
            file_put_contents('/tmp/creation_test.txt', 'my_data');
        });

        $loop->addTimer(3, function () use ($loop) {
            unlink('/tmp/creation_test.txt');
        });


        $loop->addTimer(4, function () use ($loop) {
            $loop->stop();
        });

        $observer->onCreate(function ($file_name) {
            $this->assertFileExists($file_name);
            $this->assertEquals('/tmp/creation_test.txt', $file_name);
        });


        $observer->onModify(function ($file_name) {
            $this->assertEquals('my_data', file_get_contents($file_name));
        });

        $observer->onDelete(function ($file_name) {
            $this->assertFileNotExists($file_name);
        });


        $observer->watch('/tmp/*.txt');
        $loop->run();
    }


    public function tearDown()
    {
        if(file_exists('/tmp/creation_test.txt')) {
            unlink('/tmp/creation_test.txt');
        }
        parent::tearDown();
    }
}