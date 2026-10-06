<?php

/** Chloe Wist, Josh Patey, Ray Orozco 
 * 
 * This file will create an interactive page that allows users to create and update
 * their own custom story. 
*/


// Basic session hardening (set early)
ini_set('session.cookie_httponly', 1);
// If serving over HTTPS, uncomment next line
ini_set('session.cookie_secure', 1);
    
// Ensure session is started for consistent session usage
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login to access editor
if (!isset($_SESSION['user_id'])) {
    header('Location: loginPage.php');
    exit;
}

// Connects to the database
require("helpers/config.php"); 



// Holds Prompt class, which will also require actions.php, which holds the Action class
require("helpers/prompt.php");





?>
<!DOCTYPE html>
<html>
<head>
    <title>Prompt Editor</title>
    <link rel="stylesheet" href="styles/text_adventure_editor.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="styles/main.css">
</head>
<body>
<?php

include("helpers/navbar.php");

echo '<div class="page-container">';

// Page Variables
$user_id = $_SESSION['user_id'];
$story_id;
$current_prompt; // current_prompt is the prompt the page is currently accessing

// If a user clicked edit story from their dashboard
if (isset($_GET['story_id'])) {
    $story_id = $_GET['story_id'];
}
// If the page refreshed with a new prompts
else if (isset($_GET['new_prompt'])) {
    $query = "SELECT story_id FROM prompts WHERE id = ".$_GET['new_prompt'];
    $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
    $story_id = $result['story_id'];
} // Else
else {
    echo "<p class='fail'>Unknown story</p>";
    die;
}

// Tests that the user should have access to editing this story
$query = "SELECT user_id FROM stories WHERE id = ".$story_id;
$result = mysqli_fetch_assoc(mysqli_query($conn, $query));
if ($result['user_id'] != $user_id) {
    echo "<p class='fail'>You do not have access to this story</p>";
    die;
}



// Handles form submission
require("helpers/text_editor.php");   


// new_prompt is the prompt that the previous page sent as the prompt to be accessed next
if (isset($_GET["new_prompt"])) {
    $current_prompt = new Prompt ($_GET["new_prompt"], $story_id);
}
else {
    $current_prompt = new Prompt(-1, $story_id); // Default is first prompt
}
?>

    <!-- Floating action buttons bottom-right: Back (blue) above Save (green) -->
        <div class="fab-container">
            <button class="btn btn-green" type="submit" name="save_edits" form="edit_prompt">Save Edits</button>
        </div>

<!-- Return to previous prompt button -->

    <?php 
        // This will print a link (or links) that will send a get variable with an attribute
        // called new_prompt, which is the prompt that will be loaded if the link is clicked.
        // new_prompt stores the id of the prompt to be loaded.
        // It will create a link for each prompt that leads to this prompt via an action. 
        if ($current_prompt->previous_prompts != null) { // If there is a prompt to go back to:
            echo '<div class="card" id="return_links">';
            foreach($current_prompt->previous_prompts as $p) {
                // Creates a link that holds either the short name or the prompt id if no short name is provided
                if ($p['prompt'] != $current_prompt->prompt_id) {
                    echo '
                    <a href="CustomAdventureEditor.php?new_prompt='.($p['prompt']).'"
                        class="btn btn-blue" name="return" >
                        Go to Previous Prompt: '.
                        ($p['short_name']?$p['short_name']:$p['prompt']).
                    '</a> ';
                } 
                else {
                    echo "<p class='return_null'> This prompt leads to itself</p>";
                }
            }
            echo "</div>";
        }
    ?>



