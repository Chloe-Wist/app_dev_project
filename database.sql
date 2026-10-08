-- Document made by Jonathan Smith
-- Comments with ? at start = questions about how something works or changes made from ERD diagram
-- Comments with ! at start = explanation about a choice
-- Comment with ~ at start = Unfinished work
-- There are some changes here that I have not made to the ERD yet

create database if not exists ERD;

use ERD;

Create table if not exists Sim_Role (
	Sim_Role_ID int auto_increment Primary Key,
    Sim_Role_Name varchar(50) Not Null,
    Sim_Role_Description text
);

create table if not exists Scheduling_Role (
	Schd_Role_ID int auto_increment Primary key,
    Schd_Role_Name varchar(50) Not Null,
    Schd_Role_Description text
);

-- !: User table name changed to Users here
create table if not exists Users (
	User_ID int auto_increment primary key,
    Sim_Role_ID int Not Null,
    Schd_Role_ID int Not Null,
    First_Name Varchar(50) Not Null,
    Last_Name Varchar(50) Not Null,
    Email varchar(255) Not Null Unique,
    Phone_Number varchar(20) Not Null,
    Creation_Date Date Not Null,
    Password_Hash varchar(255) Not Null,
    Photo Varchar(500),
    
    -- Foreign Keys:
    Foreign Key (Sim_Role_ID) references Sim_Role(Sim_Role_ID),
    Foreign Key (Schd_Role_ID) references Scheduling_Role(Schd_Role_ID)
);


create table if not exists Activity_Log (
	Log_ID int auto_increment Primary Key,
    User_ID int,
    Time_Stamp DateTime default Current_Timestamp not Null,
    -- ?: User_Action can change from varchar to enum if we know what actions will be done
    User_Action varchar(50) Not Null,
    -- Foreign Keys
    Foreign Key (User_ID) references Users(User_ID)
);

Create table if not exists Access_Request (
	Request_ID int auto_increment Primary Key,
    First_Name varchar(50) Not Null,
    Last_Name varchar(50) Not Null,
    -- !: From Organization to Company
    Company varchar(100) Not Null,
    Email varchar(255) Not Null,
    Reason text 
);

Create table if not exists Industry (
	Industry_ID int auto_increment Primary Key,
    -- !: Changed from Name in ERD to Industry_Name to avoid repetition
    Industry_Name varchar(100) Not Null,
    Industry_Description text
);

create table if not exists Sessions (
	Session_ID int auto_increment Primary Key,
    Session_Name varchar(50) Not Null,
    Created_By int Not Null,
    Passcode varchar(50) Not Null,
    Start_Date_Time DateTime Not Null,
    End_Date_Time DateTime Not Null,
    -- Foreign Keys
    Foreign Key (Created_By) references Users(User_ID)
);

create table if not exists Analytics (
	Analytic_ID int auto_increment Primary Key,
    Session_ID int,
    First_Name varchar(50) Not Null,
    Last_Name varchar(50) Not Null,
    Email varchar(255) Not Null,
    Job_Title varchar(50) Not Null,
    Industry_ID int,
    -- Foreign Keys
    Foreign Key (Session_ID) references Sessions(Session_ID),
    Foreign Key (Industry_ID) references Industry(Industry_ID)
);



Create table if not exists Simulation (
	Simulation_ID int auto_increment Primary key,
    Simulation_Description text,
    Is_Published Enum('Published','Unpublished') Not Null,
    Creation_Date DATETIME default Current_Timestamp,
    Last_Edited_Date DateTime default Current_Timestamp On Update Current_Timestamp,
    Industry_ID int Not Null,
    -- Foreign Keys
    Foreign Key (Industry_ID) references Industry(Industry_ID)
);

Create table if not exists Version (
	Version_ID int auto_Increment Primary Key,
    Simulation_ID int Not Null,
    Is_Published Enum('Published','Unpublished') Not Null,
    Creation_Date DATETIME default Current_Timestamp,
    Last_Edited_Date DateTime default Current_Timestamp On Update Current_Timestamp,
    Version_Language Varchar(30) Not Null,
    -- Foreign Keys
    Foreign Key (Simulation_ID) references Simulation(Simulation_ID)
);

