<?php namespace DreamFactory\Enterprise\Services\Auditing;

use DreamFactory\Enterprise\Services\Auditing\Components\GelfMessage;
use DreamFactory\Enterprise\Services\Auditing\Enums\AuditLevels;
use DreamFactory\Enterprise\Services\Auditing\Utility\GelfLogger;
use DreamFactory\Library\Utility\IfSet;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/**
 * Contains auditing methods for DFE
 */
class AuditingService
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string
     */
    const DEFAULT_FACILITY = 'fabric-instance';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type GelfLogger
     */
    protected $_logger = null;
    /**
     * @type array
     */
    protected $_metadata;
    /**
     * @type Application
     */
    protected $app;

    //********************************************************************************
    //* Public Methods
    //********************************************************************************

    /**
     * boot up
     *
     * @param Application $app
     */
    public function __construct( $app )
    {
        $this->app = $app;
        $this->_logger = new GelfLogger();
    }

    /**
     * @param string $host
     */
    public function setHost( $host = GelfLogger::DEFAULT_HOST )
    {
        $this->_logger->setHost( $host );
    }

    /**
     * Logs API requests to logging system
     *
     * @param string  $instanceId  The id of the sending instance
     * @param Request $request     The request
     * @param array   $sessionData Any session data to log
     * @param int     $level       The level, defaults to INFO
     * @param string  $facility    The facility, used for sorting
     *
     * @return bool
     */
    public function logRequest( $instanceId, Request $request, $sessionData = array(), $level = AuditLevels::INFO, $facility = self::DEFAULT_FACILITY )
    {
        try
        {
            $_metadata = IfSet::get( $sessionData, 'metadata', [] );
            unset( $sessionData['metadata'] );

            //  Add in stuff for API request logging
            static::log(
                array(
                    'facility' => $facility,
                    'dfe'      => $this->_metadata
                        ?: array(
                            'instance_id'       => $instanceId,
                            'instance_owner_id' => IfSet::get( $_metadata, 'owner-email-address' ),
                            'cluster_id'        => IfSet::get( $_metadata, 'cluster-id', $request->server->get( 'DFE_CLUSTER_ID' ) ),
                            'app_server_id'     => IfSet::get( $_metadata, 'app-server-id', $request->server->get( 'DFE_APP_SERVER_ID' ) ),
                            'db_server_id'      => IfSet::get( $_metadata, 'db-server-id', $request->server->get( 'DFE_DB_SERVER_ID' ) ),
                            'web_server_id'     => IfSet::get( $_metadata, 'web-server-id', $request->server->get( 'DFE_WEB_SERVER_ID' ) ),
                        ),
                    'user'     => $sessionData
                ),
                $level,
                $request
            );
        }
        catch ( \Exception $_ex )
        {
            //  Completely ignore any issues
        }
    }

    /**
     * Logs API requests to logging system
     *
     * @param array      $data    The data to log
     * @param int|string $level   The level, defaults to INFO
     * @param Request    $request The request, if available
     *
     * @return bool
     */
    public function log( $data = array(), $level = AuditLevels::INFO, $request = null )
    {
        try
        {
            $_request = $request ?: Request::createFromGlobals();
            $_data = array_merge( static::_buildBasicEntry( $_request ), $data );

            $_message = new GelfMessage( $_data );
            $_message->setLevel( $level );
            $_message->setShortMessage( $_request->getMethod() . ' ' . $_request->getRequestUri() );
            $_message->setFullMessage( 'DFE Audit | ' . implode( ', ', $_data['source_ip'] ) . ' | ' . $_data['request_timestamp'] );

            $this->_logger->send( $_message );
        }
        catch ( \Exception $_ex )
        {
            //  Completely ignore any issues
        }
    }

    /**
     * @param Request|\Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    protected function _buildBasicEntry( $request )
    {
        return array(
            'request_timestamp' => (double)$request->server->get( 'REQUEST_TIME_FLOAT' ),
            'user_agent'        => $request->headers->get( 'user-agent' ),
            'source_ip'         => $request->getClientIps(),
            'content_type'      => $request->getContentType(),
            'content_length'    => (int)$request->headers->get( 'Content-Length' ) ?: 0,
            'token'             => $request->headers->get( 'x-dreamfactory-session-token' ),
            'app_name'          => IfSet::get(
                $_GET,
                'app_name',
                $request->headers->get(
                    'x-dreamfactory-application-name',
                    $request->headers->get( 'x-application-name' )
                )
            ),
            'dfe'               => array(),
            'host'              => $request->getHost(),
            'method'            => $request->getMethod(),
            'path_info'         => $request->getPathInfo(),
            'path_translated'   => $request->server->get( 'PATH_TRANSLATED' ),
            'query'             => $request->query->all(),
        );
    }

    /**
     * @return GelfLogger
     */
    public function getLogger()
    {
        return $this->_logger;
    }

    /**
     * Sets a logger instance on the object
     *
     * @param GelfLogger $logger
     *
     * @return $this
     */
    public function setLogger( GelfLogger $logger )
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * @param array $metadata
     *
     * @return $this
     */
    public function setMetadata( array $metadata )
    {
        $this->_metadata = [];

        foreach ( $metadata as $_key => $_value )
        {
            $this->_metadata[str_replace( '-', '_', $_key )] = $_value;
        }

        return $this;
    }
}
