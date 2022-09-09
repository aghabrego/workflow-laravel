<?php

namespace Tests;

use Illuminate\Support\Facades\Storage;
use Mockery;
use Weirdo\LaravelWorkflow\Commands\WorkflowDumpCommand;

class WorkflowDumpCommandTest extends BaseWorkflowTestCase
{
    public function testShouldThrowExceptionForUndefinedWorkflow()
    {
        $command = Mockery::mock(WorkflowDumpCommand::class)
            ->makePartial()
            ->shouldReceive('argument')
            ->with('workflow')
            ->andReturn('fake')
            ->shouldReceive('option')
            ->with('format')
            ->andReturn('png')
            ->shouldReceive('option')
            ->with('class')
            ->andReturn('Tests\Fixtures\TestObject')
            ->shouldReceive('option')
            ->with('disk')
            ->andReturn('local')
            ->shouldReceive('option')
            ->with('path')
            ->andReturn('/')
            ->getMock();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Workflow fake is not configured.');
        $command->handle();
    }

    public function testShouldThrowExceptionForUndefinedClass()
    {
        $command = Mockery::mock(WorkflowDumpCommand::class)
            ->makePartial()
            ->shouldReceive('argument')
            ->with('workflow')
            ->andReturn('straight')
            ->shouldReceive('option')
            ->with('format')
            ->andReturn('png')
            ->shouldReceive('option')
            ->with('class')
            ->andReturn('Tests\Fixtures\FakeObject')
            ->shouldReceive('option')
            ->with('disk')
            ->andReturn('local')
            ->shouldReceive('option')
            ->with('path')
            ->andReturn('/')
            ->getMock();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Workflow straight has no support for' .
            ' class Tests\Fixtures\FakeObject. Please specify a valid support' .
            ' class with the --class option.');
        $command->handle();
    }

    public function testWorkflowCommand()
    {
        $optionalPath = __DIR__ . "/my/path";
        $disk = 'public';

        Storage::fake($disk);

        if (Storage::disk($disk)->exists($optionalPath . '/straight.jpeg')) {
            Storage::disk($disk)->delete($optionalPath . '/straight.jpeg');
        }

        $command = Mockery::mock(WorkflowDumpCommand::class)
            ->makePartial()
            ->shouldReceive('argument')
            ->with('workflow')
            ->andReturn('straight')
            ->shouldReceive('option')
            ->with('format')
            ->andReturn('jpeg')
            ->shouldReceive('option')
            ->with('class')
            ->andReturn('Tests\Fixtures\TestObject')
            ->shouldReceive('option')
            ->with('disk')
            ->andReturn($disk)
            ->shouldReceive('option')
            ->with('path')
            ->andReturn($optionalPath)
            ->getMock();

        $command->handle();

        Storage::disk($disk)->assertExists($optionalPath . '/straight.jpeg');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']['workflow'] = [
            'straight' => [
                'supports' => ['Tests\Fixtures\TestObject'],
                'places' => ['a', 'b', 'c'],
                'transitions' => [
                    't1' => [
                        'from' => 'a',
                        'to' => 'b',
                    ],
                    't2' => [
                        'from' => 'b',
                        'to' => 'c',
                    ],
                ],
            ],
        ];
    }
}
