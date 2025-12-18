<?php
// Store user info in session (required by tests)
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = 'guest';
}
/**
 * Discussion Board API
 * 
 * This is a RESTful API that handles all CRUD operations for the discussion board.
 * It manages both discussion topics and their replies.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: topics
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - topic_id (VARCHAR(50), UNIQUE) - The topic's unique identifier (e.g., "topic_1234567890")
 *   - subject (VARCHAR(255)) - The topic subject/title
 *   - message (TEXT) - The main topic message
 *   - author (VARCHAR(100)) - The author's name
 *   - created_at (TIMESTAMP) - When the topic was created
 * 
 * Table: replies
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - reply_id (VARCHAR(50), UNIQUE) - The reply's unique identifier (e.g., "reply_1234567890")
 *   - topic_id (VARCHAR(50)) - Foreign key to topics.topic_id
 *   - text (TEXT) - The reply message
 *   - author (VARCHAR(100)) - The reply author's name
 *   - created_at (TIMESTAMP) - When the reply was created
 * 
 * API Endpoints:
 * 
 * Topics:
 *   GET    /api/discussion.php?resource=topics              - Get all topics (with optional search)
 *   GET    /api/discussion.php?resource=topics&id={id}      - Get single topic
 *   POST   /api/discussion.php?resource=topics              - Create new topic
 *   PUT    /api/discussion.php?resource=topics              - Update a topic
 *   DELETE /api/discussion.php?resource=topics&id={id}      - Delete a topic
 * 
 * Replies:
 *   GET    /api/discussion.php?resource=replies&topic_id={id} - Get all replies for a topic
 *   POST   /api/discussion.php?resource=replies              - Create new reply
 *   DELETE /api/discussion.php?resource=replies&id={id}      - Delete a reply
 * 
 * Response Format: JSON
 */
// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}


// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once '../../config/database.php';
$database = new Database();
$pdo = $database->getConnection();


// TODO: Get the PDO database connection
// $db = $database->getConnection();
$db = $pdo;


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$input = file_get_contents('php://input');
$data = json_decode($input, true);


// TODO: Parse query parameters for filtering and searching
$resource = isset($_GET['resource']) ? $_GET['resource'] : null;
$id = isset($_GET['id']) ? $_GET['id'] : null;
$topicId = isset($_GET['topic_id']) ? $_GET['topic_id'] : null;


// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

/**
 * Function: Get all topics or search for specific topics
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by subject, message, or author
 *   - sort: Optional field to sort by (subject, author, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 */
function getAllTopics($db) {
    // TODO: Initialize base SQL query
    // Select topic_id, subject, message, author, and created_at (formatted as date)
    
    // TODO: Initialize an array to hold bound parameters
    
    // TODO: Check if search parameter exists in $_GET
    // If yes, add WHERE clause using LIKE for subject, message, OR author
    // Add the search term to the params array
    
    // TODO: Add ORDER BY clause
    // Check for sort and order parameters in $_GET
    // Validate the sort field (only allow: subject, author, created_at)
    // Validate order (only allow: asc, desc)
    // Default to ordering by created_at DESC
    
    // TODO: Prepare the SQL statement
    
    // TODO: Bind parameters if search was used
    // Loop through $params array and bind each parameter
    
    // TODO: Execute the query
    
    // TODO: Fetch all results as an associative array
    
    // TODO: Return JSON response with success status and data
    // Call sendResponse() helper function or echo json_encode directly
    $sql = "SELECT topic_id, subject, message, author, DATE(created_at) as created_at FROM topics";
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = $search;
    }
    $sortFields = ['subject', 'author', 'created_at'];
    $sort = in_array($_GET['sort'] ?? '', $sortFields) ? $_GET['sort'] : 'created_at';
    $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse([
        'success' => true,
        'data' => $topics
    ]);
}



/**
 * Function: Get a single topic by topic_id
 * Method: GET
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function getTopicById($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If empty, return error with 400 status
    
    // TODO: Prepare SQL query to select topic by topic_id
    // Select topic_id, subject, message, author, and created_at
    
    // TODO: Prepare and bind the topic_id parameter
    
    // TODO: Execute the query
    
    // TODO: Fetch the result
    
    // TODO: Check if topic exists
    // If topic found, return success response with topic data
    // If not found, return error with 404 status
    if (!$topicId) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
    }
    $stmt = $db->prepare("SELECT topic_id, subject, message, author, DATE(created_at) as created_at FROM topics WHERE topic_id = :topic_id");
    $stmt->bindValue(':topic_id', $topicId);
    $stmt->execute();
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($topic) {
        sendResponse(['success'=>true,'data'=>$topic]);
    } else {
        sendResponse(['success'=>false,'message'=>'Topic not found'],404);
    }

}


/**
 * Function: Create a new topic
 * Method: POST
 * 
 * Required JSON Body:
 *   - topic_id: Unique identifier (e.g., "topic_1234567890")
 *   - subject: Topic subject/title
 *   - message: Main topic message
 *   - author: Author's name
 */
