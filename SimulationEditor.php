<?php

/** Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
 * 
 * This file will create an interactive page that allows users to create and update
 * their own custom simulation. 
*/


// Basic session hardening (set early)
ini_set('session.cookie_httponly', 1);
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

// Ensures correct access
if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Owner') {
    header("Location: UserDashboard.php");
    exit;
}

// Connects to the database
require("helpers/config.php"); 

// Holds Page class, which will also require actions.php, 
// which holds the Action class and the content class
require("helpers/page.php");



?>
<!DOCTYPE html>
<html>
<head>
    <title>Page Editor</title>
    <link rel="stylesheet" href="styles/nothing_yet.css">
</head>
<body>
<?php
$title = "Page Editor";
include("helpers/header.php");

echo '<div class="page-container">';

// Page Variables
$version_id;
$current_page; // current_page is the page is currently accessed

// If a user clicked edit story from their dashboard
if (isset($_GET['version_id'])) {
    $version_id = $_GET['version_id'];
}
// If page id is set, get the version from it
else if (isset($_GET['page_id'])) {
    $query = "SELECT version_id FROM pages WHERE page_id = ".$_GET['page_id'];
    $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
    if (isset($result)) {
        $version_id = $result['version_id'];
        $query = "SELECT page_id FROM pages WHERE page_type = 'Start' AND version_id =".$version_id;
        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
        if (isset($result)) {
            $page_id = $result['page_id'];
        }
        else {
            header("Location: SimulationManagement.php");
            exit();
        }
    }
    // If this page number and version id are not found redirect to simulation management page
    else {
        header("Location: SimulationManagement.php");
        exit();
    }   
}
// If the page refreshed with a new page
else if (isset($_GET['new_page_id'])) {
    $query = "SELECT version_id FROM pages WHERE page_id = ".$_GET['new_page_id'];
    $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
    $version_id = $result['version_id'];
} // Else
else {
    echo "<p class='fail'>Unknown story</p>";
    die;
}


// Handles saving edits
require("helpers/changes_handler.php");   


// new_page is the page that the previous page sent to be accessed next
if (isset($_GET["new_page_id"])) {
    $current_page = new Page ($_GET["new_page_id"], $version_id);
}
else if (isset($_GET['page_id'])) {
    $current_page = new Page ($_GET['page_id'], $version_id);
}
else {
    $current_page = new Page (-1, $version_id); // Default is first prompt
}
?>

    <!-- Floating action buttons bottom-right: Back (blue) above Save (green) -->
        <div class="fab-container">
            <button type="submit" name="save_edits" form="edit_page">Save Edits</button>
        </div>

<!-- Return to previous prompt button -->

    <?php 
        // This will print a link (or links) that will send a get variable with an attribute
        // called new_page_id, which is the id of the page that will be loaded if the link is clicked.
        // It will create a link for each page that leads to this page via an action. 
        if ($current_page->previous_pages != null) { // If there is a prompt to go back to:
            echo '<div class="card" id="return_links">';
            foreach($current_page->previous_pages as $p) {
                // Creates a link that holds either the page name or the prompt id if no page name is provided
                if ($p['page_id'] != $current_page->page_id) {
                    echo '
                    <a href="SimulationEditor.php?new_page_id='.($p['page_id']).'"
                        class="btn btn-blue" name="return" >
                        Go to Previous Page: '.
                        ($p['page_name']?$p['page_name']:$p['page_id']).
                    '</a> ';
                } 
                else {
                    echo "<p class='return_null'> This page has no destination</p>";
                }
            }
            echo "</div>";
        }
    ?>



