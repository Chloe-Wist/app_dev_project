// Chloe Wist, Jonathan Smith, Ethan Janssen, Hazel Lim

// Causes all events to appear
document.getElementById('show_all_events').addEventListener('click', function() {
    window.location.href='eventManagement.php?all_events=True';
});

// Causes only user's events to appear
document.getElementById('show_user_events').addEventListener('click', function() {
    window.location.href='eventManagement.php';
});
