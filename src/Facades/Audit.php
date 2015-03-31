<?php namespace DreamFactory\Enterprise\Services\Auditing\Facades;

use DreamFactory\Enterprise\Services\Auditing\Enums\AuditLevels;
use DreamFactory\Enterprise\Services\Auditing\Providers\AuditServiceProvider;
use DreamFactory\Enterprise\Services\Auditing\Services\AuditingService;
use DreamFactory\Enterprise\Services\Auditing\Utility\GelfLogger;
use Illuminate\Support\Facades\Facade;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Audit
 *
 * @method void setHost( $host = GelfLogger::DEFAULT_HOST )
 * @method AuditingService setMetadata( array $metadata )
 * @method bool log( $data = [], $level = AuditLevels::INFO, $request = null )
 * @method bool logRequest( string $instanceId, Request $request, $level = AuditLevels::INFO, $facility = AuditingService::DEFAULT_FACILITY )
 * @method GelfLogger getLogger()
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