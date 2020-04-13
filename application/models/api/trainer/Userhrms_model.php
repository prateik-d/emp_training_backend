<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Userhrms_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    public function get_user($email)
    {
		$this->db->select('*');
		$this->db->from('users');
		$this->db->where('email', $email);
		$query=$this->db->get();
		
		if($query->num_rows()>0)
		{
		    $result=$query->row();
		    
            return $result;
		}
		else
		{
			return false;
		}
    }

    function create($data){
        $this->db->set($data);
        $this->db->insert('users');
        return true;
    }

    function make_user($id)
    {
        $this->db->set('role_id', '2');
        $this->db->where('id', $id);
        $this->db->update('users');

        return true;
    }

    function make_supervisor($id)
    {
        $this->db->set('is_supervisor', '1');
        $this->db->where('id', $id);
        $this->db->update('users');

        return true;
    }
    
}