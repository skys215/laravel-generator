<?php

namespace Labolagen\Generator\Generators\Scaffold;

use Illuminate\Support\Str;
use Labolagen\Generator\Common\CommandData;

class RoutesGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $routeContents;

    /** @var string */
    private $routesTemplate;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathRoutes.DIRECTORY_SEPARATOR.$this->commandData->config->mSnakePlural.'.php';
        if (!empty($this->commandData->config->prefixes['route'])) {
            $this->routesTemplate = get_template('scaffold.routes.prefix_routes', 'laravel-generator');
        } else {
            $this->routesTemplate = get_template('scaffold.routes.routes', 'laravel-generator');
        }

        /* Removing 'admin.' from route prefix
         * due to already set prefix
         * using Route::group in routes/web.php
        */
        $this->commandData->addDynamicVariable('$ROUTE_NAMED_PREFIX$', ltrim($this->commandData->dynamicVars['$ROUTE_NAMED_PREFIX$'], 'admin.'));
        $this->routeContents = fill_template($this->commandData->dynamicVars, $this->routesTemplate);
        $this->commandData->addDynamicVariable('$ROUTE_NAMED_PREFIX$', 'admin.'.$this->commandData->dynamicVars['$ROUTE_NAMED_PREFIX$']);
    }

    public function generate()
    {
        if(file_exists($this->path)){
            $existingRouteContents = file_get_contents($this->path);
            if (Str::contains($existingRouteContents, "Route::resource('".$this->commandData->config->mSnakePlural."',")) {
                $this->commandData->commandObj->info('Route '.$this->commandData->config->mPlural.' is already exists, Skipping Adjustment.');

                return;
            }
        }
        file_put_contents($this->path, $this->routeContents);

        $this->commandData->commandComment("\n".$this->commandData->config->mCamelPlural.' routes added.');
    }

    public function rollback()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
            $this->commandData->commandComment('scaffold routes deleted');
        }
    }
}
