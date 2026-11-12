-- Old ERD
-- INSERT INTO users (username, email, password_hash, role_id,
-- 	first_name, last_name, phone_number)
-- VALUES ('Al', 'user@hil.com', '1', 1, 'Alice', 'Greene', 2);

-- INSERT INTO users (username, email, password_hash, role_id,
-- 	first_name, last_name, phone_number)
-- VALUES ('Bob', 'bob@hil.com', '1', 2, 'Roberto', 'Rock', 2);

-- INSERT INTO users (username, email, password_hash, role_id,
-- 	first_name, last_name, phone_number)
-- VALUES ('Jennifer', 'jenn@hil.com', '1', 3, 'Jennifer', 'Brown', 2);

-- INSERT INTO users (username, email, password_hash, role_id,
-- 	first_name, last_name, phone_number)
-- VALUES ('Don', 'susan@hil.com', '1', 4, 'Don', 'Desmond', 2);



-- Sample users for each role (roles created in database.sql)
INSERT INTO users (username, email, password_hash, role_id,
	first_name, last_name, phone_number)
VALUES ('Owner', 'o@hil.com', '1', 1, 'Alice', 'Greene', 2);

INSERT INTO users (username, email, password_hash, role_id,
	first_name, last_name, phone_number)
VALUES ('Admin', 'a@hil.com', '1', 2, 'Roberto', 'Rock', 2);

INSERT INTO users (username, email, password_hash, role_id,
	first_name, last_name, phone_number)
VALUES ('Facilitator', 'f@hil.com', '1', 3, 'Jennifer', 'Brown', 2);

INSERT INTO users (username, email, password_hash, role_id,
	first_name, last_name, phone_number)
VALUES ('Volunteer', 'v@hil.com', '1', 4, 'Don', 'Desmond', 2);



-- Sample sessions for testing future, expired, active, and multi-simulation access
INSERT INTO Sessions
    (Session_Name, Created_By, Passcode, Start_Date_Time, End_Date_Time)
VALUES
    ('Future Test Session', 3, 'FUTURE26',
     '2027-11-01 12:00:00', '2027-11-01 15:00:00');

INSERT INTO Sessions
    (Session_Name, Created_By, Passcode, Start_Date_Time, End_Date_Time)
VALUES
    ('Active Test Session', 3, 'ACTIVE26',
     '2026-10-01 12:00:00', '2027-01-01 15:00:00');

INSERT INTO Sessions
    (Session_Name, Created_By, Passcode, Start_Date_Time, End_Date_Time)
VALUES
    ('Expired Test Session', 3, 'EXPIRED26',
     '2026-10-01 12:00:00', '2026-10-20 15:00:00');

INSERT INTO Sessions
    (Session_Name, Created_By, Passcode, Start_Date_Time, End_Date_Time)
VALUES
    ('Active Multi-Simulation Session', 3, 'MULTI26',
     '2026-10-01 12:00:00', '2027-11-01 15:00:00');



-- Sample industries used by simulations
INSERT INTO Industry (Industry_Name, Industry_Description)
VALUES ('General Adult', 'General adult audience');

INSERT INTO Industry (Industry_Name, Industry_Description)
VALUES ('Healthcare', 'Healthcare industry');

INSERT INTO Industry (Industry_Name, Industry_Description)
VALUES ('Law Enforcement', 'Law enforcement industry');

INSERT INTO Industry (Industry_Name, Industry_Description)
VALUES ('Student', 'Student audience');



-- Sample simulations for General Adult and Healthcare
INSERT INTO Simulation (Simulation_Description, Industry_ID)
VALUES ('General Adult Simulation', 1);

INSERT INTO Simulation (Simulation_Description, Industry_ID)
VALUES ('Healthcare Simulation', 2);



-- General Adult has two published languages and one unpublished language
INSERT INTO Version (Simulation_ID, Is_Published, Version_Language)
VALUES (1, 'Published', 'English');

INSERT INTO Version (Simulation_ID, Is_Published, Version_Language)
VALUES (1, 'Published', 'Spanish');

INSERT INTO Version (Simulation_ID, Is_Published, Version_Language)
VALUES (1, 'Unpublished', 'German');

-- Healthcare has one published language
INSERT INTO Version (Simulation_ID, Is_Published, Version_Language)
VALUES (2, 'Published', 'English');



-- Restricts ACTIVE26 (Session_ID 2) to the General Adult simulation only
INSERT INTO Simulation_to_Session (Session_ID, Simulation_ID)
VALUES (2, 1);

-- Allows MULTI26 to access both General Adult and Healthcare simulations
INSERT INTO Simulation_to_Session (Session_ID, Simulation_ID)
VALUES (4, 1);

INSERT INTO Simulation_to_Session (Session_ID, Simulation_ID)
VALUES (4, 2);


-- Simulation data
INSERT INTO industry (industry_name) VALUES ('General');

INSERT INTO simulation (simulation_description, industry_id)
VALUES ('General simulation', 1);

