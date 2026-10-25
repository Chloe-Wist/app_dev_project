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
     '2026-11-01 12:00:00', '2026-11-01 15:00:00');

INSERT INTO Sessions
    (Session_Name, Created_By, Passcode, Start_Date_Time, End_Date_Time)
VALUES
    ('Active Test Session', 3, 'ACTIVE26',
     '2026-10-01 12:00:00', '2026-11-01 15:00:00');

INSERT INTO Sessions
    (Session_Name, Created_By, Passcode, Start_Date_Time, End_Date_Time)
VALUES
    ('Expired Test Session', 3, 'EXPIRED26',
     '2026-10-01 12:00:00', '2026-10-20 15:00:00');

INSERT INTO Sessions
    (Session_Name, Created_By, Passcode, Start_Date_Time, End_Date_Time)
VALUES
    ('Active Multi-Simulation Session', 3, 'MULTI26',
     '2026-10-01 12:00:00', '2026-11-01 15:00:00');



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