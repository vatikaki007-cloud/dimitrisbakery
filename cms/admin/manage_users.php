<?php
/**
 * admin/manage_users.php — Admin-only: create, view, edit & delete users
 */
require_once __DIR__ . '/../auth.php';
require_admin();

$pdo = get_db();

$error   = '';
$success = '';

// ---------- Handle POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {

        $action = $_POST['action'] ?? '';

        // ── Create user ──────────────────────────────────────────────
        if ($action === 'create') {
            $new_username  = trim($_POST['new_username']  ?? '');
            $new_fullname  = trim($_POST['new_fullname']  ?? '');
            $new_password  = $_POST['new_password']  ?? '';
            $new_role      = in_array($_POST['new_role'] ?? '', ['admin', 'user']) ? $_POST['new_role'] : 'user';
            $new_pages     = $_POST['pages'] ?? [];

            if ($new_username === '' || $new_password === '') {
                $error = 'Username and password are required.';
            } elseif (strlen($new_password) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else {
                // Check uniqueness
                $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
                $check->execute([$new_username]);
                if ($check->fetch()) {
                    $error = 'That username already exists.';
                } else {
                    $hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (username, password, role, full_name) VALUES (?, ?, ?, ?)'
                    );
                    $stmt->execute([$new_username, $hash, $new_role, $new_fullname ?: null]);
                    $new_id = (int)$pdo->lastInsertId();

                    // Assign page permissions
                    foreach ($new_pages as $slug) {
                        if (array_key_exists($slug, GALLERY_PAGES) || $slug === 'site_assets') {
                            $p = $pdo->prepare('INSERT IGNORE INTO page_permissions (user_id, page_slug) VALUES (?, ?)');
                            $p->execute([$new_id, $slug]);
                        }
                    }
                    $success = "User '{$new_username}' created successfully.";
                }
            }

        // ── Delete user ──────────────────────────────────────────────
        } elseif ($action === 'delete') {
            $del_id = (int)($_POST['user_id'] ?? 0);
            if ($del_id === current_user_id()) {
                $error = 'You cannot delete your own account.';
            } elseif ($del_id > 0) {
                $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$del_id]);
                $success = 'User deleted.';
            }

        // ── Update permissions ────────────────────────────────────────
        } elseif ($action === 'update_perms') {
            $upd_id     = (int)($_POST['user_id'] ?? 0);
            $upd_pages  = $_POST['pages'] ?? [];
            if ($upd_id > 0) {
                $pdo->prepare('DELETE FROM page_permissions WHERE user_id = ?')->execute([$upd_id]);
                foreach ($upd_pages as $slug) {
                    if (array_key_exists($slug, GALLERY_PAGES) || $slug === 'site_assets') {
                        $p = $pdo->prepare('INSERT INTO page_permissions (user_id, page_slug) VALUES (?, ?)');
                        $p->execute([$upd_id, $slug]);
                    }
                }
                $success = 'Permissions updated.';
            }

        // ── Change password ───────────────────────────────────────────
        } elseif ($action === 'change_password') {
            $cp_id  = (int)($_POST['user_id'] ?? 0);
            $cp_pw  = $_POST['new_pw'] ?? '';
            if ($cp_id > 0 && strlen($cp_pw) >= 8) {
                $hash = password_hash($cp_pw, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $cp_id]);
                $success = 'Password updated.';
            } else {
                $error = 'Password must be at least 8 characters.';
            }
        }
    }
}

