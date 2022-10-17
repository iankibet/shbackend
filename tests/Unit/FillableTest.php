<?php

class FillableTest extends \Iankibet\Shbackend\Tests\TestCase
{
    public function test_get_fillables(){
        $model = \Iankibet\Shbackend\App\Models\Core\Department::class;
        $fillables = \Iankibet\Shbackend\App\Repositories\ShRepository::getFillables($model);
        $this->assertEquals(['name','description'],$fillables);
    }
}
