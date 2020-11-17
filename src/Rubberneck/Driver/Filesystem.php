<?php
/**
 * @package    rubberneck
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Rubberneck\Driver;


use Calcinai\Rubberneck\Observer;

class Filesystem extends AbstractDriver implements DriverInterface
{

    const DEFAULT_POLL_INTERVAL = 0.05;

    private $file_pattern;
    private $existing_files;


    public function watch($file_pattern)
    {
        $this->file_pattern = $file_pattern;
        $this->startFind();
    }


    private function startFind()
    {

        $this->existing_files = $this->getFileStat();
        $this->observer->getLoop()->addPeriodicTimer(self::DEFAULT_POLL_INTERVAL, [$this, 'compareExistingFiles']);
    }

    public function compareExistingFiles()
    {

        $new_files = $this->getFileStat();

        foreach ($this->existing_files as $file_name => $existing_info) {
            if (! isset($new_files[$file_name])) {
                //Deleted
                unset($this->existing_files[$file_name]);
                $this->observer->emit(Observer::EVENT_DELETE, [$file_name]);

            } elseif ($new_files[$file_name] !== $existing_info) {
                //Modified
                $this->existing_files[$file_name] = $new_files[$file_name];
                $this->observer->emit(Observer::EVENT_MODIFY, [$file_name]);
            }
        }

        if (in_array(Observer::EVENT_CREATE, $this->observer->getSubscribedEvents())) {
            foreach ($new_files as $file_name => $new_info) {
                if (! isset($this->existing_files[$file_name])) {
                    //New file
                    $this->existing_files[$file_name] = $new_info;
                    $this->observer->emit(Observer::EVENT_CREATE, [$file_name]);
                }
            }
        }
    }


    private function getFileStat()
    {

        clearstatcache();
        exec(sprintf('find %s', $this->file_pattern), $files);

        $fileInfo = [];
        if(is_array($files)) {
            foreach ($files as $file) {
                $fileInfo[$file] = filemtime($file);
            }
        }

        return $fileInfo;
    }


    public static function hasDependencies()
    {
        //Assuming it's running on *nix
        return true;
    }

}