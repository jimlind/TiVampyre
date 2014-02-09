<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Shows extends CI_Model
{	
    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->table_name = 'shows';
    }

    // Create show data row
    function create($data)
    {
        if ($this->db->insert($this->table_name, $data)) {
            return array('id' => $this->db->insert_id());
        }
        return NULL;
    }

    // Read from table, if input isn't set get all data.
    function read($id = 0)
    {
        if ($id == 0) {
            $query = $this->db->get($this->table_name);
	    return $query->result();
	} else {
	    $this->db->where('id', (int) $id);
	    $query = $this->db->get($this->table_name);
	    if ($query->num_rows() == 1) return $query->row();
            return NULL; //if no data found 
	}
    }
    
    // Read from table, join on active
    function readActive()
    {
	$this->db->select('*, jobs.status as jobs_status, '.$this->table_name.'.id as true_id');
	$this->db->from($this->table_name);
	$this->db->join('availability', 'availability.show_id = '.$this->table_name.'.id');
	$this->db->join('jobs', 'jobs.show_id = '.$this->table_name.'.id', 'left');
	$this->db->join('icons', 'icons.status = jobs.status', 'left');
	$this->db->where('availability.available', 1);
	$this->db->order_by($this->table_name.'.show_title', 'asc');
	$this->db->order_by($this->table_name.'.episode_number', 'asc');
	$this->db->order_by($this->table_name.'.date', 'asc');
	
	$query = $this->db->get();
	return $query->result();
    }

    // Update show data row
    function update($data)
    {
    	$this->db->where('id', $data['id']);
	$this->db->update($this->table_name, $data);
        return $this->db->affected_rows() > 0;
    }

    // Write the show (create or update)
    function write($data)
    {
        $data = $this->transform($data);
        $show = $this->read($data['id']);
        
        if (!$show) {
            $this->create($data);
        } else {
            $this->update($data);
        }
    }
    
    // Write final file name
    public function writeFinalFileName($id, $fileName)
    {
	$data = array('id'=>$id, 'final_name'=>$fileName);
	$this->update($data);
    }
    
    // Transform code friendly array to SQL friendly array.
    function transform($data)
    {
        $data['show_title'] = $data['title'];
        unset($data['title']);
        $data['episode_title'] = $data['episodeTitle'];
        unset($data['episodeTitle']);
        $data['episode_number'] = $data['episodeNumber'];
        unset($data['episodeNumber']);
        return $data;            
    }
}