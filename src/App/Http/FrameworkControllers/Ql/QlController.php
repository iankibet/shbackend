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
        if(strpos($query,'query') === false && strpos($query,'mutation') === false) {
            $queryString = "query $query";
        } else {
            $queryString = $query;
        }
        $parser->parse($queryString);
        $graphQlRepo = new GraphQlRepository();
        if($parser->queryIsValid()){
            $document = $parser->getParsedDocument();
            $query = $graphQlRepo->getQueryFromDocument($document);
            if(request('per_page')|| request('paginated')){
                return SearchRepo::of(array_values($query)[0])->response();
            } else {
                foreach ($query as $key=>$ormQuery){
                    $records = $ormQuery->get();
                    if(Str::singular($key) == $key){
                        $query[$key] = $records[0];
                    } else {
                        $query[$key] = $records;
                    }
                }
                return $query;
            }
        } else {
            return response([
               'status'=>'failed',
               'message'=>'Invalid Graphql Query',
                'query'=>$queryString
            ],415);
        }
    }
    public function handleMutation(){
        $parser = new Parser();
        $query = request('query');
        if(strpos($query,'query') === false && strpos($query,'mutation') === false) {
            $queryString = "query $query";
        } else {
            $queryString = $query;
        }
        $parser->parse($queryString);
        $graphQlRepo = new GraphQlRepository();
        if($parser->queryIsValid()){
            $mutationData = $graphQlRepo->getMutationArguments($queryString);
            $mutation = $mutationData->mutation;
            $selectFields = $mutationData->selectionFields;
            $arguments = $mutationData->arguments;
            $modelConfig = $this->getModelConfig($mutation,true);
            $model = $modelConfig->model;
            if(isset($modelConfig->type) && $modelConfig->type == 'update'){
                $model = $model->where('id',$arguments['id'])->first();
            }
            $saved = ShRepository::beginAutoSaveModel($model)
                ->setData($arguments)
                ->setValidationRulesFromFillable()
                ->forceFill(@(array)$modelConfig->forceFill)
                ->save();
            if(isset($modelConfig->log)){
                $log = $this->replaceLogVariables($modelConfig->log->log,$saved);
                ShRepository::storeLog($modelConfig->log->slug,$log, $saved);
            }
            $responseModel = [];
            foreach ($selectFields as $selectField){
                $responseModel[$selectField] = $saved->$selectField;
            }
            return [
              $mutation=>$responseModel
            ];
        } else {
            return response([
                'status'=>'failed',
                'message'=>'Invalid Graphql Query',
                'query'=>$queryString
            ],415);
        }
    }
    protected function replaceLogVariables($log,$model){
        $modelArr = $model->toArray();
        foreach ($modelArr as $fillable){
            $log = str_replace('{'.$fillable.'}',$model->$fillable,$log);
        }
        return $log;
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

    protected function getModelConfig($slug,$mutation = false){
        $gqlRepo = new GraphQlRepository();
        return $gqlRepo->getModelQuery($slug,$mutation);
    }
}
