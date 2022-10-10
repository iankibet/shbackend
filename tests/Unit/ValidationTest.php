<?php

class ValidationTest extends \Iankibet\Shbackend\Tests\TestCase
{
    public function test_validation_fields(){
        $model = \Iankibet\Shbackend\App\Models\Core\Department::class;
        $rules = \Iankibet\Shbackend\App\Repositories\ShRepository::getValidationRules($model, ['email']);
        $this->assertArrayHasKey('email',$rules);
        $this->assertStringContainsString('required',$rules['email']);
    }
}
