<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions;

use UserFrosting\Sprinkle\AltPermissions\ServicesProvider\AltPermissionsServicesProvider;
use UserFrosting\Sprinkle\Core\Initialize\Sprinkle;

/**
 * Bootstrapper class for the 'AltPermissions' sprinkle.
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class AltPermissions extends Sprinkle
{
    /**
     * Register AltPermissions services.
     */
    public function init()
    {
        $serviceProvider = new AltPermissionsServicesProvider();
        $serviceProvider->register($this->ci);
    }
}
