<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

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
    exit();
}


// TODO: Include the database connection class
require_once __DIR__ . '/../config/Database.php';


// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
$database = new Database();




// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();

$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? 'weeks';

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
// Get request body for POST/PUT
$requestBody = null;

if ($method === 'POST' || $method === 'PUT') {
    $input = file_get_contents('php://input');
    $requestBody = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid JSON body"]);
        exit;
    }
}

// Parse resource parameter


// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = $_GET['resource'] ?? 'weeks';


        if ($method === 'GET') {
            if (isset($_GET['week_id'])) {
                getWeekById($db, $_GET['week_id']);
            } else {
                getAllWeeks($db);
            }
        }

        elseif ($method === 'POST') {
            createWeek($db, $requestBody);
        }

        elseif ($method === 'PUT') {
            updateWeek($db, $requestBody);
        }

        elseif ($method === 'DELETE') {
            // في DELETE ممكن يجي week_id من البودي أو من الكويري
            $weekId = $requestBody['week_id'] ?? ($_GET['week_id'] ?? null);
            deleteWeek($db, $weekId);
        }

        else {
            sendResponse(['error' => 'Method not allowed'], 405);
        }
    }

    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {

        if ($method === 'GET') {
            if (!isset($_GET['week_id'])) {
                sendResponse(['error' => 'week_id is required'], 400);
            }
            getCommentsByWeek($db, $_GET['week_id']);
        }

        elseif ($method === 'POST') {
            createComment($db, $requestBody);
        }

        elseif ($method === 'DELETE') {
            $commentId = $requestBody['id'] ?? ($_GET['id'] ?? null);
            deleteComment($db, $commentId);
        }

        else {
            sendResponse(['error' => 'Method not allowed'], 405);
        }
    }

    else {
        sendResponse(['error' => 'Invalid resource'], 400);
    }
    
} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()], 500);
}

// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : null;
   $validSortFields = ['title', 'start_date', 'created_at'];
   $sortField = (isset($_GET['sort']) && in_array($_GET['sort'], $validSortFields))
                ? $_GET['sort']
                : 'start_date';

    $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC'  : 'ASC';

    
    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $sql = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    $params = [];

    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
     if ($searchTerm) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $like = "%{$searchTerm}%";
        $params[] = $like;
        $params[] = $like;
      }
    
    
    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    
    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)

    
    
    // TODO: Add ORDER BY clause to the query
      $sql .= " ORDER BY $sortField $order";
    // TODO: Prepare the SQL query using PDO
     $stmt = $db->prepare($sql);
  


    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    // TODO: Execute the query
   
    
    $stmt->execute($params);

    
    // TODO: Fetch all results as an associative array
   
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);

   
    
    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
     foreach ($weeks as &$week) {
        $week['links'] = json_decode($week['links'], true);
    }
    

    
    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
     sendResponse(['success' => true, 'data' => $weeks]);

}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    if (empty($weekId)) {
    sendResponse(['error' => 'week_id is required'], 400);
    return;
}

     $weekId = trim($weekId);
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $stmt = $db->prepare("SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?");
    // TODO: Bind the week_id parameter
    $stmt->bindParam(1, $weekId);
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch the result
    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    
    // TODO: Check if week exists
    if ($week) {
        // Decode links JSON
        $week['links'] = json_decode($week['links'], true);
        // Return success response
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        // Return error response
        sendResponse(['error' => 'Week not found'], 404);
    }
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    if (empty($data['week_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['description'])) {
        sendResponse(['error' => 'week_id, title, start_date, and description are required'], 400);
        return;
    }
    // Check if week_id, title, start_date, and description are provided
    $statement = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $statement->bindParam(1, $data['week_id']);
    $statement->execute();
    // If any field is missing, return error response with 400 status
    if ($statement->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'week_id already exists'], 409);
        return;
    }


    
    // TODO: Sanitize input data
    $weekId = trim($data['week_id']);
    $title = trim($data['title']);
    $startDate = trim($data['start_date']);
    $description = trim($data['description']);
    
    // TODO: Validate start_date format
    if (!validateDate($startDate)) {
        sendResponse(['error' => 'start_date must be in YYYY-MM-DD format'], 400);
        return;
    }
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    $stmnt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $stmnt->bindParam(1, $weekId);
    // If invalid, return error response with 400 status
    $stmnt->execute();
    
    // TODO: Check if week_id already exists
    $stmt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $stmt->bindParam(1, $weekId);
    $stmt->execute();
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'week_id already exists'], 409);
        return;
    }
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $stmt->execute();

    
    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
     $links = isset($data['links']) && is_array($data['links']) ? json_encode($data['links']) : json_encode([]);
    
    
    // TODO: Prepare INSERT query
    $stmt = $db->prepare("INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)");
    $stmt->bindParam(1, $weekId);
    $stmt->bindParam(2, $title);
    $stmt->bindParam(3, $startDate);
    $stmt->bindParam(4, $description);
    $stmt->bindParam(5, $links);
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
    
    // TODO: Bind parameters
   
     $stmt->bindParam(1, $weekId);
     $stmt->bindParam(2, $title);
     $stmt->bindParam(3, $startDate);
     $stmt->bindParam(4, $description);
     $stmt->bindParam(5, $links);


    // TODO: Execute the query
    $success = $stmt->execute();
    
    // TODO: Check if insert was successful
    if ($success) {
        // Return success response
        $newWeek = [
            'week_id' => $weekId,
            'title' => $title,
            'start_date' => $startDate,
            'description' => $description,
            'links' => json_decode($links, true),
        ];
        sendResponse(['success' => true, 'data' => $newWeek], 201);
    } else {
        sendResponse(['error' => 'Failed to create week'], 500);
    }
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
}


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($data['week_id'])) {
        sendResponse(['error' => 'week_id is required'], 400);
        return;
    }
        $weekId = $data['week_id'];

    // TODO: Check if week exists
    $stmt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $stmt->bindParam(1, $data['week_id']);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'week_id not found'], 404);
        return;
    }
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    $setClauses = [];

    // Initialize an array to hold values for binding
     $values = [];

    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    if (!empty($data['title'])) {
        $setClauses[] = "title = ?";
        $values[] = $data['title'];
    }
    // If start_date is provided, validate format and add "start_date = ?"
    if (!empty($data['start_date'])) {
        if (!validateDate($data['start_date'])) {
            sendResponse(['error' => 'start_date must be in YYYY-MM-DD format'], 400);
            return;
        }
        $setClauses[] = "start_date = ?";
        $values[] = $data['start_date'];
    }

    // If description is provided, add "description = ?"
    if (!empty($data['description'])) {
        $setClauses[] = "description = ?";
        $values[] = $data['description'];
    }
    // If links is provided, encode to JSON and add "links = ?"
    if (isset($data['links'])) {

        $setClauses[] = "links = ?";
        $values[] = json_encode($data['links']);
    }

    
    // TODO: If no fields to update, return error response with 400 status
    if (empty($setClauses)) {
        sendResponse(['error' => 'No fields to update'], 400);
        return;
    }
    
    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    
    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    $sql = "UPDATE weeks SET " . implode(", ", $setClauses) . " WHERE week_id = ?";
    $stmt = $db->prepare($sql);
    
    // TODO: Prepare the query

    
    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    foreach ($values as $index => $value) {
        $stmt->bindParam($index + 1, $value);
    }
    $stmt->bindParam(count($values) + 1, $data['week_id']);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
    if ($stmt->rowCount() > 0) {
        // Return success response
        sendResponse(['success' => true, 'message' => 'Week updated successfully']);
    } else {
        sendResponse(['error' => 'Failed to update week'], 500);
    }
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendResponse(['error' => 'week_id is required'], 400);
        return;
    }
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $stmt->bindParam(1, $weekId);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'week_id not found'], 404);
        return;
    }
    
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
     $stmt = $db->prepare("DELETE FROM comments WHERE week_id = ?");
     $stmt->bindParam(1, $weekId);
    // TODO: Execute the query
    $stmt->execute();


    // DELETE FROM comments WHERE week_id = ?
   

    // TODO: Execute comment deletion query
   

    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $stmt = $db->prepare("DELETE FROM weeks WHERE week_id = ?");
    $stmt->bindParam(1, $weekId);
    // TODO: Bind the week_id parameter
    
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
    // If no, return error response with 500 status
    if ($stmt->rowCount() > 0) {
        // Return success response
        sendResponse(['success' => true, 'message' => 'Week and associated comments deleted successfully']);
    } else {
        sendResponse(['error' => 'Failed to delete week'], 500);
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendResponse(['error' => 'week_id is required'], 400);
        return;
    }
    
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $stmt = $db->prepare("SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC");
    
    // TODO: Bind the week_id parameter
    $stmt->bindParam(1, $weekId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
    sendResponse(['success' => true, 'data' => $comments]);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    if (empty($data['week_id']) || empty($data['author']) || empty($data['text'])) {
        sendResponse(['error' => 'week_id, author, and text are required'], 400);
        return;
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $weekId = trim($data['week_id']);
    $author = trim($data['author']);
    $text = trim($data['text']);
    
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if (empty($text)) {
        sendResponse(['error' => 'Comment text cannot be empty'], 400);
        return;
    }
    
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    $stmt = $db->prepare("SELECT week_id FROM weeks WHERE week_id = ?");
    $stmt->bindParam(1, $weekId);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'week_id not found'], 404);
        return;
    }
    
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
    $stmt = $db->prepare("INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)");
    
    // TODO: Bind parameters
    $stmt->bindParam(1, $weekId);
    $stmt->bindParam(2, $author);
    $stmt->bindParam(3, $text); 
    
    
    // TODO: Execute the query
    $success = $stmt->execute();
    
    // TODO: Check if insert was successful
    if ($success) {
        // Return success response
        $newComment = [
            'id' => $db->lastInsertId(),
            'week_id' => $weekId,
            'author' => $author,
            'text' => $text,
        ];
        sendResponse(['success' => true, 'data' => $newComment], 201);
    } else {
        sendResponse(['error' => 'Failed to create comment'], 500);
    }
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    if (empty($commentId)) {
        sendResponse(['error' => 'Comment id is required'], 400);
        return;
    }
    
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $stmt->bindParam(1, $commentId);
    $stmt->execute();
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse(['error' => 'Comment id not found'], 404);
        return;
    }
    
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    
    // TODO: Bind the id parameter
    $stmt->bindParam(1, $commentId);
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($stmt->rowCount() > 0) {
        // Return success response
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully']);
    } else {
        sendResponse(['error' => 'Failed to delete comment'], 500);
    }   
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    $resource = isset($_GET['resource']) ? $_GET['resource'] : 'weeks';
    $method = $_SERVER['REQUEST_METHOD'];
    
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            if (isset($_GET['week_id'])) {
                getWeekById($db, $_GET['week_id']);
            } else {
                getAllWeeks($db);
            }
            
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
            createWeek($db, $requestBody);
            
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            updateWeek($db, $requestBody);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            if (isset($_GET['week_id'])) {
                deleteWeek($db, $_GET['week_id']);
            } else {
                sendResponse(['error' => 'week_id is required for deletion'], 400);
            }

            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            http_response_code(405);
            sendResponse(['error' => 'Method Not Allowed'], 405);
            exit();
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        if ($method === 'GET') {
          if (isset($_GET['week_id'])) {
             getCommentsByWeek($db, $_GET['week_id']);
    } else {
         sendResponse(['error' => 'week_id is required'], 400);
    }
    
            // Call getCommentsByWeek()

        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            createComment($db, $requestBody);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body

            // Call deleteComment()
            if (isset($_GET['comment_id'])) {
                deleteComment($db, $_GET['comment_id']);
            } else {
                sendResponse(['error' => 'Comment id is required for deletion'], 400);
            }
            exit();
            
        } else {
            // TODO: Return error for unsupported methods
            http_response_code(405);
            sendResponse(['error' => 'Method Not Allowed'], 405);
            // Set HTTP status to 405 (Method Not Allowed)
            exit();
        }
    
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        http_response_code(400);
        sendResponse(['error' => "Invalid resource. Use 'weeks' or 'comments'"], 400);
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    http_response_code(500);
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    
    // TODO: Return generic error response with 500 status
    sendResponse(['error' => 'Database error occurred'], 500);
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    
} catch (Exception $e) {
    // TODO: Handle general errors
    http_response_code(500);
    // Log the error message (optional)
    // Return error response with 500 status
    sendResponse(['error' => 'An unexpected error occurred'], 500);
}



// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    http_response_code($statusCode);
    
    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    echo json_encode($data);
    
    // TODO: Exit to prevent further execution
    exit();
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    $errorResponse = ['success' => false, 'error' => $message];
    
    // TODO: Call sendResponse() with the error array and status code
    sendResponse($errorResponse, $statusCode);
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);
    

    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);
    
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data);
    
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
    return in_array($field, $allowedFields);
}

?>
