<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login</title>
  <link rel="icon" type="image/x-icon" href="https://cdn.worldvectorlogo.com/logos/bdo.svg" />
  <style>
    body {
      font-family: 'Trebuchet MS', sans-serif;
      background: url('https://img.freepik.com/free-vector/gradient-hexagonal-background_23-2148944164.jpg') no-repeat center center;
      background-size: cover;
      margin: 0;
      padding: 2rem;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .container {
      background: #fff;
      border-radius: 10px;
      padding: 2rem 2.5rem;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      max-width: 400px;
      width: 100%;
      text-align: center;
    }

/*    .logos {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .logos img {
      width: 100px;
      object-fit: center;
      margin-bottom: 1rem;
    }
*/
    h2 {
      color: #ED1A3B;
      margin-bottom: 1rem;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      position: relative;
    }

    input[type="email"],
    input[type="password"] {
      padding: 12px;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      transition: border-color 0.3s;
      width: 100%;
      box-sizing: border-box;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: #ED1A3B;
      outline: none;
    }

    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .password-wrapper input {
      flex: 1;
    }

    .toggle-password {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 0.9rem;
      color: #555;
      user-select: none;
    }

    button[type="submit"] {
      background-color: #ED1A3B;
      color: white;
      font-weight: bold;
      padding: 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.3s ease;
    }

    button[type="submit"]:hover {
      background-color: #c41632;
    }

    .error-message {
      color: red;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    @media (max-width: 480px) {
      .container {
        padding: 1.5rem 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logos">
      <img src="https://upload.wikimedia.org/wikipedia/commons/9/9e/BDO_Deutsche_Warentreuhand_Logo.svg" alt="BDO Logo" style="width: 120px; margin: 0 auto 2rem; display: block;" />
    </div>
    <h2>Admin Login</h2>

    <div id="errorContainer" class="error-message" style="display:none;"></div>

    <form action="login.php" method="POST" novalidate>
      <!-- CSRF token hidden input -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>" />

      <input type="email" name="email" placeholder="Email Address" required autocomplete="email" />
      
      <div class="password-wrapper">
        <input type="password" id="password" name="password" placeholder="Password" required autocomplete="current-password" />
        <button type="button" class="toggle-password" aria-label="Toggle password visibility">Show</button>
      </div>

      <button type="submit">Log In</button>
    </form>
  </div>

  <script>
    // Show error messages based on URL param ?error=
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const errorContainer = document.getElementById('errorContainer');

    if (error) {
      let message = '';
      switch (error) {
        case 'missing':
          message = 'Please enter both email and password.';
          break;
        case 'invalid':
          message = 'Invalid email or password.';
          break;
        case 'csrf':
          message = 'Invalid session. Please try again.';
          break;
        default:
          message = 'An unknown error occurred.';
      }
      errorContainer.textContent = message;
      errorContainer.style.display = 'block';
    }

    // Toggle password visibility
    const toggleBtn = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');

    toggleBtn.addEventListener('click', () => {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.textContent = 'Hide';
      } else {
        passwordInput.type = 'password';
        toggleBtn.textContent = 'Show';
      }
    });
  </script>
</body>
</html>
