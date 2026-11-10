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
        $query = "SELECT * FROM event WHERE event_id = ".$event_id;

        $result = mysqli_fetch_assoc(mysqli_query($conn, $query));

        // Store all relevant data
        try {
            // Simple data
            $this->$id = $result['event_id'];
            $this->$name = $result['event_name'];
            $this->description = $result['event_description'];
            $this->$start_datetime = new DateTimeImmutable($result['date'].' '.$result['start_time']);
            $this->$end_datetime = new DateTimeImmutable($result['date'].' '.$result['end_time']);;
            $this->$client_name = $result['client_name'];

            /* Complicated data */
            // Manager information
            $query = "SELECT first_name, last_name From 
                users WHERE user_id = ".$result['manager_id'];
            $data = mysqli_fetch_assoc(mysqli_query($conn, $query));
            $this->$manager_id = 
                ['user_id' => $result['manager_id'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']];

            // Location Information
            $query = "SELECT address, city, state, zip_code FROM
                location WHERE location_id = ".$result['location_id'];
            $data = mysqli_fetch_assoc(mysqli_query($conn, $query));
            $this->$location = 
                ['address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip_code' => $data['zip_code']];

            // Session information
            $query = "SELECT session_id FROM sessions WHERE 
                event_id = ".$event_id;
            try {
                $data = mysqli_fetch_assoc(mysqli_query($conn, $query));
                $this->$session = $data['session_id'];
            }
            catch (Exception $e) {
                $this->$session = False;
            }

            /* Multiple matching data */
            // Areas of event
            $query = "SELECT area_name FROM event_to_area 
                JOIN area ON area.area_id = event_to_area.area_id
                WHERE event_id = ".$event_id;
            $data = mysqli_query($conn, $data);
            while ($a = mysqli_fetch_assoc($data)) {
                array_push($this->$areas, $a['area_name']);
            }

            // Documents from event
            $query = "SELECT document_id, document_name, document_path, 
                upload_date FROM event_document WHERE event_id = ".$event_id;
            $data = mysqli_query($conn, $data);
            while ($a = mysqli_fetch_assoc($data)) {
                $d = ['id' => $a['document_id'],
                    'name' => $a['document_name'],
                    'path' => $a['document_path'],
                    'upload_date' => $a['upload_date']];
                array_push($this->$areas, $d);
            }
            $this->documents;
        }
        catch (Exception $e) {
            echo "<p>Failed to find event ".$event_id."</p>";
            echo $e;
        }
    }
}