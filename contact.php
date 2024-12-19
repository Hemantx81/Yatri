<?php
// Start session to access user login state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - YATRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        /* body {
            background-color: #f8f9fa;
        } */

        .contact-header {
            background-color: #001f3f;
            color: white;
            text-align: center;
            padding: 60px 20px;
        }

        .contact-header h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .contact-form {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .contact-info {
            color: #001f3f;
        }

        .contact-info a {
            color: #004d99;
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .social-icons .social-icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            margin: 0 10px;
            border-radius: 50%;
            background-color: #004080;
            color: white;
            text-align: center;
            line-height: 40px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .social-icons .social-icon:hover {
            background-color: #cce7ff;
            color: #004080;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <?php include("includes/header.php") ?>
    <div style="margin-top: 65px;"></div> <!-- Spacer for fixed navbar -->

    <!-- Contact Header -->
    <div class="contact-header">
        <h1>Contact Us</h1>
        <p>We’re here to help! Reach out to us anytime.</p>
    </div>

    <!-- Contact Form and Info -->
    <div class="container my-5">
        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-6 mb-4">
                <h3 class="mb-4">Send Us a Message</h3>
                <form class="contact-form" action="contact_process.php" method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Your Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Your Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" placeholder="Enter your message" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit</button>
                </form>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-6 mb-4">
                <h3 class="mb-4">Contact Information</h3>
                <div class="contact-info">
                    <p><i class="fa-solid fa-envelope"></i> <a href="mailto:support@yatri.com">support@yatri.com</a></p>
                    <p><i class="fa-solid fa-phone"></i> +977-9801234567</p>
                    <p><i class="fa-solid fa-location-dot"></i> Nepalgunj, Nepal</p>
                    <div class="social-icons mt-3">
                        <h5>Follow Us</h5>
                        <a href="https://facebook.com" target="_blank" class="social-icon">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com" target="_blank" class="social-icon">
                            <i class="fa-brands fa-twitter"></i>
                        </a>
                        <a href="https://instagram.com" target="_blank" class="social-icon">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <a href="https://linkedin.com" target="_blank" class="social-icon">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background-color: #001f3f; color: white; padding: 20px; text-align: center;">
        <?php include("includes/footer.php") ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>