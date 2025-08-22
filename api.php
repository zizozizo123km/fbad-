<?php
// ========================
// 🔑 إعداد CORS
// ========================
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// ✅ التعامل مع preflight (طلبات OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === "OPTIONS") {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

// ========================
// 🔑 التحقق من API Token
// ========================
$validToken = "sk_jutdumqimr"; // غيرها لو تحب
$headers = getallheaders();

if (!isset($headers['Authorization']) || $headers['Authorization'] !== "Bearer $validToken") {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit;
}

// ========================
// 📂 مسار التخزين
// ========================
$uploadDir = "uploads/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ========================
// 🚀 رفع صورة
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $fileName = uniqid() . "_" . basename($_FILES["image"]["name"]);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $url = "https://" . $_SERVER['HTTP_HOST'] . "/" . $targetFile;
        echo json_encode([
            "success" => true,
            "url" => $url,
            "public_id" => pathinfo($fileName, PATHINFO_FILENAME)
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "فشل رفع الصورة"]);
    }
    exit;
}

// ========================
// 🗑️ حذف صورة
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    if (!empty($data['id'])) {
        $files = glob($uploadDir . $data['id'] . ".*");
        if ($files && file_exists($files[0])) {
            unlink($files[0]);
            echo json_encode(["success" => true, "message" => "تم حذف الصورة"]);
        } else {
            echo json_encode(["success" => false, "error" => "الصورة غير موجودة"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "لم يتم تحديد id"]);
    }
    exit;
}

// ========================
// 📋 عرض قائمة الصور
// ========================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $files = array_diff(scandir($uploadDir), ['.', '..']);
    $list = [];
    foreach ($files as $file) {
        $list[] = [
            "url" => "https://" . $_SERVER['HTTP_HOST'] . "/" . $uploadDir . $file,
            "public_id" => pathinfo($file, PATHINFO_FILENAME)
        ];
    }
    echo json_encode(["success" => true, "images" => $list]);
    exit;
}

// ========================
// ❌ طلب غير صالح
// ========================
echo json_encode(["success" => false, "error" => "طلب غير صالح"]);
