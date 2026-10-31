<?php
// Creates a sidebar to navigate through the pages
echo "<h3>Current Pagess</h3>";
$query = "SELECT page_id, page_name, page_type FROM pages WHERE version_id=".$version_id;
$result = mysqli_query($conn, $query);

echo "<ul class='action-list card'>";

while($a = mysqli_fetch_assoc($result)) {
    echo "<li><a href='SimulationEditor.php?new_page_id=".$a['page_id']."'
        class='".$a['page_type']."'>".($a['page_name']?$a['page_name']:"Untitled Page ")."</a>";
    echo "<p class='type'>".$a['page_type']."</p>";
    echo "</li><br>";
}
echo "</ul>";
?>