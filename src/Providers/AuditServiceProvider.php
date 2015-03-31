<?php namespace DreamFactory\Enterprise\Services\Auditing\Providers;

use DreamFactory\Enterprise\Common\Providers\BaseServiceProvider;
use DreamFactory\Enterprise\Services\Auditing\Services\AuditingService;

/**
 * Register the auditing service as a provider with Laravel.
 *
 * To use the "Audit" facade for this provider, you need to add the service provider to
 * your the providers array in your app/config/app.php file:
 *
 *  'providers' => array(
 *
 *      ... Other Providers Above ...
 *      'DreamFactory\Enterprise\Services\Auditing\Providers\AuditServiceProvider',
 *
 *  ),
 */
class AuditServiceProvider extends BaseServiceProvider
{
    //******************************************************************************
    //* Constants
    //******************************************************************************

    /**
     * @type string The name of the alias to create
     */
    const ALIAS_NAME = 'Audit';
    /**
     * @type string The name of the service in the IoC
     */
    const IOC_NAME = 'dfe.audit';

    //******************************************************************************
    //* Members
    //******************************************************************************

    /**
     * @type string
     */
    protected $_serviceClass = 'DreamFactory\\Library\\Fabric\\Auditing\\AuditingService';

    //********************************************************************************
    //* Public Methods
    //********************************************************************************

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //  Register object into instance container
        $this->singleton(
            static::IOC_NAME,
            function ( $app )
            {
                return new AuditingService( $app );
            }
        );
    }

    /**
     * Publish our junk
     */
    public function boot()
    {
        if ( !file_exists( config_path( 'instance.php' ) ) )
        {
            $this->publishes( [__DIR__ . '/../../config/instance.php' => config_path( 'instance.php' ),], 'config' );
        }
    }

}
