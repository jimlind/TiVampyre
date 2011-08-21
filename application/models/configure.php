<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Configure extends CI_Model
{	
    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->table_name = 'config';
    }
 
    // Create a config based on key/value
    function create($key, $value)
    {
        $data = array('key'=>$key, 'value'=>$value);
        if ($this->db->insert($this->table_name, $data)) {
            return array('id' => $this->db->insert_id());
        }
        return NULL;   
    }
 
    // Read value based on key
    function read($key)
    {
        $this->db->where('key', $key);
        $query = $this->db->get($this->table_name);
        if ($query->num_rows() == 1) return $query->row()->value;
        return NULL; //if no data found 
    }
    
    // Update a config based on key/value
    function update($key, $value)
    {
        $data = array('value'=>$value);
        $this->db->where('key', $key);
        $this->db->update($this->table_name, $data);
        return $this->db->affected_rows() > 0;
    }
    
    // Write the config data
    function write($data)
    {
        if (!isset($data['key'])) return false;
        if (!isset($data['value'])) return false;
        
        $configuration = $this->read($data['key']);
        if ($configuration === NULL) {
            // Create data if it is new
            $this->create($data['key'], $data['value']);
        } elseif ($configuration != $data['value']) {
            // Update data if it exists and is different
            $this->update($data['key'], $data['value']);
        }
    }
}