<!-- Creates a form to edit the prompt and its actions-->

    <form method="POST" action="CustomAdventureEditor.php?new_prompt=<?=$current_prompt->prompt_id?>" id="edit_prompt">
        <div class="card">
        <h2>Prompt</h2>

        <!-- Prompt short name input -->
        <h3>Name</h3>
        <input type="text" name="short_name" value="<?php echo ($current_prompt->short_name); ?>">


        <!--Sends the prompt id to be used for updating the database-->    
        <input type="hidden" name="prompt_id" id="prompt_id" value="<?=$current_prompt->prompt_id?>">
    
        <!-- Prompt description input -->    
        <h3>Text</h3>
        <textarea name="prompt" id="prompt"  
            placeholder="Type here..."><?php echo ($current_prompt->prompt_description); ?></textarea>

        <!--Prompt Flags -->
        <h3>Flags</h3>
        <input type="radio" id="not_flagged" name="flag" value="none"
            <?=(($current_prompt->flag == 'none')?'checked':'')?>>None <br>
        <input type="radio" id="flagged" name="flag" value="flagged"
            <?=(($current_prompt->flag == 'flagged')?'checked':'')?>>Flagged <br><br>

        <!-- Prompt Type -->
        <input type="hidden" name="prompt_type" value="<?=$current_prompt->prompt_type?>">

        <!--Prompt Deletion-->
        <?php
        // You can only delete a prompt if there are no actions under it
        if ($current_prompt->actions == []) {
            ?>
            <button class="btn btn-red" type="submit"  name="delete_prompt" value="<?=$current_prompt->prompt_id?>">Delete prompt</button>
            <?php
        }
        ?>
        </div>

        <?php
        ?>
        <!--Action input-->
        <div class="card">
        <h2>Actions</h2>
        <?php                                  
            foreach ($current_prompt->actions as $i => $action):
                if ($action instanceof Action) : ?>
                <div class="card">

                    <!-- Action ID --> 
                        <input type="hidden" name="action_id[<?php echo $i; ?>]" 
                            value="<?=$action->action_id?>">

                    <!--Action Title-->
                        <h3>Action <?=$i + 1?></h3>
                        
                    <!-- Textbox for the Action -->
                        <h5>Action Text</h5>
                        <textarea name="action_text[<?php echo $i; ?>]" rows="3" placeholder="Action text..."><?php echo ($action->display_text); ?></textarea>
                            <!--The name of the input holds the number that the action is stored in in the array-->   

                    <!--Textbox for the consequence of the action-->
                        <h5>Consequence Text</h5>
                        <textarea name="action_consequence[<?php echo $i; ?>]" rows="3" placeholder="Consequence text..."><?php echo($action->consequence_text); ?></textarea>
                            <!--The name of the input holds the number that the action is stored in in the array--> 
                            
                    <!--Destination of Action-->
                        <h5>Destination</h5>

                        <!--Displays current prompt this action leads to-->
                        <?php                            
                            // Will store the default value (the id and shortname of the prompt) of the destination if it exists
                            $default; 
                            if ($action->destination_prompt != null) { // If there is a destination prompt set
                                // This query will retrieve the short_name and ID from the prompt that this action leads to
                                $query = "SELECT short_name, prompts.id FROM prompts JOIN actions ON prompts.id = actions.consequence_prompt
	                                WHERE actions.consequence_prompt = ".$action->destination_prompt;
                                $default = mysqli_fetch_assoc(mysqli_query($conn, $query));
                                echo "Current Destination: ".($default['short_name']?$default['short_name']:$default['id']);
                            }
                            else {
                                echo "No set destination.";
                            } 
                        ?>

                        <!-- Drop down list of possible prompts the action can lead to -->
                        <select name="destination[<?php echo $i; ?>]", > 
                            <?php 
                            // Retrieves all the prompts that the user can select to create a drop down list of them
                            $query = "SELECT id, short_name FROM prompts WHERE story_id = ".$story_id;
                            $result = mysqli_query($conn, $query);

                            // For each retrieved prompt:
                            while ($a = mysqli_fetch_assoc($result)) {
                                // If no short name was given, generic names will be given
                                if ($a['short_name'] == null) {
                                    echo "<option value='".$a['id']."'".($default['id'] == $a['id']?"selected":"")."> Untitled Prompt ".$a['id']." </option>";
                                }
                                else {
                                    echo "<option value='".$a['id']."'".($default['id'] == $a['id']?"selected":"").">".$a['short_name']."</option>";
                                }
                            }
                            ?>
                        </select>
                        <!-- https://stackoverflow.com/questions/76684999/how-can-i-make-a-drop-down-menu-in-html -->


                    <!--Create a new prompt-->
                        <!--Will create a new prompt for the action it was clicked from, and save all entered information -->
                        <button class="btn" type="submit" name="new_prompt" value="<?=$action->action_id?>">Add new destination</button>


                    <!--Creates a link to go to the prompt this action leads to-->
                        <?php 
                        // If the action has a prompt that it leads to, a link will be created leading
                        // to that prompt.
                        if (isset($action->destination_prompt)) {
                            // new_prompt is the prompt that the previous page sent as the prompt to be accessed next
                            if ($action->destination_prompt == $current_prompt->prompt_id) {
                                echo "<p>This prompt leads to itself</p>";
                            }
                            else {
                                echo '<a href="CustomAdventureEditor.php?new_prompt='.$action->destination_prompt.'"
                                    class="btn" name="new_prompt">
                                    Edit Destination
                                </a>';
                            }
                        }
                        ?>

                    <br>
                    <!--Delete Action-->
                    <button class="btn btn-red" type="submit" name="delete_action" value="<?=$action->action_id?>">Delete action</button>                        
                </div>
                <?php endif; 
            endforeach; ?>
        
        <!--Create a new action-->
        <button class="btn" type="submit" name="new_action">Create a new Action</button>
        </div>

        <!-- Saves the edits button -->
        <input class="btn btn-green" type="submit" name="save_edits" id="save_edits" value="Save Edits">
        
    </form>

    <div class="card" id="editor_nav_bar">
    <!--Creates a pop up bar on the side to navigate to different prompts-->
    <?php require("helpers/text_navigation.php");?>
    </div>
</div>
<?php include("helpers/footer.php"); ?>
</body>
</html>