<?php

/*
 *  ====================================================
 *  Author              : Prateik  
 *  Module              : Trainer Courses API
 *  Created Date        : 13-03-2020
 *  Last Update Date    : 20-03-2020
 *  ====================================================
*/


require APPPATH.'libraries/REST_Controller.php';
require APPPATH.'libraries/Format.php';
require APPPATH.'vendor/firebase/php-jwt/src/BeforeValidException.php';
require APPPATH.'vendor/firebase/php-jwt/src/ExpiredException.php';
require APPPATH.'vendor/firebase/php-jwt/src/SignatureInvalidException.php';
require APPPATH.'vendor/firebase/php-jwt/src/JWT.php';
use \Firebase\JWT\JWT;


use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


class Courses extends REST_Controller {
    
  public function __construct() 
  {
     	parent::__construct();
     	$this->load->database();
     	$this->load->model('api/trainer/courses_model', 'courses_model');
        $this->load->model('api/trainer/Category_model', 'Category_model');

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
    public function show_get()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
        header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');
        
        $results = $this->courses_model->show_courses();
        if( $results != false )
        {

            foreach ($results as $result) 
            {
                $section_count = $this->courses_model->section_count($result->id);
                $lesson_count = $this->courses_model->lesson_count($result->id);
                $enroll_count = $this->courses_model->enroll_count($result->id);
                $category = $this->Category_model->get_category_by_id($result->category_id);
         
                $result->section_count = $section_count->section_count;
                $result->lesson_count = $lesson_count->lesson_count;
                $result->enroll_count = $enroll_count->enroll_count;
                $result->category = $category->name;
            }

            $message = [
                'status' => 1,
                'result'=>$results,
                'message'=>'List of all active categories'
            ];
        }
        else
        {
            $message = [
                'status' => 0,
                'message'=>"Something went wrong."
            ];
        }
        $this->response($message, REST_Controller::HTTP_OK);
    }
    public function get_get($id)
    {

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
        header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');
            
        $course_id = $id;
        if(empty($course_id))
        {
            $message = ['status' => 0,'message'=>"Invalid Inputs"];
        }
        else
        {
            $get_course = $this->courses_model->get_course_from_id($course_id);
            if($get_course != false)
            {
                $status = 1;
                $data = $get_course;
                $message = 'course fetched successfully.';
            }
            else
            {
                $status = 0;
                $data = $id;
                $message = 'course not found.';
            }
            $message = ['status' => $status, 'course' => $data,  'message' => $message];
        }
        $this->response($message, REST_Controller::HTTP_OK);
    }

    function trim_and_return_json($untrimmed_array) 
    {
        $trimmed_array = array();
        if(sizeof($untrimmed_array) > 0){
            foreach ($untrimmed_array as $row) {
                if ($row != "") {
                    array_push($trimmed_array, $row);
                }
            }
        }
        return json_encode($trimmed_array);
    }

