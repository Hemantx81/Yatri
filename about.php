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
    <title>About Us - YATRI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .about-header {
            background-color: #001f3f;
            color: white;
            text-align: center;
            padding: 60px 20px;
        }

        .about-header h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .about-content {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .about-content p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #333;
        }

        .about-team {
            margin-top: 40px;
        }

        .about-team .team-member {
            text-align: center;
            margin-bottom: 30px;
        }

        .about-team .team-member img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
        }

        .about-team .team-member h5 {
            font-size: 1.2rem;
            margin-top: 10px;
        }

        .about-team .team-member p {
            font-size: 1rem;
            color: #777;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <?php include("includes/header.php") ?>
    <div style="margin-top: 65px;"></div> <!-- Spacer for fixed navbar -->

    <!-- About Header -->
    <div class="about-header">
        <h1>About YATRI</h1>
        <p>Discover hassle-free bus booking with YATRI – your trusted online bus reservation platform.</p>
    </div>

    <!-- About Content -->
    <div class="container my-5">
        <div class="about-content">
            <h3 class="mb-4">Our Mission</h3>
            <p>
                At YATRI, our mission is to revolutionize the way people book bus tickets in Nepal. With a focus on convenience, affordability, and reliability, we aim to make your travel planning experience simple and stress-free.
                Our platform allows you to search for buses, view real-time seat availability, and easily book tickets from the comfort of your home or mobile device.
                Whether you're commuting to work or going on a long journey, YATRI provides a seamless ticket booking system designed with the traveler in mind.
            </p>

            <h3 class="mt-5 mb-4">Our Vision</h3>
            <p>
                YATRI aspires to become the leading bus reservation system in Nepal, providing accessible and efficient travel solutions to every passenger.
                We envision a future where people no longer have to deal with the hassle of crowded bus stations or long queues. Our vision is to offer an easy-to-use platform that serves as a one-stop solution for all bus travel needs.
            </p>

            <h3 class="mt-5 mb-4">Our Team</h3>
            <div class="row about-team">
                <!-- Team Member 1 -->
                <div class="col-md-4 team-member">
                    <img src="assets/images/team-member1.jpg" alt="Team Member 1">
                    <h5>Hemant Raj Upadhaya</h5>
                    <!-- <p></p> -->
                </div>
                <!-- Team Member 2 -->
                <div class="col-md-4 team-member">
                    <img src="assets/images/team-member2.jpg" alt="Team Member 2">
                    <h5>Hemant Ale</h5>
                    <!-- <p></p> -->
                </div>
                <!-- Team Member 3 -->
                <!-- <div class="col-md-4 team-member">
                    <img src="assets/images/team-member3.jpg" alt="Team Member 3">
                    <h5></h5>
                    <p></p>
                </div> -->
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