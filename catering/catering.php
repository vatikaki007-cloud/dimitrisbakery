<?php
$page_slug      = 'catering';
$db_config_path = __DIR__ . '/../cms/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Professional Catering Services | Dimitri's Bakery | Cape Town</title>
  <meta name="description" content="Turnkey catering services for every occasion. From private parties to corporate events, Dimitri's Bakery provides high-quality food and professional service across Cape Town.">
  <meta name="keywords" content="catering services Cape Town, event catering, corporate catering Parow, private party catering, turnkey catering">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://dimitrisbakery.co.za/catering/catering.php">
  <meta property="og:title" content="Turnkey Catering Services | Dimitri's Bakery">
  <meta property="og:description" content="Professional catering services for weddings, parties, and corporate events. Quality food you can trust.">
  <meta property="og:image" content="https://dimitrisbakery.co.za/index_images/logo.png">
  
  <!-- Schema.org JSON-LD for Catering -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Dimitri's Bakery - Catering Services",
    "image": "https://dimitrisbakery.co.za/index_images/logo.png",
    "@id": "https://dimitrisbakery.co.za/catering/catering.php",
    "url": "https://dimitrisbakery.co.za/catering/catering.php",
    "telephone": "0798815410",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Parow",
      "addressLocality": "Cape Town",
      "addressRegion": "Western Cape",
      "postalCode": "7500",
      "addressCountry": "ZA"
    },
    "priceRange": "R 500 - R 10000",
    "description": "Professional turnkey catering services for weddings, corporate events, and private parties in Cape Town"
  }
  </script>

  <link rel="stylesheet" href="css/catering.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&family=Source+Sans+3:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../index_images/logo.png">
</head>
<body>

<div id="global-nav"></div>
<script>
  fetch('/global_nav.html')
    .then(r => r.text())
    .then(html => {
      document.getElementById('global-nav').innerHTML = html;
      // After nav is loaded, check if specials exist and show link
      checkSpecialsLink();
    })
    .catch(err => console.error('Failed to load global nav:', err));

  function checkSpecialsLink() {
    fetch('/cms/check_specials.php')
      .then(r => r.json())
      .then(data => {
        console.log('Specials check:', data);
        if (data.hasSpecials) {
          const link = document.getElementById('specials-link');
          if (link) {
            link.style.display = 'inline';
          }
        }
      })
      .catch(err => console.error('Failed to check specials:', err));
  }
</script>

<main>
  <section class="catering-hero">
    <div class="catering-hero-overlay">
      <div class="conf-title-badge">
        <span class="conf-subtitle">Event Services</span>
        <h1 class="conf-page-title">Catering</h1>
        <div class="conf-divider"></div>
        <p class="conf-desc">Professional catering for every occasion</p>
      </div>
    </div>
  </section>

  <section class="container catering-content">
    <h2>Professional Catering Services in Cape Town</h2>
    <p>Make your event unforgettable with <strong>Dimitri's Catering in Parow, Cape Town</strong>. From <strong>gourmet platters to full-scale catering</strong>, we provide delicious, high-quality food for <strong>corporate events, private parties, and special celebrations</strong>.</p>
    <p>We offer <strong>customizable menus</strong> to suit your event's style and budget, ensuring every detail is covered. From <strong>finger foods and snack platters to full meals and desserts</strong>, our catering service takes the stress out of planning.</p>
    <p>📍 Serving <strong>Parow and surrounding Cape Town suburbs</strong>. Contact us today to discuss your <strong>catering requirements</strong>.</p>
  </section>

  <section class="catering-gallery">
    <h2>Catering Gallery</h2>
    <div class="gallery-grid">
      <?php include __DIR__ . '/../cms/gallery_loader.php'; ?>
    </div>
    <div class="order-button-container">
      <a href="https://wa.me/27799815410?text=I'm%20interested%20in%20your%20Catering%20Services" target="_blank" class="btn-primary">Place an Order</a>
    </div>
  </section>
</main>

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
        <li><a href="catering.php">Catering</a></li>
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

</body>
</html>