 	public function create_post()
    {
        try
        {
   	        header("Access-Control-Allow-Origin: *");
     	    header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
     	    header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

            $data['title'] = html_escape($this->input->post('title'));
            $data['short_description'] = $this->input->post('short_desc');
            $data['description'] = $this->input->post('desc');

            $category_details = $this->courses_model->get_category_details_by_id($this->input->post('sub_category_id'))->row_array();
            
            $data['category_id'] = $category_details['parent'];
            $data['sub_category_id'] = $this->input->post('sub_category_id');
            $data['level'] = $this->input->post('level');
            $data['language'] = $this->input->post('language_made_in');
            // $data['is_top_course'] = $this->input->post('is_top_course');
            $data['is_free_course'] = '1';
            $data['is_top_course'] = '0';
            
            // $data['outcomes'] = $this->trim_and_return_json($this->input->post('outcomes'));
            // $data['requirements'] = $this->trim_and_return_json($this->input->post('requirements'));

            $data['outcomes'] = $this->input->post('outcomes');
            $data['requirements'] = $this->input->post('requirements');


            
            $data['course_overview_provider'] = $this->input->post('course_provider');
            $course_overview_provider = $this->input->post('course_provider');


            
            // if(!empty($_FILES['video_upload']['name']))
            // {
            //     $files = array('files' => $_FILES);
            

            //     $file_name = $_FILES['video_upload']['name'];   
            //     $temp_file_location = $_FILES['video_upload']['tmp_name']; 
            //     $bucketName = AWS_BUCKET_NAME;
            //     $IAM_KEY = AWS_IAM_KEY;
            //     $IAM_SECRET = AWS_IAM_SECRET;
                
            //     try 
            //     {
            //         $s3 = S3Client::factory(
            //             array(
            //                 'credentials' => array(
            //                     'key' => $IAM_KEY,
            //                     'secret' => $IAM_SECRET
            //                 ),
            //                 'version' => AWS_VERSION,
            //                 'region'  => AWS_REGION
            //             )
            //         );
            //     }

            //     catch (Exception $e) 
            //     {

            //     }

            //     $keyName=$file_name;
            //     $keyName=time().$file_name;
            //     $pathInS3 = 'https://s3.us-east-2.amazonaws.com/' . $bucketName . '/' . $keyName;
                
            //     try
            //     {
            //         $result=$s3->putObject(array(
            //             'Bucket'     => $bucketName,
            //             'Key'        => $keyName,
            //             'SourceFile' => $temp_file_location,
            //             'ContentType' =>'audio/mpeg',
            //             'ACL'          => 'public-read'
            //         ));  
            //         $data['video_url'] = $keyName;
            //     }
            //     catch (S3Exception $e) 
            //     {
            //         $data['video_url'] = '';
            //         die('Error:' . $e->getMessage());
            //     } 
            // }
            // else
            // {
            //     if($course_overview_provider != "video_upload")
            //     {
            //         $data['video_url'] = html_escape($this->input->post('course_url'));
            //     }
            //     else
            //     {
            //         $data['video_url'] = '';
            //     }
            // }
           

            if ($_FILES['thumbnail']['name'] != "") 
            {
                // move_uploaded_file($_FILES[$course_media]['tmp_name'], 'uploads/thumbnails/course_thumbnails/'.$course_media.'_'.get_frontend_settings('theme').'_'.$course_id.'.jpg');

                // move_uploaded_file($_FILES['thumbnail']['name'], 'uploads/thumbnails/course_thumbnails/'.time().'.jpg');



                $config['upload_path']          = FCPATH.'/uploads/thumbnails/course_thumbnails/';
                $config['allowed_types']        = 'gif|jpg|png|jpeg';
                $config['max_size']             = 500;
                $config['file_name']            = time();


                $this->load->library('upload', $config);
                if ( ! $this->upload->do_upload('thumbnail')) 
                {
                        $data = array(
                            'status' => '400', 
                            'error' => $this->upload->display_errors(), 
                        );
                }
                else 
                {
                    $uploadData = $this->upload->data();
                    $data['thumbnail'] = $uploadData['file_name'];

                    $message = $data;
                }
            }
            else
            {
                $data['thumbnail'] = base_url().'assets/frontend/default/img/course_thumbnail_placeholder.jpg';
            }
            
            $data['date_added'] = strtotime(date('D, d-M-Y'));
            $data['user_id'] = $this->input->post('trainer_id');       //get it from token
            // $data['user_id'] = 1;       //get it from token
            $data['is_admin'] = 0;
            $data['status'] = 'active';
           


            $result = $this->courses_model->create($data);
            if( $result == true )
            {
                $message = "Course Successfully created";
            }
            else
            {
                $message = "Course Not created";
            }
            $data = array('message' => $message, 'status' => '200');
            $this->response($message, REST_Controller::HTTP_OK);
            // $this->response($data, REST_Controller::HTTP_OK);
   	    } 
        catch (Exception $e) 
        {
            $message = 'Something wents wrong';
            $data = array('message' => $message, 'status' => '400');
            $this->response($data, REST_Controller::HTTP_OK);
        }
        // $this->response($_POST, REST_Controller::HTTP_OK);
    }

