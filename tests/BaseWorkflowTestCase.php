<?php

namespace Tests;

use Orchestra\Testbench\TestCase;
use Weirdo\LaravelWorkflow\Facades\WorkflowFacade;
use Weirdo\LaravelWorkflow\WorkflowServiceProvider;

class BaseWorkflowTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [WorkflowServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Workflow' => WorkflowFacade::class,
        ];
    }
}
