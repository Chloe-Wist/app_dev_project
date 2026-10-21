<?php
// Chloe Wist, Ethan Janssen, Jonathan Smith, Hazel Lim
// 10/21/26

// Holds the relavant information about the page
class Page {
    public $page_id; // ID of the page
    public $content; // An array of content objects
    public $actions; // An array of action objects.
    public $short_name; // Short name for the prompt.   
    public $prompt_type; // Specifies whether the prompt is the starting prompt,
                    // an ending prompt, or none. 
    public $previous_prompts; 
        // An array of arrays containing prompt IDs and prompt short_names.
        // The prompts represented by these IDs can lead to this prompt. 
        // The array keys are 'prompt' for the prompt id and 'short_name' for the name


    // Constructs a prompt instance retrieving relavant data if it exists from the database
    public function __construct($page_id, $version_id){  //STOPPED EDITING HERE
        // Connects to database
        require "config.php"; 

        // Allows the function to construct actions to store in the $actions variable
        require_once "actions.php"; 


        // If prompt_id is -1, then no prompt was specified and therefore
        // the page should go to the start
        $query = "SELECT id FROM prompts WHERE id = ".$prompt_id;
        // Result stores the data returned
        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));

        if (!isset($result['id'])) { 
                // The prompt_type column is not null, so a null prompt_type means that there is
                // no entry for this prompt id.
            $query = "SELECT id FROM prompts WHERE prompt_type = 'start' AND story_id =".$story_id;
            $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
            $prompt_id = $result['id'];
        }

            
        // Finds the start prompt and sets the prompt id to it
        $query = "SELECT prompt, short_name, prompt_type, flag FROM prompts WHERE id = ".$prompt_id;
        // Result stores the data returned
        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));





        // Stores the prompt data
        $description = $result['prompt']; 
        $short_name = $result['short_name'];
        $prompt_type = $result['prompt_type'];
        $flag = $result['flag'];

        // Queries the database for the matching action id if exists
        $query = "SELECT id FROM actions WHERE prompt = ".$prompt_id;
        $result = mysqli_query($conn, $query);

        // Creates an array called actions to hold all of the actions associated with this prompt.
        // Each action is stored as an instance of the action object.
        $actions = [];
        while ($a = mysqli_fetch_assoc($result)) {
            array_push($actions, new Action($a['id']));
        }

        // Retrieves the prompt(s) that can lead to the current one (retrieves prompt id and short_name)
        $query = "SELECT actions.prompt, short_name FROM prompts JOIN actions 
	        ON prompts.id = actions.prompt
	        WHERE consequence_prompt = ".$prompt_id;
        $result = mysqli_query($conn, $query);

        $previous_prompts_list = [];
        while ($a = mysqli_fetch_assoc($result)) {
            $r = ['prompt' => $a['prompt'], 'short_name' => $a['short_name']];
            if (!in_array($r, $previous_prompts_list)) {
                array_push($previous_prompts_list, $r);
            }
        }        


        // Sets the variables using the data gotten from MySQL
        $this->prompt_description = $description;
        $this->actions = $actions;
        $this->previous_prompts = $previous_prompts_list;
        $this->short_name = $short_name;
        $this->prompt_type = $prompt_type;
        $this->flag = $flag;
        // These variables are set by the constructor variables
        $this->prompt_id = $prompt_id;
        
    }
}
?>

