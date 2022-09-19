<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Tests\TestCase;
use function PHPUnit\Framework\stringContains;

class AuthTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;
    protected $email;
    public function test_register_without_password_confirmation(){
        $authEmail = $this->faker->email;
        $this->email = $authEmail;
        $password = "Super#2Secured";
        $payload = [
            'name'          => $this->faker->name,
            'email'         => $authEmail,
            'phone'         => $this->faker->phoneNumber,
            'password'      => $password,
        ];
        $this->post("api/auth/register", $payload)
            ->assertStatus(422)->assertJson([
                'status'=>'failed'
            ]);
    }
    public function test_register_with_password_confirmation()
    {

        $authEmail = $this->faker->email;
        $password = "Super#2Secured";
        $payload = [
            'name'          => $this->faker->name,
            'email'         => $authEmail,
            'phone'         => $this->faker->phoneNumber,
            'password'      => $password,
            'password_confirmation'=> $password,
        ];
        $this->post("api/auth/register", $payload)
            ->assertStatus(200)->assertJsonStructure([

                "status",
                "user" => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                "token"
            ]);;
    }

    public function test_register_with_existing_email(){
        $authEmail = $this->faker->email;
        $password = "Super#2Secured";
        $payload = [
            'name'          => $this->faker->name,
            'email'         => $authEmail,
            'phone'         => $this->faker->phoneNumber,
            'password'      => $password,
            'password_confirmation'=> $password,
        ];
        $this->post("api/auth/register", $payload)
            ->assertStatus(200);
        //register again with same details
        $this->post("api/auth/register", $payload)
            ->assertStatus(422)->assertJsonStructure([
                'status',
                'errors'=>[
                    'email'
                ]
            ]);
    }
    public function test_login_with_wrong_password(){
        $authEmail = $this->faker->email;
        $password = "Super#2Secured";
        $payload = [
            'name'          => $this->faker->name,
            'email'         => $authEmail,
            'phone'         => $this->faker->phoneNumber,
            'password'      => $password,
            'password_confirmation'=> $password,
        ];
        //create auth account
        $this->post("api/auth/register", $payload)
            ->assertStatus(200);
        //try login with wrong password
        $this->post("api/auth/login", [
            'email'=>$authEmail,
            'password'=>$password.'wrong--'.Str::random()
        ])
            ->assertStatus(422)->assertJsonStructure([
                'status',
                'errors'=>[
                    'email'
                ]
            ]);
    }
    public function test_login_with_correct_password(){
        $authEmail = $this->faker->email;
        $password = "Super#2Secured";
        $payload = [
            'name'          => $this->faker->name,
            'email'         => $authEmail,
            'phone'         => $this->faker->phoneNumber,
            'password'      => $password,
            'password_confirmation'=> $password,
        ];
        //create auth account
        $this->post("api/auth/register", $payload)
            ->assertStatus(200);
        //try login with wrong password
        $this->post("api/auth/login", [
            'email'=>$authEmail,
            'password'=>$password
        ])
            ->assertStatus(200)->assertJson([
                'status'=>'success'
            ]);
    }
}
