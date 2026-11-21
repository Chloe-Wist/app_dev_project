// Chloe Wist, Jonathan Smith, Ethan Janssen, Hazel Lim

// Wait for page to load
window.addEventListener("load", () => {

    // Important HTML elements
    const locationForm = document.getElementById("edit_location");
    const location_select = document.getElementById("location_select");

    const edit_button = document.getElementById("edit_location_button");
    const cancel_edit_location = document.getElementById("cancel_edit_location");

    const change_location_button = document.getElementById("change_location");
    const cancel_location_select = document.getElementById("cancel_location_select");

    const create_button = document.getElementById("create_location_button");

    const event_changes_form = document.getElementById("edit_event");

    // Used for alternating between create new location or
    // edit existing location
    let location_id;
    let location_name;
    let address;
    let city;
    let state;
    let zipcode;


    // Performs form validation
    const validateForm = () => {
        let form = event_changes_form;

        let isValid = true;
        let errorMessage = "";

        // Check event name
        if (form.elements['event_name'].value.trim() === "") {
            isValid = false;
            errorMessage += "Event name cannot be blank";
        }

        // Check description
        if (form.elements['description'].value.trim() === "") {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "Description cannot be blank"
        }
        
        // Check date
        if (form.elements['date'].value.trim() === "") {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "Invalid date"
        }

        // Check start time
        if (form.elements['start_time'].value.trim() === "") {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "Invalid start time"
        }  
        
        // Check end time
        if (form.elements['end_time'].value.trim() === "") {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "Invalid end time"
        } 

        // Check that there is at least one area
        if (!Boolean(document.querySelector
                ('#areas_fieldset input[name="area[]"]:checked'))) {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "At least one area needs to be checked"
        } 

        // Check for a manager ID
        if (form.elements['manager'].value.trim() == -1) {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "Please select manager";
        }

        // Check for loction

        // If selecting a previously defined location, ensure that they 
        // selected a valid choice
        if (form.elements['location_select'].disabled === false && 
                form.elements['location_select'].value.trim() == -1) {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "Please select location";
        }
        // If entering a new location or editing current location,
        // ensure that all fields are entered
        else if (form.elements['edit_location'].disabled === false) {
            // Check name
            if (form.elements['location_name'].value.trim() === "") {
                if (!isValid) {
                    errorMessage += "\n";
                }
                isValid = false;
                errorMessage += "Please name the location";
            }

            // Check address
            if (form.elements['location_address'].value.trim() === "") {
                if (!isValid) {
                    errorMessage += "\n";
                }
                isValid = false;
                errorMessage += "Please specify the address";
            }
            // Check city
            if (form.elements['location_city'].value.trim() === "") {
                if (!isValid) {
                    errorMessage += "\n";
                }
                isValid = false;
                errorMessage += "Please specify city";
            }
            // Check state
            if (form.elements['location_state'].value.trim() === "") {
                if (!isValid) {
                    errorMessage += "\n";
                }
                isValid = false;
                errorMessage += "Please specify state";
            }

            // Check zip code
            if (form.elements['location_zipcode'].value.trim() === "") {
                if (!isValid) {
                    errorMessage += "\n";
                }
                isValid = false;
                errorMessage += "Please specify zip code";
            } 
        }
        // Ensures that if the program was was going to select the original
        // address, it was not undefined
        else if (form.elements['location_id_original'].value == -1
            && form.elements['location_select'].disabled === false
            && form.elements['edit_location'].disabled === false
        ) {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "Please select location";
        }

        if (form.elements["client"].value.trim() === "") {
            if (!isValid) {
                errorMessage += "\n";
            }
            isValid = false;
            errorMessage += "Please specify client name";
        }
        
        return [isValid, errorMessage];
    };

    // Saves form data and then loads the session page 
    if (document.getElementById("edit_session")) {
        document.getElementById('edit_session').addEventListener('click', async (event) => {

            const form = document.getElementById("edit_event");
            
            let [isValid, errorMessage] = validateForm();
            if (!isValid) {
                event.preventDefault();
                const error = document.getElementById("error")
                error.value = errorMessage;
                error.hidden = false;
            }
            else {
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
            }
        });
    }

    // Enables editing the current location
    if (edit_button) {
        // When the edit current location button is clicked,
        // remove the other buttons and show the 
        // current location editing form
        edit_button.addEventListener('click', () => {
            locationForm.hidden = false;
            locationForm.disabled = false;

            change_location_button.hidden = true;
            change_location_button.disabled = true;

            create_button.disabled = true;
            create_button.hidden = true;

            edit_button.hidden = true;
            edit_button.disabled = true;

            change_location_button.hidden = true;
            change_location_button.disabled = true;
        });
    }

    // Return to original state of location
    if (cancel_edit_location) {
        // Whent the cancel button on the location editing form is clicked,
        // hide the form and make the create button and the edit button come back
        cancel_edit_location.addEventListener( "click", () => {
            locationForm.hidden = true;
            locationForm.disabled = true;

            change_location_button.hidden = false;
            change_location_button.disabled = false;

            create_button.disabled = false;
            create_button.hidden = false;

            edit_button.hidden = false;
            edit_button.disabled = false;

            change_location_button.hidden = false;
            change_location_button.disabled = false;

            // Restore old information
            form = locationForm;
            
            // Reusable for holding current element
            if (location_id) {
                let element;
                
                // Location ID
                element = form.elements['location_id'];
                element.value = location_id;

                // Location Name
                element = form.elements['location_name'];
                element.value = location_name;

                // Address
                element = form.elements['location_address'];
                element.value = address;

                // City
                element = form.elements['location_city'];
                element.value = city;

                // State
                element = form.elements['location_state'];
                element.value = state;

                // Zip code
                element = form.elements['location_zipcode'];
                element.value = zipcode;

                // Hide for all events
                element = form.elements['for_all_events'];
                element.hidden = false;
                element = document.querySelector('label[for="for_all_events"]');
                element.hidden = false;
            }
        });
    }

    if (change_location_button) {
        // When the change location button is pressed, 
        change_location_button.addEventListener('click', () => {
            location_select.hidden = false;
            location_select.disabled = false;

            cancel_location_select.disabled = false;
            cancel_location_select.hidden = false;

            change_location_button.hidden = true;
            change_location_button.disabled = true;

            create_button.disabled = true;
            create_button.hidden = true;

            edit_button.hidden = true;
            edit_button.disabled = true;

            change_location_button.hidden = true;
            change_location_button.disabled = true;
        });
    }

    // Return to original state of location
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

            change_location_button.hidden = false;
            change_location_button.disabled = false;

            edit_button.hidden = false;
            edit_button.disabled = false;

            change_location_button.hidden = false;
            change_location_button.disabled = false;
        });
    }
    
    // Show the input for creating a new location
    if (create_button) {
        // Create button works the same way as edit
        create_button.addEventListener('click', () => {
            locationForm.hidden = false;
            locationForm.disabled = false;

            change_location_button.hidden = true;
            change_location_button.disabled = true;

            create_button.disabled = true;
            create_button.hidden = true;

            edit_button.hidden = true;
            edit_button.disabled = true;

            change_location_button.hidden = true;
            change_location_button.disabled = true;

            // Save old info and clear it for new location
            form = locationForm;
            
            // Reusable for holding current element
            let element;
            
            // Location ID
            element = form.elements['location_id'];
            location_id = element.value;
            element.value = -1;

            // Location Name
            element = form.elements['location_name'];
            location_name = element.value;
            element.value = "";

            // Address
            element = form.elements['location_address'];
            address = element.value;
            element.value = "";

            // City
            element = form.elements['location_city'];
            city = element.value;
            element.value = "";

            // State
            element = form.elements['location_state'];
            state = element.value;
            element.value = "";

            // Zip code
            element = form.elements['location_zipcode'];
            zipcode = element.value;
            element.value = "";

            // Hide for all events
            element = form.elements['for_all_events'];
            element.hidden = true;
            element.value = "no";
            element = document.querySelector('label[for="for_all_events"]');
            element.hidden = true; 
        });
    }

    // When the form is submitted, validate before submitting
    if (event_changes_form) {
        event_changes_form.addEventListener('submit', (event) => {
            let [isValid, errorMessage] = validateForm();

            // Stop form submission and show error
            if (!isValid) {
                event.preventDefault();
                const error = document.getElementById("error");
                error.innerText = errorMessage;
                error.hidden = false;
            }
        });
    }

});