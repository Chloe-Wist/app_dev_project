INSERT INTO sim_role (sim_role_name) 
VALUES ('Admin'), ('Editor'), ('Viewer');

INSERT INTO scheduling_role (schd_role_name)
VALUES ('Admin'), ('Scheduler'), ('Volunteer');



INSERT INTO users (username, email, password_hash, sim_role_id, schd_role_id,
	first_name, last_name, phone_number)
VALUES ('User', 'user@hil.com', '1', 1, 1, 'Alice', 'Greene', 2);