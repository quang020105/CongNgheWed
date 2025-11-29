<?php
require 'db.php';
$message = "";

// Khi người dùng Upload CSV
if (isset($_POST["submit"])) {
    if (isset($_FILES["csvfile"]) && $_FILES["csvfile"]["error"] == 0) {

        $fileTmp = $_FILES["csvfile"]["tmp_name"];

        if (($handle = fopen($fileTmp, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");

            // Chuẩn bị truy vấn INSERT
            $sql = "INSERT INTO accounts (username, password, lastname, firstname, city, email, course1)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Bỏ qua dòng rỗng
                if (count($data) < 7) continue;

                $stmt->execute($data);
            }

            fclose($handle);
            $message = "Tải lên và lưu dữ liệu vào CSDL thành công!";
        }
    } else {
        $message = "Vui lòng chọn file CSV hợp lệ!";
    }
}

// Lấy dữ liệu từ DB
$rows = $conn->query("SELECT * FROM accounts")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Upload & Lưu CSV → MySQL</title>

    <style>
        body { font-family: Arial; padding: 20px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 12px;
        }
        th {
            background: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        .msg {
            padding: 10px;
            background: #d9ffd9;
            color: #006600;
            border: 1px solid #99cc99;
            margin-bottom: 15px;
            width: fit-content;
        }
    </style>
</head>
<body>

<h2>Upload File CSV và Lưu vào CSDL</h2>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="csvfile" accept=".csv" required>
    <button type="submit" name="submit">Upload & Lưu</button>
</form>

<?php if ($message): ?>
    <div class="msg"><?= $message ?></div>
<?php endif; ?>

<h2>Dữ liệu trong CSDL</h2>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Password</th>
            <th>Lastname</th>
            <th>Firstname</th>
            <th>City</th>
            <th>Email</th>
            <th>Course</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= $row["id"] ?></td>
                <td><?= htmlspecialchars($row["username"]) ?></td>
                <td><?= htmlspecialchars($row["password"]) ?></td>
                <td><?= htmlspecialchars($row["lastname"]) ?></td>
                <td><?= htmlspecialchars($row["firstname"]) ?></td>
                <td><?= htmlspecialchars($row["city"]) ?></td>
                <td><?= htmlspecialchars($row["email"]) ?></td>
                <td><?= htmlspecialchars($row["course1"]) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
