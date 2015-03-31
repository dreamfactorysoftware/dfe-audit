<?php
/**
 * This file is part of the DreamFactory Services Platform(tm) SDK For PHP
 *
 * DreamFactory Services Platform(tm) <http://github.com/dreamfactorysoftware/dsp-core>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace DreamFactory\Enterprise\Services\Auditing\Services;

use DreamFactory\Enterprise\Services\Auditing\Components\GelfMessage;
use DreamFactory\Enterprise\Services\Auditing\Enums\AuditLevels;
use DreamFactory\Enterprise\Services\Auditing\Utility\GelfLogger;
use DreamFactory\Library\Utility\IfSet;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains auditing methods for DFE
 */
class AuditingService implements LoggerAwareInterface
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
    protected static $_logger = null;

    //********************************************************************************
    //* Public Methods
    //********************************************************************************

    /**
     * @param string $host
     */
    public static function setHost( $host = GelfLogger::DEFAULT_HOST )
    {
        static::getLogger()->setHost( $host );
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
    public static function logRequest( $instanceId, Request $request, $sessionData = array(), $level = AuditLevels::INFO, $facility = self::DEFAULT_FACILITY )
    {
        try
        {
            $_metadata = IfSet::get( $sessionData, 'metadata' );
            unset( $sessionData['metadata'] );

            //  Add in stuff for API request logging
            static::log(
                array(
                    'facility' => $facility,
                    'dfe'      => array(
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
     * @param array   $data    The data to log
     * @param int     $level   The level, defaults to INFO
     * @param Request $request The request, if available
     *
     * @return bool
     */
    public static function log( $data = array(), $level = AuditLevels::INFO, $request = null )
    {
        try
        {
            $_request = $request ?: ( app( 'request' ) ?: Request::createFromGlobals() );
            $_data = array_merge( static::_buildBasicEntry( $_request ), $data );

            $_message = new GelfMessage( $_data );
            $_message->setLevel( $level );
            $_message->setShortMessage( $_request->getMethod() . ' ' . $_request->getRequestUri() );
            $_message->setFullMessage( 'DFE Audit | ' . implode( ', ', $_data['source_ip'] ) . ' | ' . $_data['request_timestamp'] );

            static::getLogger()->send( $_message );
        }
        catch ( \Exception $_ex )
        {
            //  Completely ignore any issues
        }
    }

    /**
     * @param array   $data
     * @param Request $request
     *
     * @return array
     */
    protected static function _buildBasicEntry( $request = null )
    {
        $_request = $request ?: Request::createFromGlobals();

        return array(
            'request_timestamp' => (double)$_request->server->get( 'REQUEST_TIME_FLOAT' ),
            'user_agent'        => $_request->headers->get( 'user-agent' ),
            'source_ip'         => $_request->getClientIps(),
            'content_type'      => $_request->getContentType(),
            'content_length'    => (int)$_request->headers->get( 'Content-Length' ) ?: 0,
            'token'             => $_request->headers->get( 'x-dreamfactory-session-token' ),
            'app_name'          => IfSet::get(
                $_GET,
                'app_name',
                $_request->headers->get(
                    'x-dreamfactory-application-name',
                    $_request->headers->get( 'x-application-name' )
                )
            ),
            'dfe'               => array(),
            'host'              => $_request->getHost(),
            'method'            => $_request->getMethod(),
            'path_info'         => $_request->getPathInfo(),
            'path_translated'   => $_request->server->get( 'PATH_TRANSLATED' ),
            'query'             => $_request->query->all(),
        );
    }

    /**
     * @return GelfLogger
     */
    public static function getLogger()
    {
        return static::$_logger ?: static::$_logger = new GelfLogger();
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger( LoggerInterface $logger )
    {
        static::$_logger = $logger;

        return $this;
    }

}