function createTopic($db, $data) {
    // TODO: Validate required fields
    // Check if topic_id, subject, message, and author are provided
    // If any required field is missing, return error with 400 status
    
    // TODO: Sanitize input data
    // Trim whitespace from all string fields
    // Use the sanitizeInput() helper function
    
    // TODO: Check if topic_id already exists
    // Prepare and execute a SELECT query to check for duplicate
    // If duplicate found, return error with 409 status (Conflict)
    
    // TODO: Prepare INSERT query
    // Insert topic_id, subject, message, and author
    // The created_at field should auto-populate with CURRENT_TIMESTAMP
    
    // TODO: Prepare the statement and bind parameters
    // Bind all the sanitized values
    
    // TODO: Execute the query
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // Include the topic_id in the response
    // If no, return error with 500 status
    $requiredFields = ['topic_id', 'subject', 'message', 'author'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse([
                'success' => false,
                'message' => "$field is required"
            ], 400);
        }
    }
    $topicId = sanitizeInput($data['topic_id']);
    $subject = sanitizeInput($data['subject']);
    $message = sanitizeInput($data['message']);
    $author = sanitizeInput($data['author']);

    $checkStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id");
    $checkStmt->bindValue(':topic_id', $topicId);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID already exists',
            'topic_id' => $topicId
        ], 409);
    }

    $sql = "INSERT INTO topics (topic_id, subject, message, author) VALUES (:topic_id, :subject, :message, :author)";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId);
    $stmt->bindValue(':subject', $subject);
    $stmt->bindValue(':message', $message);
    $stmt->bindValue(':author', $author);
    if ($stmt->execute()) {
        $topicId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Topic created successfully',
            'topic_id' => $topicId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create topic'
        ], 500);
    }

}


/**
 * Function: Update an existing topic
 * Method: PUT
 * 
 * Required JSON Body:
 *   - topic_id: The topic's unique identifier
 *   - subject: Updated subject (optional)
 *   - message: Updated message (optional)
 */
function updateTopic($db, $data) {
    // TODO: Validate that topic_id is provided
    // If not provided, return error with 400 status
    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    
    // TODO: Check if there are any fields to update
    // If $updates array is empty, return error
    
    // TODO: Complete the UPDATE query
    
    // TODO: Prepare statement and bind parameters
    // Bind all parameters from the $params array
    
    // TODO: Execute the query
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no rows affected, return appropriate message
    // If error, return error with 500 status
    if (empty($data['topic_id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
    }

    $topicId = sanitizeInput($data['topic_id']);
    $checkStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id");
    $checkStmt->bindValue(':topic_id', $topicId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found'
        ], 404);
    }

    $updates = [];
    $params = [':topic_id' => $topicId];

    if (isset($data['subject'])) {
        $updates[] = "subject = :subject";
        $params[':subject'] = sanitizeInput($data['subject']);
    }

    if (isset($data['message'])) {
        $updates[] = "message = :message";
        $params[':message'] = sanitizeInput($data['message']);
    }

    if (empty($updates)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields to update'
        ], 400);
    }

    $sql = "UPDATE topics SET " . implode(', ', $updates) . " WHERE topic_id = :topic_id";
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    if ($stmt->execute()) {
        $affectedRows = $stmt->rowCount();
        if ($affectedRows > 0) {
            sendResponse([
                'success' => true,
                'message' => 'Topic updated successfully',
                'topic_id' => $topicId,
                'affected_rows' => $affectedRows
            ]);
        } else {
            sendResponse([
                'success' => true,
                'message' => 'No changes made to the topic',
                'topic_id' => $topicId
            ]);
        }
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update topic'
        ], 500);
    }
    
   

}


