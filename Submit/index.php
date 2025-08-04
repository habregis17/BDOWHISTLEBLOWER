<?php
require '../config/db.php';

$token = $_GET['token'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM clients WHERE token = ?");
$stmt->execute([$token]);
$client = $stmt->fetch();

if (!$client) die("Invalid client token.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($client['name']) ?> - Whistleblower</title>
  <link rel="icon" type="image/x-icon" href="https://cdn.worldvectorlogo.com/logos/bdo.svg">
  <style>
    body {
      font-family: 'Trebuchet Ms', sans-serif;
      background: url('https://images.unsplash.com/photo-1511284281977-10b7b4377cfc?q=80&w=1174&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D')  center center fixed;
  background-size: cover;
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-position: center center;
  background-color: #000; /* fallback in case image fails to load */
  margin: 0;
  padding: 0;
  min-height: 100vh;
      background-size: cover;
      margin: 0;
      padding: 2rem;
    }

    .container {
      max-width: 900px;
      margin: auto;
      background: #fff;
      border-radius: 10px;
      padding: 2rem 2.5rem;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .logos {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .logos img {
      width: 160px;
      object-fit: contain;
    }

    p{
      text-justify: justify-content;
    }

    h2 {
      color: #ED1A3B;
      margin-bottom: 0.5rem;
    }

    h3 {
      color: #009966;
      margin-top: 2rem;
    }

    p, li {
      font-size: 0.95rem;
      color: #333;
      line-height: 1.6;
    }

    ul {
      margin-top: 0.5rem;
      padding-left: 1.2rem;
    }

    form {
      margin-top: 2rem;
    }

    label {
      display: block;
      margin: 1rem 0 0.3rem;
      font-weight: 600;
      color: #444;
    }

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="date"],
    textarea {
      width: 100%;
      max-width: 500px;
      padding: 10px;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 5px;
      transition: border-color 0.2s;
      box-sizing: border-box;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="tel"]:focus,
    input[type="date"]:focus,
    textarea:focus {
      border-color: #ED1A3B;
      outline: none;
    }

    textarea {
      resize: horizontal;
      max-width: 900px;
    }

    .radio-group {
      display: flex;
      flex-direction: column; /* vertical radios */
      gap: 0.5rem;
      margin-top: 0.5rem;
    }

    .radio-group label {
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
    }

    .hidden {
      display: none;
    }

    .form-step {
      display: none;
    }

    .form-step.active {
      display: block;
    }

    .button-group {
      margin-top: 2rem;
      display: flex;
      justify-content: space-between;
    }

    button {
      padding: 10px 20px;
      font-size: 1rem;
      font-weight: 600;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      background-color: #ED1A3B;
      color: #fff;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #c41632;
    }
    #incident_date,
#incident_location {
  max-width: 900px;
  width: 100%;
}
.file-upload-wrapper {
  max-width: 700px;
  margin-top: 1rem;
}

input[type="file"] {
  display: none;
}

.custom-file-upload {
  background-color: #ED1A3B;
  color: #fff;
  padding: 10px 18px;
  border-radius: 6px;
  cursor: pointer;
  display: inline-block;
  font-size: 1rem;
  font-weight: 500;
  transition: background-color 0.3s;
}

.custom-file-upload:hover {
  background-color: #c71330;
}

#file-name-display {
  display: block;
  margin-top: 8px;
  font-size: 0.9rem;
  color: #555;
}

    footer {
      text-align: center;
      font-size: 0.85rem;
      margin-top: 3rem;
      color: #777;
    }

    @media (max-width: 600px) {
      .logos {
        flex-direction: column;
        gap: 1rem;
      }

      .button-group {
        flex-direction: column;
        gap: 1rem;
      }
    }

    /* Other guidelines container */
    .other-guidelines {
      display: block;
      margin-top: 1rem;
      font-size: 0.95rem;
      color: #333;
    }

    .other-guidelines.hidden {
      display: none;
    }
  </style>
  <script>
    function toggleIdentityFields(option) {
      const identityFields = document.getElementById('identityFields');
      if (option === 'reveal' || option === 'bdo') {
        identityFields.classList.remove('hidden');
      } else {
        identityFields.classList.add('hidden');
      }
    }

    function goToPreviousSection() {
      window.history.back();
    }

    function validateEmail() {
      const emailInput = document.getElementById("contact_email");
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!emailInput.value) {
        emailInput.style.borderColor = "#ccc";
        return;
      }

      if (!emailRegex.test(emailInput.value)) {
        emailInput.style.borderColor = "red";
      } else {
        emailInput.style.borderColor = "green";
      }
    }

    let currentStep = 1;

  function goToStep(step) {
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    document.getElementById(`step-${step}`).classList.add('active');
    currentStep = step;

    // Toggle other-guidelines visibility: show only on step 1
    const guidelines = document.querySelector('.other-guidelines');
    if (guidelines) {
      if (step === 1) {
        guidelines.classList.remove('hidden');
      } else {
        guidelines.classList.add('hidden');
      }
    }

    // Reset identity fields toggle in case step 1 is shown again
    if (step === 1) {
      const identityChoice = document.querySelector('input[name="identity_choice"]:checked');
      if (identityChoice) {
        toggleIdentityFields(identityChoice.value === "yes" ? "hide" : identityChoice.value);
      }
    }
  }

  window.addEventListener('DOMContentLoaded', () => {
    goToStep(1); // Show step 1 initially

    // Attach click event for "Next" button from Step 1
    const nextBtn = document.getElementById("next-to-step-2");
    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        const selected = document.querySelector('input[name="affiliation"]:checked');
        const errorMsg = document.getElementById("affiliation-error");

        if (!selected) {
          if (errorMsg) errorMsg.style.display = "block";
        } else {
          if (errorMsg) errorMsg.style.display = "none";
          goToStep(2);
        }
      });
    }
  });
  </script>
 <script>
  document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById("incident_evidence");
    const display = document.getElementById("file-name-display");

    fileInput.addEventListener("change", function () {
      const files = Array.from(fileInput.files);
      if (files.length > 0) {
        display.textContent = files.map(f => f.name).join(", ");
      } else {
        display.textContent = "No files selected";
      }
    });
  });
