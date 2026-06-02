<?php
/**
 * birthday_cakes.php — Birthday Cakes Gallery (CMS-enabled)
 * Converted from birthday_cakes.html
 * Static images kept as-is; CMS-managed photos appended dynamically.
 */
$page_slug      = 'birthday_cakes';
$db_config_path = __DIR__ . '/../../cms/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Custom Birthday Cakes | Dimitri's Bakery | Cape Town</title>
  <meta name="description" content="Fun and delicious custom birthday cakes for all ages. Dimitri's Bakery creates personalized birthday cakes in Cape Town. Make your celebration extra special.">
  <meta name="keywords" content="birthday cakes Cape Town, custom birthday cakes, kids birthday cakes, Dimitri's Bakery birthday cakes">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://dimitrisbakery.co.za/confectioner/birthday_cakes/birthday_cakes.php">
  <meta property="og:title" content="Custom Birthday Cakes | Dimitri's Bakery">
  <meta property="og:description" content="Personalized birthday cakes for every age and style. Delicious and beautiful.">
  <meta property="og:image" content="https://dimitrisbakery.co.za/confectioner/birthday_cakes/images/bc1.jpg">

  <link rel="stylesheet" href="css/birthday_cakes.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&family=Source+Sans+3:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../index_images/logo.png">
</head>
<body>

<!-- Header (Consistent Navigation) -->
<div id="global-nav"></div>
<script>
  fetch('/global_nav.html')
    .then(r => r.text())
    .then(html => { document.getElementById('global-nav').innerHTML = html; })
    .catch(err => console.error('Failed to load global nav:', err));
</script>

<main>
  <!-- HERO SECTION -->
  <section class="birthday-hero">
    <div class="birthday-hero-overlay">
      <div class="container">
        <h1 class="birthday-title">Birthday Cakes</h1>
      </div>
    </div>
  </section>

  <!-- CONTENT SECTION -->
  <section class="container birthday-content">
    <h2>Custom Birthday Cakes in Parow, Cape Town</h2>
    <p>Make every birthday unforgettable with a <strong>custom birthday cake</strong> from <strong>Dimitri's Bakery</strong> in <strong>Parow, Cape Town</strong>. Whether you're planning a <strong>fun children's birthday cake with cartoon themes</strong>, an <strong>elegant adult birthday cake</strong>, or a <strong>luxury milestone cake for 21st, 30th, 40th, or 50th celebrations</strong>, our skilled bakers bring your ideas to life.</p>
    <p>At Dimitri's Bakery, we specialize in <strong>personalized birthday cakes</strong> designed to match your theme, colors, and favorite flavors. Choose from <strong>chocolate, vanilla sponge, red velvet, caramel, fruit cake, and more</strong>—all baked with the finest ingredients to guarantee delicious taste and stunning presentation.</p>
    <p>Serving families across <strong>Parow, Cape Town, and nearby suburbs</strong>, we're trusted for our <strong>creative designs, attention to detail, and customer-focused service</strong>. Every cake is freshly made and decorated with love to ensure your birthday celebration is truly special.</p>
    <p>Visit us in Parow, Cape Town, or contact us today to order your <strong>birthday cake in Cape Town</strong> and let us create a sweet masterpiece for your celebration.</p>
  </section>

  <!-- GALLERY SECTION -->
  <section class="birthday-gallery">
    <div class="gallery-grid">
      <!-- CMS-managed photos (appended dynamically from database) -->
      <?php include $db_config_path !== false ? __DIR__ . '/../../cms/gallery_loader.php' : ''; ?>
    </div>

    <div class="order-button-container" style="text-align: center; margin-top: 40px; margin-bottom: 20px;">
      <a href="https://wa.me/27799815410?text=I'd%20like%20to%20place%20an%20order%20for%20a%20Birthday%20Cake" target="_blank" class="btn-primary" style="display: inline-block; background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Place an Order</a>
    </div>
  </section>

  <!-- CATEGORY NAVIGATION GRID -->
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

<!-- Footer -->
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
