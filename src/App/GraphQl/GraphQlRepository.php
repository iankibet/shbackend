<?php
namespace Iankibet\Shbackend\App\GraphQl;

use GraphQL\Parser\Parser;
use Illuminate\Support\Str;

class GraphQlRepository
{
    protected $userId;
    public function __construct()
    {
        $this->userId = request()->user()->id;
    }



    public function getQueryFromDocument($document){
        $document = json_decode(json_encode($document));
        $sets = [
        ];
        foreach ($document->definitions as $definition){
            if($definition->operation == 'query'){
                if($definition->selectionSet->kind == 'SelectionSet'){
                    foreach ($definition->selectionSet->selections as $selection){
                        if($selection->kind == 'Field'){
                            $key = $selection->name->value;
                            $fields = $this->getSelectionSetFields($selection->selectionSet);
                            $results = $this->getModelQuery($key)->model->select($fields['fields']);
                            if($selection->arguments) {
                                $results = $this->getSelectionArguments($results,$selection);
                            }
                            if($fields['sets']){
                                foreach ($fields['sets'] as $model=>$val){
                                    $keys = $val['keys'];
                                    $withString = 'id,'.implode(',',$keys);
                                    $selectKeys = explode(',',$withString);
                                    $selection = $val['selection'];
                                    $selected = $selection->name->value;
                                    $results = $results->with([$selected=>function($query) use($selection,$selectKeys){
                                        return $this->getSelectionArguments($query,$selection)->select($selectKeys);
                                    }]);
                                }
                            }
                            $sets[$key] = $results;
                        }
                    }
                }
            }
        }
        return $sets;
    }

    public function getSelectionArguments($query, $selection){
        $queryArguments = [];
//        dd($selection->arguments);
        foreach ($selection->arguments as $argument){

            $operator = 'like';
            $equalTypes = ['IntValue','BooleanValue'];
            if(in_array($argument->value->kind,$equalTypes)){
                $operator = '=';
            }
            if($argument->value->kind == 'ListValue'){
                $values = [];
                foreach ($argument->value->values as $valObject){
                    $values[] = $valObject->value;
                }
                $query = $query->whereIn($argument->name->value,$values);
            } else {
                $value = $argument->value->value;
                $query = $query->where($argument->name->value,$operator,$value);
            }

        }
        return $query;
    }
    protected function getArgumentsAsArr($selection){
        $queryArguments = [];
//        dd($selection->arguments);
        foreach ($selection->arguments as $argument){

            $operator = 'like';
            $equalTypes = ['IntValue','BooleanValue'];
            $key = $argument->name->value;
            if(in_array($argument->value->kind,$equalTypes)){
                $operator = '=';
            }
            if($argument->value->kind == 'ListValue'){
                $values = [];
                foreach ($argument->value->values as $valObject){
                    $values[] = $valObject->value;
                }
            } else {
                $value = $argument->value->value;
            }
            $queryArguments[$key] = $value;

        }
        return $queryArguments;
    }

    function getSelectionSetFields($set){
        $fields = [];
        $sets = [];
        if($set->kind == 'SelectionSet'){
            foreach ($set->selections as $selection){
                if($selection->selectionSet){
                    $sets[$selection->name->value] = [
                        'keys'=>$this->getSelectionSetFields($selection->selectionSet)['fields'],
                        'selection'=>$selection
                    ];
                } else {
                    $fields[] = $selection->name->value;
                }
            }
        }
        return [
            'fields'=>$fields,
            'sets'=>$sets
        ];
    }
    protected function getSelectionFieldsAsArr($selections){
        $fields = [];
        foreach ($selections as $selection){
            if(!$selection->selectionSet){
                $fields[] = $selection->name->value;
            }
        }
        return $fields;
    }

    protected function getModelQuery($slug){
        $slug = Str::plural($slug);
        $modelConfig = config('shql.'.$slug);
        if(!$modelConfig){
            throw new \Exception("($slug) Sh model slug does not exist");
        }
        $where = null;
        if(isset($modelConfig['where'])){
            $where = $modelConfig['where'];
        }
        $modelConfig = json_encode($modelConfig);
        $modelConfig = str_replace('{current_user_id}',@request()->user()->id,$modelConfig);
        $modelConfig = str_replace('{user_id}',@request()->user()->id,$modelConfig);
        $modelConfig = json_decode($modelConfig);
        $model = new $modelConfig->model;
        if(isset($modelConfig->where)){
            $userId = $this->userId;
            $where = array_map(function($value) use($userId){
                if(!is_array($value)){
                    return str_replace('{current_user_id}',$userId,$value);
                } else {
                    return array_map(function ($val) use($userId){
                        if(!is_array($val)){
                            return str_replace('{current_user_id}',$userId,$val);
                        }
                        return $val;
                    },$value);
                }
            },$where);
            $model = $model->where($where);
        }
        $modelConfig->model = $model;
        return $modelConfig;
    }

    public function getQueryModels($query){
        if(strpos($query,'query') === false && strpos($query,'mutation') === false) {
            $queryString = "query $query";
        } else {
            $queryString = $query;
        }
        $parser = new Parser();
        $parser->parse($queryString);
        if(!$parser->queryIsValid()){
           abort(response([
               'status'=>'failed',
               'message'=>'Invalid Graphql Query',
               'query'=>$queryString
           ],415));
        }
        $definitions = $parser->getParsedDocument()['definitions'];
        $allQueries = [];
        foreach ($definitions as $definition){
            $definition = json_decode(json_encode($definition));
            $queries = $this->getSelectionQuery($definition->selectionSet);
            $allQueries = array_merge($queries,$allQueries);
        }
        return $allQueries;
    }
    public function getMutationArguments($query){
        if(strpos($query,'query') === false && strpos($query,'mutation') === false) {
            $queryString = "query $query";
        } else {
            $queryString = $query;
        }
        $parser = new Parser();
        $parser->parse($queryString);
        if(!$parser->queryIsValid()){
           abort(response([
               'status'=>'failed',
               'message'=>'Invalid Graphql Query',
               'query'=>$queryString
           ],415));
        }
        $selection = $parser->getParsedDocument()['definitions'][0]['selectionSet']['selections'][0];
        $mutation = $selection['name']['value'];
        $selection = json_decode(json_encode($selection));
        $arguments = $this->getArgumentsAsArr($selection);
        $selectionFields = $this->getSelectionFieldsAsArr($selection->selectionSet->selections);
        return (object)[
            'mutation'=>$mutation,
            'arguments'=>$arguments,
            'selectionFields'=>$selectionFields
        ];
    }

    protected function getSelectionQuery($selectionSet, $parentSlug = null, $foundSlugs = []){
        if($parentSlug){
            $foundSlugs[] = $parentSlug;
        }
        foreach ($selectionSet->selections as $selection){
            $slug = $selection->name->value;
            if(isset($selection->selectionSet) && $selection->selectionSet){
                $fields = [];
                $newSlugs = $this->getSelectionQuery($selection->selectionSet, ltrim($parentSlug.'.'.$slug,'.'));
                if($newSlugs){
                    $foundSlugs = array_merge($foundSlugs,$newSlugs);
                }
            }
        }
        return $foundSlugs;
    }
}
