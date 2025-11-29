<?php
require 'functions.php';

$meta = load_metadata();
$messages = [];

// HÀNH ĐỘNG: upload (add new image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        if ($file['error'] !== 0) {
            $messages[] = ['type'=>'error','text'=>'Lỗi upload.'];
        } elseif (!allowed_mime($file['tmp_name'])) {
            $messages[] = ['type'=>'error','text'=>'Chỉ cho phép ảnh JPG/PNG/GIF/WEBP.'];
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $namebase = pathinfo($file['name'], PATHINFO_FILENAME);
            // tên file an toàn: base + random
            $safe = preg_replace('/[^A-Za-z0-9_\-]/', '-', $namebase);
            $newname = $safe . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = IMAGES_DIR . '/' . $newname;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // thêm metadata (tên & mô tả từ form nếu có)
                $n = trim($_POST['name'] ?? '');
                $d = trim($_POST['description'] ?? '');
                $meta[$newname] = [
                    'name' => $n !== '' ? $n : ucwords(str_replace(['-','_'], ' ', pathinfo($newname, PATHINFO_FILENAME))),
                    'description' => $d !== '' ? $d : ''
                ];
                save_metadata($meta);
                $messages[] = ['type'=>'ok','text'=>'Upload thành công.'];
            } else {
                $messages[] = ['type'=>'error','text'=>'Không thể lưu file.'];
            }
        }
    } else {
        $messages[] = ['type'=>'error','text'=>'Chưa chọn file.'];
    }
}

// HÀNH ĐỘNG: edit metadata (name/description)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $file = $_POST['file'] ?? '';
    if ($file && isset($meta[$file])) {
        $meta[$file]['name'] = trim($_POST['name'] ?? $meta[$file]['name']);
        $meta[$file]['description'] = trim($_POST['description'] ?? $meta[$file]['description']);
        save_metadata($meta);
        $messages[] = ['type'=>'ok','text'=>'Cập nhật thành công.'];
    } else {
        $messages[] = ['type'=>'error','text'=>'File không tồn tại trong metadata.'];
    }
}

// HÀNH ĐỘNG: delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $path = IMAGES_DIR . '/' . $file;
    if (file_exists($path)) {
        @unlink($path);
    }
    if (isset($meta[$file])) {
        unset($meta[$file]);
        save_metadata($meta);
    }
    header('Location: admin.php');
    exit;
}

// build $flowers array for display (from images and metadata)
$flowers = load_flowers();

?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Admin - Quản lý hoa</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
  <div class="header">
    <h1>Quản trị - Danh sách hoa</h1>
    <div><a class="btn" href="index.php">Xem trang khách</a></div>
  </div>

  <div class="card">
    <?php foreach($messages as $m): ?>
      <div class="<?php echo $m['type'] === 'ok' ? 'msg-ok' : 'msg-err'; ?>"><?php echo e($m['text']); ?></div>
    <?php endforeach; ?>

    <h3>Upload ảnh mới</h3>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:16px">
      <input type="hidden" name="action" value="upload">
      <div class="form-row">
        <label>Tên (tuỳ chọn)</label>
        <input type="text" name="name" placeholder="Tên hoa (ví dụ: Hoa Hồng)">
      </div>
      <div class="form-row">
        <label>Mô tả (tuỳ chọn)</label>
        <textarea name="description" rows="3" placeholder="Mô tả ngắn"></textarea>
      </div>
      <div class="form-row">
        <label>Chọn ảnh</label>
        <input type="file" name="image" accept="image/*">
      </div>
      <div><button class="btn" type="submit">Upload</button></div>
    </form>

    <h3>Danh sách (bảng)</h3>
    <table class="table">
      <thead>
        <tr>
          <th>Ảnh</th>
          <th>Tên</th>
          <th>Mô tả</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($flowers)): ?>
          <tr><td colspan="4">Chưa có ảnh trong images/</td></tr>
        <?php else: foreach ($flowers as $f): ?>
          <tr>
            <td style="width:120px">
              <?php if (file_exists('images/' . $f['image'])): ?>
                <img src="images/<?php echo e($f['image']); ?>" style="width:100px;height:60px;object-fit:cover;border-radius:4px">
              <?php else: ?>
                (không)
              <?php endif; ?>
            </td>
            <td><?php echo e($f['name']); ?></td>
            <td><?php echo e($f['description']); ?></td>
            <td>
              <!-- Edit form (inline) -->
              <details>
                <summary class="btn">Sửa</summary>
                <form method="post" style="margin-top:8px">
                  <input type="hidden" name="action" value="edit">
                  <input type="hidden" name="file" value="<?php echo e($f['image']); ?>">
                  <div class="form-row"><input type="text" name="name" value="<?php echo e($f['name']); ?>"></div>
                  <div class="form-row"><textarea name="description" rows="3"><?php echo e($f['description']); ?></textarea></div>
                  <div><button class="btn" type="submit">Lưu</button></div>
                </form>
              </details>

              <a class="btn btn-danger" href="admin.php?action=delete&file=<?php echo urlencode($f['image']); ?>" onclick="return confirm('Xóa ảnh này?')">Xóa</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
