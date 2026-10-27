<?php
// Creates a sidebar to navigate through the pages
echo "<h3>Current Pagess</h3>";
$query = "SELECT page_id, page_name FROM pages WHERE version_id=".$version_id;
$result = mysqli_query($conn, $query);

echo "<ul class='action-list card'>";

while($a = mysqli_fetch_assoc($result)) {
    echo "<li><a href='SimulationEditor.php?new_page_id=".$a['page_id']."'
        >".($a['page_name']?$a['page_name']:"Untitled Page ".$a['page_id'])."</a>";
    echo "</li><br>";
}
echo "</ul>";
?>