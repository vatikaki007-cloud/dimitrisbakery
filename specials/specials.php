<?php
$page_slug      = 'specials';
$db_config_path = __DIR__ . '/../cms/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Current Specials & Promotions | Dimitri's Bakery | Cape Town</title>
  <meta name="description" content="Check out our current specials and promotions on cakes and catering services. Limited-time offers from Dimitri's Bakery in Cape Town.">
  <meta name="keywords" content="bakery specials Cape Town, cake promotions, catering deals, Dimitri's Bakery specials">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://dimitrisbakery.co.za/specials/specials.php">
  <meta property="og:title" content="Current Specials & Promotions | Dimitri's Bakery">
  <meta property="og:description" content="Exciting specials and promotions on our delicious cakes and catering services.">
  <meta property="og:image" content="https://dimitrisbakery.co.za/index_images/logo.png">

  <!-- Schema.org JSON-LD for Specials -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Dimitri's Bakery - Specials",
    "image": "https://dimitrisbakery.co.za/index_images/logo.png",
    "@id": "https://dimitrisbakery.co.za/specials/specials.php",
    "url": "https://dimitrisbakery.co.za/specials/specials.php",
    "telephone": "0798815410",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Parow",
      "addressLocality": "Cape Town",
      "addressRegion": "Western Cape",
      "postalCode": "7500",
      "addressCountry": "ZA"
    },
    "description": "Current specials and promotions on cakes and catering"
  }
  </script>

  <link rel="stylesheet" href="css/specials.css">
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
  <section class="specials-hero">
    <div class="specials-hero-overlay">
      <div class="conf-title-badge">
        <span class="conf-subtitle">Limited Time</span>
        <h1 class="conf-page-title">Current Specials</h1>
        <div class="conf-divider"></div>
        <p class="conf-desc">Check out our amazing promotions</p>
      </div>
    </div>
  </section>

  <!-- CONTENT SECTION -->
  <section class="container specials-content">
    <h2>Exclusive Offers from Dimitri's Bakery</h2>
    <p>Don't miss out on our <strong>current specials and limited-time promotions</strong>. From <strong>discounted cakes to special catering packages</strong>, we have amazing deals on our <strong>premium confectionery and catering services in Cape Town</strong>.</p>
    <p>Contact us today at <strong>079 981 5410</strong> or via <strong>WhatsApp</strong> to book your special offer before it's gone!</p>
  </section>

  <!-- SPECIALS GALLERY -->
  <section class="specials-gallery">
    <h2>Our Current Specials</h2>
    <div class="gallery-grid">
      <?php include __DIR__ . '/../cms/gallery_loader.php'; ?>
    </div>
    <div class="order-button-container" style="text-align:center;margin-top:40px;margin-bottom:20px;">
      <a href="https://wa.me/27799815410?text=I'm%20interested%20in%20your%20specials" target="_blank" class="btn-primary" style="display:inline-block;background-color:#007bff;color:white;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Inquire About Specials</a>
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
        <li><a href="../weekley_menu/weekly_menu.html">Weekly Menu's</a></li>
        <li><a href="../confectioner.html">Confectionery</a></li>
        <li><a href="../catering/catering.php">Catering</a></li>
        <li><a href="specials.php">Specials</a></li>
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
