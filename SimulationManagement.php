<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: loginPage.php');
    exit;
}

require_once "helpers/config.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$user_id = (int) $_SESSION['user_id'];

function owns_story(mysqli $conn, int $story_id, int $user_id): bool {
    $stmt = $conn->prepare("SELECT 1 FROM stories WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $story_id, $user_id);
    $stmt->execute();
    $owns = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $owns;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Invalid request. Please go back and try again.');
}

// Variable Declaration
$story_list = [];
    // This array holds the ID of all the stories the user has.
    // The name and public variables are changeable in this interface.

// Currently, for each story in the list, the program will create a set of buttons.
// One button to play the story,
// One button to edit the story,
// And one button to toggle the public/private state of the story.

// The id will be displayed for each story so that people can share each other's ID's for public story searching.


//Additionally, there is a New Story button. When pressed, it takes you straight to the editor. (With a new story added to the database.)

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Stories</title>
                <link rel="stylesheet" href="styles/navbar.css">
                <link rel="stylesheet" href="styles/main.css">
    </head>
    <body>

    <?php include("helpers/navbar.php")?>

        <div class="page-container">
            <div class="row">
                <a class="btn btn-blue fixed-top-left" href="UserDashboard.php">Back</a>
            </div>

<?php
// Updates the story information
    if (isset($_POST['save_edits']) && isset($_POST['story_id'])) {
        foreach ($_POST['story_id'] as $i => $raw_id) {
            $id = (int) $raw_id;
            $story_title = trim($_POST['story_title'][$i] ?? "");
            $public = isset($_POST['public'][$i]) ? 1 : 0;

            try {
                $stmt = $conn->prepare(
                    "UPDATE stories SET story_title = ?, public = ? WHERE id = ? AND user_id = ?"
                );
                $stmt->bind_param("siii", $story_title, $public, $id, $user_id);
                $stmt->execute();
                $stmt->close();
                echo "<p class='success'>Story " . ((int) $i + 1) . " Updated</p>";
            } catch (Exception $e) {
                echo "<p class='fail'>Story " . ((int) $i + 1) . " Failed to Update</p>";
            }
        }
    }

    // Creates Story
    if (isset($_POST['create_story'])) {
    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare("INSERT INTO stories (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $new_story_id = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare(
            "INSERT INTO prompts (prompt, prompt_type, story_id) VALUES ('Story Start', 'start', ?)"
        );
        $stmt->bind_param("i", $new_story_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo "<p class='success'>Story Created</p>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p class='fail'>Failed to Create Story</p>";
    }
}

    // Delete Story
    if(isset($_POST['delete_story_start'])) {
        $id = (int) $_POST['delete_story_start'];
        $stmt = $conn->prepare("SELECT story_title FROM stories WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result) {
        ?>
        <div class="card">
        <form method="POST" action="UserStories.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <p class='warning'>Are you sure you want to delete this story? 
                Deleted stories cannot be recovered.<br>
                Story ID: <?=$id?><br>
                <?=($result['story_title'] ? 'Story Title: '.htmlspecialchars($result['story_title']).'<br>' : '')?>
                </p>
            <input type="radio" name="delete_story" value="<?=$id?>">
            Yes, delete this story.<br>
            <input type="radio" name="delete_story" value="cancel" checked>
            No, do not delete this story.<br>
            <input type="submit" class="btn btn-red" value="Confirm">
        </form>
        </div>
        <?php }
    }

    if (isset($_POST['delete_story'])) {
        $story_id = $_POST['delete_story'];

        if ($story_id !== 'cancel') {
            $story_id = (int) $story_id;

            if (!owns_story($conn, $story_id, $user_id)) {
                echo "<p class='fail'>Failed to Delete Story</p>";
            } else {
                try {
                    $conn->begin_transaction();
                    $statements = [
                        "DELETE FROM prompts WHERE story_id = ?",
                        "DELETE FROM actions WHERE story_id = ?",
                        "DELETE FROM stories WHERE id = ?"
                    ];
                    foreach ($statements as $sql) {
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $story_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                    $conn->commit();
                    echo "<p class='success'>Story Deleted</p>";
                } catch (Exception $e) {
                    $conn->rollback();
                    echo "<p class='fail'>Failed to Delete Story</p>";
                }
            }
        } else {
            echo "<p class='warning'>Deletion Canceled</p>";
        }
    }
    
    // Play story
    if (isset($_POST['play_story'])) {
        $id = (int) $_POST['play_story'];
        if (owns_story($conn, $id, $user_id)) {
            header("Location: text_adventure.php?story_id=" . $id);
            exit;
        }
    }

    // Edit story
    if (isset($_POST['edit_story'])) {
        $id = (int) $_POST['edit_story'];
        if (owns_story($conn, $id, $user_id)) {
            header("Location: CustomAdventureEditor.php?story_id=" . $id);
            exit;
        }
    }

    $stmt = $conn->prepare("SELECT id, story_title, public FROM stories WHERE user_id = ? ORDER BY id");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $story_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    ?>

    <!--List of Stories User has Created-->
    <div class="card"><h2 style="margin:0;color:#fff;">Your Stories</h2></div>

    <form id="user_stories_form" method="POST" action="UserStories.php">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <button type="submit" name="save_edits" value="1" tabindex="-1" aria-hidden="true"
            style="position:absolute;left:-9999px;">Save Edits</button>

    <?php
        foreach ($story_list as $i => $story):
    ?>

    <div class="card">
        <!--Story ID-->
            <p> Story ID: <?php echo $story["id"]; ?> </p>
            <input type="hidden" name="story_id[<?php echo $i; ?>]" value="<?=$story["id"]?>">
        <!--Story Title-->
            <p> Story Title:
                <input type="text" name="story_title[<?php echo $i; ?>]" value="<?php echo ($story["story_title"]); ?>">
            </p>

        <!--Public vs Private Setting-->
                Public:
                <!-- Rounded switch -->
                <label class="switch">
                    <input type="checkbox" name="public[<?php echo $i; ?>]" 
                        <?php echo ($story["public"] ? 'checked' : ''); ?>>
                    <span class="slider_round"></span>
                </label>
                <!-- https://www.w3schools.com/howto/howto_css_switch.asp -->
                 <br>
                <button class="btn btn-red" type="submit" name="delete_story_start" value="<?=$story["id"]?>"> Delete Story </button>
                <br>
                
                <button class="btn" type="submit" name="play_story" value="<?php echo $story["id"]; ?>"> Play Story </button>
                <button class="btn" type="submit" name="edit_story" value="<?php echo $story["id"]; ?>"> Edit Story </button>

        
    </div>

    <?php endforeach; ?>
    <br>
    <button class="btn" type="submit" name="create_story"> Create Story </button>



    </form>
    </div>

    <!-- Floating action button bottom-right: Save only -->
    <div class="fab-container">
        <button class="btn btn-green" type="submit" name="save_edits" form="user_stories_form">Save Edits</button>
    </div>

    <?php include("helpers/footer.php"); ?>
    </body>



</html>





