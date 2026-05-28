<?php
/**
 * admin/manage_assets.php — Allow admins to replace static site images
 */
require_once __DIR__ . '/../auth.php';

// Only admins or users with specific permission can manage global site assets
require_login();
if (!is_admin() && !user_can_access_page('site_assets')) {
    header('Location: ../dashboard.php?error=access_denied');
    exit;
}

$csrf_token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Site Assets — Dimitri's Bakery CMS</title>
  <link rel="stylesheet" href="../css/cms.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    .asset-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }
    .asset-card {
      background: var(--bg-card);
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.3);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .asset-preview {
      width: 100%;
      height: 150px;
      background: #111;
      border-radius: 4px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .asset-preview img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
    .asset-info h3 {
      margin: 0 0 5px 0;
      font-size: 1.1rem;
      color: var(--text-primary);
    }
    .asset-info p {
      margin: 0;
      font-size: 0.85rem;
      color: var(--text-secondary);
    }
    .upload-form {
      margin-top: auto;
      padding-top: 15px;
      border-top: 1px solid var(--border-color);
    }
    .upload-form input[type="file"] {
      width: 100%;
      margin-bottom: 10px;
      font-size: 0.85rem;
      color: var(--text-secondary);
    }
    .upload-form button {
      width: 100%;
      padding: 8px;
    }
    
    /* Toast notifications */
    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #4caf50;
        color: white;
        padding: 12px 20px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
        z-index: 1000;
        pointer-events: none;
    }
    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }
    .toast.error {
        background: #f44336;
    }
  </style>
</head>
<body>

<div class="cms-layout">
  <!-- ═══════════ SIDEBAR ═══════════ -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="<?= SITE_ROOT ?>/index_images/logo.png" alt="Dimitri's Bakery">
      <span>Dimitri's Bakery CMS</span>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section-label">Gallery</div>
      <?php 
      $my_pages = get_user_pages();
      foreach (GALLERY_PAGES as $slug => $label): 
        if (!is_admin() && !in_array($slug, $my_pages, true)) continue;
      ?>
        <a href="<?= CMS_ROOT ?>/dashboard.php?tab=<?= urlencode($slug) ?>" class="sidebar-link">
          <span class="icon">🖼️</span> <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>

      <?php if (is_admin() || user_can_access_page('site_assets')): ?>
        <?php if (is_admin()): ?>
          <div class="sidebar-section-label" style="margin-top:16px;">Admin</div>
          <a href="manage_users.php" class="sidebar-link">
            <span class="icon">👥</span> Manage Users
          </a>
        <?php else: ?>
          <div class="sidebar-section-label" style="margin-top:16px;">Admin Tools</div>
        <?php endif; ?>
        
        <a href="manage_assets.php" class="sidebar-link active">
          <span class="icon">🎨</span> Site Assets
        </a>
      <?php endif; ?>

      <div class="sidebar-section-label" style="margin-top:16px;">Site</div>
      <a href="<?= SITE_ROOT ?>" target="_blank" class="sidebar-link">
        <span class="icon">🌐</span> View Website
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-chip">
        <div class="avatar"><?= strtoupper(substr(current_username(), 0, 1)) ?></div>
        <div>
          <div style="font-weight:600;color:var(--text);"><?= htmlspecialchars(current_full_name()) ?></div>
          <span class="badge badge-<?= current_role() ?>"><?= current_role() ?></span>
        </div>
      </div>
      <a href="<?= CMS_ROOT ?>/logout.php" class="btn btn-outline btn-sm w-full" style="justify-content:center;">Sign Out</a>
    </div>
  </aside>

  <!-- ═══════════ CONTENT ═══════════ -->
  <main class="cms-content">
    <div class="page-header animate-in">
      <div>
        <h1 class="page-title">🎨 Site Assets</h1>
        <p class="page-subtitle">Manage global site images like logos and backgrounds. Changes here apply immediately to the live website.</p>
      </div>
    </div>

    <div class="content-area">
      <div class="asset-grid">
        <?php foreach (SITE_ASSETS as $key => $asset): ?>
          <div class="asset-card">
            <div class="asset-preview">
              <!-- Add a cache-buster so the new image shows immediately after upload -->
              <img src="<?= SITE_ROOT . htmlspecialchars($asset['path']) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($asset['name']) ?>">
            </div>
            <div class="asset-info">
              <h3><?= htmlspecialchars($asset['name']) ?></h3>
              <p><?= htmlspecialchars($asset['desc']) ?></p>
              <p style="font-family: monospace; font-size: 0.75rem; margin-top: 5px; color: #888;">File: <?= htmlspecialchars($asset['path']) ?></p>
            </div>
            <form class="upload-form" onsubmit="uploadAsset(event, '<?= $key ?>')">
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
              <input type="hidden" name="asset_key" value="<?= $key ?>">
              <input type="file" name="photo" accept="<?= implode(',', ALLOWED_MIME_TYPES) ?>" required>
              <button type="submit" class="btn-primary">Upload & Replace</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>

<div id="toast" class="toast"></div>

<script>
async function uploadAsset(e, assetKey) {
  e.preventDefault();
  const form = e.target;
  const submitBtn = form.querySelector('button');
  const originalText = submitBtn.innerText;
  
  submitBtn.disabled = true;
  submitBtn.innerText = 'Uploading...';

  const formData = new FormData(form);
  
  try {
    const response = await fetch('../upload_asset.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      showToast('Asset replaced successfully!');
      // Force refresh the preview image
      const img = form.closest('.asset-card').querySelector('.asset-preview img');
      const url = new URL(img.src);
      url.searchParams.set('t', Date.now());
      img.src = url.toString();
      form.reset();
    } else {
      showToast(result.error || 'Upload failed', true);
    }
  } catch (err) {
    showToast('Network error during upload', true);
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerText = originalText;
  }
}

function showToast(message, isError = false) {
  const toast = document.getElementById('toast');
  toast.innerText = message;
  toast.className = 'toast show' + (isError ? ' error' : '');
  setTimeout(() => toast.className = 'toast', 3000);
}
</script>

</body>
</html>
