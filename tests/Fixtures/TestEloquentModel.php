<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Weirdo\LaravelWorkflow\Traits\WorkflowTrait;

class TestEloquentModel extends Model
{
    use WorkflowTrait;

    public $marking = 'here';
}
