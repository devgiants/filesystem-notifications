<?php
/**
 * @package    calcinai/phpi
 * @author     Michael Calcinai <michael@calcin.ai>
 */

namespace Calcinai\Rubberneck\Driver;

use Calcinai\Rubberneck\Observer;

class InotifyWait extends AbstractDriver implements DriverInterface
{

    const IN_CREATE = 'CREATE';
    const IN_MODIFY = 'MODIFY';
    const IN_DELETE = 'DELETE';


    static $EVENT_MAP = [
        self::IN_CREATE => Observer::EVENT_CREATE,
        self::IN_MODIFY => Observer::EVENT_MODIFY,
        self::IN_DELETE => Observer::EVENT_DELETE
    ];

    public function watch($path)
    {
        $subprocessCmd = sprintf('inotifywait -mr %s 2>/dev/null', $path);

        $this->observer->getLoop()->addReadStream(popen($subprocessCmd, 'r'), [$this, 'onData']);

        return true;
    }


    /**
     * Public vis for callback, not cause it should be called by anyone.
     *
     * @param $stream
     */
    public function onData($stream)
    {
        $eventLines = fread($stream, 1024);

        // Can have multiple events per read (or not enough)
        foreach (explode("\n", $eventLines) as $eventLine) {
            list($directory, $events, $file) = sscanf($eventLine, '%s %s %s');
            $events = explode(',', $events);

            if (!empty($file)) {
                $this->logger->addDebug("Data incoming on {$directory}{$file}", $events);
                foreach ($events as $event) {

                    //If we don't know about that event, continue
                    if (!isset(static::$EVENT_MAP[$event])) {
                        $this->logger->addDebug("{$event} is unkown. Abort");
                        continue;
                    }

                    $eventName = static::$EVENT_MAP[$event];

                    //If not subscribed, continue
                    if (!in_array($eventName, $this->observer->getSubscribedEvents())) {
                        $this->logger->addDebug("{$event} is unfollowed. Abort");
                        continue;
                    }

                    //Otherwise, good to fire
                    $this->logger->addDebug("Emit {$eventName} for {$file}");
                    $this->observer->emit($eventName, [$file]);
                }
            }
        }
    }

    public static function hasDependencies()
    {
        return `command -v inotifywait` !== null;
    }
}
