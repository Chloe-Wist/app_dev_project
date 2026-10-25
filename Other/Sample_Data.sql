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






INSERT INTO Roles (Role_Name, Role_Description)
VALUES ('Owner', 'Owner role');

INSERT INTO Roles (Role_Name, Role_Description)
VALUES ('Admin', 'Admin role');

INSERT INTO Roles (Role_Name, Role_Description)
VALUES ('Facilitator', 'Facilitator role');

INSERT INTO Roles (Role_Name, Role_Description)
VALUES ('Volunteer', 'Volunteer role');






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