INSERT INTO version (simulation_id, is_published, version_language)
VALUES (1, 'Published', 'English');

INSERT INTO version (simulation_id, is_published, version_language)
VALUES (1, 'Unpublished', 'Twang');

INSERT INTO pages (version_id, page_type)
VALUEs (1, 'start');

INSERT INTO pages (version_id, page_type)
VALUEs (2, 'start');



-- Data for event management
INSERT INTO area (Area_Name) VALUES ('Area1'), ('Area2');

-- Area1 is in area 1, area2 is in area 2, and area12 is in areas 1 and 2
INSERT INTO users (username, email, password_hash, role_id,
first_name, last_name, phone_number)
VALUES ('Area1', 'user122@hil.com', '1', 1, 'Alice', 'Greene', 2);


INSERT INTO users (username, email, password_hash, role_id,
first_name, last_name, phone_number)
VALUES ('Area2', 'user12@hil.com', '1', 1, 'Alice', 'Greene', 2);

INSERT INTO users (username, email, password_hash, role_id,
first_name, last_name, phone_number)
VALUES ('Area12', 'user1212@hil.com', '1', 1, 'Alice', 'Greene', 2);


-- These might not match for different number of users
INSERT INTO user_to_area (user_id, area_id)
VALUES (11, 1), (12, 2);

INSERT INTO user_to_area (user_id, area_id)
VALUES (13, 1), (13, 2)

-- INSERT INTO Area (Area_Name, Area_Description)
-- VALUES ('Event Coordinator', 'Coordinates Events'), ('Security', 'Handles Security at events');

-- INSERT INTO User_to_Area (User_ID, Area_ID)
-- VALUES (1, 1);




-- ==========================================
-- Additional sample data for Events.php
-- ==========================================
-- Assigns Owner (User_ID 1) to Area1
INSERT INTO User_to_Area (User_ID, Area_ID)
VALUES (1, 1);

-- ------------------------------------------
-- Sample location
-- ------------------------------------------
-- All sample events use the same location (Location_ID 1)
INSERT INTO Location (Address, City, State, Zip_Code)
VALUES ('123 Main Street', 'Harrisburg', 'PA', '17101');


-- ------------------------------------------
-- Sample events
-- ------------------------------------------
-- Six upcoming events and three past events (as of 11/12/2026)
-- All managed by Owner (User_ID 1)
-- All use Location_ID 1

INSERT INTO Event (
    Event_Name, Event_Description, Event_Date,
    Start_Time, End_Time, Manager_ID, Location_ID
)
VALUES
    ('Community Outreach', 'Community outreach event.',
     '2026-12-05', '10:00:00', '14:00:00', 1, 1),

    ('Volunteer Orientation', 'Orientation for new volunteers.',
     '2026-11-20', '09:00:00', '11:00:00', 1, 1),

    ('Fundraising Dinner', 'Annual fundraising dinner.',
     '2026-11-25', '18:00:00', '21:00:00', 1, 1),

    ('Area1 Training', 'Training for Area1 volunteers.',
     '2026-12-02', '13:00:00', '16:00:00', 1, 1),

    ('Community Awareness Fair', 'Public awareness event.',
     '2026-12-10', '10:00:00', '15:00:00', 1, 1),

    ('Volunteer Appreciation', 'Appreciation gathering for volunteers.',
     '2026-12-15', '17:00:00', '19:00:00', 1, 1),

    ('Past Outreach Workshop', 'Previous outreach workshop.',
     '2026-10-15', '10:00:00', '12:00:00', 1, 1),

    ('Past Area1 Meeting', 'Previous Area1 meeting.',
     '2026-11-05', '14:00:00', '16:00:00', 1, 1),

    ('Past Fundraising Event', 'Previous fundraising event.',
     '2026-09-20', '18:00:00', '21:00:00', 1, 1);


-- ------------------------------------------
-- Event invitations and RSVP statuses
-- ------------------------------------------
-- Community Outreach: Invited, No Reply
-- Volunteer Orientation: Invited, No Reply
-- Fundraising Dinner: Invited, Yes
-- Community Awareness Fair: Not invited, Maybe
-- Volunteer Appreciation: Invited, No

-- Assumes the nine sample events have Event_IDs 1 through 9
INSERT INTO Event_Signup (Event_ID, User_ID, Status, Is_Invited)
VALUES
    (1, 1, 'No Reply', TRUE),
    (2, 1, 'No Reply', TRUE),
    (3, 1, 'Yes', TRUE),
    (5, 1, 'Maybe', FALSE),
    (6, 1, 'No', TRUE);


-- ------------------------------------------
-- Event area assignments
-- ------------------------------------------
-- Area1 Training: Area1
-- Community Awareness Fair: Area1
-- Volunteer Appreciation: Area1 and Area2
-- Past Area1 Meeting: Area1
INSERT INTO Event_to_Area (Event_ID, Area_ID)
VALUES
    (4, 1),
    (5, 1),
    (6, 1),
    (6, 2),
    (8, 1);