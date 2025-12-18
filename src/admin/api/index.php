<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

session_start(); //already started the session, but loading it now

//check if user is authenticated and is admin
if (isset($_GET['verify'])) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        sendResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        exit;
    }
    
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        sendResponse(['success' => false, 'message' => 'Not authorized, Admin access required'], 403);
        exit;
    }
    
    // Return user info
    sendResponse([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'is_admin' => $_SESSION['is_admin']
        ]
    ]);
    exit;
}

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); //change * to my frontend port!!
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// TODO: Get the PDO database connection

function getConnection() {
    $host = 'localhost';
    $db   = 'course';
    $user = 'admin';
    $pass = 'password123';
    $dsn = "mysql:host=$host;dbname=$db;";
    
    try {
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}

try {
    $db = getConnection();
}catch (PDOException $e){
    sendResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    exit;
}



// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$input = json_decode(file_get_contents('php://input'), true);


// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;



/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db, $queryParams) { //i added queryparams it wasn't there
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields

    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)

    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    
    // TODO: Bind parameters if using search
    
    // TODO: Execute the query
    
    // TODO: Fetch all results as an associative array
    
    // TODO: Return JSON response with success status and data

    $search = $queryParams['search'] ?? '';
    $sort = $queryParams['sort'] ?? 'name';
    $order = $queryParams['order'] ?? 'asc';

    $allowedSortFields = ['name', 'id', 'email', 'created_at'];
    $sort = in_array($sort, $allowedSortFields) ? $sort: 'name';

    $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

    if (!empty($search)){
        $sql = "SELECT id, name, email, is_admin, created_at FROM users
                WHERE is_admin = 0 AND (name LIKE :search OR email LIKE :search)
                ORDER BY $sort $order";

        $stmt = $db->prepare($sql);
        $searchTerm = "%$search%";
        $stmt->bindParam(':search', $searchTerm);
    }else{
        $sql = "SELECT id, name, email, is_admin, created_at FROM users
                WHERE is_admin = 0
                ORDER BY $sort $order";
        $stmt = $db->prepare($sql);
    }

    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data' => $students,
        'total' => count($students)
    ]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    if (empty($studentId)) {
        sendResponse(['success' => false, 'message' => 'Student ID is required'], 400);
        return;
    }

    // TODO: Prepare SQL query to select student by student_id
    $sql = "SELECT id, name, email, is_admin, created_at FROM users WHERE id = :id AND is_admin = 0";
    $stmt = $db->prepare($sql);
    // TODO: Bind the student_id parameter
    $stmt->bindParam(':id', $studentId);
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch the result
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status

    if ($student){
        sendResponse(['success' => true, 'data' => $student]);
    }else{
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    $requiredFields = ['name', 'email', 'password'];

    foreach($requiredFields as $field) {
        if (empty($data[$field])){
            sendResponse(['success' => false, 'message' => "$field is required"], 400);
            return;
        }
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    $name = sanitizeInput(trim($data['name']));
    $email = sanitizeInput(trim($data['email']));
    $password = $data['password']; //should i also trim the password??

    if (!validateEmail($email)){
        sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
        return;
    }
    
    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checkSql = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();

    if ($checkStmt-> fetch()){
        sendResponse(['success' => false, 'message' => 'Student ID or email already exists'], 409);
        return;
    }


    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // TODO: Prepare INSERT query
    $sql = "INSERT INTO users (name, email, password, is_admin) VALUES (:name, :email, :password, 0)";
    
    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    
    // TODO: Execute the query
    try {
        $stmt->execute();

    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status

    $lastInsertId = $db->lastInsertId();
        
        sendResponse([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => [
                'id' => $lastInsertId,
                'name' => $name, 
                'email' => $email
            ]
        ], 201);
    }catch (PDOException $e) {
        error_log("Create user error: " . $e->getMessage());
        sendResponse([
            'success' => false,
            'message' => 'Failed to create student'
        ], 500);
    }
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (empty($data['id'])){
        sendResponse(['success' => false, 'message' => 'Student ID is required'], 400);
        return;
    }
    
    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $studentId = $data['id'];

    $checkSql = "SELECT id FROM users WHERE id = :id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':id', $studentId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()){
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
        return;
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    
    $updates = [];
    $params = [':id' => $studentId];

    if (!empty($data['name'])){
        $updates[] = 'name = :name';
        $params[':name'] = sanitizeInput(trim($data['name']));
    }

    // TODO: If email is being updated, check if new email already exists
    // Prepare and execute a SELECT query
    // Exclude the current student from the check
    // If duplicate found, return error response with 409 status

    if (!empty($data['email'])){
        $newEmail = sanitizeInput(trim($data['email']));

        if (!validateEmail($newEmail)){
            sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
            return;
        }

        $emailCheckSql = "SELECT id FROM users WHERE email = :email AND id != :id";
        $emailCheckStmt = $db->prepare($emailCheckSql);
        $emailCheckStmt->bindParam(':email', $newEmail);
        $emailCheckStmt->bindParam(':id', $studentId);
        $emailCheckStmt->execute();
        
        if ($emailCheckStmt->fetch()) {
            sendResponse(['success' => false, 'message' => 'Email already exists'], 409);
            return;
        }

        $updates[] = 'email = :email';
        $params[':email'] = $newEmail;
    }
    
    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
    if (empty($updates)){
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
        return;
    }
    
    // TODO: Execute the query
    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id AND is_admin = 0";
    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value){
        $stmt->bindValue($key, $value);
    }
    
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Student updated successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update student'], 500);
    }
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (empty($studentId)){
        sendResponse(['success' => false, 'message' => 'Student ID is required'], 400);
        return;
    }
    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    
    $checkSql = "SELECT id FROM users WHERE id = :id AND is_admin = 0";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':id', $studentId);
    $checkStmt->execute();

    if (!$checkStmt->fetch()){
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
        return;
    }

    // TODO: Prepare DELETE query
    
    $sql = "DELETE FROM users WHERE id = :id AND is_admin = 0";
    $stmt = $db->prepare($sql);

    // TODO: Bind the student_id parameter
    $stmt->bindParam(':id', $studentId);

    // TODO: Execute the query
    $success = $stmt->execute();

    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($success){
        sendResponse(['success' => true, 'message' => 'Student deleted successfully']);
    }else{
        sendResponse(['success' => false, 'message' => 'Failed to delete student'], 500);
    }
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status

    $requiredFields = ['id', 'current_password', 'new_password'];

    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            sendResponse(['success' => false, 'message' => "$field is required"], 400);
            return;
        }
    }
    
    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    $studentId = $data['id'];
    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];

    if (strlen($newPassword) < 8) {
        sendResponse(['success' => false, 'message' => 'New password must be at least 8 characters long'], 400);
        return;
    }
    
    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    $sql = "SELECT password FROM users WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $studentId);
    $stmt->execute();

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
        return;
    }
    
    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    if (!password_verify($currentPassword, $student['password'])){
        sendResponse(['success' => false, 'message' => 'Current password is incorrect'], 500); //should be 500, since user authorized but while changing password
        return;
    }
    
    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // TODO: Update password in database
    // Prepare UPDATE query
    $updateSql = "UPDATE users SET password = :password WHERE id = :id";
    $updateStmt = $db->prepare($updateSql);
    
    // TODO: Bind parameters and execute
    $updateStmt->bindParam(':password', $hashedPassword);
    $updateStmt->bindParam(':id', $studentId);
    $success = $updateStmt->execute();

    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($success){
        sendResponse(['success' => true, 'message' => 'Password changed successfully']);
    }else{
        sendResponse(['success' => false, 'message' => 'Failed to change password'], 500);
    }

}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)

        if (isset($queryParams['id'])) {
            getStudentById($db, $queryParams['id']);
        }else{
            getStudents($db, $queryParams);
        }

    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()

        if (isset($queryParams['action']) && $queryParams['action'] === 'change_password'){
            changePassword($db, $input);
        }else{
            createStudent($db, $input);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        updateStudent($db, $input);
        
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        $studentId = $queryParams['id'] ?? $input['id'] ?? '';
        deleteStudent($db, $studentId);
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    error_log("Database error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error occurred'], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    error_log("General error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'An error occurred'], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    // TODO: Echo JSON encoded data
    echo json_encode($data);
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    // TODO: Strip HTML tags using strip_tags()
    // TODO: Convert special characters using htmlspecialchars()
    // Return sanitized data

    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

?>
