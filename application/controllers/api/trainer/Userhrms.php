<?php

/*
 *  ====================================================
 *  Author              : Prateik D 
 *  Module              : Trainer HRMS User API
 *  Created Date        : 28-02-2020
 *  Last Update Date    : 11-03-2020
 *  ====================================================
*/


require APPPATH.'libraries/REST_Controller.php';
require APPPATH.'libraries/Format.php';
require APPPATH.'vendor/firebase/php-jwt/src/BeforeValidException.php';
require APPPATH.'vendor/firebase/php-jwt/src/ExpiredException.php';
require APPPATH.'vendor/firebase/php-jwt/src/SignatureInvalidException.php';
require APPPATH.'vendor/firebase/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;

    
class Userhrms extends REST_Controller {
    
    public function __construct() 
    {
       	parent::__construct();
       	$this->load->database();
       	$this->load->model('api/trainer/userhrms_model', 'userhrms_model');

        $jwt=$this->input->get_request_header('token');

        // if(!empty($jwt))
        // {   
        // 	try
        //     {
        //     	$decoded = JWT::decode($jwt, $this->config->item('jwt_key'), array('HS256'));
          
        //         $this->admin_id = $decoded->admin_id;
        //         $this->admin_username = $decoded->admin_username;
        //         $this->admin_email = $decoded->admin_email;
        //     }
        //     catch(Exception $e)
        //     {
        //     	$message = ['status' => 0,'message'=>"Invalid token"];
        //         // $this->response($message, REST_Controller::HTTP_BAD_REQUEST); 
        //         // $this->set_response($message, REST_Controller::HTTP_OK);
        //         echo $message = json_encode($message);
        //         die;
        //     }    
        // }
        // else
        // {
        // 	$message = ['status' => '0','message'=>"Access denied"];
        //     // $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
        //     // $this->set_response($message, REST_Controller::HTTP_OK);   
        //     echo $message = json_encode($message);
        //     die;
        // }
    }

   	public function add_user_post()
   	{

        // print_r("expression");

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
        header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

        $hrms_id = $this->post('hrms_id');
        $first_name = $this->post('first_name');
        $last_name = $this->post('last_name');
        $email = $this->post('email');
        $usertype = $this->post('usertype');

        $date_added = strtotime("now");
        $password = password_hash('123456', PASSWORD_DEFAULT);

        $result = $this->userhrms_model->get_user($email);



        if($result == false)
        {
            // $data = '1';

            if($usertype == 'user')
            {
                $role_id = '2';
            }
            else if($usertype == 'supervisor')
            {
                $role_id = '4';
            }

            $info = array(
                            'hrms_id' =>$hrms_id,
                            'first_name' =>$first_name,
                            'last_name' =>$last_name,
                            'email' =>$email,
                            'role_id' =>$role_id,
                            'password' =>$password,
                            'date_added' =>$date_added,
                            'last_modified' =>$date_added,
                            'status' =>'1',
                        );

            $hrms = $this->userhrms_model->create($info);

            if ($hrms == true) 
            {
                
                $status = '200';
                $message = 'User added successfully';

            }

        }
        else
        {
            if(
                ($usertype == 'user' && $result->is_supervisor == '0') ||
                ($usertype == 'supervisor' && $result->is_supervisor == '1')
                )
            {
                $status = '400';
                $message = 'User already exist';
            }
            else
            {
                if($usertype == 'user')
                {
                    $user_edit = $this->userhrms_model->make_user($result->id);
                }
                else if($usertype == 'supervisor')
                {
                    $supervisor_edit = $this->userhrms_model->make_supervisor($result->id);
                }

                $status = '200';
                $message = 'User Updated';   
            }

        }


        $data = array(
                        'status' => $status, 
                        'message' => $message
                    );



        $this->set_response($data, REST_Controller::HTTP_OK); // HTTP_OK (200)


    }
}