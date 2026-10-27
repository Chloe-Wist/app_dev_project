<?php
// Chloe Wist, Ethan Janssen, Jonathan Smith, Hazel Lim
// 10/21/26

// Holds the relavant information about the page
class Page {
    public $page_id; // ID of the page
    public $content; 
        // An array of content objects
        // Each content object has the keys content_id and
        // page_content
    public $choices; // An array of choice objects.
    public $page_name; // Short name for the prompt.   
    public $page_type; // Specifies whether the prompt is the starting prompt,
                    // an ending prompt, or none. 
    public $previous_pages; 
        // An array of arrays containing page IDs and page short_names.
        // The pages represented by these IDs can lead to this page. 
        // The array keys are 'page' for the page id and 'short_name' for the name


    // Constructs a prompt instance retrieving relavant data if it exists from the database
    public function __construct($page_id, $version_id){  
        // Connects to database
        require "config.php"; 

        // Allows the function to construct actions to store in the $actions variable
        require_once "choices.php"; 


        // If page_id is -1, then no page was specified and therefore
        // the page should go to the start
        $query = "SELECT page_id FROM pages WHERE page_id = ".$page_id;
        // Result stores the data returned
        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));

        if (!isset($result['page_id'])) { 
                // The prompt_type column is not null, so a null page_type means that there is
                // no entry for this page id.
            $query = "SELECT page_id FROM pages WHERE page_type = 'start' AND version_id =".$version_id;
            $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
            $page_id = $result['page_id'];
        }

            
        // Finds the start page and sets the page id to it
        $query = "SELECT page_id, page_name, page_type FROM pages WHERE page_id = ".$page_id;
        // Result stores the data returned
        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));


        // Stores the page data
        $page_name = $result['page_name'];
        $page_type = $result['page_type'];

        // Retrieve content
        $query = "SELECT page_content_id, page_content FROM page_content 
            WHERE page_id = ".$page_id;
        $result = mysqli_query($conn, $query);

        $content = [];
        while ($a = mysqli_fetch_assoc($result)) {
            $r = ['page_content_id' => $a['page_content_id'], 
                    'page_content' => $a['page_content']];
            if (!in_array($r, $content)) {
                array_push($content, $r);
            }
        }


        // Queries the database for the matching action id if exists
        $query = "SELECT choice_id FROM choice WHERE page_id = ".$page_id;
        $result = mysqli_query($conn, $query);

        // Creates an array called actions to hold all of the actions associated with this prompt.
        // Each action is stored as an instance of the action object.
        $choices = [];
        while ($a = mysqli_fetch_assoc($result)) {
            array_push($choices, new Choice($a['choice_id']));
        }

        // Retrieves the page(s) that can lead to the current one (retrieves page id and page name)
        $query = "SELECT choice.page_id, page_name FROM pages JOIN choice
	        ON pages.page_id = choice.page_id
	        WHERE next_page_id = ".$page_id;
        $result = mysqli_query($conn, $query);

        $previous_pages = [];
        while ($a = mysqli_fetch_assoc($result)) {
            $r = ['page_id' => $a['page_id'], 'page_name' => $a['page_name']];
            if (!in_array($r, $previous_pages)) {
                array_push($previous_pages, $r);
            }
        }        


        // Sets the variables using the data gotten from MySQL
        $this->choices = $choices;
        $this->content = $content;
        $this->previous_pages = $previous_pages;
        $this->page_name = $page_name;
        $this->page_type = $page_type;
        // These variables are set by the constructor variables
        $this->page_id = $page_id;
        
    }
}
?>

