<?php

// Holds the relavant information about actions
class Choice {
    public $choice_id; // Choice id
    public $text; // Choice text
    public $next_page_id; // Page that choice leads to
    public $page_id; // Page that choice appears on


    // Constructs an action instance retrieving relavant data if it exists from the database
    public function __construct($choice_id){ 

        require "config.php"; // Connects to database

        // Queries the database for matching data if it exists
        $query = "SELECT * FROM choice where choice_id = ".$choice_id;
        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));

        // Sets the variables to store the information from the database
        $text = isset($result['Choice_Text'])?$result['Choice_Text']:"";
        $destination = isset($result['Next_Page_ID'])?$result['Next_Page_ID']:-1;
        $parent = $result['Page_ID'];


        // Set the variables using the data retrieved from the database
        $this->text = $text;
        $this->next_page_id = $destination;
        $this->page_id = $parent;
        // Stores choice id given through constructor variable
        $this->choice_id = $choice_id; 
    }
}
 
?>