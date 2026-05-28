<?php
$page_slug      = 'wedding_cakes';
$db_config_path = __DIR__ . '/../../cms/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Custom Wedding Cakes | Dimitri's Bakery | Cape Town</title>
  <meta name="description" content="Beautiful and delicious custom wedding cakes in Cape Town. Dimitri's Bakery creates stunning wedding cakes tailored to your special day. Quality and elegance in every bite.">
  <meta name="keywords" content="wedding cakes Cape Town, custom wedding cakes, elegant wedding cakes, Dimitri's Bakery wedding cakes">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://dimitrisbakery.co.za/confectioner/wedding_cakes/wedding_cakes.php">
  <meta property="og:title" content="Custom Wedding Cakes | Dimitri's Bakery">
  <meta property="og:description" content="Stunning wedding cakes for your special day. Custom designs and exquisite flavors.">
  <meta property="og:image" content="https://dimitrisbakery.co.za/confectioner/wedding_cakes/images/wc1.jpg">
  <link rel="stylesheet" href="css/wedding_cakes.css">
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
  <section class="wedding-hero">
    <div class="wedding-hero-overlay">
      <div class="container">
        <h1 class="wedding-title">Wedding Cakes</h1>
      </div>
    </div>
  </section>

  <section class="container wedding-content">
    <p>Celebrate your special day with a handcrafted <strong>wedding cake</strong> from <strong>Dimitri's Bakery</strong> in <strong>Parow, Cape Town</strong>. Our expert bakers specialize in creating <strong>custom wedding cakes</strong> that are as beautiful as they are delicious.</p>
    <p>At Dimitri's Bakery, we use only the finest ingredients to ensure your <strong>wedding cake not only looks stunning but tastes unforgettable</strong>. Whether you prefer a <strong>traditional fruit cake, decadent chocolate, vanilla sponge, or a fusion of flavors</strong>, our team will design the perfect centerpiece for your celebration.</p>
    <p>Serving couples throughout <strong>Parow, Cape Town, and surrounding areas</strong>, Dimitri's Bakery is trusted for its <strong>personalized service, attention to detail, and exquisite cake artistry</strong>.</p>
    <p>📍 Visit us in Parow, Cape Town, or contact us today to book a <strong>wedding cake consultation</strong>.</p>
    <div class="pricing-list">
      <p>Pricing starts from:</p>
      <p class="pricing-item">1 Tier – R 900.00</p>
      <p class="pricing-item">2 Tier – R 1400.00</p>
      <p class="pricing-item">3 Tier – R 1890.00</p>
    </div>
  </section>

  <section class="wedding-gallery">
    <div class="gallery-grid">
      <?php include __DIR__ . '/../../cms/gallery_loader.php'; ?>
    </div>
    <div class="order-button-container" style="text-align:center;margin-top:40px;margin-bottom:20px;">
      <a href="https://wa.me/27799815410?text=I'd%20like%20to%20place%20an%20order%20for%20a%20Wedding%20Cake" target="_blank" class="btn-primary" style="display:inline-block;background-color:#007bff;color:white;padding:12px 30px;text-decoration:none;border-radius:5px;font-weight:bold;">Place an Order</a>
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
