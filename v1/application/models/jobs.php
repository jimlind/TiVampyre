<?php if (!defined("BASEPATH")) exit("No direct script access allowed");

class Jobs extends CI_Model
{	
    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->table_name = "jobs";
    }

    function checkJob($id)
    {
        $this->db->where("show_id", (int) $id);
        $query = $this->db->get($this->table_name);
	if ($query->num_rows() == 1) return $query->row();
        return false; //if no data found 
    }

    function addJob($data)
    {	
	if ($this->checkJob($data["show_id"])) {
            $this->removeJob($data["show_id"]);
        }
        
	$data["status"] = "waiting";
        if ($this->db->insert($this->table_name, $data)) {
            return array("id" => $this->db->insert_id());
        }
        return NULL;
    }
    
    function updateJob($id, $status)
    {
	$data = array('status'=>$status);
	$this->db->where('id', $id);
	$this->db->update($this->table_name, $data);
        return $this->db->affected_rows() > 0;
    }
    
    function removeJob($id)
    {
        $this->db->where("show_id", $id);
        $this->db->delete($this->table_name); 
    }

    function getNextJob()
    {
	// count encoding or downloaded jobs
	$this->db->select("COUNT(id) AS count");
	$this->db->where("status", "downloaded");
	$this->db->or_where("status", "encoding");
	$count = $this->db->get($this->table_name)->row()->count;
	
	// abort if something has downloaded and something is encoding
	if ($count >= 2) return false;
	
	// find job that is waiting or has downloaded
	$this->db->select("*, jobs.id AS jobs_id");
	$this->db->from($this->table_name);
	$this->db->join("availability", "availability.show_id = ".$this->table_name.".show_id");
	$this->db->join("shows", "shows.id = ".$this->table_name.".show_id");
	$this->db->where("availability.available", 1);
	$this->db->where($this->table_name.".status", "waiting");
	$this->db->or_where($this->table_name.".status", "downloaded");
	$this->db->order_by($this->table_name.".id", "ASC");
	$this->db->limit(1);
	
	return $this->db->get()->row();
    }
    
    function anyStatus($input)
    {
	$this->db->where('status', $input);
	$query = $this->db->get($this->table_name);
	return $query->num_rows() >= 1;
    }
}