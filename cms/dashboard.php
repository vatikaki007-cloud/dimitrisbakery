<?php
/**
 * dashboard.php — Role-aware CMS Dashboard
 * Admin sees everything; users see only their assigned pages.
 */
require_once __DIR__ . '/auth.php';
require_login();

$pdo = get_db();

// ---------- Stats for admin ----------
$total_photos = 0;
$total_users  = 0;
if (is_admin()) {
    $total_photos = (int)$pdo->query('SELECT COUNT(*) FROM photos')->fetchColumn();
    $total_users  = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

// ---------- Which pages can this user manage? ----------
$my_pages = get_user_pages();

// ---------- Flash messages ----------
$flash_success = $_GET['success'] ?? '';
$flash_error   = $_GET['error']   ?? '';

// Determine active tab
$active_tab = $_GET['tab'] ?? ($my_pages ? $my_pages[0] : '');
if ($active_tab && !in_array($active_tab, $my_pages, true) && !is_admin()) {
    $active_tab = $my_pages[0] ?? '';
}

// Load photos for active page
$page_photos = [];
if ($active_tab) {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.username AS uploader FROM photos p
         JOIN users u ON u.id = p.uploaded_by
         WHERE p.page_slug = ?
         ORDER BY p.uploaded_at DESC'
    );
    $stmt->execute([$active_tab]);
    $page_photos = $stmt->fetchAll();
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Dimitri's Bakery CMS</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="<?= CMS_ROOT ?>/css/cms.css">
  <link rel="icon" type="image/png" href="<?= SITE_ROOT ?>/index_images/logo.png">
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
      <?php foreach (GALLERY_PAGES as $slug => $label):
        if (!is_admin() && !in_array($slug, $my_pages, true)) continue;
        $is_active = ($active_tab === $slug) ? 'active' : '';
      ?>
        <a href="?tab=<?= urlencode($slug) ?>" class="sidebar-link <?= $is_active ?>">
          <span class="icon">🖼️</span> <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>

      <?php if (is_admin() || user_can_access_page('site_assets')): ?>
        <div class="sidebar-section-label" style="margin-top:16px;">Admin</div>
        <?php if (is_admin()): ?>
        <a href="admin/manage_users.php" class="sidebar-link">
          <span class="icon">👥</span> Manage Users
        </a>
        <?php endif; ?>
        <?php if (is_admin() || user_can_access_page('site_assets')): ?>
        <a href="admin/manage_assets.php" class="sidebar-link">
          <span class="icon">🎨</span> Site Assets
        </a>
        <?php endif; ?>
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
          <div><span class="badge badge-<?= current_role() ?>"><?= current_role() ?></span></div>
        </div>
      </div>
      <a href="logout.php" class="btn btn-outline btn-sm w-full" style="justify-content:center;">Sign Out</a>
    </div>
  </aside>

  <!-- ═══════════ MAIN CONTENT ═══════════ -->
  <main class="cms-content">

    <!-- Page header -->
    <div class="page-header animate-in">
      <div>
        <h1 class="page-title">
          <?php if ($active_tab && isset(GALLERY_PAGES[$active_tab])): ?>
            <?= htmlspecialchars(GALLERY_PAGES[$active_tab]) ?>
          <?php else: ?>
            Dashboard
          <?php endif; ?>
        </h1>
        <p class="page-subtitle">Welcome back, <?= htmlspecialchars(current_full_name()) ?></p>
      </div>
      <?php if ($active_tab): ?>
        <button class="btn btn-gold" id="open-upload-btn">
          ＋ Upload Photos
        </button>
      <?php endif; ?>
    </div>

    <!-- Flash messages -->
    <?php if ($flash_success === 'uploaded'): ?>
      <div class="alert alert-success">✓ Photo uploaded successfully.</div>
    <?php elseif ($flash_success === 'deleted'): ?>
      <div class="alert alert-success">✓ Photo deleted.</div>
    <?php elseif ($flash_error === 'access_denied'): ?>
      <div class="alert alert-error">✗ You do not have permission to access that area.</div>
    <?php endif; ?>

    <!-- Admin stats -->
    <?php if (is_admin() && !$active_tab): ?>
    <div class="stats-row animate-in">
      <div class="stat-card">
        <div class="stat-number"><?= $total_photos ?></div>
        <div class="stat-label">Total Photos</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $total_users ?></div>
        <div class="stat-label">Staff Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= count(GALLERY_PAGES) ?></div>
        <div class="stat-label">Gallery Pages</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- No pages assigned (regular user with no permissions) -->
    <?php if (!$my_pages): ?>
    <div class="card animate-in">
      <p class="text-muted text-center" style="padding:40px 0;">
        You haven't been assigned to any gallery pages yet.<br>
        Please ask your administrator to assign you permissions.
      </p>
    </div>
    <?php endif; ?>

    <!-- Gallery tab content -->
    <?php if ($active_tab): ?>
    <div class="card animate-in">
      <div class="card-header">
        <div class="card-title">
          🖼️ Photos — <?= htmlspecialchars(GALLERY_PAGES[$active_tab] ?? $active_tab) ?>
          <span class="badge badge-user"><?= count($page_photos) ?> photos</span>
        </div>
        <?php 
          $live_url = ($active_tab === 'catering') 
            ? SITE_ROOT . '/catering/catering.php'
            : SITE_ROOT . '/confectioner/' . urlencode($active_tab) . '/' . urlencode($active_tab) . '.php';
        ?>
        <a href="<?= $live_url ?>"
           target="_blank" class="btn btn-outline btn-sm">
          View Live Page →
        </a>
      </div>

      <!-- Thumbnail grid -->
      <?php if ($page_photos): ?>
      <div class="upload-grid" id="photo-grid">
        <?php foreach ($page_photos as $ph): ?>
        <div class="upload-thumb" id="thumb-<?= $ph['id'] ?>">
          <img
            src="<?= UPLOADS_URL . '/' . $ph['page_slug'] . '/' . htmlspecialchars($ph['filename']) ?>"
            alt="<?= htmlspecialchars($ph['caption'] ?? $ph['filename']) ?>"
            loading="lazy"
          >
          <?php if ($ph['caption']): ?>
            <div class="thumb-caption"><?= htmlspecialchars($ph['caption']) ?></div>
          <?php endif; ?>
          <div class="thumb-overlay">
            <?php if (is_admin() || (int)$ph['uploaded_by'] === current_user_id()): ?>
            <button
              class="btn btn-danger btn-sm delete-btn"
              data-id="<?= $ph['id'] ?>"
              data-filename="<?= htmlspecialchars($ph['filename']) ?>"
            >🗑 Delete</button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="text-center text-muted" style="padding:40px 0;">
        <p style="font-size:2rem;margin-bottom:10px;">📷</p>
        <p>No photos yet. Upload the first one!</p>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

<!-- ═══════════ UPLOAD MODAL ═══════════ -->
<div class="modal-backdrop" id="upload-modal" style="display:none;">
  <div class="modal-box">
    <h2 class="modal-title">Upload Photos</h2>
    <p class="text-muted text-sm mb-8">JPG, PNG, WebP or GIF · Max 10 MB per file</p>

    <div class="drop-zone" id="drop-zone">
      <div class="drop-icon">☁️</div>
      <p>Drag & drop images here, or <span>browse files</span></p>
      <input type="file" id="file-input" class="file-input-hidden" accept="image/*" multiple>
    </div>

    <div class="form-group mt-16">
      <label for="caption-input">Caption (optional)</label>
      <input type="text" id="caption-input" placeholder="e.g. Custom 3-tier wedding cake">
    </div>

    <div class="upload-progress" id="upload-progress">
      <div class="upload-progress-bar" id="upload-progress-bar"></div>
    </div>

    <div id="upload-feedback" style="margin-top:10px;"></div>

    <div class="modal-actions">
      <button class="btn btn-outline" id="cancel-upload-btn">Cancel</button>
      <button class="btn btn-gold" id="do-upload-btn">Upload</button>
    </div>
  </div>
</div>

<!-- ═══════════ DELETE CONFIRM MODAL ═══════════ -->
<div class="modal-backdrop" id="delete-modal" style="display:none;">
  <div class="modal-box">
    <h2 class="modal-title">Delete Photo?</h2>
    <p class="text-muted">This will permanently remove the photo from the gallery. This cannot be undone.</p>
    <p style="margin-top:10px;font-size:0.85rem;color:var(--text-dim);" id="delete-filename"></p>
    <div class="modal-actions">
      <button class="btn btn-outline" id="cancel-delete-btn">Cancel</button>
      <button class="btn btn-danger" id="confirm-delete-btn">Delete</button>
    </div>
  </div>
</div>

<script>
const CMS_ROOT   = '<?= CMS_ROOT ?>';
const PAGE_SLUG  = '<?= addslashes($active_tab) ?>';
const CSRF_TOKEN = '<?= $csrf ?>';

// ── Upload modal ──────────────────────────────────────────────────────
const uploadModal   = document.getElementById('upload-modal');
const dropZone      = document.getElementById('drop-zone');
const fileInput     = document.getElementById('file-input');
const captionInput  = document.getElementById('caption-input');
const progressWrap  = document.getElementById('upload-progress');
const progressBar   = document.getElementById('upload-progress-bar');
const feedbackEl    = document.getElementById('upload-feedback');
const openUploadBtn = document.getElementById('open-upload-btn');

if (openUploadBtn) {
  openUploadBtn.addEventListener('click', () => {
    uploadModal.style.display = 'flex';
  });
}

document.getElementById('cancel-upload-btn').addEventListener('click', () => {
  uploadModal.style.display = 'none';
  fileInput.value = '';
  feedbackEl.innerHTML = '';
  progressWrap.style.display = 'none';
  progressBar.style.width = '0%';
});

// Click on drop zone → open file picker
dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropZone.classList.add('dragover');
});
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  fileInput.files = e.dataTransfer.files;
  showFileNames(e.dataTransfer.files);
});

