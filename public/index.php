<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';



$app = new \Slim\App();

$servername = "localhost";  
$username = "root";         
$pass = "";             
$dbname = "webstore";  
try{
    $conn = new mysqli($servername, $username, $pass, $dbname);

    if ($conn->connect_error) {
        return $response->withStatus(500) 
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
    }
    }catch(Exception $e){
        echo $e;
}

$app->post('/newuser', function ($request, $response, $args) use ($conn){
    
    $data = $request->getParsedBody();
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);


    $sql = "INSERT INTO users (first_name, last_name, email, password) 
            VALUES ('$first_name', '$last_name', '$email', '$hashed_password')";


    if ($conn->query($sql) === TRUE) {
        return $response->withStatus(200)->write("New user created successfully");
    } else {
        return $response->withStatus(500)->write("Error: " . $conn->error);

        $conn->close();
    }

    
});


$app->post('/newbook', function ($request, $response, $args) use ($conn) {

    $data = $request->getParsedBody();
    
    $cover = $data['cover'] ?? '';
    $title = $data['title'] ?? '';
    $stuck =$data['stuck']??'';
    $author = $data['author'] ?? '';

    if (empty($cover) || empty($title) || empty($author)) {
        return $response->withStatus(400) 
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Missing required fields: cover, title, or author'
                        ]));
    }


    $sql = "INSERT INTO books (cover, title, author,stuck) VALUES ('$cover', '$title', '$author','$stuck')";

    if ($conn->query($sql) === TRUE) {
        $last_id = $conn->insert_id;

        return $response->withStatus(200) 
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'success',
                            'message' => 'Book added successfully',
                            'book' => [
                                'id' => $last_id, 
                                'cover' => $cover,
                                'title' => $title,
                                'stuck'=>$stuck,
                                'author' => $author
                            ]
                        ]));
    } else {
        return $response->withStatus(500) 
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Error: ' . $conn->error
                        ]));
    }

    $conn->close();
});