    public function edit_post(){
        try{
          header("Access-Control-Allow-Origin: *");
          header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
          header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

          $data['id'] = $this->input->post('id');
          $data['title'] = html_escape($this->input->post('title'));
          $data['short_description'] = $this->input->post('short_description');
          $data['description'] = $this->input->post('description');
          $category_details = $this->courses_model->get_category_details_by_id($this->input->post('sub_category_id'))->row_array();
          $data['category_id'] = $category_details['parent'];
          $data['sub_category_id'] = $this->input->post('sub_category_id');
          $data['level'] = $this->input->post('level');
          $data['language'] = $this->input->post('language_made_in');
          $data['is_top_course'] = $this->input->post('is_top_course');
          $data['is_free_course'] = $this->input->post('is_free_course');
          $data['course_overview_provider'] = $this->input->post('course_overview_provider');
          $course_overview_provider = $this->input->post('course_overview_provider');
          if(!empty($_FILES['video_upload']['name'])){

            $file_name = $_FILES['video_upload']['name'];   
            $temp_file_location = $_FILES['video_upload']['tmp_name']; 
            $bucketName = AWS_BUCKET_NAME;
            $IAM_KEY = AWS_IAM_KEY;
            $IAM_SECRET = AWS_IAM_SECRET;
            try {
              $s3 = S3Client::factory(
                  array(
                      'credentials' => array(
                      'key' => $IAM_KEY,
                      'secret' => $IAM_SECRET
                  ),
                  'version' => AWS_VERSION,
                  'region'  => AWS_REGION
                  )
              );
            } 
            catch (Exception $e) {}
            $keyName=$file_name;
            $keyName=time().$file_name;
            $pathInS3 = 'https://s3.us-east-2.amazonaws.com/' . $bucketName . '/' . $keyName;
            try{
                $result=$s3->putObject(array(
                    'Bucket'     => $bucketName,
                    'Key'        => $keyName,
                    'SourceFile' => $temp_file_location,
                    'ContentType' =>'audio/mpeg',
                    'ACL'          => 'public-read'
                ));  
                $data['video_url'] = $keyName;
            }catch (S3Exception $e) {
                $data['video_url'] = '';
                die('Error:' . $e->getMessage());
            } 
          }else{
            if($course_overview_provider != "video_upload"){
              $data['video_url'] = html_escape($this->input->post('course_overview_url'));
            }else{
              $data['video_url'] = '';
            }
          }
          $course_media_files = themeConfiguration(get_frontend_settings('theme'), 'course_media_files');
          foreach ($course_media_files as $course_media => $size){
            if ($_FILES[$course_media]['name'] != "") {
              // move_uploaded_file($_FILES[$course_media]['tmp_name'], 'uploads/thumbnails/course_thumbnails/'.$course_media.'_'.get_frontend_settings('theme').'_'.$course_id.'.jpg');
              move_uploaded_file($_FILES[$course_media]['tmp_name'], 'uploads/thumbnails/course_thumbnails/'.$course_media.'_'.get_frontend_settings('theme').'.jpg');

            }
          }
          $data['thumbnail'] = $_FILES[$course_media]['name'];
          $data['date_added'] = strtotime(date('D, d-M-Y'));
          // $data['user_id'] = $this->session->userdata('user_id');
          $data['user_id'] = 1;
          // $admin_details = $this->user_model->get_admin_details()->row_array();
          if ($admin_details['id'] = $data['user_id']) {
            $data['is_admin'] = 1;
          }else{
            $data['is_admin'] = 0;
          }
          if ($param1 = "save_to_draft"){
            $data['status'] = 'draft';
          }else{
            if (true == true) {
              $data['status'] = 'active';
            }else{
              $data['status'] = 'pending';
            }
          }
          $result = $this->courses_model->update($data);
          if( $result == true){
            $message = "Course Successfully Updated";
          }else{
            $message = "Not Updated";
          }
          $this->response($message, REST_Controller::HTTP_OK);
        } catch (Exception $e) 
        {
          $this->response('Something wents wrong', REST_Controller::HTTP_OK);
        }
    }

