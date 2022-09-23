<?php
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaveModelTest extends \Iankibet\Shbackend\Tests\TestCase
{
    use RefreshDatabase;
    public function test_autosave_model(){
        $model = \Iankibet\Shbackend\App\Models\Core\Department::class;
        $data = [
          'name'=>'Super Admin',
          'description'=>'Test description',
        ];
        $department = \Iankibet\Shbackend\App\Repositories\ShRepository::autoSaveModel($model, $data);
        $this->assertIsInt($department->id);
    }

    public function test_edit_existing_model(){
        $model = \Iankibet\Shbackend\App\Models\Core\Department::class;
        $data = [
            'name'=>'Super Admin',
            'description'=>'Test description',
        ];
        $department = \Iankibet\Shbackend\App\Repositories\ShRepository::autoSaveModel($model,$data);
        $newName = 'No Admin';
        $newData = [
            'name'=>$newName,
        ];
        $editedDepartment = \Iankibet\Shbackend\App\Repositories\ShRepository::autoSaveModel($model,$newData,['id'=>$department->id]);
        $this->assertEquals($editedDepartment->id,$department->id);
        $this->assertEquals($editedDepartment->name,$newName);
    }
}