-- !: Page changed to Pages
Create table if not exists Pages (
	Page_ID int primary key auto_increment,
    Version_ID int Not Null,
    Page_Name varchar(100) Not Null,
    -- !: Using JSON instead of BLOB as storing a combination of images, text and videos would cause problems with the database.
    -- This also lets us be able to search for videos, images, etc. in the database by using JSON_EXTRACT() or ->> shortcut
    Page_Content JSON Not Null,
    -- Foreign Keys
    Foreign Key (Version_ID) references Version (Version_ID)
);

-- ?~: I am unsure about how Next_Page is supposed to work here if it's an int that denotes page number or 
-- if it's meant to tell the system to change the state of the webpage the user is currently on
Create table if not exists Choice (
	Choice_ID int auto_increment primary key,
    Page_Id int Not Null,
    Choice_Text text Not Null,
    Next_Page_ID int Not Null,
    -- Foreign Key
    Foreign Key (Next_Page_ID) references Pages(Page_ID),
    Foreign Key (Page_ID) references Pages(Page_ID)
);

Create table if not exists Area (
	Area_ID int auto_increment Primary Key,
    Area_Name Varchar(50) not Null,
    Area_Description text not null
);

-- !: Removed User_To_Area column as we can make the Foreign keys a composite Primary Key
Create table if not exists User_to_Area (
	User_Id int Not Null,
    Area_ID int Not Null,
    
    Primary Key(User_ID,Area_ID),
    -- Foreign keys
    Foreign Key (User_ID) references Users (User_ID),
    Foreign Key (Area_ID) references Area(Area_ID)
);

Create table if not exists Location (
	Location_ID int auto_Increment Primary key,
    Address varchar(255),
    City varchar(50),
    State varchar(50),
    Zip_Code varchar(9)
);

-- !: Client changed to Client_Name because Workbench was highlighting it
Create table if not exists Event (
	Event_ID int auto_increment Primary Key,
    Event_Name Varchar(100) not Null,
    Event_Description text Not Null,
    Event_Date Date Not Null,
    Start_Time Time Not Null,
    End_Time Time Not Null,
    Created_By int not null,
    Location_Id int not null,
    Client_Name varchar(100),
    -- ~: Assume Doucment is here: Document 
    -- Foreign Keys
    Foreign Key (Created_By) references Users(User_Id),
    Foreign Key (Location_ID) references Location(Location_ID)
);

-- !: Removed Event_To_Area column as we can make the Foreign keys a composite Primary Key
Create table if not exists Event_to_Area (
	Event_ID int not null,
    Area_ID int not null,
    Primary Key(Event_ID,Area_ID),
    -- Foreign Keys
    Foreign Key (Event_ID) references Event(Event_ID),
    Foreign Key (Area_ID) references Area(Area_ID)
);

Create table if not exists Event_Signup (
	Event_ID Int Not Null,
    User_ID Int Not Null,
    Status ENUM('Yes','Maybe','No', 'No Reply') Not Null default('No Reply'),
    
    Primary Key (Event_ID, User_ID),
    
    -- Foreign Keys
    Foreign Key (Event_ID) References Event(Event_ID),
    Foreign Key (User_ID) references Users(User_ID)
);

Create table if not exists Availability (
	Availability_ID Int auto_Increment primary Key,
    User_ID Int Not Null,
    Day Enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') Not Null,
    Start_Time Time Not Null,
    End_Time Time Not Null,
    -- Foreign Keys
    Foreign Key (User_ID) References Users(User_ID)
);

Create table if not exists Event_Document (
    Document_ID int auto_increment Primary Key,
    Event_ID int Not Null,
    Document_Name varchar(100) Not Null,
    Document_Path varchar(500) Not Null,
    Upload_Date DateTime default Current_Timestamp,
    -- Foreign Keys
    Foreign Key (Event_ID) references Event(Event_ID) on delete cascade
);