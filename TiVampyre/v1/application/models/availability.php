<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Availability extends CI_Model
{	
    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->table_name = 'availability';
    }
 
    // Create a show's availability
    function create($data)
    {
        if ($this->db->insert($this->table_name, $data)) {
            return array('id' => $this->db->insert_id());
        }
        return NULL;   
    }
 
    // Read show's availability
    function read($showId)
    {
        if (!is_int($showId)) return NULL;
        $this->db->where('show_id', (int) $showId);
        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1) return $query->row();
        return NULL; //if no data found 
    }
    
    // Update a show's availability
    function update($data)
    {   
        $this->db->where('show_id', $data['show_id']);
        $this->db->update($this->table_name, $data);
        return $this->db->affected_rows() > 0;
    }
    
    // Write the activity for the show (create or update)
    function write($showId, $available, $timestamp = false)
    {
        if (!is_int($showId)) return false;
        if (!is_bool($available)) return false;
        $data = array();
        $data['show_id'] = $showId;
        $data['available'] = $available ? 1 : 0;
        $data['timestamp'] = $timestamp ? $timestamp : date('Y-m-d H:i:s');
        
        $availability = $this->read($showId);
        if (!$availability) {
            $this->create($data);
        } else {
            $this->update($data);
        }
    }
    
    public function rebuildAvailability()
    {
        $this->db->select_max('timestamp');
        $query = $this->db->get($this->table_name);
        $time = $query->row()->timestamp;
        
        $where = "timestamp < '$time'";
        $this->db->where($where);
        $this->db->update($this->table_name, array('available'=>0));
        return $this->db->affected_rows() > 0;
    }
}