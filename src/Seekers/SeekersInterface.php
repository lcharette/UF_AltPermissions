<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Seekers;

/**
 * Interface for the seekers
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
interface SeekersInterface
{
    public function getModel();
}