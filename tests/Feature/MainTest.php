<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;

class MainTest extends TestCase
{
   /**
    * Get user already exist or create if not exist
    *
    * @return User
    */
    public function getUser():User{
        return (($user = User::query()->limit(1)->first()) ==true )?$user:User::factory()->create();
    }
    /**
     * GetLastTask
     *
     * @return Task
     */
    public function getLastTask():Task{
        return Task::query()->limit(1)->orderByDesc("id")->first();
    }

    function getTaskCount(): int{
        return DB::table('tasks')->count();
    }
    /**
     * Get random task
     *
     * @return Task
     */
    public function getRandomTask():Task{
        $tasks = Task::all();
        $count = $this->getTaskCount();
        return $tasks[rand(1,$count-1)];
    }
    public function test_the_application_sanctum_csrf(){
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertCookie('XSRF-TOKEN');
        $response->assertNoContent();
        $response->assertStatus(204);   

    }
    /**
     * Test authorization
     *
     * @return void
     */
    public function test_the_application_authorization(){
        $user = $this->getUser();
       
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            ])
            ->post('/login',["email" => $user->email,"password"=> "password"]);

        $response->assertNoContent();
        $response->assertStatus(204);
    }
    /**
     * Test get task list
     *
     * @return void
     */
    public function test_the_application_get_all_tasks(){
       
        $user = $this->getUser();
        $count = $this->getTaskCount();
        $url = '/api/tasks';

        $response = $this->actingAs($user)
                        ->withSession(['banned' => false])
                        ->withHeaders([
                        'Accept' => 'application/json',
                        ])->get($url);

        $response ->assertJson(fn (AssertableJson $json) =>$json
                        ->has('data.0.id')
                        ->has('data.0.name')
                        ->has('data.0.status')
                        ->has('data.0.description')
                        ->has('data.0.created_at')
                        ->has('data.0.end_date')
                        ->missing('data.0.updated_at')
                        ->etc()
                );                    
        $response->assertJsonCount($count,"data") ;   
        //$response->dump();
        $response->assertStatus(200);
    }
    /**
     * If wrong Task Id
     *
     * @return void
     */
    public function test_the_application_error_task_if_wrong_id(){
        $user = $this->getUser();
        $task_id= $this->getRandomTask()["id"];
        $url = '/api/tasks/'.PHP_INT_MAX;
        $response = $this->actingAs($user)
                        ->withSession(['banned' => false])
                        ->withHeaders([
                        'Accept' => 'application/json',
                        ])->get($url);
                        
               
        $response->assertStatus(404);                
    }
    /**
     * Test get task by id
     *
     * @return void
     */
    public function test_the_application_get_task_by_id(){
        $user = $this->getUser();
        $task_id= $this->getRandomTask()["id"];
        $url = '/api/tasks/'. $task_id;
        $response = $this->actingAs($user)
                        ->withSession(['banned' => false])
                        ->withHeaders([
                        'Accept' => 'application/json',
                        ])->get($url);
                        
        
        $response ->assertJson(fn (AssertableJson $json) =>$json->where('data.id', $task_id)
                                ->has('data.id')
                                ->has('data.name')
                                ->has('data.status')
                                ->has('data.description')
                                ->has('data.created_at')
                                ->has('data.end_date')
                                ->missing('data.updated_at')
                                ->etc()
                        );
        // $response->dump(); 
        $response->assertJsonCount(1) ;                 
        $response->assertJsonCount(6, "data") ;            
        $response->assertStatus(200);                
    }
    
    /**
     * Test create new Task
     *
     * @return void
     */
    public function test_the_application_get_create_test(){
        $user = $this->getUser();
        $url = '/api/tasks';
        $response = $this->actingAs($user)
            ->withSession(['banned' => false])
            ->withHeaders([
            'Accept' => 'application/json',
            ])->post($url,['name' => fake()->text(),'description' => fake()->text()]);

        $response->assertStatus(201);      
    }

    /**
     * Update
     *
     * @return void
     */
    public function test_the_application_update(){
        $user = $this->getUser();
        $task_id= $this->getRandomTask()["id"];
        $url = '/api/tasks/'.$task_id;

        $newName = fake()->text();
        $newDecription = fake()->text();
        $newStatus = 1;
        $newEndDate = date("Y-m-d H:i:s");

        $response = $this->actingAs($user)
            ->withSession(['banned' => false])
            ->withHeaders([
            'Accept' => 'application/json',
            ])->post($url,[
                '_method' => "PUT",
                "name" =>$newName, 
                "description" =>$newDecription,
                "status" => $newStatus,
                "end_date" => $newEndDate
            ]);
         
         
        $updatedTask = Task::query()->where("id",$task_id)->first();   

        $this->assertEquals($newName,$updatedTask->name); 
        $this->assertEquals($newDecription,$updatedTask->description);
        $this->assertEquals($newStatus,$updatedTask->status); 
        $this->assertEquals($newEndDate,$updatedTask->end_date);
        $response->assertStatus(200);    
    }
    /**
     * Delete by ID
     *
     * @return void
     */
    public function test_the_application_delete_task_by_id(){
        $user = $this->getUser();
        $task_id= $this->getRandomTask()["id"];
        $url = '/api/tasks/'.$task_id;
        $count = $this->getTaskCount();

        $response = $this->actingAs($user)
            ->withSession(['banned' => false])
            ->withHeaders([
            'Accept' => 'application/json',
            ])->post($url,['_method' => "DELETE"]);

        $_count = $this->getTaskCount();   
        $this->assertEquals($count, $_count+1); 
        $response->assertNoContent();
        $response->assertStatus(204);
    }
    /**
     * Test filter by status and end_date
     *
     * @return void
     */
    public function test_the_application_get_tasks_by_filter(){
        $user = $this->getUser();
        $task = $this->getRandomTask();
        $status = $task->status;
        $end_date = $task->end_date;
        if($end_date != NULL){
            $url = '/api/tasks/?status='.$status.'&end_date='.$end_date;
            $task_id = Task::query()->where("status",$status)->where("end_date",$end_date)->first()->id;
        }
        else{
            $url = '/api/tasks/?status='.$status;
            $task_id = Task::query()->where("status",$status)->first()->id;
        }    
            
        $response = $this->actingAs($user)
                        ->withSession(['banned' => false])
                        ->withHeaders([
                        'Accept' => 'application/json',
                        ])->get($url);
        
             $response ->assertJson(fn (AssertableJson $json) =>$json->where('data.0.id', $task_id)
                        ->etc()
                );                
                     
        $response->assertStatus(200);                 
    }
    /**
     * Order by status DESC
     *
     * @return void
     */
    public function test_the_application_order_by_status_desc(){
        $user = $this->getUser();
        $url = '/api/tasks/?sort_field=status&sort_direction=desc';
       
        $response = $this->actingAs($user)
                        ->withSession(['banned' => false])
                        ->withHeaders([
                        'Accept' => 'application/json',
                        ])->get($url);

        $task_id = Task::query()->orderByDesc("status")->first()->id;
        $response ->assertJson(fn (AssertableJson $json) =>$json->where('data.0.id', $task_id)
                ->etc()
        );  
          
        $response->assertStatus(200);
    }
    /**
     * Order by status ASC
     *
     * @return void
     */
    public function test_the_application_order_by_status_asc(){
        $user = $this->getUser();
        $url = '/api/tasks/?sort_field=status&sort_direction=asc';
       
        $response = $this->actingAs($user)
                        ->withSession(['banned' => false])
                        ->withHeaders([
                        'Accept' => 'application/json',
                        ])->get($url);

        $task_id = Task::query()->orderBy("status")->first()->id;
        $response ->assertJson(fn (AssertableJson $json) =>$json->where('data.0.id', $task_id)
                ->etc()
        );  
          
        $response->assertStatus(200);
    }
    /**
     * Order by end_date DESC
     *
     * @return void
     */
    public function test_the_application_order_by_end_date_desc(){
        $user = $this->getUser();
        $url = '/api/tasks/?sort_field=end_date&sort_direction=desc';
       
        $response = $this->actingAs($user)
                        ->withSession(['banned' => false])
                        ->withHeaders([
                        'Accept' => 'application/json',
                        ])->get($url);

                        
        $task_id = Task::query()->orderByDesc("end_date")->first()->id;
        $response ->assertJson(fn (AssertableJson $json) =>$json->where('data.0.id', $task_id)
                ->etc()
        );  
      //  $response->dump();  
        $response->assertStatus(200);
    }
    /**
     * Order by end_date ASC
     *
     * @return void
     */
    public function test_the_application_order_by_end_date_asc(){
        $user = $this->getUser();
        $url = '/api/tasks/?sort_field=end_date&sort_direction=asc';
       
        $response = $this->actingAs($user)
                        ->withSession(['banned' => false])
                        ->withHeaders([
                        'Accept' => 'application/json',
                        ])->get($url);

        $task_id = Task::query()->orderBy("end_date")->first()->id;
        $response ->assertJson(fn (AssertableJson $json) =>$json->where('data.0.id', $task_id)
                ->etc()
        );  
      //  $response->dump();  
        $response->assertStatus(200);
    }
}
