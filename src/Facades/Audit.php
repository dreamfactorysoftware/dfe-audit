<?php
namespace DreamFactory\Library\Fabric\Auditing\Facades;

use DreamFactory\Library\Fabric\Auditing\Enums\AuditLevels;
use DreamFactory\Library\Fabric\Auditing\Providers\AuditServiceProvider;
use DreamFactory\Library\Fabric\Auditing\Services\AuditingService;
use DreamFactory\Library\Fabric\Auditing\Utility\GelfLogger;
use Illuminate\Support\Facades\Facade;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Audit
 *
 * @method static void setHost( $host = GelfLogger::DEFAULT_HOST )
 * @method static bool log( $data = [], $level = AuditLevels::INFO, $facility = AuditingService::DEFAULT_FACILITY, $request = null )
 * @method static bool logRequest( string $instanceId, Request $request, $level = AuditLevels::INFO, $facility = AuditingService::DEFAULT_FACILITY )
 * @method static GelfLogger getLogger()
 * @method AuditingService setLogger( LoggerInterface $logger )
 */
class Audit extends Facade
{
    //******************************************************************************
    //* Methods
    //******************************************************************************

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return AuditServiceProvider::ALIAS_NAME;
    }

}