<?php

namespace InfyOm\Generator\Generators\Scaffold;

use Illuminate\Support\Str;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Utils\FileUtil;

class BreadcrumbGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $basePath;

    /** @var string */
    private $path;

    /** @var string */
    private $fileName;

    /** @var string */
    private $parentPath;

    /** @var string */
    private $parentFileName = 'backend.php';

    /** @var string */
    private $templateType;

    /** @var string */
    private $breadcrumbContents;

    /** @var string */
    private $template;

    /** @var string */
    private $includeContents;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $basepath = config(
            'infyom.laravel_generator.path.views',
            resource_path('views/'
            )
        );
        $this->templateType = config('infyom.laravel_generator.templates', 'coreui-templates');

        $templateName = 'breadcrumb';

        if ($this->commandData->isLocalizedTemplates()) {
            $templateName .= '_locale';
        }

        $this->template = get_template('scaffold.routes.'.$templateName, $this->templateType);

        $this->basePath = config('laravel_generator.path.breadcrumb_route', base_path('routes/breadcrumbs/'));
        $this->basePath .= 'backend/';
        $this->fileName = $this->commandData->config->mSnake.'.php';
        $this->path = $this->basePath.$this->fileName;
        $this->parentPath = $this->basePath.$this->parentFileName;

        $this->breadcrumbContents = fill_template($this->commandData->dynamicVars, $this->template);
        $this->includeContents = "require __DIR__.'/{$this->fileName}';";
    }

    public function generate()
    {
        if (file_exists($this->path)) {
            $this->commandData->commandObj->info('Breadcrumb '.$this->commandData->config->mPlural.' already exists, Skipping Adjustment.');

            return;
        }
        FileUtil::createFile($this->basePath, $this->fileName, $this->breadcrumbContents);
        $this->commandData->commandComment("Created ".$this->commandData->config->mCamelPlural.' to breadcrumb.');

        $breadcrumbRouteFileContent = file_get_contents($this->parentPath);
        $breadcrumbRouteFileContent .= $this->includeContents.PHP_EOL;
        file_put_contents($this->parentPath, $breadcrumbRouteFileContent);
        $this->commandData->commandComment("Appended ".$this->commandData->config->mCamelPlural.' to breadcrumb backend route.');
    }

    public function rollback()
    {
        // remove require from backend.php
        $existingBreadcrumbContents = file_get_contents($this->parentPath);
        if (Str::contains($existingBreadcrumbContents, $this->includeContents)) {
            file_put_contents($this->parentPath, trim(str_replace($this->includeContents, '', $existingBreadcrumbContents)).PHP_EOL);
            $this->commandData->commandComment('Breadcrumb removed from '.$this->parentFileName);
        }

        //delete breadcrumb route file
        if(file_exists($this->path)){
            unlink($this->path);
            $this->commandData->commandObj->info('Deleted '.$this->fileName.'.');
        }
    }
}
