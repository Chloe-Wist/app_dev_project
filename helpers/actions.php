<?php

// Holds the relavant information about actions
class Action {
    public $action_id; // Action id
    public $display_text; // Action text
    public $destination_prompt; // Prompt that action leads to
    public $parent_prompt; // Prompt that action appears under
    public $consequence_text; // Text of consequence


    // Constructs an action instance retrieving relavant data if it exists from the database
    public function __construct($action_id){ 

        require "config.php"; // Connects to database

        // Queries the database for matching data if it exists
        $query = "SELECT * FROM actions where id = ".$action_id;
        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));

        // Sets the variables to store the information from the database
        $text = $result['action'];
        $consequence = $result['consequence_prompt'];
        $parent_prompt = $result['prompt'];
        $consequence_text = $result['consequence_text'];


        // Set the variables using the data retrieved from the database
        $this->display_text = $text;
        $this->destination_prompt = $consequence;
        $this->parent_prompt = $parent_prompt;
        $this->consequence_text = $consequence_text;
        // Stores action id given through constructor variable
        $this->action_id = $action_id; 
    }
}
 

?>