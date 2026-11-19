// Chloe Wist, Jonathan Smith, Ethan Janssen, Hazel Lim

// Wait for page to load
window.addEventListener("load", () => {

    // Saves form data and then loads the session page 
    if (document.getElementById("edit_session")) {
        document.getElementById('edit_session').addEventListener('click', async () => {

            const form = document.getElementById("edit_event");

            try {
                // Send the form data in the background
                const formData = new FormData(form);
                await fetch(form.action, {
                    method: form.method,
                    body: formData
                });

                // Go to session
                let event_id = document.getElementById("event_id").value;
                window.location.href='sessionManagement.php?event_id=' + event_id;
            } 
            catch (error) {
                console.error('Form submission failed:', error);
            }
        });
    }

    const locationForm = document.getElementById("edit_location");
    const location_select = document.getElementById("location_select");

    const edit_button = document.getElementById("edit_location_button");
    const cancel_edit_location = document.getElementById("cancel_edit_location");

    const change_location_button = document.getElementById("change_location");
    const cancel_location_select = document.getElementById("cancel_location_select");

    const create_button = document.getElementById("create_location_button");

    const editEvents = document.getElementsByClassName("eventEditor");

    // Enables editing the current location
    if (edit_button) {
        // When the edit current location button is clicked,
        // remove the create location button and the 
        // edit location button, and show the 
        // current location editing form
        edit_button.addEventListener('click', () => {
            locationForm.hidden = false;
            locationForm.disabled = false;

            create_button.disabled = true;
            create_button.hidden = true;

            edit_button.hidden = true;
            edit_button.disabled = true;

            change_location_button.hidden = true;
            change_location_button.disabled = true;
        });
    }

    if (cancel_edit_location) {
        // Whent the cancel button on the location editing form is clicked,
        // hide the form and make the create button and the edit button come back
        cancel_edit_location.addEventListener( "click", () => {
            locationForm.hidden = true;
            locationForm.disabled = true;

            create_button.disabled = false;
            create_button.hidden = false;

            edit_button.hidden = false;
            edit_button.disabled = false;

            change_location_button.hidden = false;
            change_location_button.disabled = false;
        });
    }

    if (change_location_button) {
        // When the change location button is pressed, 
        change_location_button.addEventListener('click', () => {
            location_select.hidden = false;
            location_select.disabled = false;

            cancel_location_select.disabled = false;
            cancel_location_select.hidden = false;

            create_button.disabled = true;
            create_button.hidden = true;

            edit_button.hidden = true;
            edit_button.disabled = true;

            change_location_button.hidden = true;
            change_location_button.disabled = true;
        });
    }

    if (cancel_location_select) {
        // Whent the cancel button on the location editing form is clicked,
        // hide the form and make the create button and the edit button come back
        cancel_location_select.addEventListener( "click", () => {
            location_select.hidden = true;
            location_select.disabled = true;

            cancel_location_select.disabled = true;
            cancel_location_select.hidden = true;

            create_button.disabled = false;
            create_button.hidden = false;

            edit_button.hidden = false;
            edit_button.disabled = false;

            change_location_button.hidden = false;
            change_location_button.disabled = false;
        });
    }
    
    if (create_button) {
        // Create button works the same way as edit
        create_button.addEventListener('click', () => {
            locationForm.hidden = false;
            locationForm.disabled = false;

            create_button.disabled = true;
            create_button.hidden = true;

            edit_button.hidden = true;
            edit_button.disabled = true;

            change_location_button.hidden = true;
            change_location_button.disabled = true;
        });
    }
});