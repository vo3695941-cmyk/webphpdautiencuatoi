<?php
// ============================================
// CODEBRAIN IMAGE AI 1.2
// PURE PHP SVG GENERATOR
// SINGLE FILE EDITION
// ============================================
error_reporting(0);
mb_internal_encoding("UTF-8");

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

function has($text, $words) {
    foreach ($words as $word) {
        if (mb_strpos($text, $word) !== false) {
            return true;
        }
    }
    return false;
}

function generateImage($prompt) {

    $text = mb_strtolower($prompt, "UTF-8");

    // ==========================================
    // COLORS
    // ==========================================

    $color = "#2563eb";

    $colors = [
        "đỏ" => "#ef4444",
        "xanh lá" => "#16a34a",
        "xanh dương" => "#2563eb",
        "xanh" => "#2563eb",
        "vàng" => "#eab308",
        "tím" => "#9333ea",
        "hồng" => "#ec4899",
        "cam" => "#f97316",
        "đen" => "#111827",
        "trắng" => "#ffffff"
    ];

    foreach ($colors as $name => $value) {
        if (has($text, [$name])) {
            $color = $value;
            break;
        }
    }

    // ==========================================
    // ENVIRONMENT
    // ==========================================

    $night = has($text, ["đêm", "ban đêm", "tối"]);

    $sky = $night ? "#10172c" : "#87ceeb";
    $ground = "#65a765";

    if (has($text, ["biển", "đại dương"])) {
        $ground = "#168bc2";
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg"
    width="800" height="600" viewBox="0 0 800 600">';

    // Background
    $svg .= '<rect width="800" height="600" fill="'.$sky.'"/>';

    // ==========================================
    // SUN / MOON / STARS
    // ==========================================

    if ($night) {

        $svg .= '<circle cx="650" cy="110" r="48"
        fill="#fff1b8"/>';

        for ($i = 0; $i < 18; $i++) {
            $x = 30 + (($i * 137) % 740);
            $y = 25 + (($i * 71) % 260);

            $svg .= '<circle cx="'.$x.'" cy="'.$y.'"
            r="3" fill="white"/>';
        }

    } else {

        $svg .= '<circle cx="660" cy="100" r="55"
        fill="#ffdb4d"/>';
    }

    // ==========================================
    // CLOUDS
    // ==========================================

    if (!$night || has($text, ["mây", "nhiều mây"])) {

        $svg .= '
        <g fill="white">
            <ellipse cx="150" cy="130" rx="60" ry="22"/>
            <circle cx="145" cy="110" r="30"/>
            <circle cx="190" cy="105" r="38"/>
            <ellipse cx="220" cy="130" rx="40" ry="20"/>
        </g>';
    }

    // ==========================================
    // MOUNTAINS
    // ==========================================

    if (has($text, ["núi", "đồi", "mountain"])) {

        $svg .= '
        <path d="M0 430 L200 150 L400 430 Z"
        fill="#64748b"/>

        <path d="M200 150 L160 220 L200 205 L245 225 Z"
        fill="#ffffff"/>

        <path d="M300 430 L560 130 L800 430 Z"
        fill="#475569"/>

        <path d="M560 130 L510 200 L560 185 L600 220 Z"
        fill="#ffffff"/>';
    }

    // ==========================================
    // GROUND
    // ==========================================

    $svg .= '<rect x="0" y="430" width="800"
    height="170" fill="'.$ground.'"/>';

    // ==========================================
    // HOUSE ENGINE
    // ==========================================

    if (has($text, ["nhà", "ngôi nhà", "house"])) {

        $svg .= '
        <rect x="270" y="285" width="250" height="180"
        fill="'.$color.'" stroke="#263238" stroke-width="5"/>

        <path d="M240 295 L395 155 L550 295 Z"
        fill="#a34c35" stroke="#263238" stroke-width="5"/>

        <rect x="365" y="355" width="65" height="110"
        fill="#754c24"/>

        <rect x="290" y="320" width="55" height="55"
        fill="#b9e7ff"/>

        <rect x="455" y="320" width="45" height="55"
        fill="#b9e7ff"/>';
    }

    // ==========================================
    // TREE ENGINE 1.2
    // ==========================================

    if (has($text, ["cây", "rừng", "tree"])) {

        $leafColor = "#16803d";

        if (has($text, ["lá đỏ", "cây đỏ"])) {
            $leafColor = "#ef4444";
        } elseif (has($text, ["lá vàng", "cây vàng"])) {
            $leafColor = "#eab308";
        } elseif (has($text, ["lá tím", "cây tím"])) {
            $leafColor = "#9333ea";
        } elseif (has($text, ["lá xanh dương"])) {
            $leafColor = "#2563eb";
        } elseif (has($text, ["lá hồng"])) {
            $leafColor = "#ec4899";
        }

        // Trunk color
        $trunkColor = "#754c24";

        if (has($text, ["thân cây đỏ"])) {
            $trunkColor = "#ef4444";
        } elseif (has($text, ["thân cây đen"])) {
            $trunkColor = "#111827";
        } elseif (has($text, ["thân cây trắng"])) {
            $trunkColor = "#ffffff";
        }

        // Tree position
        $treeX = 650;

        if (has($text, ["bên trái", "phía trái"])) {
            $treeX = 120;
        } elseif (has($text, ["ở giữa", "chính giữa"])) {
            $treeX = 400;
        } elseif (has($text, ["bên phải", "phía phải"])) {
            $treeX = 680;
        }

        // Tree count
        $treeCount = 1;

        if (preg_match('/(\d+)\s*cây/u', $text, $m)) {
            $treeCount = (int)$m[1];
        }

        $treeCount = max(1, min(10, $treeCount));

        for ($i = 0; $i < $treeCount; $i++) {

            $x = $treeX + ($i - ($treeCount - 1) / 2) * 75;

            $svg .= '
            <g>
                <rect x="'.($x - 12).'" y="335"
                width="24" height="110"
                fill="'.$trunkColor.'"/>

                <circle cx="'.$x.'" cy="310"
                r="48" fill="'.$leafColor.'"/>

                <circle cx="'.($x - 30).'" cy="325"
                r="30" fill="'.$leafColor.'"/>

                <circle cx="'.($x + 30).'" cy="325"
                r="30" fill="'.$leafColor.'"/>
            </g>';
        }
    }

    // ==========================================
    // ROBOT ENGINE
    // ==========================================

    if (has($text, ["robot", "người máy"])) {

        $svg .= '
        <rect x="320" y="230" width="160" height="180"
        rx="25" fill="'.$color.'" stroke="#222"
        stroke-width="7"/>

        <circle cx="365" cy="290" r="15" fill="#111"/>
        <circle cx="435" cy="290" r="15" fill="#111"/>

        <rect x="365" y="335" width="70" height="12"
        rx="6" fill="#111"/>

        <line x1="400" y1="230" x2="400" y2="170"
        stroke="#222" stroke-width="8"/>

        <circle cx="400" cy="160" r="15"
        fill="#ef4444"/>';
    }

    // ==========================================
    // MULTI-STICKMAN ENGINE 1.2
    // ==========================================

    if (has($text, ["người que", "stickman"])) {

        // Count
        $count = 1;

        if (preg_match('/(\d+)\s*người que/u', $text, $m)) {
            $count = (int)$m[1];
        } elseif (has($text, ["hai người que"])) {
            $count = 2;
        } elseif (has($text, ["ba người que"])) {
            $count = 3;
        } elseif (has($text, ["năm người que"])) {
            $count = 5;
        }

        $count = max(1, min(10, $count));

        // Stickman color
        $stickColor = "#111827";

        $stickColors = [
            "người que màu đỏ" => "#ef4444",
            "người que màu xanh lá" => "#16a34a",
            "người que màu xanh" => "#2563eb",
            "người que màu vàng" => "#eab308",
            "người que màu tím" => "#9333ea",
            "người que màu trắng" => "#ffffff",
            "người que màu đen" => "#111827"
        ];

        foreach ($stickColors as $word => $value) {
            if (mb_strpos($text, $word) !== false) {
                $stickColor = $value;
                break;
            }
        }

        // Pose
        $pose = "stand";

        if (has($text, ["nằm sấp"])) {
            $pose = "prone";
        } elseif (has($text, ["nằm nghiêng"])) {
            $pose = "side";
        } elseif (has($text, ["nằm ngửa", "nằm xuống", "nằm"])) {
            $pose = "lying";
        } elseif (has($text, ["ngồi"])) {
            $pose = "sit";
        } elseif (has($text, ["chạy"])) {
            $pose = "run";
        } elseif (has($text, ["nhảy"])) {
            $pose = "jump";
        } elseif (has($text, ["đi bộ"])) {
            $pose = "walk";
        } elseif (has($text, ["vẫy tay"])) {
            $pose = "wave";
        } elseif (has($text, ["giơ tay"])) {
            $pose = "handsup";
        }

        // Positions
        $spacing = 700 / ($count + 1);

        for ($i = 0; $i < $count; $i++) {

            $x = 50 + $spacing * ($i + 1);
            $y = 365;

            $svg .= '<g transform="translate('.$x.' '.$y.') scale(0.55)"
            fill="none" stroke="'.$stickColor.'"
            stroke-width="9" stroke-linecap="round"
            stroke-linejoin="round">';

            // STAND
            if ($pose === "stand") {

                $svg .= '
                <circle cx="0" cy="-150" r="30"/>
                <path d="
                M0 -120 L0 0
                M0 -90 L-55 -35
                M0 -90 L55 -35
                M0 0 L-45 100
                M0 0 L45 100"/>';
            }

            // LYING
            elseif ($pose === "lying") {

                $svg .= '
                <circle cx="-145" cy="0" r="30"/>
                <path d="
                M-115 0 L110 0
                M-30 0 L-70 -55
                M-30 0 L20 -55
                M110 0 L175 -55
                M110 0 L175 55"/>';
            }

            // PRONE
            elseif ($pose === "prone") {

                $svg .= '
                <circle cx="-145" cy="0" r="30"/>
                <path d="
                M-115 0 L110 0
                M-30 0 L-70 55
                M-30 0 L20 55
                M110 0 L175 -55
                M110 0 L175 55"/>';
            }

            // SIDE
            elseif ($pose === "side") {

                $svg .= '
                <circle cx="-100" cy="0" r="30"/>
                <path d="
                M-70 0 L110 0
                M0 0 L-30 -50
                M0 0 L30 -50
                M110 0 L165 -45
                M110 0 L165 45"/>';
            }

            // SIT
            elseif ($pose === "sit") {

                $svg .= '
                <circle cx="0" cy="-150" r="30"/>
                <path d="
                M0 -120 L0 -30
                L80 -30 L120 65
                M0 -30 L-65 65
                M0 -90 L-55 -40
                M0 -90 L55 -40"/>';
            }

            // RUN
            elseif ($pose === "run") {

                $svg .= '
                <circle cx="0" cy="-160" r="30"/>
                <path d="
                M0 -130 L15 -10
                M5 -90 L-75 -145
                M5 -90 L85 -150
                M15 -10 L-80 80
                M15 -10 L115 65"/>';
            }

            // JUMP
            elseif ($pose === "jump") {

                $svg .= '
                <circle cx="0" cy="-180" r="30"/>
                <path d="
                M0 -150 L0 -30
                M0 -115 L-80 -210
                M0 -115 L80 -210
                M0 -30 L-75 50
                M0 -30 L75 50"/>';
            }

            // WALK
            elseif ($pose === "walk") {

                $svg .= '
                <circle cx="0" cy="-150" r="30"/>
                <path d="
                M0 -120 L0 0
                M0 -90 L-55 -35
                M0 -90 L55 -55
                M0 0 L-75 90
                M0 0 L65 75"/>';
            }

            // WAVE
            elseif ($pose === "wave") {

                $svg .= '
                <circle cx="0" cy="-150" r="30"/>
                <path d="
                M0 -120 L0 0
                M0 -90 L-55 -35
                M0 -90 L65 -180
                M65 -180 L90 -205
                M0 0 L-45 100
                M0 0 L45 100"/>';
            }

            // HANDS UP
            elseif ($pose === "handsup") {

                $svg .= '
                <circle cx="0" cy="-150" r="30"/>
                <path d="
                M0 -120 L0 0
                M0 -90 L-75 -180
                M0 -90 L75 -180
                M0 0 L-45 100
                M0 0 L45 100"/>';
            }

            $svg .= '</g>';
        }
    }

    // ==========================================
    // FLOWERS
    // ==========================================

    if (has($text, ["hoa", "flower"])) {

        foreach ([150, 230, 570, 680] as $x) {

            $svg .= '
            <line x1="'.$x.'" y1="510" x2="'.$x.'" y2="470"
            stroke="#16803d" stroke-width="5"/>

            <circle cx="'.$x.'" cy="465" r="13"
            fill="#ec4899"/>

            <circle cx="'.$x.'" cy="465" r="5"
            fill="#facc15"/>';
        }
    }

    // ==========================================
    // IMAGE LABEL
    // ==========================================

    $svg .= '<text x="400" y="570"
    text-anchor="middle" font-size="17"
    fill="'.($night ? '#ffffff' : '#263238').'"
    font-family="Arial">CODEBRAIN IMAGE AI 1.2</text>';

    $svg .= '</svg>';

    return $svg;
}

// ============================================
// FORM PROCESSING
// ============================================

$error = "";
$image = "";
$prompt = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $prompt = trim($_POST["prompt"] ?? "");

    if ($prompt === "") {

        $error = "Hãy nhập mô tả hình ảnh!";

    } elseif (mb_strlen($prompt, "UTF-8") > 300) {

        $error = "Mô tả tối đa 300 ký tự!";

    } else {

        $image = generateImage($prompt);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>CODEBRAIN IMAGE AI 1.2</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 20px;
    background: #10131c;
    color: white;
    font-family: Arial, sans-serif;
}

header {
    text-align: center;
    margin: 20px 0 35px;
}

header h1 {
    color: #65aaff;
    letter-spacing: 2px;
}

header p {
    color: #8798b5;
}

.panel, .result {
    max-width: 800px;
    margin: 18px auto;
    padding: 22px;
    background: #1c2230;
    border-radius: 15px;
}

textarea {
    width: 100%;
    height: 120px;
    padding: 15px;
    border-radius: 10px;
    border: 1px solid #40516c;
    background: #101722;
    color: white;
    font-size: 16px;
    resize: vertical;
}

button {
    width: 100%;
    padding: 16px;
    margin-top: 15px;
    background: #2879ed;
    border: 0;
    border-radius: 10px;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

button:active {
    background: #1556b5;
}

.preview {
    background: #101722;
    border-radius: 12px;
    padding: 10px;
    overflow: hidden;
}

.preview svg {
    width: 100%;
    height: auto;
    display: block;
}

.download {
    display: block;
    padding: 14px;
    text-align: center;
    background: #2879ed;
    color: white;
    text-decoration: none;
    border-radius: 10px;
    margin-top: 15px;
}

.error {
    color: #ff7777;
}

.info {
    color: #9baac1;
    font-size: 13px;
}
</style>
</head>

<body>

<header>
    <h1>CODEBRAIN IMAGE</h1>
    <p>PHP SVG GENERATOR 1.2</p>
</header>

<div class="panel">

    <h2>✦ Image Generator</h2>

    <form method="POST">

        <textarea
            name="prompt"
            maxlength="300"
            placeholder="Ví dụ: 3 người que đang chạy, một ngôi nhà màu đỏ và 2 cây..."
            required><?= h($prompt) ?></textarea>

        <button type="submit">
            ✦ GENERATE IMAGE
        </button>

    </form>

    <p class="info">
        PHP Engine | SVG Renderer | Offline | No API
    </p>

    <?php if ($error): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

</div>

<?php if ($image): ?>

<div class="result">

    <h2>✦ Generated Image</h2>

    <div class="preview">
        <?= $image ?>
    </div>

    <a class="download"
       download="codebrain-image.svg"
       href="data:image/svg+xml;charset=utf-8,<?= rawurlencode($image) ?>">
        ↓ DOWNLOAD SVG
    </a>

</div>

<?php endif; ?>

</body>
</html>
