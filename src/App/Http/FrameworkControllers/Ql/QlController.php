<?php

namespace Iankibet\Shbackend\App\Http\FrameworkControllers\Ql;

use App\Http\Controllers\Controller;
use GraphQL\Parser\Parser;
use Iankibet\Shbackend\App\GraphQl\GraphQlRepository;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Iankibet\Shbackend\App\Repositories\ShRepository;
use Illuminate\Support\Str;

class QlController extends Controller
{
    //
    protected $related = [];
    public function handleQuery(){
        $parser = new Parser();
        $query = request('query');
        $queryString = "query $query";
        $parser->parse($queryString);
        $graphQlRepo = new GraphQlRepository();
        if($parser->queryIsValid()){
            $document = $parser->getParsedDocument();
            $query = $graphQlRepo->getQueryFromDocument($document);
            return SearchRepo::of(array_values($query)[0])->response();
        }
    }
    protected function getSelectionFields($modelSelections,$prefix=null){
        $fields = [];
        foreach ($modelSelections['selections'] as $selection){
            $field = $selection['name']['value'];
            if(count($selection['selectionSet']) > 0) {
                if(!isset($this->related[$field])){
                   $this->related[$field] = [];
                }
                $children = $this->getSelectionFields($selection['selectionSet'],$field.'.');
                $fields = array_merge($fields,$children);
                $this->related[$field] = $children;
            } else {
                $fields[] = $prefix.$field;
            }
        }
        return $fields;
    }
    protected function getSelectionArguments($selection){
        $argumentArr = [];
        if(isset($selection['arguments'])){
            $arguments = $selection['arguments'];
            foreach ($arguments as $argument){
                $key = $argument['name']['value'];
                $value = $argument['value']['value'];
                $operator = 'like';
//                if(is_numeric($value)){
//                    $operator = '=';
//                }
                $argumentArr[] = [
                    $key,$operator,"%$value%"
                ];
            }
        }
        return $argumentArr;
    }
    public function createModel($slug){
        if(request('id')){
            return $this->updateModel($slug,request('id'));
        }
        $modelConfig = $this->getModelConfig($slug);
        $model = $modelConfig->model;
        $forceFill = $modelConfig->forceFill ?? [];
        $forceFill = (array)$forceFill;
        return ShRepository::beginAutoSaveModel($model)
            ->setDataFromRequest()
            ->setValidationRulesFromFillable()
            ->forceFill($forceFill)
            ->response();
    }
    public function updateModel($slug,$id){
        $modelConfig = $this->getModelConfig($slug);
        $model = $modelConfig->model;
        $model = $model->findOrFail($id);
        $forceFill = $modelConfig->forceFill ?? [];
        $forceFill = (array)$forceFill;
        return ShRepository::beginAutoSaveModel($model)
            ->setDataFromRequest()
            ->setValidationRulesFromFillable()
            ->forceFill($forceFill)
            ->response();
    }

    protected function getModelConfig($slug){
        $modelConfig = config('shql.'.$slug);
        if(!$modelConfig){
            throw new \Exception("($slug) Sh model slug does not exist");
        }
        $modelConfig = json_encode($modelConfig);
        $modelConfig = str_replace('{current_user_id}',@request()->user()->id,$modelConfig);
        $modelConfig = str_replace('{user_id}',@request()->user()->id,$modelConfig);
        $modelConfig = json_decode($modelConfig);
        $modelConfig->model = new $modelConfig->model;
        return $modelConfig;
    }
}