    public function delete_get($id)
    {
        try{
          header("Access-Control-Allow-Origin: *");
          header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
          header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

          $data['course_id'] = $id;
          $result = $this->courses_model->delete($data);
          if( $result == true){
            $message = ['status' => 1,'message'=>"Deleted Successfully"];
          }else{
            $message = ['status' => 0, 'message'=>"Invalid Inputs"];
          }
          $this->response($message, REST_Controller::HTTP_OK);
        } catch (Exception $e) {
          $this->response('Something wents wrong', REST_Controller::HTTP_OK);
        }   
    }


    public function show_section_get($course_id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
        header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');
        
        $results = $this->courses_model->show_section($course_id);

        // print_r($results);
        // die();

        if( $results != false )
        {

            $message = [
                'status' => 1,
                'result'=>$results,
                'message'=>'List of all active categories'
            ];
        }
        else
        {
            $message = [
                'status' => 0,
                'message'=>"Something went wrong."
            ];
        }
        $this->set_response($message, REST_Controller::HTTP_OK); // HTTP_OK (200)
    }

    public function get_section_get($section_id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
        header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');
        
        $results = $this->courses_model->get_section_by_id($section_id);

        if( $results != false )
        {

            $message = [
                'status' => 1,
                'result'=>$results,
                'message'=>'Details of section'
            ];
        }
        else
        {
            $message = [
                'status' => 0,
                'message'=>"Something went wrong."
            ];
        }
        $this->set_response($message, REST_Controller::HTTP_OK); // HTTP_OK (200)
    }


    public function get_lesson_get($lesson_id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
        header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');
        
        $results = $this->courses_model->get_lesson($lesson_id);

        // print_r($results);
        // die();

        if( $results != false )
        {

            $message = [
                'status' => 1,
                'result'=>$results,
                'message'=>'Details of lesson'
            ];
        }
        else
        {
            $message = [
                'status' => 0,
                'message'=>"Something went wrong."
            ];
        }
        $this->set_response($message, REST_Controller::HTTP_OK); // HTTP_OK (200)
    }


    public function add_section_post()
    {
        try
        {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
            header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

            $data['title'] = $this->input->post('title');
            $data['course_id'] = $this->input->post('course_id');
            
            $result = $this->courses_model->add_section($data);
            
            if( $result == true )
            {
                $message = "Section Successfully Created";
                $data = array('status' => '200', 'message' => $message);
            }
            else
            {
                $message = "Section Not created";
                $data = array('status' => '400', 'message' => $message);
            }
            
            $this->response($message, REST_Controller::HTTP_OK);
        }
        catch (Exception $e) 
        {
            $this->response('Something wents wrong', REST_Controller::HTTP_OK);
        }
    }

    public function edit_section_post()
    {
        try
        {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
            header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

            $data['id'] = $this->input->post('id');
            $data['title'] = html_escape($this->input->post('title'));
            $data['course_id'] = $this->input->post('course_id');
            $result = $this->courses_model->update_section($data);
            
            if( $result == true)
            {
                $message = "Section Successfully Updated";
            }
            else
            {
                $message = "Section Not Updated";
            }
            $this->response($message, REST_Controller::HTTP_OK);
        }
        catch (Exception $e) 
        {
            $this->response('Something wents wrong', REST_Controller::HTTP_OK);
        }
    }

