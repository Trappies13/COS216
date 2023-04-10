<!--
<?php
include('header.php');
?>
-->

<?php

class CarAPI {
    private static $instance = null;
    private $db = null;

    private function __construct() {
        // Connect to the database
        $this->db = new mysqli('localhost', 'username', 'password', 'database');
        if ($this->db->connect_error) {
            die('Connection failed: ' . $this->db->connect_error);
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new CarAPI();
        }
        return self::$instance;
    }
    
    public function handleRequest() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('HTTP/1.1 405 Method Not Allowed');
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $postData = json_decode(file_get_contents('php://input'), true);

    // Handle the request and provide the response
    if (isset($postData['name']) && isset($postData['email'])) {
        // Save the data to a database or send an email
        $name = $postData['name'];
        $email = $postData['email'];

        // Send a success response
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => true]);
        exit;
    } else {
        // Send an error response if the required data is missing
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['error' => 'Missing required data']);
        exit;
    }
}
    
    private function validateApiKey($apiKey) {
    $validKeys = ['key1', 'key2', 'key3'];
    return in_array($apiKey, $validKeys);
}
    
    private function getCars($returnFields, $search, $limit, $sort, $order, $fuzzy) {
    // Start building the SQL query
    $query = 'SELECT ';

    // Check if returnFields is specified, otherwise select all fields
    if ($returnFields) {
        $query .= implode(',', $returnFields);
    } else {
        $query .= '*';
    }

    // Add the table name
    $query .= ' FROM cars';

    // Check if a search query is provided
    if ($search) {
        $searchFields = ['make', 'model', 'year', 'color'];
        $searchQuery = '(';
        foreach ($searchFields as $field) {
            $searchQuery .= "$field LIKE '%$search%' OR ";
        }
        $searchQuery = substr($searchQuery, 0, -4);
        $searchQuery .= ')';
        if ($fuzzy) {
            $searchQuery = 'MATCH (' . implode(',', $searchFields) . ') AGAINST (:search IN BOOLEAN MODE)';
        }
        $query .= " WHERE $searchQuery";
    }

    // Check if a sort order is specified
    if ($sort && $order) {
        $query .= " ORDER BY $sort $order";
    }

    // Check if a limit is specified
    if ($limit) {
        $query .= " LIMIT $limit";
    }

    // Prepare and execute the SQL query
    $stmt = $this->db->prepare($query);
    if ($search && $fuzzy) {
        $stmt->bindParam(':search', $search, PDO::PARAM_STR);
    }
    $stmt->execute();

    // Fetch the results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $results;
}
    
    private function getCarImage($imageUrl) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $imageUrl,
        CURLOPT_USERAGENT => 'CarAPI'
    ));
    $imageData = curl_exec($curl);
    curl_close($curl);
    return base64_encode($imageData);
}
}