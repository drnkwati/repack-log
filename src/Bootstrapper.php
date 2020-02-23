<?php

namespace Repack\Log;

use ArrayAccess;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;

class Bootstrapper
{
    public static function bootstrap(ArrayAccess $ioc)
    {
        if ($ioc->offsetExists('log') && $ioc['log'] instanceof LoggerInterface) {
            return;
        }

        $ioc->offsetSet('log', function () use ($ioc) {
            return Bootstrapper::createLogger($ioc);
        });
    }

    /**
     * Create the logger.
     *
     * @return Writer
     */
    public static function createLogger(ArrayAccess $ioc)
    {
        $log = new Writer(
            new Monolog(static::channel($ioc)),
            $ioc->offsetExists('events') ? $ioc['events'] : null
        );

        static::configureHandler($log, $ioc);

        return $log;
    }

    /**
     * Get the name of the log "channel".
     *
     * @return string
     */
    protected static function channel($ioc)
    {
        if ($ioc->offsetExists('config') &&
            $channel = $ioc['config']['ioc.log_channel']) {
            return $channel;
        }

        return $ioc->offsetExists('env') ? $ioc['env'] : 'production';
    }

    /**
     * Configure the Monolog handlers for the ioclication.
     *
     * @param  Writer  $log
     * @return void
     */
    protected static function configureHandler(Writer $log, $ioc)
    {
        $method = 'configure' . ucfirst(static::handler($ioc)) . 'Handler';

        static::$method($log, $ioc);
    }

    protected static function storagePath($ioc)
    {
        return method_exists($ioc, 'storagePath') ? $ioc->storagePath() : $ioc['config']['app.storage_path'];
    }

    /**
     * Configure the Monolog handlers for the ioclication.
     *
     * @param  Writer  $log
     * @return void
     */
    protected static function configureSingleHandler(Writer $log, $ioc)
    {
        $log->useFiles(
            static::storagePath($ioc) . '/logs/app.log',
            static::logLevel($ioc)
        );
    }

    /**
     * Configure the Monolog handlers for the ioclication.
     *
     * @param  Writer  $log
     * @return void
     */
    protected static function configureDailyHandler(Writer $log, $ioc)
    {
        $log->useDailyFiles(
            static::storagePath($ioc) . '/logs/app.log', static::maxFiles($ioc),
            static::logLevel($ioc)
        );
    }

    /**
     * Configure the Monolog handlers for the ioclication.
     *
     * @param  Writer  $log
     * @return void
     */
    protected static function configureSyslogHandler(Writer $log, $ioc)
    {
        $log->useSyslog('laravel', static::logLevel($ioc));
    }

    /**
     * Configure the Monolog handlers for the ioclication.
     *
     * @param  Writer  $log
     * @return void
     */
    protected static function configureErrorlogHandler(Writer $log, $ioc)
    {
        $log->useErrorLog(static::logLevel($ioc));
    }

    /**
     * Get the default log handler.
     *
     * @return string
     */
    protected static function handler($ioc)
    {
        if ($ioc->offsetExists('config')) {
            return $ioc['config']['app.log'] ?: 'single';
        }

        return 'single';
    }

    /**
     * Get the log level for the ioclication.
     *
     * @return string
     */
    protected static function logLevel($ioc)
    {
        if ($ioc->offsetExists('config')) {
            return $ioc['config']['app.log_level'] ?: 'debug';
        }

        return 'debug';
    }

    /**
     * Get the maximum number of log files for the ioclication.
     *
     * @return int
     */
    protected static function maxFiles($ioc)
    {
        if ($ioc->offsetExists('config')) {
            return $ioc['config']['app.log_max_files'] ?: 5;
        }

        return 0;
    }
}