    public function delete_section_get($id)
    {
        try
        {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
            header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

            $data['section_id'] = $id;
            if(is_null($data))
            {
                $message = ['status' => 0, 'message'=>"Invalid Inputs"];
            }
            else
            {
                $section_data = $this->courses_model->get_section_by_id($data['section_id']);
                $data['course_id'] = $section_data->course_id;
                $result = $this->courses_model->delete_section($data);
                $message = ['status' => 1,'message'=>"Deleted Successfully"];
            }
            $this->response($message, REST_Controller::HTTP_OK);
        }
        catch (Exception $e) 
        {
            $this->response('Something wents wrong', REST_Controller::HTTP_OK);
        }
    }


    public function add_lesson_post()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
        header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');
        
        try
        {

            // echo "<pre>";
            // print_r($_POST);exit();
            $data['title'] = html_escape($this->input->post('title'));
            $data['course_id'] = html_escape($this->input->post('course_id'));
            $data['section_id'] = html_escape($this->input->post('section_id'));
            // $lesson_type_array = explode('-', $this->input->post('lesson_type'));
            // $lesson_type = $lesson_type_array[0];
            // $data['lesson_type'] = $lesson_type;
            $data['lesson_type'] = $this->input->post('lesson_type');
            $data['summary'] = $this->input->post('summary');
            // $data['attachment_type'] = $lesson_type_array[1];

            if($this->input->post('lesson_type') == 'video')
            {
                $lesson_provider = $this->input->post('lesson_provider');
                
                if ($lesson_provider == 'youtube' || $lesson_provider == 'vimeo') 
                {
                    if ($this->input->post('video_url') == "" || $this->input->post('duration') == "") 
                    {
                        $message = "Invalid lesson url and duration";
                    }
                    $data['video_url'] = html_escape($this->input->post('video_url'));
                    $duration_formatter = explode(':', $this->input->post('duration'));
                    $hour = sprintf('%02d', $duration_formatter[0]);
                    $min = sprintf('%02d', $duration_formatter[1]);
                    $sec = sprintf('%02d', $duration_formatter[2]);
                    $data['duration'] = $hour.':'.$min.':'.$sec;

                    $video_details = $this->video_model->getVideoDetails($data['video_url']);
                    $data['video_type'] = $video_details['provider'];
                }
                elseif ($lesson_provider == 'video_upload') 
                {
                    if (!empty($_FILES['video_upload']['name']) || $this->input->post('duration') != "") 
                    {
                        if(!empty($_FILES['video_upload']['name']))
                        {
                            $file_name = $_FILES['video_upload']['name'];   
                            $temp_file_location = $_FILES['video_upload']['tmp_name']; 
            
                            $bucketName = AWS_BUCKET_NAME;
                            $IAM_KEY = AWS_IAM_KEY;
                            $IAM_SECRET = AWS_IAM_SECRET;           
                            try 
                            {
                                $s3 = S3Client::factory(
                                    array(
                                        'credentials' => array(
                                            'key' => $IAM_KEY,
                                            'secret' => $IAM_SECRET
                                        ),
                                        'version' => AWS_VERSION,
                                        'region'  => AWS_REGION
                                    )
                                );
                            }
                            catch (Exception $e) {}
                            $keyName = $file_name;
                            $keyName = time().$file_name;
                            $pathInS3 = 'https://s3.us-east-2.amazonaws.com/' . $bucketName . '/' . $keyName;
             
                            try 
                            { 
                                $result=$s3->putObject(array(
                                    'Bucket'     => $bucketName,
                                    'Key'        => $keyName,
                                    'SourceFile' => $temp_file_location,
                                    'ContentType' =>'audio/mpeg', //<-- this is what you need!
                                    'ACL'          => 'public-read'//<-- this makes it public so people can see it
                                ));
                                $data['video_url'] = $keyName;
                            }
                            catch (S3Exception $e) {
                                $data['video_url'] = '';
                            } 
                        }
                        else
                        {
                            //$data['video_url'] = '';
                            //echo "no file";exit;
                        }
                    }
                    else
                    {
                        $message = "Invalid lesson url and duration"; 
                        $status  = '400';
                    }
                    if ($_FILES['thumbnail']['name'] != "") 
                    {
                        $fileName_thumbnail           = $_FILES['thumbnail']['name'];
                        $tmp_thumbnail                = explode('.', $fileName_thumbnail);
                        $fileExtension_thumbnail      = end($tmp_thumbnail);
                        $uploadable_file_thumbnail    =  md5(uniqid(rand(), true)).'.'.$fileExtension_thumbnail;
                        $data['attachment'] = $uploadable_file_thumbnail;


                        if (!file_exists('uploads/thumbnails/lesson_thumbnails')) 
                        {
                            mkdir('uploads/thumbnails/lesson_thumbnails', 0777, true);
                        }
                        // move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/thumbnails/lesson_thumbnails/inserted_id.jpg');
                        move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/thumbnails/lesson_thumbnails/'.$uploadable_file_thumbnail);
                    }
                    $duration_formatter = explode(':', $this->input->post('duration'));
                    $hour = sprintf('%02d', $duration_formatter[0]);
                    $min = sprintf('%02d', $duration_formatter[1]);
                    $sec = sprintf('%02d', $duration_formatter[2]);
                    $data['duration'] = $hour.':'.$min.':'.$sec;
                    $data['video_type'] = 'video_upload';
                } 
                else 
                {
                    $message = "Invalid lesson provider";
                    $status  = '400';
                }    
            }
            else
            {



                if ($_FILES['attachment']['name'] == "") 
                {
                    $message = "Invalid attachment";
                    $status  = '400';
                }
                else
                {
                    $fileName           = $_FILES['attachment']['name'];
                    $tmp                = explode('.', $fileName);
                    $fileExtension      = end($tmp);
                    $uploadable_file    =  md5(uniqid(rand(), true)).'.'.$fileExtension;
                    $data['attachment'] = $uploadable_file;
                    if (!file_exists('uploads/lesson_files')) 
                    {
                        mkdir('uploads/lesson_files', 0777, true);
                    }
                    move_uploaded_file($_FILES['attachment']['tmp_name'], 'uploads/lesson_files/'.$uploadable_file);
                }
            }

            $data['date_added'] = strtotime(date('D, d-M-Y'));

            // if($_FILES['thumbnail'])
            // {

            //     if ($_FILES['thumbnail']['name'] != "") 
            //     {
            //         $fileName_thumbnail           = $_FILES['thumbnail']['name'];
            //         $tmp_thumbnail                = explode('.', $fileName_thumbnail);
            //         $fileExtension_thumbnail      = end($tmp_thumbnail);
            //         $uploadable_file_thumbnail    =  md5(uniqid(rand(), true)).'.'.$fileExtension_thumbnail;
            //         $data['attachment'] = $uploadable_file_thumbnail;


            //         if (!file_exists('uploads/thumbnails/lesson_thumbnails')) 
            //         {
            //             mkdir('uploads/thumbnails/lesson_thumbnails', 0777, true);
            //         }
            //         // move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/thumbnails/lesson_thumbnails/inserted_id.jpg');
            //         move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/thumbnails/lesson_thumbnails/'.$uploadable_file_thumbnail);
            //     }
            // }
            $result = $this->courses_model->add_lesson($data);
            if( $result == true )
            {
                $message = "Lesson Successfully Created";
                $status  = '200';
            }
            else
            {
                $message = "Lesson Not created";
                $status  = '400';
            }
            $feed = array(
                            'status' => $status, 
                            'message' => $message, 
                        );
            $this->response($feed, REST_Controller::HTTP_OK);
        }
        catch (Exception $e) 
        {
            $this->response('Something wents wrong', REST_Controller::HTTP_OK);
        }
    }

    public function edit_lesson_post()
    {
        try
        {

            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
            header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

            $data['id'] = html_escape($this->input->post('id'));
            $data['title'] = html_escape($this->input->post('title'));
            $data['course_id'] = html_escape($this->input->post('course_id'));
            $data['section_id'] = html_escape($this->input->post('section_id'));
            // $lesson_type_array = explode('-', $this->input->post('lesson_type'));
            // $lesson_type = $lesson_type_array[0];
            // $data['lesson_type'] = $lesson_type;
            // $data['attachment_type'] = $lesson_type_array[1];

            $data['lesson_type'] = $this->input->post('lesson_type');
            $data['summary'] = $this->input->post('summary');
            // $data['lesson_provider'] = $this->input->post('lesson_provider');
            

            if($this->input->post('lesson_type') == 'video')
            {
                $lesson_provider = $this->input->post('lesson_provider');
                if ($lesson_provider == 'youtube' || $lesson_provider == 'vimeo') 
                {
                    if ($this->input->post('video_url') == "" || $this->input->post('duration') == "") 
                    {
                        $message = "Invalid lesson url and duration";
                        $status  = '400';
                    }
                    $data['video_url'] = html_escape($this->input->post('video_url'));
                    $duration_formatter = explode(':', $this->input->post('duration'));
                    $hour = sprintf('%02d', $duration_formatter[0]);
                    $min = sprintf('%02d', $duration_formatter[1]);
                    $sec = sprintf('%02d', $duration_formatter[2]);
                    $data['duration'] = $hour.':'.$min.':'.$sec;

                    $video_details = $this->video_model->getVideoDetails($data['video_url']);
                    $data['video_type'] = $video_details['provider'];
                }
                // // elseif ($lesson_provider == 'html5') 
                // // {
                // //     if ($this->input->post('html5_video_url') == "" || $this->input->post('html5_duration') == "") 
                // //     {
                // //         $message = "Invalid lesson url and duration";
                // //     }
                // //     $data['video_url'] = html_escape($this->input->post('html5_video_url'));
                // //     $duration_formatter = explode(':', $this->input->post('html5_duration'));
                // //     $hour = sprintf('%02d', $duration_formatter[0]);
                // //     $min = sprintf('%02d', $duration_formatter[1]);
                // //     $sec = sprintf('%02d', $duration_formatter[2]);
                // //     $data['duration'] = $hour.':'.$min.':'.$sec;
                // //     $data['video_type'] = 'html5';
                // // }
                elseif ($lesson_provider == 'video_upload')
                {
                    if (!empty($_FILES['video_upload']['name']) || $this->input->post('duration') != "") 
                    {
                        if(!empty($_FILES['video_upload']['name']))
                        {
                            $file_name = $_FILES['video_upload']['name'];   
                            $temp_file_location = $_FILES['video_upload']['tmp_name']; 

                            $bucketName = AWS_BUCKET_NAME;
                            $IAM_KEY = AWS_IAM_KEY;
                            $IAM_SECRET = AWS_IAM_SECRET;
                           
                            try 
                            {
                                $s3 = S3Client::factory(
                                    array(
                                      'credentials' => array(
                                          'key' => $IAM_KEY,
                                          'secret' => $IAM_SECRET
                                      ),
                                      'version' => AWS_VERSION,
                                      'region'  => AWS_REGION
                                    )
                                );
                            }
                            catch (Exception $e) {}
                            $keyName = $file_name;
                            $keyName = time().$file_name;
                            $pathInS3 = 'https://s3.us-east-2.amazonaws.com/' . $bucketName . '/' . $keyName;
             
                            try 
                            { 
                                $result=$s3->putObject(array(
                                    'Bucket'     => $bucketName,
                                    'Key'        => $keyName,
                                    'SourceFile' => $temp_file_location,
                                    'ContentType' =>'audio/mpeg', //<-- this is what you need!
                                    'ACL'          => 'public-read'//<-- this makes it public so people can see it
                                ));
                                $data['video_url'] = $keyName;
                            } 
                            catch (S3Exception $e) 
                            {
                                $data['video_url'] = '';
                            } 
                        }
                        else
                        {
                            //$data['video_url'] = '';
                            //echo "no file";exit;
                        }
                    }
                    else
                    {
                        $message = "Invalid lesson url and duration"; 
                        $status  = '400';
                    }

                    $duration_formatter = explode(':', $this->input->post('duration'));
                    $hour = sprintf('%02d', $duration_formatter[0]);
                    $min = sprintf('%02d', $duration_formatter[1]);
                    $sec = sprintf('%02d', $duration_formatter[2]);
                    $data['duration'] = $hour.':'.$min.':'.$sec;
                    $data['video_type'] = 'video_upload';
                }
                else 
                {
                    $message = "Invalid lesson provider";
                    $status  = '400';
                }
            }
            else
            {
                if ($_FILES['attachment']['name'] == "") 
                {
                    $message = "Invalid attachment";
                    $status  = '400';
                }
                else
                {
                    $fileName           = $_FILES['attachment']['name'];
                    $tmp                = explode('.', $fileName);
                    $fileExtension      = end($tmp);
                    $uploadable_file    =  md5(uniqid(rand(), true)).'.'.$fileExtension;
                    $data['attachment'] = $uploadable_file;
                    if (!file_exists('uploads/lesson_files')) 
                    {
                        mkdir('uploads/lesson_files', 0777, true);
                    }
                    move_uploaded_file($_FILES['attachment']['tmp_name'], 'uploads/lesson_files/'.$uploadable_file);
                }
            }

            $data['date_added'] = strtotime(date('D, d-M-Y'));

            if($_POST['thumbnail'] != '')
            {
                if ($_FILES['thumbnail']['name'] != "") 
                {
                    $fileName_thumbnail           = $_FILES['thumbnail']['name'];
                    $tmp_thumbnail                = explode('.', $fileName_thumbnail);
                    $fileExtension_thumbnail      = end($tmp_thumbnail);
                    $uploadable_file_thumbnail    =  md5(uniqid(rand(), true)).'.'.$fileExtension_thumbnail;
                    $data['attachment'] = $uploadable_file_thumbnail;


                    if (!file_exists('uploads/thumbnails/lesson_thumbnails')) 
                    {
                        mkdir('uploads/thumbnails/lesson_thumbnails', 0777, true);
                    }
                    // // move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/thumbnails/lesson_thumbnails/inserted_id.jpg');
                    move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/thumbnails/lesson_thumbnails/'.$uploadable_file_thumbnail);
                }
            }

            $result = $this->courses_model->update_lesson($data);
            if( $result == true)
            {
                $message = "Lesson Successfully Updated";
                $status  = '200';
            }
            else
            {
                $message = "Not Updated";
                $status  = '400';
            }

            $feed = array(
                            'status' => $status, 
                            'message' => $message, 
                        );
            $this->response($feed, REST_Controller::HTTP_OK);
            // $inserted_id = $this->db->insert_id();
        }
        catch (Exception $e) 
        {
            $this->response('Something wents wrong', REST_Controller::HTTP_OK);
        }
        // $this->response($_POST, REST_Controller::HTTP_OK);
    }

   public function delete_lesson_get($id){
    try{
      header("Access-Control-Allow-Origin: *");
      header("Access-Control-Request-Headers: GET,POST,OPTIONS,DELETE,PUT");
      header('Access-Control-Allow-Headers: Accept,Accept-Language,Content-Language,Content-Type');

      $data['lesson_id'] = $id;
      $result = $this->courses_model->delete_lesson($data);
      if( $result == true){
        $message = ['status' => 1,'message'=>"Deleted Successfully"];
      }else{
        $message = ['status' => 0, 'message'=>"Invalid Inputs"];
      }
      $this->response($message, REST_Controller::HTTP_OK);
    }catch (Exception $e) {
      $this->response('Something wents wrong', REST_Controller::HTTP_OK);
    }
  }
}