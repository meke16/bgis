<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        .contact-info {
            margin-top: 20px;
            font-size: 18px;
            color: #555;
        }

        .contact-info strong {
            font-weight: bold;
        }

        .contact-details {
            margin-top: 15px;
            line-height: 1.6;
        }

        .contact-details span {
            display: block;
            margin-bottom: 10px;
        }

        .contact-details .label {
            font-weight: bold;
            color: #333;
        }

        .contact-details .info {
            font-style: italic;
            color: #777;
        }

        form {
            margin-top: 30px;
        }

        textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: vertical;
        }

        button {
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #888;
        }

        .footer a {
            text-decoration: none;
            color: #007BFF;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .message {
            margin-top: 15px;
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contact Support</h1>

        <?php
        include("../connect.php");

        // Fetch current admin info
        $admin = $conn->query("SELECT * FROM admin LIMIT 1")->fetch_assoc();
        $contact = $admin['contact'];
        $name = $admin['name'];

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report'])) {
            $report = trim($_POST['report']);
            if (!empty($report)) {
                // Update admin table with report (you could use a separate table instead)
                $stmt = $conn->prepare("UPDATE admin SET report = ? WHERE id = ?");
                $stmt->bind_param("si", $report, $admin['id']);
                if ($stmt->execute()) {
                    echo "<p class='message'>Report submitted successfully.</p>";
                } else {
                    echo "<p class='message' style='color:red;'>Failed to submit report.</p>";
                }
                $stmt->close();
            } else {
                echo "<p class='message' style='color:red;'>Report cannot be empty.</p>";
            }
        }
        ?>


        <form method="post">
            <label for="report"><strong>Report your case below:</strong></label>
            <textarea name="report" id="report" required></textarea>
            <button type="submit">Submit Report</button>
        </form>

        <div class="footer">
            <p>For more information, visit <a href="#">our website</a>.</p>
        </div>
    </div>
</body>
</html>
