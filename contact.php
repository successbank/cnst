<?php
$currentPage = 'contact';
$pageTitle = 'Contact';
include 'head.php';

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $messageText = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    // Here you would normally process the form (send email, save to database, etc.)
    // For demo purposes, we'll just show a success message
    if ($name && $email && $messageText) {
        $message = '<div class="alert success">Thank you for your message! We will get back to you soon.</div>';
    } else {
        $message = '<div class="alert error">Please fill in all required fields.</div>';
    }
}
?>

<section class="content-section">
    <h2>Contact Us</h2>
    <p>Get in touch with our team. We're here to help!</p>
    
    <?php echo $message; ?>
    
    <div class="contact-wrapper">
        <div class="contact-info">
            <h3>Get in Touch</h3>
            <div class="info-item">
                <strong>Email:</strong>
                <p>support@project1.com</p>
            </div>
            <div class="info-item">
                <strong>Phone:</strong>
                <p>+1 (555) 123-4567</p>
            </div>
            <div class="info-item">
                <strong>Business Hours:</strong>
                <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                Saturday - Sunday: Closed</p>
            </div>
            <div class="info-item">
                <strong>Address:</strong>
                <p>123 Business Street<br>
                Suite 100<br>
                City, State 12345</p>
            </div>
        </div>
        
        <form id="contact-form" method="POST" action="contact.php" class="contact-form">
            <div class="form-group">
                <label for="name">Name: <span class="required">*</span></label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email: <span class="required">*</span></label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject">
            </div>
            
            <div class="form-group">
                <label for="message">Message: <span class="required">*</span></label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            
            <button type="submit">Send Message</button>
        </form>
    </div>
</section>

<?php include 'tail.php'; ?>