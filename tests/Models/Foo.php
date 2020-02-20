<?php

/*
 * UF AltPermissions Sprinkle
 *
 * @author    Louis Charette
 * @copyright Copyright (c) 2018 Louis Charette
 * @link      https://github.com/lcharette/UF_AltPermissions
 * @license   https://github.com/lcharette/UF_AltPermissions/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Tests\Models;

use UserFrosting\Sprinkle\AltPermissions\Database\Models\Traits\Auth;
use UserFrosting\Sprinkle\Core\Database\Models\Model;

/**
 * Foo model class.
 */
class Foo extends Model
{
    use Auth;

    /**
     * @var string The name of the table for the current model.
     */
    protected $table = 'alt_foo';

    /**
     * @var array The fields of the table for the current model.
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @var bool Enable timestamps for Users.
     */
    public $timestamps = true;
}
