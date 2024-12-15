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
    $stuck =$data['stuck']??'';
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
    $sql = "INSERT INTO books (cover, title, author,stuck) VALUES ('$cover', '$title', '$author','$stuck')";

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
                                'stuck'=>$stuck,
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

$app->post('/newreview', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the data from the request (from JSON body)
    $data = $request->getParsedBody();
    
    $userid = $data['userid'] ?? '';  // User ID
    $bookid = $data['bookid'] ?? '';  // Book ID
    $orderid = $data['orderid'] ?? '';  // Order ID
    $review = $data['review'] ?? '';  // Review text
    $rating = $data['rating'] ?? '';  // Rating (assumed to be an integer, like 1 to 5)
    $like = $data['like'] ?? 0;  // Initial like count (defaults to 0)
    $dislike = $data['dislike'] ?? 0;  // Initial dislike count (defaults to 0)

    // Validate if all necessary fields are provided
    if (empty($userid) || empty($bookid) || empty($orderid) || empty($review) || empty($rating)) {
        return $response->withStatus(400) // Bad Request
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Missing required fields: userid, bookid, orderid, review, or rating'
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

    // Prepare the SQL query to insert the review with all the required columns
    $sql = "INSERT INTO reviews (userid, bookid, orderid, review, rating, `like`, `dislike`) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters to the prepared statement
        $stmt->bind_param("iiisiii", $userid, $bookid, $orderid, $review, $rating, $like, $dislike);

        // Execute the query
        if ($stmt->execute()) {
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
                                    'userid' => $userid,
                                    'bookid' => $bookid,
                                    'orderid' => $orderid,
                                    'review' => $review,
                                    'rating' => $rating,
                                    'like' => $like,
                                    'dislike' => $dislike
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
    } else {
        // Return an error response if preparing the statement failed
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Database query preparation failed'
                        ]));
    }

    // Close the connection
    $conn->close();
});
$app->get('/getreviewsbybookid', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'bookid' query parameter from the request
    $bookid = $request->getQueryParam('bookid', null);

    // Check if bookid is provided
    if (!$bookid) {
        return $response->withJson(['error' => 'Book ID is required'], 400); // Bad Request
    }

    // Create a new mysqli connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare the SQL query to select reviews based on the provided bookid
    $sql = "SELECT reviewid, userid, bookid, orderid, review, rating, created_at,`like`, `dislike` FROM reviews WHERE bookid = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the bookid parameter to the prepared statement
        $stmt->bind_param("i", $bookid);

        // Execute the query
        if ($stmt->execute()) {
            // Bind the result to variables
            $stmt->bind_result($id, $userid, $bookid, $orderid, $review, $rating, $created_at, $like, $dislike);

            // Fetch the results into an array
            $reviews = [];
            while ($stmt->fetch()) {
                $reviews[] = [
                    'id' => $id,
                    'userid' => $userid,
                    'bookid' => $bookid,
                    'orderid' => $orderid,
                    'review' => $review,
                    'rating' => $rating,
                    'created_at' => $created_at,
                    'like' => $like,
                    'dislike' => $dislike,
                ];
            }

            // Close the statement
            $stmt->close();

            // If there are reviews, return them as JSON
            if (count($reviews) > 0) {
                return $response->withJson($reviews);
            } else {
                // If no reviews are found, return a 404
                return $response->withJson(['error' => 'No reviews found for this book'], 404);
            }
        } else {
            // Error in executing the query
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the SQL query
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

$app->post('/neworder', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get POST data
    $data = $request->getParsedBody();
    $email = $data['email'] ?? null;
    $book_title = $data['book_title'] ?? null;
    $quantity = $data['quantity'] ?? null;
    $order_status = $data['order_status'] ?? null;




    
    // Check for missing data
    if (!$email || !$book_title || !$order_status) {
        // Return a 400 response with a JSON error message
        return $response->withJson(['error' => 'Missing required fields'], 400);
    }


    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }
    // Prepare SQL query to insert new order
    $sql = "INSERT INTO orders (email, book_title, quantity, order_status) VALUES (?, ?, ?, ?)";
    
    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("sss", $email, $book_title,$quantity, $order_status);
        
        // Execute the query
        if ($stmt->execute()) {
            // Success response (201 Created)
            return $response->withJson([
                'message' => 'Order created successfully',
                'order' => [
                    'email' => $email,
                    'book_title' => $book_title,
                    'quantity' => $quantity,
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

$app->get('/getusers', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Create the database connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to select all users
    $sql = "SELECT userid, first_name, last_name, email, password FROM users";

    // Execute the query
    if ($result = $conn->query($sql)) {
        // Fetch all users as an associative array
        $users = $result->fetch_all(MYSQLI_ASSOC);
        
        // Free result set
        $result->free();

        // Return the users as JSON (200 OK)
        return $response->withJson($users);
    } else {
        // Query failed (500 Internal Server Error)
        return $response->withJson(['error' => 'Failed to retrieve users'], 500);
    }

    // Close the connection
    $conn->close();
});
$app->get('/getuserbyemail', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'email' query parameter from the request
    $email = $request->getQueryParam('email', null);

    // Check if email is provided
    if (!$email) {
        return $response->withJson(['error' => 'Email is required'], 400); // Bad Request
    }

    // Create the database connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to select the user based on the provided email
    $sql = "SELECT userid, first_name, last_name, email, password FROM users WHERE email = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the email parameter to the prepared statement
        $stmt->bind_param("s", $email);

        // Execute the query
        if ($stmt->execute()) {
            // Bind the result to variables
            $stmt->bind_result($userid, $first_name, $last_name, $email, $password);

            // Fetch the result
            if ($stmt->fetch()) {
                // Return the user as JSON
                $user = [
                    'userid' => $userid,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                ];
                
                // Close the statement
                $stmt->close();

                // Return the user data as JSON (200 OK)
                return $response->withJson($user);
            } else {
                // No user found with the provided email (404 Not Found)
                return $response->withJson(['error' => 'User not found'], 404);
            }
        } else {
            // Error in executing query (500 Internal Server Error)
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the statement (500 Internal Server Error)
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});
$app->get('/getuserbyid', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'id' query parameter from the request
    $id = $request->getQueryParam('id', null);

    // Check if ID is provided
    if (!$id) {
        return $response->withJson(['error' => 'User ID is required'], 400); // Bad Request
    }

    // Create the database connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to select the user based on the provided ID
    $sql = "SELECT userid, first_name, last_name, email, password FROM users WHERE userid = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the ID parameter to the prepared statement
        $stmt->bind_param("i", $id);

        // Execute the query
        if ($stmt->execute()) {
            // Bind the result to variables
            $stmt->bind_result($userid, $first_name, $last_name, $email, $password);

            // Fetch the result
            if ($stmt->fetch()) {
                // Return the user as JSON
                $user = [
                    'userid' => $userid,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'password' => $password, // You might want to exclude password in a real-world scenario
                ];
                
                // Close the statement
                $stmt->close();

                // Return the user data as JSON (200 OK)
                return $response->withJson($user);
            } else {
                // No user found with the provided ID (404 Not Found)
                return $response->withJson(['error' => 'User not found'], 404);
            }
        } else {
            // Error in executing query (500 Internal Server Error)
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the statement (500 Internal Server Error)
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

$app->get('/getbooks', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Create the database connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to select all books
    $sql = "SELECT bookid, title, author, stuck, cover FROM books";

    // Execute the query
    if ($result = $conn->query($sql)) {
        // Fetch all books as an associative array
        $books = $result->fetch_all(MYSQLI_ASSOC);
        
        // Free result set
        $result->free();

        // Return the books as JSON (200 OK)
        return $response->withJson($books);
    } else {
        // Query failed (500 Internal Server Error)
        return $response->withJson(['error' => 'Failed to retrieve books'], 500);
    }

    // Close the connection
    $conn->close();
});
$app->get('/getbookbyid', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'bookid' query parameter from the request
    $bookid = $request->getQueryParam('bookid', null);

    // Check if bookid is provided
    if (!$bookid) {
        return $response->withJson(['error' => 'Book ID is required'], 400); // Bad Request
    }

    // Create the database connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to select the book based on the provided bookid
    $sql = "SELECT bookid, title, author, stuck, cover FROM books WHERE bookid = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the bookid parameter to the prepared statement
        $stmt->bind_param("i", $bookid);

        // Execute the query
        if ($stmt->execute()) {
            // Bind the result to variables
            $stmt->bind_result($bookid, $title, $author, $stuck, $cover);

            // Fetch the result
            if ($stmt->fetch()) {
                // Return the book as JSON
                $book = [
                    'bookid' => $bookid,
                    'title' => $title,
                    'author' => $author,
                    'stuck' => $stuck,
                    'cover' => $cover,
                ];

                // Close the statement
                $stmt->close();

                // Return the book data as JSON (200 OK)
                return $response->withJson($book);
            } else {
                // No book found with the provided bookid (404 Not Found)
                return $response->withJson(['error' => 'Book not found'], 404);
            }
        } else {
            // Error in executing query (500 Internal Server Error)
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the statement (500 Internal Server Error)
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

$app->put('/updatebookstuck', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'bookid' and 'stuck' from the request
    $bookid = $request->getParsedBodyParam('bookid', null);
    $stuck = $request->getParsedBodyParam('stuck', null);

    // Check if both bookid and stuck are provided
    if (!$bookid || !$stuck) {
        return $response->withJson(['error' => 'Book ID and stuck value are required'], 400); // Bad Request
    }

    // Validate 'stuck' to make sure it's a positive integer
    if (!is_numeric($stuck) || $stuck < 0) {
        return $response->withJson(['error' => 'The stuck value must be a non-negative number'], 400); // Bad Request
    }

    // Create the database connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to update the stuck field for the specific book by bookid
    $sql = "UPDATE books SET stuck = ? WHERE bookid = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters
        $stmt->bind_param("ii", $stuck, $bookid);

        // Execute the query
        if ($stmt->execute()) {
            // Check if any rows were affected (i.e., book exists and was updated)
            if ($stmt->affected_rows > 0) {
                // Return success response (200 OK)
                return $response->withJson(['message' => 'Book stuck updated successfully'], 200);
            } else {
                // If no rows were affected, it means no book was found with the given bookid
                return $response->withJson(['error' => 'No book found with the provided bookid'], 404); // Not Found
            }
        } else {
            // Error in executing the query (500 Internal Server Error)
            return $response->withJson(['error' => 'Failed to update book stuck'], 500);
        }

        // Close the statement
        $stmt->close();
    } else {
        // Error in preparing statement (500 Internal Server Error)
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

$app->get('/getorders', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {

    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to select all orders
    $sql = "SELECT orderid, email, book_title, quantity, order_status, ordered_at FROM orders";

    // Execute the query
    if ($result = $conn->query($sql)) {
        // Fetch all orders as an associative array
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        
        // Free result set
        $result->free();

        // Return the orders as JSON (200 OK)
        return $response->withJson($orders);
    } else {
        // Query failed (500 Internal Server Error)
        return $response->withJson(['error' => 'Failed to retrieve orders'], 500);
    }

    // Close the connection
    $conn->close();
});

// New route to get order by orderid
$app->get('/getorderbyid', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'orderid' query parameter from the request
    $orderid = $request->getQueryParam('orderid', null);

    // Check if orderid is provided
    if (!$orderid) {
        return $response->withJson(['error' => 'Order ID is required'], 400); // Bad Request
    }

    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to select the order based on the provided orderid
    $sql = "SELECT orderid, email, book_title, quantity, order_status, ordered_at FROM orders WHERE orderid = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the orderid parameter to the prepared statement
        $stmt->bind_param("i", $orderid);

        // Execute the query
        if ($stmt->execute()) {
            // Bind the result to variables
            $stmt->bind_result($orderid, $email, $book_title, $order_status, $ordered_at);

            // Fetch the result
            if ($stmt->fetch()) {
                // Return the order as JSON
                $order = [
                    'orderid' => $orderid,
                    'email' => $email,
                    'book_title' => $book_title,
                    'order_status' => $order_status,
                    'ordered_at' => $ordered_at,
                ];

                // Close the statement
                $stmt->close();

                // Return the order data as JSON (200 OK)
                return $response->withJson($order);
            } else {
                // No order found with the provided orderid (404 Not Found)
                return $response->withJson(['error' => 'Order not found'], 404);
            }
        } else {
            // Error in executing query (500 Internal Server Error)
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the statement (500 Internal Server Error)
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

// New route to get orders by email
$app->get('/getorderbyemail', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'email' query parameter from the request
    $email = $request->getQueryParam('email', null);

    // Check if email is provided
    if (!$email) {
        return $response->withJson(['error' => 'Email is required'], 400); // Bad Request
    }

    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to select orders based on the provided email
    $sql = "SELECT orderid, email, book_title, quantity, order_status, ordered_at FROM orders WHERE email = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the email parameter to the prepared statement
        $stmt->bind_param("s", $email);

        // Execute the query
        if ($stmt->execute()) {
            // Bind the result to variables
            $stmt->bind_result($orderid, $email, $book_title, $quantity, $order_status, $ordered_at);

            // Fetch the result
            $orders = [];
            while ($stmt->fetch()) {
                $orders[] = [
                    'orderid' => $orderid,
                    'email' => $email,
                    'book_title' => $book_title,
                    'quantity' =>$quantity,
                    'order_status' => $order_status,
                    'ordered_at' => $ordered_at,
                ];
            }

            // Close the statement
            $stmt->close();

            if (count($orders) > 0) {
                // Return the orders data as JSON (200 OK)
                return $response->withJson($orders);
            } else {
                // No orders found for the provided email (404 Not Found)
                return $response->withJson(['error' => 'No orders found for this email'], 404);
            }
        } else {
            // Error in executing query (500 Internal Server Error)
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the statement (500 Internal Server Error)
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

$app->put('/updateorderstatus', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'orderid' and 'order_status' from the request
    $orderid = $request->getParsedBodyParam('orderid', null);
    $order_status = $request->getParsedBodyParam('order_status', null); // true or false

    // Check if both orderid and order_status are provided
    if (!$orderid || !isset($order_status)) {
        return $response->withJson(['error' => 'Order ID and order status are required'], 400); // Bad Request
    }

    // Validate the order_status (it should be a boolean value)
    if (!is_bool($order_status)) {
        return $response->withJson(['error' => 'The order_status value must be a boolean (true or false)'], 400); // Bad Request
    }

    // Create the database connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to update the order status for the specific order by orderid
    $sql = "UPDATE orders SET order_status = ? WHERE orderid = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters
        $stmt->bind_param("ii", $order_status, $orderid);

        // Execute the query
        if ($stmt->execute()) {
            // Check if any rows were affected (i.e., order exists and was updated)
            if ($stmt->affected_rows > 0) {
                // Return success response (200 OK)
                return $response->withJson(['message' => 'Order status updated successfully'], 200);
            } else {
                // If no rows were affected, it means no order was found with the given orderid
                return $response->withJson(['error' => 'No order found with the provided orderid'], 404); // Not Found
            }
        } else {
            // Error in executing query (500 Internal Server Error)
            return $response->withJson(['error' => 'Failed to update order status'], 500);
        }

        // Close the statement
        $stmt->close();
    } else {
        // Error in preparing statement (500 Internal Server Error)
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

$app->post('/incrementlike', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'reviewid' query parameter from the request
    $reviewid = $request->getParsedBodyParam('reviewid', null);

    // Check if reviewid is provided
    if (!$reviewid) {
        return $response->withJson(['error' => 'Review ID is required'], 400); // Bad Request
    }

    // Create a new mysqli connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to update the like count by incrementing it by 1
    $sql = "UPDATE reviews SET `like` = `like` + 1 WHERE id = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the reviewid parameter to the prepared statement
        $stmt->bind_param("i", $reviewid);

        // Execute the query
        if ($stmt->execute()) {
            // Check if any rows were affected
            if ($stmt->affected_rows > 0) {
                // Return a success response (200 OK)
                return $response->withJson([
                    'status' => 'success',
                    'message' => 'Like incremented successfully',
                    'reviewid' => $reviewid
                ]);
            } else {
                // No rows were updated (review not found)
                return $response->withJson(['error' => 'Review not found'], 404);
            }
        } else {
            // Error in executing the query
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the SQL query
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

$app->post('/incrementdislike', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'reviewid' from the request body
    $reviewid = $request->getParsedBodyParam('reviewid', null);

    // Check if reviewid is provided
    if (!$reviewid) {
        return $response->withJson(['error' => 'Review ID is required'], 400); // Bad Request
    }

    // Create a new mysqli connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare SQL query to update the dislike count by incrementing it by 1
    $sql = "UPDATE reviews SET `dislike` = `dislike` + 1 WHERE id = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the reviewid parameter to the prepared statement
        $stmt->bind_param("i", $reviewid);

        // Execute the query
        if ($stmt->execute()) {
            // Check if any rows were affected
            if ($stmt->affected_rows > 0) {
                // Return a success response (200 OK)
                return $response->withJson([
                    'status' => 'success',
                    'message' => 'Dislike incremented successfully',
                    'reviewid' => $reviewid
                ]);
            } else {
                // No rows were updated (review not found)
                return $response->withJson(['error' => 'Review not found'], 404);
            }
        } else {
            // Error in executing the query
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the SQL query
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});

$app->delete('/deletebook', function ($request, $response, $args) use ($servername, $username, $pass, $dbname) {
    // Get the 'bookid' query parameter from the request
    $bookid = $request->getQueryParam('bookid', null);

    // Check if bookid is provided
    if (!$bookid) {
        return $response->withJson(['error' => 'Book ID is required'], 400); // Bad Request
    }

    // Create a new mysqli connection
    $conn = new mysqli($servername, $username, $pass, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        return $response->withStatus(500) // Internal Server Error
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }

    // Prepare the SQL query to delete the book with the provided bookid
    $sql = "DELETE FROM books WHERE bookid = ?";

    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the bookid parameter to the prepared statement
        $stmt->bind_param("i", $bookid);

        // Execute the query
        if ($stmt->execute()) {
            // Check if any rows were affected (i.e., the book was deleted)
            if ($stmt->affected_rows > 0) {
                // Return success response
                return $response->withJson(['status' => 'success', 'message' => 'Book deleted successfully']);
            } else {
                // If no rows were affected, it means the book doesn't exist
                return $response->withJson(['error' => 'Book not found'], 404); // Not Found
            }
        } else {
            // Error in executing the query
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        // Error in preparing the SQL query
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    // Close the connection
    $conn->close();
});


$app->run();