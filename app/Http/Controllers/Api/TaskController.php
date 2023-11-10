<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskFilter;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public $selectedFileds = ["id","name","status","description","created_at","end_date"];

    /**
     * Display a listing of the resource.
     */
    public function index(TaskFilter $filter)
    {
        
       
        $fileter = $filter->validated();
        $query = Task::query()->select($this->selectedFileds);

        //Order
        $sortField = request('sort_field');
        if($sortField && in_array($sortField,$this->selectedFileds)){
            $sortDirection = request('sort_direction',"desc");
            $query->orderBy($sortField,$sortDirection);
            unset($fileter["sort_direction"]);
            unset($fileter["sort_field"]);
        }
        
        //Filter
        foreach($fileter as $fieldName => $filterValue){
            $query->where($fieldName,$filterValue);
        }
      
        return  TaskResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaskStoreRequest $request)
    {
        
        return new TaskResource(Task::create($request->validated()));

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new TaskResource(Task::select($this->selectedFileds)->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskStoreRequest $request, Task $task)
    {
        $task->update($request->validated());
        
        return new TaskResource($task);
     
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return response()->noContent();
    }
}
