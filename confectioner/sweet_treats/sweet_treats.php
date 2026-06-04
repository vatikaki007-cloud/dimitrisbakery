<?php
$page_slug      = 'sweet_treats';
$db_config_path = __DIR__ . '/../../cms/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sweet Treats &amp; Confectionery | Dimitri's Bakery | Cape Town</title>
  <meta name="description" content="Delicious sweet treats and confectionery from Dimitri's Bakery. From biscuits to traditional desserts, enjoy our high-quality sweets in Cape Town.">
  <meta name="keywords" content="sweet treats Cape Town, confectionery, artisan biscuits, Dimitri's Bakery sweets">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://dimitrisbakery.co.za/confectioner/sweet_treats/sweet_treats.php">
  <meta property="og:title" content="Sweet Treats &amp; Confectionery | Dimitri's Bakery">
  <meta property="og:description" content="A wide variety of delicious sweet treats and confectionery. Perfect for any sweet tooth.">
  <meta property="og:image" content="https://dimitrisbakery.co.za/confectioner/sweet_treats/images/st1.jpg">
  
  <!-- Schema.org JSON-LD for Sweet Treats -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Dimitri's Bakery - Sweet Treats",
    "image": "https://dimitrisbakery.co.za/confectioner/sweet_treats/images/st1.jpg",
    "@id": "https://dimitrisbakery.co.za/confectioner/sweet_treats/sweet_treats.php",
    "url": "https://dimitrisbakery.co.za/confectioner/sweet_treats/sweet_treats.php",
    "telephone": "0798815410",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Parow",
      "addressLocality": "Cape Town",
      "addressRegion": "Western Cape",
      "postalCode": "7500",
      "addressCountry": "ZA"
    },
    "priceRange": "R 50 - R 500",
    "description": "Delicious sweet treats, confectionery, fancies, and artisan biscuits"
  }
  </script>

  <link rel="stylesheet" href="../../css/global.css">
  <link rel="stylesheet" href="css/sweet_treats.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&family=Source+Sans+3:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../index_images/logo.png">
</head>
<body>

<div id="global-nav"></div>
<script>
  fetch('/global_nav.html')
    .then(r => r.text())
    .then(html => {
      document.getElementById('global-nav').innerHTML = html;
    })
    .catch(err => console.error('Failed to load global nav:', err));
</script>

<main>
  <section class="sweet-treats-hero">
    <div class="sweet-treats-hero-overlay">
      <div class="conf-title-badge">
        <span class="conf-subtitle">Delicious Sweet Treats</span>
        <h1 class="conf-page-title">Sweet Treats</h1>
        <div class="conf-divider"></div>
        <p class="conf-desc">Indulge in our artisan sweet creations</p>
      </div>
    </div>
  </section>

  <section class="container sweet-treats-content">
    <h2>Sweet Treats and Confectionery in Cape Town</h2>
    <p>Indulge in a world of <strong>delicious desserts with Dimitri's Sweet Treats in Parow, Cape Town</strong>. From <strong>fancies, bento cakes, cake pops, dessert platters, and more</strong>, we craft treats that are perfect for any celebration or just a sweet indulgence.</p>
    <p>Whether you're planning a <strong>birthday, baby shower, corporate event, or casual gathering</strong>, Dimitri's Sweet Treats provides <strong>customized desserts to match your theme, colors, and preferences</strong>.</p>
    <p>📍 Serving all of <strong>Parow, Cape Town, and surrounding areas</strong>. Contact us today to explore our <strong>sweet treats menu</strong> and order your next dessert masterpiece!</p>
  </section>

  <section class="sweet-treats-gallery">
    <h2>Sweet Treats Gallery</h2>
    <div class="gallery-grid">
      <?php include __DIR__ . '/../../cms/gallery_loader.php'; ?>
    </div>
    <div class="order-button-container">
      <a href="https://wa.me/27799815410?text=I'd%20like%20to%20place%20an%20order%20for%20Sweet%20Treats" target="_blank" class="btn-primary">Place an Order</a>
    </div>
  </section>

  <section class="container conf-grid-section">
    <div class="conf-grid">
      <div class="conf-item">
        <img src="../../confectioner_images/weddingcakes.jpg" alt="Wedding Cakes">
        <a href="../wedding_cakes/wedding_cakes.php" class="btn-category">Wedding Cakes</a>
      </div>
      <div class="conf-item">
        <img src="../../confectioner_images/birthdaycakes.jpg" alt="Birthday Cakes">
        <a href="../birthday_cakes/birthday_cakes.php" class="btn-category">Birthday Cakes</a>
      </div>
      <div class="conf-item">
        <img src="../../confectioner_images/anyoccasioncakes.jpg" alt="Any Occasion Cakes">
        <a href="../any_occasion/any_occasion.php" class="btn-category">Any Occasion Cakes</a>
      </div>
      <div class="conf-item">
        <img src="../../confectioner_images/sweettreats.jpg" alt="Sweet Treats">
        <a href="../sweet_treats/sweet_treats.php" class="btn-category">Sweet Treats</a>
      </div>
    </div>
  </section>
</main>

<footer class="main-footer">
  <div class="container footer-grid">
    <div class="footer-col">
      <img src="../../index_images/logo.png" alt="Dimitri's Bakery" class="footer-logo">
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
        <li><a href="../../index.html">Home</a></li>
        <li><a href="../../weekley_menu/weekly_menu.html">Weekly Menu's</a></li>
        <li><a href="../../confectioner.html">Confectionery</a></li>
        <li><a href="../../catering/catering.php">Catering</a></li>
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
