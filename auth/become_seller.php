<?php
require_once '../includes/Art2CartConfig.php';

// Get base URL configuration
$baseHref = Art2CartConfig::getBaseUrl();
$baseUrl = Art2CartConfig::getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - Art2Cart</title>
    
    <!-- Base URL configuration -->
    <?php Art2CartConfig::echoBaseHref(); ?>
    
    <!-- Standard favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="static/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="static/images/favicon/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="96x96" href="static/images/favicon/favicon-96x96.png">
    
    <!-- ICO fallback for older browsers -->
    <link rel="shortcut icon" href="static/images/favicon/favicon.ico" type="image/x-icon">

    <!-- Apple Touch Icon (iOS/iPadOS) -->
    <link rel="apple-touch-icon" sizes="180x180" href="static/images/favicon/apple-touch-icon.png">

    <!-- Android/Chrome -->
    <link rel="icon" type="image/png" sizes="192x192" href="static/images/favicon/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="static/images/favicon/android-chrome-512x512.png">

    <!-- Web Manifest for PWA support -->
    <link rel="manifest" href="static/images/favicon/site.webmanifest">

    <!-- Optional theme color -->
    <meta name="theme-color" content="#ffffff">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            background-color: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        button {
            background-color: #ffd700;
            color: #000;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #ffc700;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .help-text {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #666;
        }

        input[type="file"] {
            padding: 6px 0;
        }

        input[type="file"]::-webkit-file-upload-button {
            background-color: #ffd700;
            color: #000;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }

        input[type="file"]::-webkit-file-upload-button:hover {
            background-color: #ffc700;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Become a Seller</h1>
        <p>Ready to start selling your digital art on Art2Cart?</p>
        <div id="upgradeAlert" class="alert"></div>
        
        <form id="sellerForm" onsubmit="upgradeToBeSeller(event)">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label for="experience">Years of Experience</label>
                <input type="number" id="experience" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="portfolio">Portfolio URL</label>
                <input type="url" id="portfolio" required placeholder="https://your-portfolio.com">
            </div>

            <div class="form-group">
                <label for="governmentId">Valid Government ID</label>
                <input type="file" id="governmentId" required accept="image/*,.pdf">
                <small class="help-text">Please upload a clear image/scan of your government-issued ID (Accepted formats: Images, PDF)</small>
            </div>

            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea id="bio" rows="4" required placeholder="Tell us about yourself and your art..."></textarea>
            </div>

            <button type="submit">Apply to Become a Seller</button>
        </form>
    </div>

    <script>
        // Pass PHP base URL to JavaScript
        window.baseHref = '<?php echo $baseHref; ?>';
        
        async function upgradeToBeSeller(event) {
            event.preventDefault();
            
            try {
                const governmentIdFile = document.getElementById('governmentId').files[0];
                if (!governmentIdFile) {
                    showAlert('Please upload a valid government ID', 'error');
                    return;
                }

                // Create FormData object to handle file upload
                const formData = new FormData();
                formData.append('name', document.getElementById('name').value.trim());
                formData.append('email', document.getElementById('email').value.trim());
                formData.append('experience', document.getElementById('experience').value);
                formData.append('portfolio', document.getElementById('portfolio').value.trim());
                formData.append('bio', document.getElementById('bio').value.trim());
                formData.append('governmentId', governmentIdFile);
                formData.append('application_date', new Date().toISOString());

                // Validate form data
                if (!formData.get('name') || !formData.get('email') || !formData.get('experience') || !formData.get('bio') || !formData.get('portfolio')) {
                    showAlert('Please fill in all required fields', 'error');
                    return;
                }

                // Use dynamic base URL for the fetch request
                const response = await fetch(window.baseHref + 'auth/upgrade_seller.php', {
                    method: 'POST',
                    body: formData // Send form data directly, don't set Content-Type header for multipart/form-data
                });

                const data = await response.json();

                if (response.ok) {
                    showAlert('Application submitted successfully! Please wait for admin approval.', 'success');
                    setTimeout(() => {
                        window.location.href = window.baseHref + 'account.php';
                    }, 2000);
                } else {
                    showAlert(data.error || 'An error occurred', 'error');
                }
            } catch (error) {
                showAlert('An error occurred. Please try again.', 'error');
            }
        }

        function showAlert(message, type) {
            const alert = document.getElementById('upgradeAlert');
            alert.textContent = message;
            alert.className = `alert alert-${type}`;
            alert.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 2000);
            }
        }
    </script>
</body>
</html>
