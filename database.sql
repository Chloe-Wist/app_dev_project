-- Document made by Jonathan Smith
-- Comments with ? at start = questions about how something works or changes made from ERD diagram
-- Comments with ! at start = explanation about a choice
-- Comment with ~ at start = Unfinished work
-- (Jonathan) There are some changes here that I have not made to the ERD yet
-- (Hazel) I synchronized this script with the ERD and ERD document on 10/9/2026 5:30PM.

CREATE DATABASE IF NOT EXISTS ERD;

USE ERD;

CREATE TABLE IF NOT EXISTS Sim_Role (
    Sim_Role_ID INT AUTO_INCREMENT PRIMARY KEY,
    Sim_Role_Name VARCHAR(50) NOT NULL,
    Sim_Role_Description TEXT
);

CREATE TABLE IF NOT EXISTS Scheduling_Role (
    Schd_Role_ID INT AUTO_INCREMENT PRIMARY KEY,
    Schd_Role_Name VARCHAR(50) NOT NULL,
    Schd_Role_Description TEXT
);

CREATE TABLE IF NOT EXISTS Users (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    Sim_Role_ID INT NOT NULL,
    Schd_Role_ID INT NOT NULL,
    First_Name VARCHAR(50) NOT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    Username VARCHAR(20) NOT NULL UNIQUE,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Phone_Number VARCHAR(20) NOT NULL,
    Creation_Date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Password_Hash VARCHAR(255) NOT NULL,
    Photo VARCHAR(500),

    -- Foreign Keys:
    FOREIGN KEY (Sim_Role_ID) REFERENCES Sim_Role(Sim_Role_ID),
    FOREIGN KEY (Schd_Role_ID) REFERENCES Scheduling_Role(Schd_Role_ID)
);

CREATE TABLE IF NOT EXISTS Activity_Log (
    Log_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT,
    Time_Stamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    -- ?: User_Action can change from varchar to enum if we know what actions will be done
    User_Action VARCHAR(50) NOT NULL,

    -- Foreign Keys
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID)
);

CREATE TABLE IF NOT EXISTS Access_Request (
    Request_ID INT AUTO_INCREMENT PRIMARY KEY,
    First_Name VARCHAR(50) NOT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    Company VARCHAR(100) NOT NULL,
    Email VARCHAR(255) NOT NULL,
    Reason TEXT
);

CREATE TABLE IF NOT EXISTS Industry (
    Industry_ID INT AUTO_INCREMENT PRIMARY KEY,
    Industry_Name VARCHAR(100) NOT NULL,
    Industry_Description TEXT
);

CREATE TABLE IF NOT EXISTS Sessions (
    Session_ID INT AUTO_INCREMENT PRIMARY KEY,
    Session_Name VARCHAR(50) NOT NULL,
    Created_By INT NOT NULL,
    Passcode VARCHAR(50) NOT NULL UNIQUE,
    Start_Date_Time DATETIME NOT NULL,
    End_Date_Time DATETIME,

    -- Foreign Keys
    FOREIGN KEY (Created_By) REFERENCES Users(User_ID)
);

CREATE TABLE IF NOT EXISTS Analytics (
    Analytic_ID INT AUTO_INCREMENT PRIMARY KEY,
    Session_ID INT NOT NULL,
    First_Name VARCHAR(50) NOT NULL,
    Last_Name VARCHAR(50) NOT NULL,
    Email VARCHAR(255) NOT NULL,
    Job_Title VARCHAR(50) NOT NULL,
    Industry_ID INT NOT NULL,

    -- Foreign Keys
    FOREIGN KEY (Session_ID) REFERENCES Sessions(Session_ID),
    FOREIGN KEY (Industry_ID) REFERENCES Industry(Industry_ID)
);

CREATE TABLE IF NOT EXISTS Simulation (
    Simulation_ID INT AUTO_INCREMENT PRIMARY KEY,
    Simulation_Description TEXT,
    Creation_Date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Last_Edited_Date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Industry_ID INT NOT NULL UNIQUE,

    -- Foreign Keys
    FOREIGN KEY (Industry_ID) REFERENCES Industry(Industry_ID)
);

