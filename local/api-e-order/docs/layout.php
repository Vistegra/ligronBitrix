<?php

/**
 * @var array|array[] $menu
 * @var string $activePage
 * @var string $pageTitle
 * @var array $breadcrumbs
 * @var string $content
 */

?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | API Documentation</title>
  <link rel="stylesheet" href="styles.css">
  <style>
      /* Дополнительные стили для Layout */
      body { margin: 0; display: flex; min-height: 100vh; background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }

      /* Sidebar */
      .sidebar {
          width: 280px;
          background-color: #2c3e50;
          color: #ecf0f1;
          flex-shrink: 0;
          overflow-y: auto;
          position: fixed;
          height: 100%;
          left: 0; top: 0;
      }
      .sidebar-header { padding: 20px; border-bottom: 1px solid #34495e; font-weight: bold; font-size: 1.2em; }
      .sidebar-menu { list-style: none; padding: 0; margin: 0; }
      .sidebar-category { padding: 15px 20px 5px; font-size: 0.85em; text-transform: uppercase; color: #95a5a6; font-weight: bold; letter-spacing: 1px; }
      .sidebar-link { display: block; padding: 10px 20px; color: #bdc3c7; text-decoration: none; border-left: 4px solid transparent; transition: 0.2s; }
      .sidebar-link:hover { background-color: #34495e; color: #fff; }
      .sidebar-link.active { background-color: #34495e; color: #fff; border-left-color: #3498db; }

      /* Main Content Wrapper */
      .main-wrapper { margin-left: 280px; width: 100%; display: flex; flex-direction: column; }

      /* Header / Breadcrumbs */
      .top-header { background: #fff; padding: 15px 30px; border-bottom: 1px solid #e9ecef; display: flex; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
      .breadcrumbs { display: flex; align-items: center; font-size: 0.9em; color: #6c757d; }
      .breadcrumbs a { text-decoration: none; color: #3498db; }
      .breadcrumbs a:hover { text-decoration: underline; }
      .breadcrumbs .sep { margin: 0 10px; color: #ccc; }
      .breadcrumbs .current { color: #495057; font-weight: 500; }

      /* Content Area */
      .content { padding: 30px; max-width: 900px; margin: 0 auto; width: 100%; box-sizing: border-box; }

      /* Mobile */
      @media (max-width: 768px) {
          .sidebar { transform: translateX(-100%); transition: transform 0.3s; z-index: 1000; }
          .sidebar.open { transform: translateX(0); }
          .main-wrapper { margin-left: 0; }
          .mobile-toggle { display: block; margin-right: 15px; cursor: pointer; }
      }
      @media (min-width: 769px) { .mobile-toggle { display: none; } }
  </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><rect width="12" height="12" x="10" y="2" fill="#42a5f5" rx="6"/><rect width="12" height="12" x="18" y="18" fill="#42a5f5" rx="6"/><rect width="12" height="12" x="2" y="18" fill="#42a5f5" rx="6"/><path fill="none" stroke="#42a5f5" stroke-miterlimit="10" stroke-width="3" d="m16 8l8 16M16 8L8 24"/></svg>

            <span style="font-size: 1em; font-weight: 600;">API E-Order</span>
        </div>
    </div>
  <ul class="sidebar-menu">
    <?php
    $lastCategory = null;
    foreach ($menu as $slug => $item):
      // Группировка по категориям
      if ($lastCategory !== $item['category']):
        $lastCategory = $item['category'];
        ?>
        <li class="sidebar-category"><?= htmlspecialchars($lastCategory) ?></li>
      <?php endif; ?>

      <li>
        <a href="/local/api-e-order/docs/<?= $slug === 'index' ? '' : $slug ?>"
           class="sidebar-link <?= $activePage === $slug ? 'active' : '' ?>">
          <?= htmlspecialchars($item['title']) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</aside>

<div class="main-wrapper">
  <!-- Header & Breadcrumbs -->
  <header class="top-header">
    <div class="mobile-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</div>

    <nav class="breadcrumbs">
      <?php foreach ($breadcrumbs as $index => $crumb): ?>
        <?php if ($index > 0): ?><span class="sep">/</span><?php endif; ?>

        <?php if ($crumb['link']): ?>
          <a href="<?= $crumb['link'] ?>"><?= htmlspecialchars($crumb['title']) ?></a>
        <?php else: ?>
          <span class="current"><?= htmlspecialchars($crumb['title']) ?></span>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
  </header>

  <!-- Main Content -->
  <main class="content">
    <!-- Вставка контента конкретной страницы -->
    <?= $content ?>
  </main>
</div>

</body>
</html>