</script>

 <!-- Before next -->
<script>
function checkConsentBeforeNext() {
  const identityChoice = document.querySelector('input[name="identity_choice"]:checked');
  const consentCheckbox = document.querySelector('input[name="privacyconsent"]');

  if (identityChoice && (identityChoice.value === 'Identifiable' || identityChoice.value === 'Identifiable to BDO only')) {
    if (!consentCheckbox || !consentCheckbox.checked) {
      alert("Please confirm that you have read and consented to the Data Privacy Policy before proceeding.");
      return;
    }
  }

  goToStep(2);
}
</script>
</head>
<body>
  <div class="container">

    <!-- Logos -->
    <div class="logos">
      <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/BDO_Deutsche_Warentreuhand_Logo.svg" alt="BDO Logo" />
      <img src="https://images.africanfinancials.com/rw-bok-logo-min.png" alt="Bank of Kigali Plc Logo" />
    </div>
    <h2><?= htmlspecialchars($client['name']) ?> Whistleblowing Channel</h2>
  <!-- OTHER GUIDELINES (Only shown on Step 1) -->
    <div class="other-guidelines">
    <!-- Intro Text -->

    
    <h3>Reporting Guidelines</h3>
    <h3>Types of Concerns You Can Report</h3>
<p>The following list presents specific circumstances of potential wrongdoing, including the protection of those who choose to report an incident:</p>

<ul>
  <li>A criminal offence has been committed, is being committed, or is likely to be committed. Criminal offences could include suspicions of fraud, bribery, theft, money laundering, terrorism financing, and sanctions breaches.</li>
  <li>Any harm against any vulnerable person whom BK’s projects may come into contact with, such as children, beneficiaries, or adults at risk.</li>
  <li>Sexual exploitation, abuse, and harassment; physical or verbal abuse; exploitation; bullying; or intimidation of employees, partners, or contractors.</li>
  <li>Failure to comply with any legal obligation to which the individual is subject.</li>
  <li>Endangerment of the health and safety of any individual.</li>
  <li>Environmental damage or degradation that has occurred, is occurring, or is likely to occur.</li>
  <li>Any act that results or may result in the wastage of BK’s resources.</li>
  <li>Conduct likely to damage the financial well-being, reputation, or standing of BK.</li>
  <li>Serious breaches of BK policies, procedures, or Code of Conduct.</li>
  <li>A deliberate attempt to conceal any of the above.</li>
  <li>Other.</li>
</ul>

<p>If your case does not fall within any of the above categories, your case will be handled and governed by other guidelines than those described in BK's whistleblowing policy.</p>

<p>Please provide as detailed and accurate information as possible. If you would like those who investigate the report to be able to contact you, you are required to provide your name and contact details. This will allow them to keep you informed about the status of your case or to request additional information important for the investigation.</p>

<p>You may choose to hide your identity completely. Please be aware that this may make it more difficult to investigate your report.</p>