CREATE TABLE IF NOT EXISTS Version (
    Version_ID INT AUTO_INCREMENT PRIMARY KEY,
    Simulation_ID INT NOT NULL,
    Is_Published ENUM('Published','Unpublished') NOT NULL,
    Creation_Date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Last_Edited_Date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Version_Language VARCHAR(30) NOT NULL,

    -- Foreign Keys
    FOREIGN KEY (Simulation_ID) REFERENCES Simulation(Simulation_ID)
);

CREATE TABLE IF NOT EXISTS Pages (
    Page_ID INT PRIMARY KEY AUTO_INCREMENT,
    Version_ID INT NOT NULL,
    Page_Name VARCHAR(100) NOT NULL,
    -- !: Using JSON instead of BLOB as storing a combination of images, text and videos would cause problems with the database.
    -- This also lets us be able to search for videos, images, etc. in the database by using JSON_EXTRACT() or ->> shortcut
    Page_Content JSON NOT NULL,

    -- Foreign Keys
    FOREIGN KEY (Version_ID) REFERENCES Version(Version_ID)
);

CREATE TABLE IF NOT EXISTS Choice (
    Choice_ID INT AUTO_INCREMENT PRIMARY KEY,
    Page_ID INT NOT NULL,
    Choice_Text TEXT NOT NULL,
    Next_Page_ID INT NOT NULL,

    -- Foreign Keys
    FOREIGN KEY (Next_Page_ID) REFERENCES Pages(Page_ID),
    FOREIGN KEY (Page_ID) REFERENCES Pages(Page_ID)
);

CREATE TABLE IF NOT EXISTS Area (
    Area_ID INT AUTO_INCREMENT PRIMARY KEY,
    Area_Name VARCHAR(50) NOT NULL,
    Area_Description TEXT
);

CREATE TABLE IF NOT EXISTS User_to_Area (
    User_ID INT NOT NULL,
    Area_ID INT NOT NULL,

    PRIMARY KEY (User_ID, Area_ID),

    -- Foreign Keys
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID),
    FOREIGN KEY (Area_ID) REFERENCES Area(Area_ID)
);

CREATE TABLE IF NOT EXISTS Location (
    Location_ID INT AUTO_INCREMENT PRIMARY KEY,
    Address VARCHAR(255),
    City VARCHAR(50),
    State VARCHAR(50),
    Zip_Code VARCHAR(10)
);

CREATE TABLE IF NOT EXISTS Event (
    Event_ID INT AUTO_INCREMENT PRIMARY KEY,
    Event_Name VARCHAR(100) NOT NULL,
    Event_Description TEXT NOT NULL,
    Event_Date DATE NOT NULL,
    Start_Time TIME NOT NULL,
    End_Time TIME NOT NULL,
    Created_By INT NOT NULL,
    Location_ID INT NOT NULL,
    Client_Name VARCHAR(100),

    -- Foreign Keys
    FOREIGN KEY (Created_By) REFERENCES Users(User_ID),
    FOREIGN KEY (Location_ID) REFERENCES Location(Location_ID)
);

CREATE TABLE IF NOT EXISTS Event_to_Area (
    Event_ID INT NOT NULL,
    Area_ID INT NOT NULL,

    PRIMARY KEY (Event_ID, Area_ID),

    -- Foreign Keys
    FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID),
    FOREIGN KEY (Area_ID) REFERENCES Area(Area_ID)
);

CREATE TABLE IF NOT EXISTS Event_Signup (
    Event_ID INT NOT NULL,
    User_ID INT NOT NULL,
    Status ENUM('Yes','Maybe','No','No Reply') NOT NULL DEFAULT('No Reply'),

    PRIMARY KEY (Event_ID, User_ID),

    -- Foreign Keys
    FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID),
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID)
);

CREATE TABLE IF NOT EXISTS Availability (
    Availability_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    Start_Time TIME NOT NULL,
    End_Time TIME NOT NULL,

    -- Foreign Keys
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID)
);

CREATE TABLE IF NOT EXISTS Event_Document (
    Document_ID INT AUTO_INCREMENT PRIMARY KEY,
    Event_ID INT NOT NULL,
    Document_Name VARCHAR(100) NOT NULL,
    Document_Path VARCHAR(500) NOT NULL,
    Upload_Date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Foreign Keys
    FOREIGN KEY (Event_ID) REFERENCES Event(Event_ID) ON DELETE CASCADE
);