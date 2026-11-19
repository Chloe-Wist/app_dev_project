<?php
// Chloe Wist, Hazel Lim, Jonathan Smith, Ethan Janssen
// 11/8/26

// Holds information about each event

class Events {
    public $id;
    public $name;
    public $description;
    public $start_datetime;
    public $end_datetime;
    public $manager_id;
    public $location;
    public $client_name;
    public $areas = [];
    public $documents = [];
    public $sessions;

    // Constructor
    public function __construct($event_id) {
        // Connect to database
        require "config.php";

        // Find the matching event, if it exists
        $query = "SELECT Event_ID, Event_Name, Event_Description, Event_Date, 
            Start_Time, End_Time, Manager_ID, Location_ID, Client_Name
            FROM event WHERE event_id = ".$event_id;

        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));

        // Store all relevant data
        try {
            // Simple data
            $this->id = $result['Event_ID'];
            $this->name = $result['Event_Name'];
            $this->description = $result['Event_Description'];
            $this->start_datetime = new DateTimeImmutable($result['Event_Date'].' '.$result['Start_Time']);
            $this->end_datetime = new DateTimeImmutable($result['Event_Date'].' '.$result['End_Time']);;
            $this->client_name = $result['Client_Name'];

            /* Complicated data */
            // Manager information
            $query = "SELECT first_name, last_name From 
                users WHERE user_id = ".$result['Manager_ID'];
            $data = mysqli_fetch_assoc(mysqli_query($conn, $query));
            $this->manager_id = 
                ['user_id' => $result['Manager_ID'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']];

            // Location Information
            $query = "SELECT address, city, state, zip_code, name FROM
                location WHERE location_id = ".$result['Location_ID'];
            $data = mysqli_fetch_assoc(mysqli_query($conn, $query));
            $this->location = 
                ['location_id' => $result['Location_ID'],
                'name' => $data['name'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip_code' => $data['zip_code']];

            // Session information
            $query = "SELECT session_id FROM sessions WHERE 
                event_id = ".$event_id;
            $data = mysqli_fetch_assoc(mysqli_query($conn, $query));

            if (isset($data)) {
                $this->sessions = $data['Session_ID'];
            }
            else {
                $this->sessions = False;
            }

            /* Multiple matching data */
            // Areas of event
            $query = "SELECT area_name, area.area_id FROM event_to_area 
                JOIN area ON area.area_id = event_to_area.area_id
                WHERE event_id = ".$this->id;
            $data = mysqli_query($conn, $query);
            while ($a = mysqli_fetch_assoc($data)) {
                array_push($this->areas, ["area_id" => $a['area_id'], 
                                        "area_name" => $a['area_name']]);
            }

            // Documents from event
            $query = "SELECT document_id, document_name, document_path, 
                upload_date FROM event_document WHERE event_id = ".$this->id;
            $data = mysqli_query($conn, $query);
            while ($a = mysqli_fetch_assoc($data)) {
                $d = ['id' => $a['Document_ID'],
                    'name' => $a['Document_Name'],
                    'path' => $a['Document_Path'],
                    'upload_date' => $a['Upload_Date']];
                array_push($this->areas, $d);
            }
            $this->documents;
        }
        catch (Exception $e) {
            echo "<p>Failed to find event ".$event_id."</p>";
            echo $e;
        }
    }
}