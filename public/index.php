<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';



$app = new \Slim\App();
$servername = "localhost";  // MySQL server (localhost if using XAMPP)
$username = "root";         // MySQL username (default in XAMPP is root)
$pass = "";             // MySQL password (default in XAMPP is empty)
$dbname = "webstore";  

$app->post('/newuser', function ($request, $response, $args) use ($servername, $username, $pass,$dbname){
    global $servername, $username, $pass,$dbname;
    
    // Get the data from the request
    $data = $request->getParsedBody();
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    // Hash the password before saving
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Create a new mysqli connection
    try{ 
    
        $conn = new mysqli($servername, $username, $pass, $dbname);

        // Check the connection
        if ($conn->connect_error) {
            return $response->withStatus(500)->write("Connection failed: " . $conn->connect_error);
        }

        // Prepare the SQL query
        $sql = "INSERT INTO users (first_name, last_name, email, password) 
                VALUES ('$first_name', '$last_name', '$email', '$hashed_password')";

        // Execute the query
        if ($conn->query($sql) === TRUE) {
            return $response->withStatus(200)->write("New user created successfully");
        } else {
            return $response->withStatus(500)->write("Error: " . $conn->error);

            $conn->close();
        }}catch(Exception $e){
            echo $e;
        }

    // Close the connection
    
});


$app->post('/newbook', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    global $servername, $username, $pass,$dbname;
    // Get the data from the request (from JSON body)
    $data = $request->getParsedBody();
    
    $cover = $data['cover'] ?? '';
    $title = $data['title'] ?? '';
    $author = $data['author'] ?? '';

    // Validate if all necessary fields are provided
    if (empty($cover) || empty($title) || empty($author)) {
        return $response->withStatus(400) // Bad Request
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Missing required fields: cover, title, or author'
                        ]));
    }

    // Create a new mysqli connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare the SQL query to insert the book
    $sql = "INSERT INTO books (cover, title, author) VALUES ('$cover', '$title', '$author')";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        // Retrieve the last inserted ID
        $last_id = $conn->insert_id;

        // Return a success response with the book information and ID as JSON
        return $response->withStatus(200) // OK
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'success',
                            'message' => 'Book added successfully',
                            'book' => [
                                'id' => $last_id, // Include the newly inserted ID
                                'cover' => $cover,
                                'title' => $title,
                                'author' => $author
                            ]
                        ]));
    } else {
        // Return an error response in case of failure
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Error: ' . $conn->error
                        ]));
    }

    // Close the connection
    $conn->close();
});

$app->post('/newreview', function ($request, $response, $args) use ($servername, $username, $password, $dbname) {
    // Get the data from the request (from JSON body)
    $data = $request->getParsedBody();
    
    $book_title = $data['book_title'] ?? '';
    $review = $data['review'] ?? '';
    $username = $data['username'] ?? '';  // New field for the user who wrote the review

    // Validate if all necessary fields are provided
    if (empty($book_title) || empty($review) || empty($username)) {
        return $response->withStatus(400) // Bad Request
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Missing required fields: book_title, review, or username'
                        ]));
    }

    // Create a new mysqli connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare the SQL query to insert the review
    $sql = "INSERT INTO reviews (book_title, review, username) VALUES ('$book_title', '$review', '$username')";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        // Retrieve the last inserted ID
        $last_id = $conn->insert_id;

        // Return a success response with the review information and ID as JSON
        return $response->withStatus(200) // OK
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'success',
                            'message' => 'Review added successfully',
                            'review' => [
                                'id' => $last_id, // Include the newly inserted ID
                                'book_title' => $book_title,
                                'review' => $review,
                                'username' => $username // Include the username
                            ]
                        ]));
    } else {
        // Return an error response in case of failure
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Error: ' . $conn->error
                        ]));
    }

    // Close the connection
    $conn->close();
});
$app->post('/neworder', function ($request, $response, $args) use ($servername, $username, $password, $dbname) {
    // Get POST data
    $data = $request->getParsedBody();
    $email = $data['email'] ?? null;
    $book_title = $data['book_title'] ?? null;
    $order_status = $data['order_status'] ?? null;




    
    // Check for missing data
    if (!$email || !$book_title || !$order_status) {
        // Return a 400 response with a JSON error message
        return $response->withJson(['error' => 'Missing required fields'], 400);
    }


    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }
    // Prepare SQL query to insert new order
    $sql = "INSERT INTO orders (email, book_title, order_status) VALUES (?, ?, ?)";
    
    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("sss", $email, $book_title, $order_status);
        
        // Execute the query
        if ($stmt->execute()) {
            // Success response (201 Created)
            return $response->withJson([
                'message' => 'Order created successfully',
                'order' => [
                    'email' => $email,
                    'book_title' => $book_title,
                    'order_status' => $order_status
                ]
            ], 201);
        } else {
            // Error in execution (500 Internal Server Error)
            return $response->withJson(['error' => 'Failed to create order'], 500);
        }

        // Close statement
        $stmt->close();
    } else {
        // Error in preparing statement (500 Internal Server Error)
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }
});

// function con(){
//     $servername = "localhost";  // MySQL server (localhost if using XAMPP)
//     $username = "root";         // MySQL username (default in XAMPP is root)
//     $password = "";             // MySQL password (default in XAMPP is empty)
//     $dbname = "webstore";    // Name of your database

//     // Create connection
//         try{    
//             $conn = new mysqli($servername, $username, $password, $dbname);
//         // Check connection
//         // Prepare the SQL query to insert data
//         $first_name = "John";
//         $last_name = "Doe";
//         $email = "john.doe@example.com";
//         $password = "securepassword"; 

//         $sql = "INSERT INTO users (first_name, last_name, email, password) 
//                 VALUES ('$first_name', '$last_name', '$email', '$password')";

//         if ($conn->query($sql) === TRUE) {
//             echo "New record created successfully";
//         } else {
//             // echo "Error: " . $sql . "<br>" . $conn->error;
//         }}catch(mysqli_sql_exception $e){
//             echo $e->getMessage();
//         }

//     // Close connection
//     $conn->close();
// }


$app->run();