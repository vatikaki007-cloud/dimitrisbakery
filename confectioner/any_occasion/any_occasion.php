<?php
$page_slug      = 'any_occasion';
$db_config_path = __DIR__ . '/../../cms/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cakes for Any Occasion | Dimitri's Bakery | Cape Town</title>
  <meta name="description" content="Special cakes for any occasion. From anniversaries to graduations, Dimitri's Bakery in Cape Town creates the perfect cake for your event.">
  <meta name="keywords" content="anniversary cakes, graduation cakes, special occasion cakes Cape Town, Dimitri's Bakery cakes">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://dimitrisbakery.co.za/confectioner/any_occasion/any_occasion.php">
  <meta property="og:title" content="Special Occasion Cakes | Dimitri's Bakery">
  <meta property="og:description" content="Custom cakes for every special moment in your life. Quality you can taste.">
  <meta property="og:image" content="https://dimitrisbakery.co.za/confectioner/any_occasion/images/ao1.jpg">
  
  <!-- Schema.org JSON-LD for Any Occasion Cakes -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Dimitri's Bakery - Any Occasion Cakes",
    "image": "https://dimitrisbakery.co.za/confectioner/any_occasion/images/ao1.jpg",
    "@id": "https://dimitrisbakery.co.za/confectioner/any_occasion/any_occasion.php",
    "url": "https://dimitrisbakery.co.za/confectioner/any_occasion/any_occasion.php",
    "telephone": "0798815410",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Parow",
      "addressLocality": "Cape Town",
      "addressRegion": "Western Cape",
      "postalCode": "7500",
      "addressCountry": "ZA"
    },
    "priceRange": "R 400 - R 3000",
    "description": "Custom cakes for confirmations, baptisms, anniversaries, and all special occasions"
  }
  </script>

  <link rel="stylesheet" href="css/any_occasion.css">
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
  <section class="any-occasion-hero">
    <div class="any-occasion-hero-overlay">
      <div class="conf-title-badge">
        <span class="conf-subtitle">Special Occasions</span>
        <h1 class="conf-page-title">Any Occasion Cakes</h1>
        <div class="conf-divider"></div>
        <p class="conf-desc">Celebrate every milestone with us</p>
      </div>
    </div>
  </section>

  <section class="container any-occasion-content">
    <h2>Special Occasion Cakes in Cape Town</h2>
    <p>Celebrate every milestone with a <strong>custom cake from Dimitri's Bakery in Parow, Cape Town</strong>. We specialize in creating <strong>cakes for confirmations, baptisms, bride-to-be parties, matric farewells, and other special occasions</strong>.</p>
    <p>Choose from a variety of flavors, including <strong>chocolate, vanilla sponge, red velvet, fruit cake, and more</strong>, all made with <strong>fresh, high-quality ingredients</strong>.</p>
    <p>📍 Serving <strong>Parow and surrounding Cape Town areas</strong>. Contact us today to order your <strong>custom occasion cake</strong>!</p>
  </section>

  <section class="any-occasion-gallery">
    <h2>Any Occasion Cake Gallery</h2>
    <div class="gallery-grid">
      <?php include __DIR__ . '/../../cms/gallery_loader.php'; ?>
    </div>
    <div class="order-button-container">
      <a href="https://wa.me/27799815410?text=I'd%20like%20to%20place%20an%20order%20for%20an%20Any%20Occasion%20Cake" target="_blank" class="btn-primary">Place an Order</a>
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