$app->post('/newreview', function ($request, $response, $args) use ($conn) {
    $data = $request->getParsedBody();
    
    $userid = $data['userid'] ?? '';  // User ID
    $bookid = $data['bookid'] ?? '';  // Book ID
    $orderid = $data['orderid'] ?? '';  // Order ID
    $review = $data['review'] ?? '';  
    $rating = $data['rating'] ?? '';  
    $like = $data['like'] ?? 0;  
    $dislike = $data['dislike'] ?? 0;  

    if (empty($userid) || empty($bookid) || empty($orderid) || empty($review) || empty($rating)) {
        return $response->withStatus(400) 
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Missing required fields: userid, bookid, orderid, review, or rating'
                        ]));
    }

    $sql = "INSERT INTO reviews (userid, bookid, orderid, review, rating, `like`, `dislike`) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iiisiii", $userid, $bookid, $orderid, $review, $rating, $like, $dislike);

        if ($stmt->execute()) {
            $last_id = $conn->insert_id;

            return $response->withStatus(200) 
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode([
                                'status' => 'success',
                                'message' => 'Review added successfully',
                                'review' => [
                                    'id' => $last_id, 
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
            return $response->withStatus(500) 
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode([
                                'status' => 'error',
                                'message' => 'Error: ' . $conn->error
                            ]));
        }
    } else {
        
        return $response->withStatus(500) 
                        ->withHeader('Content-Type', 'application/json')
                        ->write(json_encode([
                            'status' => 'error',
                            'message' => 'Database query preparation failed'
                        ]));
    }

    $conn->close();
});
$app->get('/getreviewsbybookid', function ($request, $response, $args) use ($conn) {
    
    $bookid = $request->getQueryParam('bookid', null);

    
    if (!$bookid) {
        return $response->withJson(['error' => 'Book ID is required'], 400); 
    }




    
    $sql = "SELECT reviewid, userid, bookid, orderid, review, rating, created_at,`like`, `dislike` FROM reviews WHERE bookid = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("i", $bookid);

        
        if ($stmt->execute()) {
            
            $stmt->bind_result($id, $userid, $bookid, $orderid, $review, $rating, $created_at, $like, $dislike);

            
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

            
            $stmt->close();

            
            if (count($reviews) > 0) {
                return $response->withJson($reviews);
            } else {
                
                return $response->withJson(['error' => 'No reviews found for this book'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->post('/neworder', function ($request, $response, $args) use ($conn) {
    $data = $request->getParsedBody();
    $email = $data['email'] ?? null;
    $book_title = $data['book_title'] ?? null;
    $quantity = $data['quantity'] ?? null;
    $order_status = $data['order_status'] ?? null;




    
    
    if (!$email || !$book_title || !$order_status) {
        
        return $response->withJson(['error' => 'Missing required fields'], 400);
    }


    
    $sql = "INSERT INTO orders (email, book_title, quantity, order_status) VALUES (?, ?, ?, ?)";
    
    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("sss", $email, $book_title,$quantity, $order_status);
        
        
        if ($stmt->execute()) {
            
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
            
            return $response->withJson(['error' => 'Failed to create order'], 500);
        }

        
        $stmt->close();
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }
    $conn->close();
});

$app->get('/getusers', function ($request, $response, $args) use ($conn) {

    
    $sql = "SELECT userid, first_name, last_name, email, password FROM users";

    
    if ($result = $conn->query($sql)) {
        
        $users = $result->fetch_all(MYSQLI_ASSOC);
        
        
        $result->free();

        
        return $response->withJson($users);
    } else {
        
        return $response->withJson(['error' => 'Failed to retrieve users'], 500);
    }

    
    $conn->close();
});
$app->get('/getuserbyemail', function ($request, $response, $args) use ($conn) {
    
    $email = $request->getQueryParam('email', null);

    
    if (!$email) {
        return $response->withJson(['error' => 'Email is required'], 400); 
    }


    
    $sql = "SELECT userid, first_name, last_name, email, password FROM users WHERE email = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("s", $email);

        
        if ($stmt->execute()) {
            
            $stmt->bind_result($userid, $first_name, $last_name, $email, $password);

            
            if ($stmt->fetch()) {
                
                $user = [
                    'userid' => $userid,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                ];
                
                
                $stmt->close();

                
                return $response->withJson($user);
            } else {
                
                return $response->withJson(['error' => 'User not found'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});
$app->get('/getuserbyid', function ($request, $response, $args) use ($conn) {
    
    $id = $request->getQueryParam('id', null);

    
    if (!$id) {
        return $response->withJson(['error' => 'User ID is required'], 400); 
    }

    
    $sql = "SELECT userid, first_name, last_name, email, password FROM users WHERE userid = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("i", $id);

        
        if ($stmt->execute()) {
            
            $stmt->bind_result($userid, $first_name, $last_name, $email);

            
            if ($stmt->fetch()) {
                
                $user = [
                    'userid' => $userid,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                ];
                
                
                $stmt->close();

                
                return $response->withJson($user);
            } else {
                
                return $response->withJson(['error' => 'User not found'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->get('/getbooks', function ($request, $response, $args) use ($conn) {

    
    $sql = "SELECT bookid, title, author, stuck, cover FROM books";

    
    if ($result = $conn->query($sql)) {
        
        $books = $result->fetch_all(MYSQLI_ASSOC);
        
        
        $result->free();

        
        return $response->withJson($books);
    } else {
        
        return $response->withJson(['error' => 'Failed to retrieve books'], 500);
    }

    
    $conn->close();
});
$app->get('/getbookbyid', function ($request, $response, $args) use ($conn) {
    
    $bookid = $request->getQueryParam('bookid', null);

    
    if (!$bookid) {
        return $response->withJson(['error' => 'Book ID is required'], 400); 
    }



    
    $sql = "SELECT bookid, title, author, stuck, cover FROM books WHERE bookid = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("i", $bookid);

        
        if ($stmt->execute()) {
            
            $stmt->bind_result($bookid, $title, $author, $stuck, $cover);

            
            if ($stmt->fetch()) {
                
                $book = [
                    'bookid' => $bookid,
                    'title' => $title,
                    'author' => $author,
                    'stuck' => $stuck,
                    'cover' => $cover,
                ];

                
                $stmt->close();

                return $response->withJson($book);
            } else {
                return $response->withJson(['error' => 'Book not found'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->put('/updatebookstuck', function ($request, $response, $args) use ($conn) {
    $bookid = $request->getParsedBodyParam('bookid', null);
    $stuck = $request->getParsedBodyParam('stuck', null);

    if (!$bookid || !$stuck) {
        return $response->withJson(['error' => 'Book ID and stuck value are required'], 400); 
    }

    if (!is_numeric($stuck) || $stuck < 0) {
        return $response->withJson(['error' => 'The stuck value must be a non-negative number'], 400); 
    }

    $sql = "UPDATE books SET stuck = ? WHERE bookid = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("ii", $stuck, $bookid);

        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return $response->withJson(['message' => 'Book stuck updated successfully'], 200);
            } else {
                return $response->withJson(['error' => 'No book found with the provided bookid'], 404); 
            }
        } else {
            return $response->withJson(['error' => 'Failed to update book stuck'], 500);
        }

        
        $stmt->close();
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->get('/getorders', function ($request, $response, $args) use ($conn) {

   
    $sql = "SELECT orderid, email, book_title, quantity, order_status, ordered_at FROM orders";

    
    if ($result = $conn->query($sql)) {
        $orders = $result->fetch_all(MYSQLI_ASSOC);
        
        
        $result->free();

        return $response->withJson($orders);
    } else {
        
        return $response->withJson(['error' => 'Failed to retrieve orders'], 500);
    }

    
    $conn->close();
});


$app->get('/getorderbyid', function ($request, $response, $args) use ($conn) {
    
    $orderid = $request->getQueryParam('orderid', null);

    if (!$orderid) {
        return $response->withJson(['error' => 'Order ID is required'], 400); 
    }

    $sql = "SELECT orderid, email, book_title, quantity, order_status, ordered_at FROM orders WHERE orderid = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $orderid);

        
        if ($stmt->execute()) {
            
            $stmt->bind_result($orderid, $email, $book_title, $order_status, $ordered_at);

            
            if ($stmt->fetch()) {
                $order = [
                    'orderid' => $orderid,
                    'email' => $email,
                    'book_title' => $book_title,
                    'order_status' => $order_status,
                    'ordered_at' => $ordered_at,
                ];

                
                $stmt->close();

                return $response->withJson($order);
            } else {
                return $response->withJson(['error' => 'Order not found'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->get('/getorderbyemail', function ($request, $response, $args) use ($conn) {
    
    $email = $request->getQueryParam('email', null);

    
    if (!$email) {
        return $response->withJson(['error' => 'Email is required'], 400); 
    }

    $sql = "SELECT orderid, email, book_title, quantity, order_status, ordered_at FROM orders WHERE email = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("s", $email);

        
        if ($stmt->execute()) {
            
            $stmt->bind_result($orderid, $email, $book_title, $quantity, $order_status, $ordered_at);

            
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

            
            $stmt->close();

            if (count($orders) > 0) {
                return $response->withJson($orders);
            } else {
                return $response->withJson(['error' => 'No orders found for this email'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->put('/updateorderstatus', function ($request, $response, $args) use ($conn) {
    $orderid = $request->getParsedBodyParam('orderid', null);
    $order_status = $request->getParsedBodyParam('order_status', null); 

    if (!$orderid || !isset($order_status)) {
        return $response->withJson(['error' => 'Order ID and order status are required'], 400); 
    }

    if (!is_bool($order_status)) {
        return $response->withJson(['error' => 'The order_status value must be a boolean (true or false)'], 400); 
    }



    $sql = "UPDATE orders SET order_status = ? WHERE orderid = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("ii", $order_status, $orderid);

        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return $response->withJson(['message' => 'Order status updated successfully'], 200);
            } else {
                return $response->withJson(['error' => 'No order found with the provided orderid'], 404); 
            }
        } else {
            
            return $response->withJson(['error' => 'Failed to update order status'], 500);
        }

        
        $stmt->close();
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->post('/incrementlike', function ($request, $response, $args) use ($conn) {
    $reviewid = $request->getParsedBodyParam('reviewid', null);

    if (!$reviewid) {
        return $response->withJson(['error' => 'Review ID is required'], 400); 
    }


    $sql = "UPDATE reviews SET `like` = `like` + 1 WHERE id = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $reviewid);

        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return $response->withJson([
                    'status' => 'success',
                    'message' => 'Like incremented successfully',
                    'reviewid' => $reviewid
                ]);
            } else {
                return $response->withJson(['error' => 'Review not found'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->post('/incrementdislike', function ($request, $response, $args) use ($conn) {
    $reviewid = $request->getParsedBodyParam('reviewid', null);

    if (!$reviewid) {
        return $response->withJson(['error' => 'Review ID is required'], 400); 
    }



    $sql = "UPDATE reviews SET `dislike` = `dislike` + 1 WHERE id = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $reviewid);

        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return $response->withJson([
                    'status' => 'success',
                    'message' => 'Dislike incremented successfully',
                    'reviewid' => $reviewid
                ]);
            } else {
                return $response->withJson(['error' => 'Review not found'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});

$app->delete('/deletebook', function ($request, $response, $args) use ($conn) {
    
    $bookid = $request->getQueryParam('bookid', null);

    
    if (!$bookid) {
        return $response->withJson(['error' => 'Book ID is required'], 400); 
    }



    $sql = "DELETE FROM books WHERE bookid = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        
        $stmt->bind_param("i", $bookid);

        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return $response->withJson(['status' => 'success', 'message' => 'Book deleted successfully']);
            } else {
                return $response->withJson(['error' => 'Book not found'], 404);
            }
        } else {
            
            return $response->withJson(['error' => 'Query execution failed'], 500);
        }
    } else {
        
        return $response->withJson(['error' => 'Database query preparation failed'], 500);
    }

    
    $conn->close();
});


$app->run();