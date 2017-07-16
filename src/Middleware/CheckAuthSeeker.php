<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2013-2016 Alexander Weissman
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Middleware;

use Slim\Exception\NotFoundException;

 /**
 * Making sure the `seeker` argument from the route is autorized in the config files
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class CheckAuthSeeker
{
    /**
     * @var \UserFrosting\Config\Config, the config object
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param $config \UserFrosting\Config\Config, the config object
     */
    public function __construct($config)
    {
        $this->config = $config;

        // First check that the config is valid
        if (!is_array($this->config['seekers']))
        {
            throw new \InvalidArgumentException('The AltPermissions.seekers configuration value is not a valid array');
        }
    }

    /**
     * Invoke the CheckAuthSeeker middleware, making sure the `seeker` argument from the route is autorized in the config files.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        // Get the seeker argument
        $route = $request->getAttribute('route');
        $seeker = $route->getArgument('seeker');

        if ($seeker == "" || !array_key_exists($seeker, $this->config['seekers']))
        {
            throw new NotFoundException($request, $response);
        }
        //!TODO Check class exist
        else
        {
            return $next($request, $response);
        }

        return $response;
    }
}