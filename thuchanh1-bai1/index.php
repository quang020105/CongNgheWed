<?php
require 'functions.php';

// LẤY danh sách hoa từ thư mục images (đây chính là mảng $flowers theo đề)
$flowers = load_flowers();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Danh sách hoa</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Danh sách các loài hoa</h1>
    <div><a class="btn" href="admin.php">Trang quản trị</a></div>
  </div>

  <div class="grid">
    <?php if (empty($flowers)): ?>
      <div class="card">Chưa có ảnh trong thư mục images/</div>
    <?php else: foreach ($flowers as $f): ?>
      <article class="card">
        <?php if (file_exists('images/' . $f['image'])): ?>
          <img class="img-thumb" src="images/<?php echo e($f['image']); ?>" alt="<?php echo e($f['name']); ?>">
        <?php else: ?>
          <div class="no-img">Không có ảnh</div>
        <?php endif; ?>

        <h2><?php echo e($f['name']); ?></h2>
        <p><?php echo nl2br(e($f['description'])); ?></p>
      </article>
    <?php endforeach; endif; ?>
  </div>
</div>
</body>
</html>