<p>You may also choose to remain anonymous towards BK but not to BDO. In such cases, BK will not be made aware of your identity, and all communication will be handled via BDO.</p>

    </div>

    <!-- FORM START -->
    <form id="whistleForm" action="submit_case.php" method="POST"enctype="multipart/form-data">
      <!-- Step 1 -->
      <div class="form-step active" id="step-1">
            <!-- Hidden token field -->
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label>Please specify whether you are:</label>
        <div class="radio-group">
          <label><input type="radio" name="affiliation" value="Employee" required> Employee</label>
          <label><input type="radio" name="affiliation" value="Supplier"> Supplier</label>
          <label><input type="radio" name="affiliation" value="Implementing Partner"> Implementing Partner</label>
          <label><input type="radio" name="affiliation" value="Other" checked> Other</label>
        </div>

        <label>Would you like to hide your identity?</label>
        <div class="radio-group">
          <label><input type="radio" name="identity_choice" value="Anonymous" onclick="toggleIdentityFields('hide')" checked> Yes</label>
          <label><input type="radio" name="identity_choice" value="Identifiable" onclick="toggleIdentityFields('reveal')"> No</label>
          <label><input type="radio" name="identity_choice" value="Identifiable to BDO only" onclick="toggleIdentityFields('bdo')"> Hide my information apart from BDO</label>
        </div>

        <div id="identityFields" class="hidden">
          <div>
            <h2>Data Protection &amp; Privacy Notice</h2>

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
<p>If we contrary to expectation should detect a security breach in our systems, or if we in any way can affirm that information is made public for unauthorized people, we will immediately inform you. We will as well, in compliance with the measures that are current in the Personal Data protection laws and regulations, warn supervisory authority about this within the deadlines that are relevant for the case.</p>

          </div>

          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname">

          <label for="department">Department</label>
          <input type="text" id="department" name="department">

          <label for="contact_email">Email Address</label>
          <input type="email" id="contact_email" name="contact_email" oninput="validateEmail()">

          <label for="phone">Telephone Number</label>
          <input type="tel" id="phone" name="phone">

           <label>
          <input type="checkbox" name="privacyconsent">I acknowledge that I have reviewed the summary of the <a href="privacy-policy.html" target="_blank">Data Privacy Policy</a> and related terms above, and I consent accordingly.
          </label>
        </div>

        <div class="button-group">
          <a href="../?token=<?= $client['token'] ?>"><button type="button">Previous</button></a>
          <button type="button" onclick="checkConsentBeforeNext()">Next</button>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="form-step" id="step-2">
        <h3>Information About the Incident</h3>
        <p>Please provide as detailed and accurate information as possible. Try to focus on concrete answers to the following questions: who was involved, what occurred, where did it occur, when did it occur, and how it happened. You are not required to prove your observations. However, it is important for us to be able to distinguish between factual observations and interpretations.</p>

         <p>Please complete the fields below and answer the questions as precisely as you can:</p>

        <label for="incident_description">When did the incident(s) take place? </label>
        <textarea id="incident_description" name="incident_when" rows="5" ></textarea>

        <label for="incident_description">Where did the incident(s) take place? </label>
        <textarea id="incident_description" name="incident_where" rows="5" ></textarea>

        <label for="incident_description">Which business division, department or site does your report concern? </label>
        <textarea id="incident_description" name="incident_division" rows="5" ></textarea>

        <label for="incident_description">Please describe in more detail the incident(s) you wish to report. </label>
        <textarea id="incident_description" name="incident_description" rows="5" ></textarea>

        <label for="incident_description">Attachments(Optional) </label>
        <div class="file-upload-wrapper">
  <label for="incident_evidence" class="custom-file-upload">Attach Files</label>
  <input type="file" id="incident_evidence" name="incident_evidence" multiple>
  <span id="file-name-display">No files selected</span>
</div>
        <div class="button-group">
          <button type="button" onclick="goToStep(1)">Previous</button>
          <button type="submit">Submit Report</button>
        </div>
      </div>
    </form>
    <div id="confirmation-message" style="display: none; max-width: 700px; margin-top: 2rem;">
  <p><strong>The information you have provided has been registered.</strong></p>
  <p>
    If you have not provided your name, address, email address or phone number, we will be unable to contact you.
    You will therefore also not be informed of the result or status of your notification.
  </p>
  <p>
    If you wish to receive a receipt of your responses, you may enter your email below:
  </p>
  <form id="email-receipt-form" style="margin-top: 1rem;">
    <label for="receipt_email">Your email address:</label>
    <input type="email" id="receipt_email" name="receipt_email" required style="width: 100%; padding: 8px; margin: 8px 0;">
    <button type="submit" class="submit-btn" style="background-color: #ED1A3B; color: white;">Request Receipt</button>
    <p id="receipt-status" style="margin-top: 1rem; color: green; display: none;">Receipt request received.</p>
  </form>
</div>

    <!-- FOOTER -->
    <footer>
      &copy; <?php echo date("Y"); ?> BDO East Africa (Rwanda) Ltd. All rights reserved.
    </footer>

  </div>

  <script>
    // Show the initial step and toggle "other guidelines"
    document.addEventListener('DOMContentLoaded', () => {
      goToStep(1);
    });
  </script>
</body>
</html>
