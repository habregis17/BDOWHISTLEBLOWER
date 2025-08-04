<?php
$casenumber = $_GET['casenumber'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Thank You - Case Submitted</title>
    <link rel="icon" type="image/x-icon" href="https://cdn.worldvectorlogo.com/logos/bdo.svg">
    <style>
        body {
            font-family: 'Trebuchet MS', sans-serif;
            padding: 2rem;
            background: url('https://images.unsplash.com/photo-1511284281977-10b7b4377cfc?q=80&w=1174&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D') no-repeat center center;
            background-size: cover;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px #ccc;
        }

        .logos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .logos img {
            width: 200px;
            object-fit: contain;
        }

        h2 {
            color: #ED1A3B;
        }

        .message {
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .casenumber-box {
            background: #f2f2f2;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .form-box {
            margin-top: 2rem;
            background: #f7f7f7;
            padding: 1.5rem;
            border-radius: 6px;
        }

        .form-box label {
            display: block;
            margin-top: 1rem;
            font-weight: bold;
        }

        .form-box input {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .button {
            margin-top: 1.2rem;
            background-color: #ED1A3B;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
        }

        .button:hover {
            background-color: #c71330;
        }

        .note {
            font-size: 0.85rem;
            color: #555;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logos">
        <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/BDO_Deutsche_Warentreuhand_Logo.svg" alt="BDO Logo" />
        <img src="https://images.africanfinancials.com/rw-bok-logo-min.png" alt="Bank of Kigali Logo" />
    </div>

    <h2>Thank You for Your Report</h2>
    <p class="message">
        Your whistleblowing report has been successfully submitted. Below is your unique case number for reference:
    </p>

    <div class="casenumber-box">
        Case Number: <?= htmlspecialchars($casenumber) ?>
    </div>

    <div class="form-box">
        <h3>Would you like to receive a copy of your report?</h3>
        <form method="POST" action="Sendreceipt/">
            <label for="receipt_email">Enter your email address:</label>
            <input type="email" name="receipt_email" id="receipt_email" required />
            <input type="hidden" name="casenumber" value="<?= htmlspecialchars($casenumber) ?>" />

            <button type="submit" class="button">Send Receipt</button>
            <div class="note">The email will include your submitted information and this case number.</div>
        </form>
    </div>
</div>
</body>
</html>