fileInput.addEventListener('change', () => showFileNames(fileInput.files));

function showFileNames(files) {
  if (!files.length) return;
  feedbackEl.innerHTML = `<p class="text-sm text-muted">${files.length} file(s) selected: ${Array.from(files).map(f => f.name).join(', ')}</p>`;
}

// Do upload
document.getElementById('do-upload-btn').addEventListener('click', () => {
  const files = fileInput.files;
  if (!files.length) {
    feedbackEl.innerHTML = '<div class="alert alert-error">Please select at least one file.</div>';
    return;
  }
  uploadFiles(files);
});

async function uploadFiles(files) {
  progressWrap.style.display = 'block';
  feedbackEl.innerHTML = '';
  const caption = captionInput.value.trim();
  let uploaded = 0;
  let errors   = [];

  for (let i = 0; i < files.length; i++) {
    const formData = new FormData();
    formData.append('photo',       files[i]);
    formData.append('page_slug',   PAGE_SLUG);
    formData.append('caption',     caption);
    formData.append('csrf_token',  CSRF_TOKEN);

    try {
      const res  = await fetch(CMS_ROOT + '/upload.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        uploaded++;
        addThumbToGrid(data);
      } else {
        errors.push(files[i].name + ': ' + data.error);
      }
    } catch (e) {
      errors.push(files[i].name + ': Network error');
    }

    progressBar.style.width = Math.round(((i + 1) / files.length) * 100) + '%';
  }

  let html = '';
  if (uploaded)       html += `<div class="alert alert-success">✓ ${uploaded} photo(s) uploaded.</div>`;
  if (errors.length)  html += `<div class="alert alert-error">${errors.join('<br>')}</div>`;
  feedbackEl.innerHTML = html;
  fileInput.value = '';
}

