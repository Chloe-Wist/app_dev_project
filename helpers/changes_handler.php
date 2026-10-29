<?php
// -----------------------------
// Handles any edits on the simulation
// -----------------------------

// This will update the user entered values for the actions and prompt
if (isset($_POST['page_id']) && !isset($_POST['delete_page'])) {

    // Updates the page values
    $page_name = htmlspecialchars($_POST['page_name']);
    $page_id = $_POST['page_id'];

    $query = "UPDATE pages SET 
                page_name = '".$page_name."'
                WHERE page_id = ".$page_id;
    try {
        mysqli_query($conn, $query);
    }
    catch(Exception $e) {
        echo "<p class='fail'>Page Failed to Update</p>";
    }

    // Update page content values
    if (isset($_POST['page_content'])) {
        for ($i = 0; $i < count($_POST['page_content']); $i++) {
            $page_content = htmlspecialchars($_POST['page_content'][$i]);
            $page_content_id = $_POST['page_content_id'][$i];
            
            $query = "UPDATE page_content SET
                    page_content = '".$page_content."'
                    WHERE page_content_id = ".$page_content_id;
        }
        try {
            mysqli_query($conn, $query);
        }
        catch(Exception $e) {
            echo "<p class='fail'>Page Content Failed to Update</p>";
        }
    }

    // Updates the choice values
    if (isset($_POST['choice_text'])) {
        for ($i = 0; $i < count($_POST['choice_text']); $i++) {
            $choice_id = $_POST['choice_id'][$i];
            $choice_text = htmlspecialchars($_POST['choice_text'][$i]);
            $next_page_id = $_POST['next_page_id'][$i]; // Don't need special characters because it only 
                                                // stores numbers and the user can't enter information
            $query = "UPDATE choice SET 
                choice_text = '".$choice_text."',
                next_page_id = ".$next_page_id."
                WHERE choice_id = ".$choice_id;
            try {
                mysqli_query($conn, $query);
            }
            catch (Exception $e) {
               echo "<p class='fail'>Choice ".($i + 1)." Failed to Update</p>";
            }
        }
    }

    // Create new page content
    if (isset($_POST['new_content'])) {
        $query = 'INSERT INTO page_content (page_id)
                VALUES ('.$page_id.')';
        try {
                mysqli_query($conn, $query);
        }
        catch (Exception $e) {
            echo "<p class='fail'>Failed to create new content</p>";
        }        
    }
    // Delete page content
    if (isset($_POST['delete_content_id'])) {
        $query = "DELETE FROM page_content WHERE page_content_id = ".$_POST['delete_content_id'];
        try {
            mysqli_query($conn, $query);
        }
        catch (Exception $e) {
            echo "<p class='fail'>Page Content Failed to Delete</p>";
        }
    }
}

// Creates a new choice
if (isset($_POST['new_choice'])) {
    if ($_POST['page_type'] == 'End') {
        $query = 'UPDATE pages SET page_type = "none" WHERE page_id = '.$_POST['page_id'];
        try {
            mysqli_query($conn, $query);
        }
        catch (Exception $e) {
            echo "<p class='fail>Failed to create a new choice</p>";
        }
    }
    $query = "INSERT INTO choice (page_id, choice_text, next_page_id) 
        VALUES (".$_POST['page_id'].", '', ".$_POST['page_id'].")";
            // Inserts the current page as the destination page
    try {
        mysqli_query($conn, $query);
    }
    catch (Exception $e) {
        echo "<p class='fail'>Failed to create a new choice</p>";
    }
}


// Will create a new page for the choice it was clicked from
if (isset($_POST['new_page'])) {
    $choice_id = $_POST['new_page']; // The choice id is sent by this submit button
    $query = "INSERT INTO pages (page_type, version_id) VALUES ('end', ".$version_id.")"; // Creates a new prompt 
    mysqli_query($conn, $query);
    // Links the newly created page to the most recent action
    $query = "SELECT LAST_INSERT_ID() AS id";
    $result = mysqli_fetch_assoc(mysqli_query($conn, $query));
    $query = "UPDATE choice SET next_page_id = (".$result['id'].") WHERE choice_id = ".$choice_id.";";
    mysqli_query($conn, $query);
    header("Location: SimulationEditor.php?new_page_id=".$result['id']);
}

// Deletes choices
if (isset($_POST['delete_choice_id'])) {
    $query = "DELETE FROM choice WHERE choice_id = ".$_POST['delete_choice_id'];
    try {
        mysqli_query($conn, $query);
    }
    catch (Exception $e) {
        echo "<p class='fail'>Choice Failed to Delete</p>";
    }
    // If there are no choices for the current page, set that page to "end"
    $query = "SELECT choice_id FROM choice WHERE next_page_id = ".$_POST['page_id'];
    $result = mysqli_query($conn, $query);
    if (mysqli_fetch_assoc($result) == null && $_POST['page_type'] != 'Start') {
        $query = 'UPDATE pages SET page_type = "end" WHERE page_id = '.$_POST['page_id'];
        try {
        mysqli_query($conn, $query);
    }
    catch (Exception $e) {
        echo $e;
    }
    }
}

// Deletes pages
if (isset($_POST['delete_page_id'])) {
    $page_id = $_POST['delete_page_id'];

    $query = "SELECT page_type FROM pages WHERE page_id = ".$page_id;
    $type = mysqli_fetch_assoc(mysqli_query($conn, $query))['page_type'];

    // Cannot delete start pages
    if ($type != 'Start') {

        // Delete the page from the next_page of the choices
        // (the webpage requires choices under this page to be deleted 
        // before the page can be deleted, so choices under the page are not a concern)
        $query = "UPDATE choice SET next_page_id = choice.page_id 
            WHERE next_page_id = ".$page_id;
        mysqli_query($conn, $query);

        // Delete the page_content
        $query = "DELETE FROM page_content WHERE page_id = ".$page_id;
        try {
            mysqli_query($conn, $query);
        }
        catch (Exception $e) {
            echo "<p class='fail'>Failed to delete page</p>" ;
        }

        // Deletes the prompt itself
        $query = "DELETE FROM pages WHERE page_id = ".$page_id;
        try {
            mysqli_query($conn, $query);
        }
        catch (Exception $e) {
            echo "<p class='fail'>Failed to delete page</p>" ;
        }
    }
    else {
        echo "<p class='fail'>Cannot delete start page</p>";
    }
}

// If any pages no longer have a page leading to them, and are not the start page,
// their type is now 'orphaned'

// Retrieves all pages with no parents
 $query = 
 "SELECT pages.page_id,
       SUM(CASE
           WHEN choice.next_page_id IS NOT NULL
                AND choice.page_id = choice.next_page_id THEN 0
           WHEN choice.next_page_id IS NOT NULL THEN 1
           ELSE 0
       END) AS num_of_parents
FROM pages
LEFT JOIN choice ON pages.page_id = choice.next_page_id
WHERE version_id = 1 AND pages.page_type != 'Start'
GROUP BY pages.page_id HAVING num_of_parents = 0";

$result = mysqli_query($conn, $query);

// Sets those pages to have the page_type of 'orphaned
while ($a = mysqli_fetch_assoc($result)) {
    $query = "UPDATE pages SET page_type = 'Orphaned' WHERE page_id = ".$a['page_id'];
    try {
        mysqli_query($conn, $query);
    }
    catch (Exception $e){
        echo "<p class='fail'>Failed to update page type</p>";
    }
}