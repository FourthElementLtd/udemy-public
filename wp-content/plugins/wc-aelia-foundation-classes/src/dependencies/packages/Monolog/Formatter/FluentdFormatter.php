<?php

/*
 * This file is part of the Aelia\Dependencies\Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aelia\Dependencies\Monolog\Formatter;

use Aelia\Dependencies\Monolog\Utils;

/**
 * Class FluentdFormatter
 *
 * Serializes a log message to Fluentd unix socket protocol
 *
 * Fluentd config:
 *
 * <source>
 *  type unix
 *  path /var/run/td-agent/td-agent.sock
 * </source>
 *
 * Aelia\Dependencies\Monolog setup:
 *
 * $logger = new Aelia\Dependencies\Monolog\Logger('fluent.tag');
 * $fluentHandler = new Aelia\Dependencies\Monolog\Handler\SocketHandler('unix:///var/run/td-agent/td-agent.sock');
 * $fluentHandler->setFormatter(new Aelia\Dependencies\Monolog\Formatter\FluentdFormatter());
 * $logger->pushHandler($fluentHandler);
 *
 * @author Andrius Putna <fordnox@gmail.com>
 */
class FluentdFormatter implements FormatterInterface
{
    /**
     * @var bool $levelTag should message level be a part of the fluentd tag
     */
    protected $levelTag = false;

    public function __construct($levelTag = false)
    {
        if (!function_exists('json_encode')) {
            throw new \RuntimeException('PHP\'s json extension is required to use Aelia\Dependencies\Monolog\'s FluentdUnixFormatter');
        }

        $this->levelTag = (bool) $levelTag;
    }

    public function isUsingLevelsInTag()
    {
        return $this->levelTag;
    }

    public function format(array $record)
    {
        $tag = $record['channel'];
        if ($this->levelTag) {
            $tag .= '.' . strtolower($record['level_name']);
        }

        $message = array(
            'message' => $record['message'],
            'context' => $record['context'],
            'extra' => $record['extra'],
        );

        if (!$this->levelTag) {
            $message['level'] = $record['level'];
            $message['level_name'] = $record['level_name'];
        }

        return Utils::jsonEncode(array($tag, $record['datetime']->getTimestamp(), $message));
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }
}
