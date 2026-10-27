INSERT INTO users (username, email, password_hash, role_id,
	first_name, last_name, phone_number)
VALUES ('Al', 'user@hil.com', '1', 1, 'Alice', 'Greene', 2);

INSERT INTO users (username, email, password_hash, role_id,
	first_name, last_name, phone_number)
VALUES ('Bob', 'bob@hil.com', '1', 2, 'Roberto', 'Rock', 2);

INSERT INTO users (username, email, password_hash, role_id,
	first_name, last_name, phone_number)
VALUES ('Jennifer', 'jenn@hil.com', '1', 3, 'Jennifer', 'Brown', 2);

INSERT INTO users (username, email, password_hash, role_id,
	first_name, last_name, phone_number)
VALUES ('Don', 'susan@hil.com', '1', 4, 'Don', 'Desmond', 2);






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



-- Simulation data
INSERT INTO industry (industry_name) VALUES ('General');

INSERT INTO simulation (simulation_description, industry_id)
VALUES ('General simulation', 1);

INSERT INTO version (simulation_id, is_published, version_language)
VALUES (1, 'Unpublished', 'English');

INSERT INTO pages (version_id, page_type)
VALUEs (1, 'start')