function addThumbToGrid(data) {
  const grid = document.getElementById('photo-grid') || createGrid();
  const div  = document.createElement('div');
  div.className = 'upload-thumb';
  div.id = 'thumb-' + data.photo_id;
  div.innerHTML = `
    <img src="${data.url}" alt="${data.filename}" loading="lazy">
    <div class="thumb-overlay">
      <button class="btn btn-danger btn-sm delete-btn"
        data-id="${data.photo_id}"
        data-filename="${data.filename}">🗑 Delete</button>
    </div>`;
  grid.prepend(div);
  attachDeleteListener(div.querySelector('.delete-btn'));
}

function createGrid() {
  const card = document.querySelector('.card');
  const header = card.querySelector('.card-header');
  const grid = document.createElement('div');
  grid.className = 'upload-grid';
  grid.id = 'photo-grid';
  card.querySelector('.text-center')?.remove();
  card.appendChild(grid);
  return grid;
}

// ── Delete modal ──────────────────────────────────────────────────────
const deleteModal    = document.getElementById('delete-modal');
const deleteFilename = document.getElementById('delete-filename');
let pendingDeleteId  = null;

document.getElementById('cancel-delete-btn').addEventListener('click', () => {
  deleteModal.style.display = 'none';
  pendingDeleteId = null;
});

document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
  if (!pendingDeleteId) return;
  const formData = new FormData();
  formData.append('photo_id',   pendingDeleteId);
  formData.append('csrf_token', CSRF_TOKEN);

  const btn = document.getElementById('confirm-delete-btn');
  btn.disabled = true;
  btn.textContent = 'Deleting…';

  try {
    const res  = await fetch(CMS_ROOT + '/delete.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      document.getElementById('thumb-' + pendingDeleteId)?.remove();
      deleteModal.style.display = 'none';
      pendingDeleteId = null;
    } else {
      alert('Error: ' + data.error);
    }
  } catch (e) {
    alert('Network error. Please try again.');
  }
  btn.disabled = false;
  btn.textContent = 'Delete';
});

// Attach delete listeners to all existing buttons
document.querySelectorAll('.delete-btn').forEach(attachDeleteListener);

function attachDeleteListener(btn) {
  if (!btn) return;
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    pendingDeleteId = btn.dataset.id;
    deleteFilename.textContent = 'File: ' + btn.dataset.filename;
    deleteModal.style.display = 'flex';
  });
}
</script>

</body>
</html>