<!-- Creates a form to edit the page and its choices-->

    <form method="POST" action="SimulationEditor.php?page_id=<?=$current_page->page_id?>" id="edit_page">
        <div class="card">
        <h2>Page</h2>

        <!-- Page name input -->
        <h3>Name</h3>
        <input type="text" name="page_name" value="<?php echo ($current_page->page_name); ?>">


        <!--Sends the page id to be used for updating the database-->    
        <input type="hidden" name="page_id" id="page_id" value="<?=$current_page->page_id?>">
    
        <!-- Page content -->    
        <?php 
        for ($i = 0; $i < count($current_page->content); $i++) {
            ?>
        <h3>Page Content</h3>
        <!-- Page content input --> 
        <textarea name="page_content[<?=$i?>]"
            placeholder="Type here..."><?php 
            if ($current_page->content[$i]['page_content']) {
                echo ($current_page->content[$i]['page_content']); 
            }
        ?></textarea>
        <input type="hidden" name="page_content_id[<?=$i?>]"   
            value=<?=$current_page->content[$i]['page_content_id']?>>

        <!-- Page content deleting -->
        <button type="submit" name="delete_content_id" 
            value="<?=$current_page->content[$i]['page_content_id']?>">Delete Content</button>
        
        <?php
        }
        ?>
        <!-- Page content adding -->
        <button type="submit" name="new_content">Add Content</button>


        <!-- Page Type -->
        <input type="hidden" name="page_type" value="<?=$current_page->page_type?>">

        <!-- Page Deletion-->
        <?php
        // You can only delete a page if there are no choices under it
        // and it is not the start page
        if ($current_page->choices == [] && $current_page->page_type != 'Start') {
            ?>
            <button class="btn btn-red" type="submit"  name="delete_page_id" value="<?=$current_page->page_id?>">Delete page</button>
            <?php
        }
        ?>
        </div>

        <?php
        ?>
        <!--Choice input-->
        <div class="card">
        <h2>Choices</h2>
        <?php                                  
            foreach ($current_page->choices as $i => $choice):
                if ($choice instanceof Choice) : ?>
                <div class="card">

                    <!-- Choice ID --> 
                        <input type="hidden" name="choice_id[<?php echo $i; ?>]" 
                            value="<?=$choice->choice_id?>">

                    <!--Choice Title-->
                        <h3>Choice <?=$i + 1?></h3>
                        
                    <!-- Textbox for the Action -->
                        <h5>Choice Text</h5>
                        <textarea name="choice_text[<?php echo $i; ?>]" rows="3" placeholder="Choice text..."><?php echo ($choice->text); ?></textarea>
                            <!--The name of the input holds the number that the choice is stored in in the array-->   
       
                    <!--Next Page of Choice-->
                        <h5>Next Page</h5>

                        <!--Displays current page this choice leads to-->
                        <?php                            
                            // Will store the default value (the id and name of the page) of the next page if it exists
                            
                            // This query will retrieve the name and ID from the page that this action leads to
                            $query = "SELECT page_name, pages.page_id FROM pages JOIN choice ON pages.page_id = choice.next_page_id
                                WHERE choice.next_page_id = ".$choice->next_page_id;
                            $default = mysqli_fetch_assoc(mysqli_query($conn, $query));
                            if ($choice->next_page_id != $current_page->page_id) { // If there is a next page set
                                echo "Current Destination: ".(isset($default['page_name'])?$default['page_name']:$default['page_id']);
                            }
                            else {
                                echo "No set destination.";
                            } 
                        ?>

                        <!-- Next Page ID -->
                        <input type="hidden" name="next_page_id[<?=$i?>]" value ="<?=$choice->next_page_id?>">


                        <!-- Drop down list of possible pages the choice can lead to -->
                            <?php 
                            // Retrieves all the pages that the user can select to create a drop down list of them
                            $query = "SELECT page_id, page_name FROM pages WHERE version_id = ".$version_id."
                                AND page_id != ".$current_page->page_id;
                            $result = mysqli_query($conn, $query);
                            $options = [];

                            while ($a = mysqli_fetch_assoc($result)) {
                                // If no name was given, generic names will be given
                                if ($a['page_name'] == null) {
                                    $options[] = "<option value='".$a['page_id']."' ".($default['page_id'] 
                                        == $a['page_id']?"selected":"")."> Untitled Page </option>";
                                }
                                else {
                                    $options[] = "<option value='".$a['page_id']."' ".($default['page_id'] 
                                        == $a['page_id']?"selected":"").">".$a['page_name']."</option>";
                                }
                            }

                            $options[] = "<option value='".$current_page->page_id."' ".($default['page_id'] 
                                        == $current_page->page_id?"selected":"")."> None Selected </option>";

                            if ($options) { ?>
                                <select name="next_page_id[<?php echo $i; ?>]", > 
                                <?php 
                                    foreach ($options as $o) {
                                        echo $o;
                                    }
                                ?>
                            
                                </select>
                            <?php } ?>
                        <!-- https://stackoverflow.com/questions/76684999/how-can-i-make-a-drop-down-menu-in-html -->


                    <!--Create a new page-->
                        <!--Will create a new page for the choice it was clicked from, and save all entered information -->
                        <button class="btn" type="submit" name="new_page" value="<?=$choice->choice_id?>">Add new destination</button>


                    <!--Creates a link to go to the page this action leads to-->
                        <?php 
                        // If the choice has a page that it leads to, a link will be created leading
                        // to that page.
                        if (isset($choice->next_page_id)) {
                            // next_page_id is the page that the previous page sent to be accessed next
                            if ($choice->next_page_id != $current_page->page_id) {
                                echo '<a href="SimulationEditor.php?new_page_id='.$choice->next_page_id.'"
                                    class="btn" name="new_page_id">
                                    Edit Destination
                                </a>';
                            }
                        }
                        ?>

                    <br>
                    <!--Delete choice-->
                    <button class="btn btn-red" type="submit" name="delete_choice_id" value="<?=$choice->choice_id?>">Delete choice</button>                        
                </div>
                <?php endif; 
            endforeach; ?>
        
        <!--Create a new choice-->
        <button type="submit" name="new_choice">Create a new choice</button>
        </div>

        <!-- Saves the edits button -->
        <input type="submit" name="save_edits" value="Save Edits">
        
    </form>

    <div class="card" id="editor_nav_bar">
    <!--Creates a pop up bar on the side to navigate to different prompts-->
    <?php require("helpers/text_navigation.php");?> 
    </div>
</div>
</body>
</html>