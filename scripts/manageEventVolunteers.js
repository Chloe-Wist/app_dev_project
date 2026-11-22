// Chloe Wist, Jonathan Smith, Ethan Janssen, Hazel Lim

// Wait for page to load
window.addEventListener("load", () => {
    // Important elements

    // Used for showing different users that can be invited
    const show_all_areas_button = document.getElementById("show_all_areas");
    const show_unavailable_button = document.getElementById("show_unavailable");

    const show_matching_areas_button = document.getElementById("show_matching_areas");
    const show_available_button = document.getElementById("show_available");

    let show_all_areas = false;
    let show_unavailable = false;

    // Used for showing users that have different RSVP statuses

    const return_to_invite_button = document.getElementById("return_to_invite");

    const invite_div = document.getElementById("invite_section");

    const rsvp_yes_button = document.getElementById("rsvp_yes");
    const rsvp_maybe_button = document.getElementById("rsvp_maybe");
    const rsvp_no_button = document.getElementById("rsvp_no");
    const rsvp_no_reply_button = document.getElementById("rsvp_no_reply");

    const rsvp_yes_list = document.getElementById("rsvp_yes_list");
    const rsvp_maybe_list = document.getElementById("rsvp_maybe_list");
    const rsvp_no_list = document.getElementById("rsvp_no_list");
    const rsvp_no_reply_list = document.getElementById("rsvp_no_reply_list");

    // When the show users in any area button is clicked
    show_all_areas_button.addEventListener('click', () => {
        // Change which buttons are visible
        show_all_areas_button.hidden = true;
        show_all_areas_button.disabled = true;

        show_matching_areas_button.hidden = false;
        show_matching_areas_button.disabled = false;

        // Show users with not matching areas, and if the user also wants
        // to see unavailable users, include the ones with unmatched areas
        $users = [...document.getElementsByClassName("user10"), ...
            (show_unavailable?document.getElementsByClassName("user00"):[])
        ];
        $users.forEach(($u) => {
            $u.hidden = false;
        });

        show_all_areas = true;
    });

    // When the show unavailable users button is clicked
    show_unavailable_button.addEventListener('click', () => {
        // Change which buttons are visible
        show_unavailable_button.hidden = true;
        show_unavailable_button.disabled = true;

        show_available_button.hidden = false;
        show_available_button.disabled = false;

        // Show unavailable users and if the user also wants
        // to see not matching area users, include them
        $users = [...document.getElementsByClassName("user01"), ...
            (show_all_areas?document.getElementsByClassName("user00"):[])
        ];
        $users.forEach(($u) => {
            $u.hidden = false;
        });

        show_unavailable = true;
    });

    // When the show only users in matching areas button is clicked
    show_matching_areas_button.addEventListener('click', () => {
        // Change which buttons are visible
        show_all_areas_button.hidden = false;
        show_all_areas_button.disabled = false;

        show_matching_areas_button.hidden = true;
        show_matching_areas_button.disabled = true;

        $users = [...document.getElementsByClassName("user10"), ...
            (show_unavailable?document.getElementsByClassName("user00"):[])
        ];
        $users.forEach(($u) => {
            $u.hidden = true;
        });

        show_all_areas = false;
    });

    // When the show only users who are available button is clicked
    show_available_button.addEventListener('click', () => {
        // Change which buttons are visible
        show_unavailable_button.hidden = false;
        show_unavailable_button.disabled = false;

        show_available_button.hidden = true;
        show_available_button.disabled = true;

        $users = [...document.getElementsByClassName("user01"), ...
            (show_all_areas?document.getElementsByClassName("user00"):[])
        ];
        $users.forEach(($u) => {
            $u.hidden = true;
        });

        show_unavailable = false;
    });

    // Show the rsvp yes page
    rsvp_yes_button.addEventListener("click", () => {
        // Show the return button
        return_to_invite_button.hidden = false;
        return_to_invite_button.disabled = false;

        // Hide the invite section
        invite_div.hidden = true;

        // Show the RSVP yes section
        rsvp_yes_list.hidden = false;
        rsvp_yes_list.disabled = false;

        // Hide the other sections
        rsvp_maybe_list.hidden = true;
        rsvp_maybe_list.disabled = true;

        rsvp_no_list.hidden = true;
        rsvp_no_list.disabled = true;

        rsvp_no_reply_list.hidden = true;
        rsvp_no_reply_list.disabled = true;
    });

    // Show the rsvp maybe page
    rsvp_maybe_button.addEventListener("click", () => {
        // Show the return button
        return_to_invite_button.hidden = false;
        return_to_invite_button.disabled = false;

        // Hide the invite section
        invite_div.hidden = true;

        // Show the RSVP maybe section
        rsvp_maybe_list.hidden = false;
        rsvp_maybe_list.disabled = false;

        // Hide the other sections
        rsvp_yes_list.hidden = true;
        rsvp_yes_list.disabled = true;

        rsvp_no_list.hidden = true;
        rsvp_no_list.disabled = true;

        rsvp_no_reply_list.hidden = true;
        rsvp_no_reply_list.disabled = true;
    });

    // Show the rsvp no page
    rsvp_no_button.addEventListener("click", () => {
        // Show the return button
        return_to_invite_button.hidden = false;
        return_to_invite_button.disabled = false;

        // Hide the invite section
        invite_div.hidden = true;

        // Show the RSVP no section
        rsvp_no_list.hidden = false;
        rsvp_no_list.disabled = false;

        // Hide the other sections
        rsvp_yes_list.hidden = true;
        rsvp_yes_list.disabled = true;
        
        rsvp_maybe_list.hidden = true;
        rsvp_maybe_list.disabled = true;

        rsvp_no_reply_list.hidden = true;
        rsvp_no_reply_list.disabled = true;
    });

    // Show the rsvp yes page
    rsvp_no_reply_button.addEventListener("click", () => {
        // Show the return button
        return_to_invite_button.hidden = false;
        return_to_invite_button.disabled = false;

        // Hide the invite section
        invite_div.hidden = true;

        // Show the RSVP no reply section
        rsvp_no_reply_list.hidden = false;
        rsvp_no_reply_list.disabled = false;

        // Hide the other sections
        rsvp_yes_list.hidden = true;
        rsvp_yes_list.disabled = true;

        rsvp_maybe_list.hidden = true;
        rsvp_maybe_list.disabled = true;

        rsvp_no_list.hidden = true;
        rsvp_no_list.disabled = true;
    });

    // Return to the invite page
    return_to_invite_button.addEventListener("click", () => {
        // Hide the return button
        return_to_invite_button.hidden = true;
        return_to_invite_button.disabled = true;

        // Show the invite section
        invite_div.hidden = false;

        // Hide the RSVP sections
        rsvp_yes_list.hidden = true;
        rsvp_yes_list.disabled = true;

        rsvp_maybe_list.hidden = true;
        rsvp_maybe_list.disabled = true;

        rsvp_no_list.hidden = true;
        rsvp_no_list.disabled = true;

        rsvp_no_reply_list.hidden = true;
        rsvp_no_reply_list.disabled = true;
    });
});