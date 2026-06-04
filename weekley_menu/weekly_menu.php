<?php
$page_slug      = 'weekly_menu';
$db_config_path = __DIR__ . '/../cms/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Weekly Menu | Home Cooked Meals | Dimitri's Bakery | Cape Town</title>
  <meta name="description" content="Tired of cooking? Check out our weekly menu of delicious, home-cooked meals. Dimitri's Bakery offers a variety of prepared meals to make your week easier in Cape Town.">
  <meta name="keywords" content="weekly menu, home cooked meals Cape Town, prepared meals Parow, Dimitri's Bakery menu, food delivery Cape Town">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://dimitrisbakery.co.za/weekley_menu/weekly_menu.php">
  <meta property="og:title" content="Weekly Home Cooked Meals | Dimitri's Bakery">
  <meta property="og:description" content="Delicious home-cooked meals prepared for you every week. Save time and eat well with Dimitri's Bakery.">
  <meta property="og:image" content="https://dimitrisbakery.co.za/index_images/logo.png">

  <!-- Schema.org JSON-LD for Weekly Menu -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Dimitri's Bakery - Weekly Menu",
    "image": "https://dimitrisbakery.co.za/index_images/logo.png",
    "@id": "https://dimitrisbakery.co.za/weekley_menu/weekly_menu.php",
    "url": "https://dimitrisbakery.co.za/weekley_menu/weekly_menu.php",
    "telephone": "0798815410",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Parow",
      "addressLocality": "Cape Town",
      "addressRegion": "Western Cape",
      "postalCode": "7500",
      "addressCountry": "ZA"
    },
    "description": "Weekly home-cooked meals menu with fresh, prepared dishes"
  }
  </script>

  <link rel="stylesheet" href="css/weekly_menu.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&family=Source+Sans+3:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../index_images/logo.png">
</head>
<body>

<div id="global-nav"></div>
<script>
  fetch('/global_nav.html')
    .then(r => r.text())
    .then(html => { document.getElementById('global-nav').innerHTML = html; })
    .catch(err => console.error('Failed to load global nav:', err));
</script>

<main>
  <!-- HERO SECTION -->
  <section class="menu-hero">
    <div class="menu-hero-overlay">
      <div class="conf-title-badge">
        <span class="conf-subtitle">Home Cooked</span>
        <h1 class="conf-page-title">Weekly Menu's</h1>
        <div class="conf-divider"></div>
        <p class="conf-desc">Fresh meals prepared for you every week</p>
      </div>
    </div>
  </section>

  <!-- CONTENT SECTION -->
  <section class="container menu-content">
    <h2>Weekly Home Cooked Meals in Cape Town</h2>
    <p>Stay delighted with <strong>Dimitri's Bakery Weekly Menus in Parow, Cape Town</strong>. Our <strong>menus change every week</strong>, offering a variety of freshly baked treats, desserts, platters, and sweet surprises. Perfect for <strong>office orders, family gatherings, or casual indulgence</strong>, our weekly selections provide something for everyone.</p>
    <p>Each week's menu features <strong>high-quality ingredients, seasonal flavors, and creative presentations</strong>, ensuring your treats are always fresh, delicious, and exciting. Follow our <strong>weekly updates</strong> to plan your orders and enjoy something new every week!</p>
    <p>📍 Serving all of <strong>Parow and surrounding areas</strong>, Dimitri's Bakery is committed to <strong>taste, quality, and customer satisfaction</strong>. Contact us today to request this week's menu and discover your next favorite treat!</p>
  </section>

  <!-- MENU GALLERY -->
  <section class="menu-gallery">
    <h2>This Week's Menu</h2>
    <div class="gallery-grid">
      <?php include __DIR__ . '/../cms/gallery_loader.php'; ?>
    </div>
    <div class="order-button-container" style="text-align:center;margin-top:40px;margin-bottom:20px;">
      <a href="https://wa.me/27799815410?text=I'd%20like%20to%20place%20an%20order%20from%20the%20weekly%20menu" target="_blank" class="btn-primary" style="display:inline-block;background-color:#007bff;color:white;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Place an Order</a>
    </div>
  </section>

  <!-- CATEGORY NAVIGATION GRID -->
  <section class="container conf-grid-section">
    <div class="conf-grid">
      <div class="conf-item">
        <img src="../confectioner_images/weddingcakes.jpg" alt="Wedding Cakes">
        <a href="../confectioner/wedding_cakes/wedding_cakes.php" class="btn-category">Wedding Cakes</a>
      </div>
      <div class="conf-item">
        <img src="../confectioner_images/birthdaycakes.jpg" alt="Birthday Cakes">
        <a href="../confectioner/birthday_cakes/birthday_cakes.php" class="btn-category">Birthday Cakes</a>
      </div>
      <div class="conf-item">
        <img src="../confectioner_images/anyoccasioncakes.jpg" alt="Any Occasion Cakes">
        <a href="../confectioner/any_occasion/any_occasion.php" class="btn-category">Any Occasion Cakes</a>
      </div>
      <div class="conf-item">
        <img src="../confectioner_images/sweettreats.jpg" alt="Sweet Treats">
        <a href="../confectioner/sweet_treats/sweet_treats.php" class="btn-category">Sweet Treats</a>
      </div>
    </div>
  </section>
</main>

<!-- Footer -->
<footer class="main-footer">
  <div class="container footer-grid">
    <div class="footer-col">
      <img src="../index_images/logo.png" alt="Dimitri's Bakery" class="footer-logo">
      <p>Quality confectionery and turnkey catering services for every occasion. Family owned and run since the beginning.</p>
      <div class="footer-socials">
        <a href="https://www.facebook.com/dimitrisbakery" target="_blank">Facebook</a>
        <a href="https://wa.me/27799815410" target="_blank">WhatsApp</a>
        <a href="#">Instagram</a>
      </div>
    </div>
    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="../index.html">Home</a></li>
        <li><a href="weekly_menu.php">Weekly Menu's</a></li>
        <li><a href="../confectioner.html">Confectionery</a></li>
        <li><a href="../catering/catering.php">Catering</a></li>
        <li><a href="../specials/specials.php">Specials</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Get In Touch</h4>
      <p><strong>Phone:</strong> 079 981 5410</p>
      <p><strong>Email:</strong> info@dimitrisbakery.co.za</p>
      <p><strong>Location:</strong> Cape Town, South Africa</p>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container footer-bottom-flex">
      <p>Copyright &copy; 2026 | Dimitri's Bakery</p>
      <div class="legal-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>

<!-- Image Modal for Fullscreen View -->
<div id="imageModal" class="modal">
  <span class="close">&times;</span>
  <img class="modal-content" id="modalImage">
  <div id="caption" style="display:none;"></div>
</div>

<script>
// Modal functionality for image enlargement
var modal = document.getElementById("imageModal");
var modalImg = document.getElementById("modalImage");
var captionText = document.getElementById("caption");

// Get all gallery images
var galleryImages = document.querySelectorAll('.gallery-grid img');

galleryImages.forEach(function(img) {
  img.onclick = function() {
    modal.style.display = "block";
    modalImg.src = this.src;
    captionText.textContent = this.alt;
  };
});

// Close modal when X is clicked
var span = document.getElementsByClassName("close")[0];
span.onclick = function() {
  modal.style.display = "none";
};

// Close modal when clicking outside the image
modal.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
};
</script>

</body>
</html>
