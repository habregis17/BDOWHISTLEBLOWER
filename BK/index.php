<?php
// Check if consent was submitted
if (isset($_GET['consent'])) {
    if ($_GET['consent'] === 'yes') {
        require 'config/db.php';

        $tokenid = '077e3ee0dc6c06f0fbb998b77c547f40';

        // Retrieve token from the query string
        $token = $tokenid ?? '';

        $stmt = $pdo->prepare("SELECT * FROM clients WHERE token = ?");
        $stmt->execute([$token]);
        $client = $stmt->fetch();

        if (!$client) {
            die("Invalid client token.");
        }

        // Redirect to the submit form
        header('Location: Submit/?token=' . urlencode($token));
        exit;
    } elseif ($_GET['consent'] === 'no') {
        header('Location: Thankyou/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Welcome - BK WhistleBlower</title>
      <link rel="icon" type="image/x-icon" href="https://cdn.worldvectorlogo.com/logos/bdo.svg">
    <style>
        body { font-family: 'Trebuchet Ms', sans-serif; padding: 2rem;   background: url('https://images.unsplash.com/photo-1511284281977-10b7b4377cfc?q=80&w=1174&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D')  center center fixed;
  background-size: cover;
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-position: center center;
  background-color: #000; /* fallback in case image fails to load */
  margin: 0;
  padding: 0;
  min-height: 100vh; }
        .container { max-width: 900px; margin: auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        
        /* Logo container */
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

        P{
              text-align: justify;
        }

        h2 { 
            margin-top: 0; 
            color: #ED1A3B;
        }
        h3{
            color: #009966;
        }

        button { 
            padding: 0.6rem 1.2rem; 
            font-size: 1rem; 
            margin: 0.5rem; 
            cursor: pointer; 
            border-radius: 4px; 
            border: none; 
            font-family: 'Trebuchet Ms';
        }
        .button-group {
            display: flex;
            justify-content: center; /* Center horizontally */
            gap: 1rem; /* Space between buttons */
            margin-top: 1.5rem;
}
        .yes-btn { background-color: #009966; color: white; }
        .no-btn { background-color: #333333; color: white; }
    </style>
</head>
<body>
<div class="container">

    <div class="logos">
        <!-- Your Logo -->
        <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/BDO_Deutsche_Warentreuhand_Logo.svg" alt="BDO OFFICIAL Logo" />

        <!-- Client Logo -->
        <img src="https://images.africanfinancials.com/rw-bok-logo-min.png" alt="Client Logo"  />
    </div>

        <h2>Welcome</h2>

    <!-- Privacy Policy Block -->
    <div class="privacy">
<p>Welcome to the external whistleblowing channel for Bank of Kigali. This system is independently operated and monitored by BDO East Africa. This whistleblowing channel is open to all employees of BK and all BK’s associated parties, including members of the Board, implementing partners, vendors, contractors and any other third party. Reports regarding instances of objectionable conditions, whether ethical or legal, can be made via this channel in connection with BK and any of its business partners.</p>



<!-- <h2>Data Protection &amp; Privacy Notice</h2>

<h3>About This Privacy Policy</h3>
<p>By using the Service you acknowledge that you give us access to information that may identify you as a person, and the company you may represent.</p>

<p>This privacy policy includes compliance to our handling and routines surrounding this information. It also includes a closer look at the information we collect, how they are treated and what their purpose they serve in the context.</p>

<p>We care about your personal details and other information you give us access to. They will be handled in compliance with all policies regarding how personal information must be treated. You can be sure that information provided will be properly handled.</p>

<h3>What Kind of Information Is Collected and Who Has Access?</h3>
<p>We store the information that you have given us through filling out the form in using the Service. The form contains both sensitive information and other information. The personal information we collect and store is:</p>

<ul>
  <li>Name</li>
  <li>Email address</li>
  <li>Telephone number</li>
</ul>

<p>The form contains questions that would not be considered personal information, but this information is treated along the same lines as the rest of the information we collect.</p>

<p>If you choose to give additional information in the form of a comment or other input fields in the form this information will also get collected and processed by us.</p>

<p>Your personal information will only be accessible to employees who need access to the information to ensure that The Service can be provided. This would typically be IT administrators and our consulting experts. Everyone who is given access to your personal information will have professional confidentiality. The information can only be used as long as it is necessary to offer The Service. The information is stored in Ireland. We will not transfer your information to other countries.</p>

<h3>Purpose of Processing of Personal Data</h3>
<p>All information we collect is done so in order for us to enable us to give you access to The Service, and to be able create analyses and reports based on your input. The information is also used to create statistics that enable us to improve The Service. The data used for this purpose will be anonymous and cannot be tracked back to any single user.</p>

<p>Information will only be shared if a special agreement has been established or if it is imposed through law or legal obligations (enforceable law etc.) The information will be stored with us for a period of up to 12 months and will have the possibility to be available for our advisors that is responsible for the field of study. The information will however only be available and be utilized if you have accepted that one of our advisors can contact you for a follow-up after usage of the service.</p>

<h3>Insight in the Process – Removal/Extradition of Information</h3>
<p>You have at any time the right to know what information we have stored about you and how this information is being used. You can at any time contact us at <a href="mailto:rwanda@bdo-ea.com">rwanda@bdo-ea.com</a>. You can as well ask us to remove all information bound to your usage of the service and return that information to you. Redelivery of information will happen in the format that we have stored the information in.</p>

<h3>Security Breaches</h3>
<p>If we contrary to expectation should detect a security breach in our systems, or if we in any way can affirm that information is made public for unauthorized people, we will immediately inform you. We will as well, in compliance with the measures that are current in the Personal Data protection laws and regulations, warn supervisory authority about this within the deadlines that are relevant for the case.</p> -->

    </div>

    <!-- Proper form structure with hidden token -->
    <form method="get" action="">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>" />
        <div class="button-group">
            <button type="submit" name="consent" value="yes" class="yes-btn">Continue</button>
            <!-- <button type="submit" name="consent" value="no" class="no-btn">No, Thank You</button> -->
        </div>
    </form>
</div>
</body>
</html>