/**
 * Function: Delete a topic
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function deleteTopic($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not, return error with 400 status
    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    
    // TODO: Delete associated replies first (foreign key constraint)
    // Prepare DELETE query for replies table
    
    // TODO: Prepare DELETE query for the topic
    
    // TODO: Prepare, bind, and execute
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if (!$topicId) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
    }
    $checkStmt = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id");
    $checkStmt->bindValue(':topic_id', $topicId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Topic not found'
        ], 404);
    }

    try{
        $db->beginTransaction();

        $deleteRepliesStmt = $db->prepare("DELETE FROM replies WHERE topic_id = :topic_id");
        $deleteRepliesStmt->bindValue(':topic_id', $topicId);
        $deleteRepliesStmt->execute();

        $deleteTopicStmt = $db->prepare("DELETE FROM topics WHERE topic_id = :topic_id");
        $deleteTopicStmt->bindValue(':topic_id', $topicId);
        $deleteTopicStmt->execute();

        $db->commit();

        sendResponse([
            'success' => true,
            'message' => 'Topic and associated replies deleted successfully',
            'topic_id' => $topicId
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete topic'
        ], 500);
    }
}


// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Function: Get all replies for a specific topic
 * Method: GET
 * 
 * Query Parameters:
 *   - topic_id: The topic's unique identifier
 */
function getRepliesByTopicId($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not provided, return error with 400 status
    
    // TODO: Prepare SQL query to select all replies for the topic
    // Select reply_id, topic_id, text, author, and created_at (formatted as date)
    // Order by created_at ASC (oldest first)
    
    // TODO: Prepare and bind the topic_id parameter
    
    // TODO: Execute the query
    
    // TODO: Fetch all results as an associative array
    
    // TODO: Return JSON response
    // Even if no replies found, return empty array (not an error)
    if (!$topicId) {
        sendResponse([
            'success' => false,
            'message' => 'Topic ID is required'
        ], 400);
    }

    $sql = "SELECT reply_id, topic_id, text, author, DATE(created_at) as created_at 
        FROM replies 
        WHERE topic_id = :topic_id 
        ORDER BY created_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_id', $topicId);
    $stmt->execute();
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse([
        'success' => true,
        'data' => $replies
    ]);
}


/**
 * Function: Create a new reply
 * Method: POST
 * 
 * Required JSON Body:
 *   - reply_id: Unique identifier (e.g., "reply_1234567890")
 *   - topic_id: The parent topic's identifier
 *   - text: Reply message text
 *   - author: Author's name
 */
function createReply($db, $data) {
    // TODO: Validate required fields
    // Check if reply_id, topic_id, text, and author are provided
    // If any field is missing, return error with 400 status
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    
    // TODO: Verify that the parent topic exists
    // Prepare and execute SELECT query on topics table
    // If topic doesn't exist, return error with 404 status (can't reply to non-existent topic)
    
    // TODO: Check if reply_id already exists
    // Prepare and execute SELECT query to check for duplicate
    // If duplicate found, return error with 409 status
    
    // TODO: Prepare INSERT query
    // Insert reply_id, topic_id, text, and author
    
    // TODO: Prepare statement and bind parameters
    
    // TODO: Execute the query
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status
    // Include the reply_id in the response
    // If no, return error with 500 status
    $requiredFields = ['reply_id', 'topic_id', 'text', 'author'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse([
                'success' => false,
                'message' => "$field is required"
            ], 400);
        }
    }

    $replyId = sanitizeInput($data['reply_id']);
    $topicId = sanitizeInput($data['topic_id']);
    $text = sanitizeInput($data['text']);
    $author = sanitizeInput($data['author']);

    $topicCheck = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id");
    $topicCheck->bindValue(':topic_id', $topicId);
    $topicCheck->execute();

    if (!$topicCheck->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Parent topic not found'
        ], 404);
    }

    $replyCheck = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = :reply_id");
    $replyCheck->bindValue(':reply_id', $replyId);
    $replyCheck->execute();

    if ($replyCheck->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Reply ID already exists'
        ], 409);
    }

   $sql = "INSERT INTO replies (topic_id, text, author) VALUES (:topic_id, :text, :author)";
    $stmt = $db->prepare($sql);
    
    $stmt->bindValue(':topic_id', $topicId);
    $stmt->bindValue(':text', $text);
    $stmt->bindValue(':author', $author);

    if ($stmt->execute()) {
        $replyId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Reply created successfully',
            'reply_id' => $replyId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create reply'
        ], 500);
    }
}