// ---------- Load all users + their permissions ----------
$users = $pdo->query(
    'SELECT u.*, GROUP_CONCAT(p.page_slug SEPARATOR ",") AS pages
     FROM users u
     LEFT JOIN page_permissions p ON p.user_id = u.id
     GROUP BY u.id
     ORDER BY u.role ASC, u.created_at ASC'
)->fetchAll();

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users — Dimitri's Bakery CMS</title>
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
      <?php foreach (GALLERY_PAGES as $slug => $label): ?>
        <a href="<?= CMS_ROOT ?>/dashboard.php?tab=<?= urlencode($slug) ?>" class="sidebar-link">
          <span class="icon">🖼️</span> <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>

      <div class="sidebar-section-label" style="margin-top:16px;">Admin</div>
      <a href="manage_users.php" class="sidebar-link active">
        <span class="icon">👥</span> Manage Users
      </a>
      <a href="manage_assets.php" class="sidebar-link">
        <span class="icon">🎨</span> Site Assets
      </a>

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
          <span class="badge badge-admin">admin</span>
        </div>
      </div>
      <a href="<?= CMS_ROOT ?>/logout.php" class="btn btn-outline btn-sm w-full" style="justify-content:center;">Sign Out</a>
    </div>
  </aside>

  <!-- ═══════════ CONTENT ═══════════ -->
  <main class="cms-content">

    <div class="page-header animate-in">
      <div>
        <h1 class="page-title">👥 Manage Users</h1>
        <p class="page-subtitle">Create and manage staff accounts &amp; page permissions</p>
      </div>
      <button class="btn btn-gold" id="show-create-btn">＋ New User</button>
    </div>

    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- ── Create user form (hidden by default) ── -->
    <div class="card animate-in" id="create-user-card" style="display:none;">
      <div class="card-header">
        <div class="card-title">✏️ Create New User</div>
        <button class="btn btn-outline btn-sm" id="hide-create-btn">Cancel</button>
      </div>

      <form method="POST" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div class="form-group">
            <label>Username *</label>
            <input type="text" name="new_username" required placeholder="e.g. anna">
          </div>
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="new_fullname" placeholder="e.g. Anna Bakery">
          </div>
          <div class="form-group">
            <label>Password * (min 8 chars)</label>
            <input type="password" name="new_password" required minlength="8" placeholder="Minimum 8 characters">
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="new_role">
              <option value="user">User (Staff)</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Gallery Page Permissions</label>
          <div class="perm-grid">
            <?php foreach (GALLERY_PAGES as $slug => $label): ?>
            <div class="perm-item">
              <input type="checkbox" id="cp_<?= $slug ?>" name="pages[]" value="<?= $slug ?>">
              <label for="cp_<?= $slug ?>"><?= htmlspecialchars($label) ?></label>
            </div>
            <?php endforeach; ?>
            <div class="perm-item" style="border-top: 1px solid #333; padding-top: 10px; grid-column: 1 / -1;">
              <input type="checkbox" id="cp_site_assets" name="pages[]" value="site_assets">
              <label for="cp_site_assets" style="color: #ffb703;">🎨 Manage Site Assets (Global Images)</label>
            </div>
          </div>
          <p class="text-sm text-muted mt-8">Admin users automatically have access to all pages.</p>
        </div>

        <button type="submit" class="btn btn-gold">Create User</button>
      </form>
    </div>

    <!-- ── Users table ── -->
    <div class="card animate-in">
      <div class="card-header">
        <div class="card-title">Staff Accounts <span class="badge badge-user"><?= count($users) ?></span></div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>User</th>
              <th>Role</th>
              <th>Page Permissions</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($users as $u):
            $user_pages = $u['pages'] ? explode(',', $u['pages']) : [];
          ?>
            <tr>
              <td>
                <div class="flex items-center gap-8">
                  <div class="avatar" style="width:28px;height:28px;font-size:0.75rem;">
                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                  </div>
                  <div>
                    <strong><?= htmlspecialchars($u['username']) ?></strong>
                    <?php if ($u['full_name']): ?>
                      <br><span class="text-sm text-muted"><?= htmlspecialchars($u['full_name']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
              <td>
                <?php if ($u['role'] === 'admin'): ?>
                  <span class="text-muted text-sm">All pages (admin)</span>
                <?php elseif ($user_pages): ?>
                  <?php foreach ($user_pages as $pg): ?>
                    <span class="badge badge-user" style="margin:2px 2px 2px 0;">
                      <?= htmlspecialchars($pg === 'site_assets' ? '🎨 Site Assets' : (GALLERY_PAGES[$pg] ?? $pg)) ?>
                    </span>
                  <?php endforeach; ?>
                <?php else: ?>
                  <span class="text-dim text-sm">No pages assigned</span>
                <?php endif; ?>
              </td>
              <td class="text-sm text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div class="td-actions">
                  <button class="btn btn-outline btn-sm edit-perms-btn"
                    data-id="<?= $u['id'] ?>"
                    data-username="<?= htmlspecialchars($u['username']) ?>"
                    data-pages="<?= htmlspecialchars($u['pages'] ?? '') ?>">
                    ✏️ Permissions
                  </button>
                  <button class="btn btn-outline btn-sm change-pw-btn"
                    data-id="<?= $u['id'] ?>"
                    data-username="<?= htmlspecialchars($u['username']) ?>">
                    🔑 Password
                  </button>
                  <?php if ((int)$u['id'] !== current_user_id()): ?>
                  <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete user \'<?= htmlspecialchars($u['username']) ?>\'?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<!-- Edit permissions modal -->
<div class="modal-backdrop" id="perm-modal" style="display:none;">
  <div class="modal-box">
    <h2 class="modal-title">Page Permissions — <span id="perm-username"></span></h2>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_perms">
      <input type="hidden" name="user_id" id="perm-user-id">
      <div class="perm-grid" id="perm-grid">
        <?php foreach (GALLERY_PAGES as $slug => $label): ?>
        <div class="perm-item">
          <input type="checkbox" id="ep_<?= $slug ?>" name="pages[]" value="<?= $slug ?>" class="perm-check">
          <label for="ep_<?= $slug ?>"><?= htmlspecialchars($label) ?></label>
        </div>
        <?php endforeach; ?>
        <div class="perm-item" style="border-top: 1px solid #333; padding-top: 10px; grid-column: 1 / -1;">
          <input type="checkbox" id="ep_site_assets" name="pages[]" value="site_assets" class="perm-check">
          <label for="ep_site_assets" style="color: #ffb703;">🎨 Manage Site Assets (Global Images)</label>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="close-perm-modal">Cancel</button>
        <button type="submit" class="btn btn-gold">Save Permissions</button>
      </div>
    </form>
  </div>
</div>

<!-- Change password modal -->
<div class="modal-backdrop" id="pw-modal" style="display:none;">
  <div class="modal-box">
    <h2 class="modal-title">Change Password — <span id="pw-username"></span></h2>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="change_password">
      <input type="hidden" name="user_id" id="pw-user-id">
      <div class="form-group">
        <label>New Password (min 8 chars)</label>
        <input type="password" name="new_pw" required minlength="8" placeholder="Enter new password">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="close-pw-modal">Cancel</button>
        <button type="submit" class="btn btn-gold">Save Password</button>
      </div>
    </form>
  </div>
</div>

<script>
// Create user toggle
document.getElementById('show-create-btn').addEventListener('click', () => {
  document.getElementById('create-user-card').style.display = 'block';
});
document.getElementById('hide-create-btn').addEventListener('click', () => {
  document.getElementById('create-user-card').style.display = 'none';
});

// Edit permissions modal
const permModal = document.getElementById('perm-modal');
document.querySelectorAll('.edit-perms-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('perm-user-id').value   = btn.dataset.id;
    document.getElementById('perm-username').textContent = btn.dataset.username;
    const userPages = btn.dataset.pages ? btn.dataset.pages.split(',') : [];
    document.querySelectorAll('.perm-check').forEach(cb => {
      cb.checked = userPages.includes(cb.value);
    });
    permModal.style.display = 'flex';
  });
});
document.getElementById('close-perm-modal').addEventListener('click', () => {
  permModal.style.display = 'none';
});

// Change password modal
const pwModal = document.getElementById('pw-modal');
document.querySelectorAll('.change-pw-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('pw-user-id').value = btn.dataset.id;
    document.getElementById('pw-username').textContent = btn.dataset.username;
    pwModal.style.display = 'flex';
  });
});
document.getElementById('close-pw-modal').addEventListener('click', () => {
  pwModal.style.display = 'none';
});
</script>

</body>
</html>