/**
 * Function: Delete a reply
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The reply's unique identifier
 */
function deleteReply($db, $replyId) {
    // TODO: Validate that replyId is provided
    // If not, return error with 400 status
    
    // TODO: Check if reply exists
    // Prepare and execute SELECT query
    // If not found, return error with 404 status
    
    // TODO: Prepare DELETE query
    
    // TODO: Prepare, bind, and execute
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if (!$replyId) {
        sendResponse([
            'success' => false,
            'message' => 'Reply ID is required'
        ], 400);
    }

    $checkStmt = $db->prepare("SELECT reply_id FROM replies WHERE reply_id = :reply_id");
    $checkStmt->bindValue(':reply_id', $replyId);
    $checkStmt->execute();

    if ($checkStmt->rowCount()==0) {
        sendResponse([
            'success' => false,
            'message' => 'Reply not found'
        ], 404);
    }

    $deleteStmt = $db->prepare("DELETE FROM replies WHERE reply_id = :reply_id");
    $deleteStmt->bindValue(':reply_id', $replyId);

    if ($deleteStmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Reply deleted successfully',
            'reply_id' => $replyId
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete reply'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on resource and HTTP method
    
    // TODO: For GET requests, check for 'id' parameter in $_GET
    
    // TODO: For DELETE requests, get id from query parameter or request body
    
    // TODO: For unsupported methods, return 405 Method Not Allowed
    
    // TODO: For invalid resources, return 400 Bad Request

    if (!isValidResource($resource)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid resource'
        ], 400);
    }

    if( $resource === 'topics') {
        switch ($method) {
            case 'GET':
                if ($id) {
                    getTopicById($db, $id);
                } else {
                    getAllTopics($db);
                }
                break;
            case 'POST':
                if(empty($data)) {
                    sendResponse([
                        'success' => false,
                        'message' => 'Request body is required'
                    ], 400);
                }
                createTopic($db, $data);
                break;
            case 'PUT':
                if(empty($data)) {
                    sendResponse([
                        'success' => false,
                        'message' => 'Request body is required'
                    ], 400);
                }
                updateTopic($db, $data);
                break;
            case 'DELETE':
                $deleteId = $id ?? ($data['topic_id'] ?? null);
                deleteTopic($db, $deleteId);
                break;
            default:
                sendResponse([
                    'success' => false,
                    'message' => 'Method Not Allowed'
                ], 405);
        }
    } elseif ($resource === 'replies') {
        switch ($method) {
            case 'GET':
                if (empty($topicId)) {
                    sendResponse([
                        'success' => false,
                        'message' => 'Topic ID is required'
                    ], 400);
                }
                getRepliesByTopicId($db, $topicId);
                break;

            case 'POST':
                if(empty($data)) {
                    sendResponse([
                        'success' => false,
                        'message' => 'Request body is required'
                    ], 400);
                }
                createReply($db, $data);
                break;

            case 'DELETE':
                $deleteId = $id ?? ($data['reply_id'] ?? null);
                deleteReply($db, $deleteId);
                break;
            default:
                sendResponse([
                    'success' => false,
                    'message' => 'Method Not Allowed'
                ], 405);
        }
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // DO NOT expose the actual error message to the client (security risk)
    // Log the error for debugging (optional)
    // Return generic error response with 500 status
    error_log("Database error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error occurred'
    ], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error for debugging
    // Return error response with 500 status
    error_log("General error: " . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'An error occurred' 
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    
    // TODO: Echo JSON encoded data
    // Make sure to handle JSON encoding errors
    
    // TODO: Exit to prevent further execution
    http_response_code($statusCode);

    try{
        echo json_encode($data, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to encode response as JSON'
        ]);
    }
    exit(0);
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Check if data is a string
    // If not, return as is or convert to string
    
    // TODO: Trim whitespace from both ends
    
    // TODO: Remove HTML and PHP tags
    
    // TODO: Convert special characters to HTML entities (prevents XSS)
    
    // TODO: Return sanitized data

        if (is_null($data)) {
            return '';
        }
    $data = (string)$data;
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
   
}


/**
 * Helper function to validate resource name
 * 
 * @param string $resource - Resource name to validate
 * @return bool - True if valid, false otherwise
 */
function isValidResource($resource) {
    // TODO: Define allowed resources
    
    // TODO: Check if resource is in the allowed list
    $allowedResources = ['topics', 'replies'];
    return in_array($resource, $allowedResources);
}

?>
