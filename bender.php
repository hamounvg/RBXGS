<?php
// made by Hamoun and mp3
// uses js database
session_start();

$ip = "127.0.0.1";
$port = 64989;
$dbPath = __DIR__ . '/data/users.json';
$thumbDir = __DIR__ . '/thumbs/';
$baseUrl = "http://localhost";  

if (!is_dir(dirname($dbPath))) mkdir(dirname($dbPath), 0777, true);
if (!is_dir($thumbDir)) mkdir($thumbDir, 0777, true);

function getHexFromRobloxId($id) {
    $colors = [
        1 => '#F2F3F3', 208 => '#E5E4DF', 194 => '#A3A2A5', 199 => '#635F62',
        26 => '#1B2A35', 21 => '#C4281C', 24 => '#F5CD30', 226 => '#FDEA8D',
        23 => '#0D69AC', 107 => '#008F9C', 102 => '#6E99CA', 11 => '#80BBDB',
        45 => '#B4D2E4', 135 => '#74869D', 106 => '#DA8541', 105 => '#E29B40',
        141 => '#27462D', 28 => '#287F47', 37 => '#4B974B', 119 => '#A4BD47',
        29 => '#A1C48C', 210 => '#789082', 38 => '#A05F35', 192 => '#694028',
        104 => '#6B327C', 9 => '#E8BAC8', 101 => '#DA867A', 5 => '#D7C59A',
        153 => '#957977', 217 => '#7C5C46', 18 => '#CC8E69', 125 => '#EAB892'
    ];
    return isset($colors[$id]) ? $colors[$id] : '#CCCCCC';
}

if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $_SESSION['userId'] = (int)$_POST['userId'];
    header("Location: bender.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: bender.php");
    exit;
}

$loggedInId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
$editId = isset($_REQUEST['editId']) ? (int)$_REQUEST['editId'] : ($loggedInId ?? 1);

$db = file_exists($dbPath) ? json_decode(file_get_contents($dbPath), true) : [];

$defaults = [
    "username" => "Player", 
    "headColor" => 24,  "torsoColor" => 23, "lArmColor" => 24, 
    "rArmColor" => 24,  "lLegColor" => 119, "rLegColor" => 119,
    "tshirt" => 0, "face" => 0,
    "tmode" => "proxy", "fmode" => "proxy", "hatid" => 0, "hatmode" => "proxy"
];

$userData = isset($db[$editId]) ? array_merge($defaults, $db[$editId]) : $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    header('Content-Type: application/json');
    
    $targetId = (int)$_POST['userId'];

    $newData = [
        "username" => $_POST['username'] ?? "Player",
        "headColor" => (int)$_POST['headColor'], "torsoColor" => (int)$_POST['torsoColor'],
        "lArmColor" => (int)$_POST['lArmColor'], "rArmColor" => (int)$_POST['rArmColor'],
        "lLegColor" => (int)$_POST['lLegColor'], "rLegColor" => (int)$_POST['rLegColor'],
        "tshirt" => $_POST['tshirt'], "tmode" => $_POST['tmode'],
        "face" => $_POST['face'], "fmode" => $_POST['fmode'], "hatid" => $_POST['hat'], "hatmode" => $_POST['hatid']
    ];
    
    $db[$targetId] = $newData;
    file_put_contents($dbPath, json_encode($db, JSON_PRETTY_PRINT));

    $faceUrl = "";
    if (!empty($newData['face']) && $newData['face'] != "0") {
        if ($newData['fmode'] == 'direct') $faceUrl = $newData['face'];
        else $faceUrl = $baseUrl . "/api/AssetProxy.php?faceid=" . (int)$newData['face'];
    }
    $appString = $baseUrl . "/api/BodyColors.php?userId=" . $targetId; 
    
    if (!empty($newData['tshirt']) && $newData['tshirt'] != "0") {
        if ($newData['tmode'] == 'direct') $appString .= ";" . $newData['tshirt'];
        else $appString .= ";" . $baseUrl . "/api/BodyColors.php?id=" . $newData['tshirt'];
    }
    
    if (!empty($newData['hatid']) && $newData['hatid'] != "0") {
        if ($newData['hatmode'] == 'direct') $appString .= ";" . $newData['hatid'];
        else $appString .= ";" . $baseUrl . "/api/Hat.php?hat=" . $newData['hatid'];
    }
$_SESSION['tshirt'] = $newData['tshirt'];
    $xml_open = '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" xmlns:tns="urn:Roblox"><soap:Body><tns:OpenEnvironment/></soap:Body></soap:Envelope>';
    
    $ch = curl_init("http://$ip:$port");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "SOAPAction: OpenEnvironment"));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_open);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); 
    $response = curl_exec($ch);
    
    preg_match('/<return>(.*?)<\/return>/', $response, $matches);
    $envID = $matches[1] ?? null;

    if ($envID) {
        $luaCode = 'pcall(function() game:GetService("ContentProvider"):SetBaseUrl("'.$baseUrl.'/") end) ';
        $luaCode .= 'pcall(function() game.Players:CreateLocalPlayer(0) end) ';
        $luaCode .= 'local plr = game.Players.LocalPlayer ';
        $luaCode .= 'plr.CharacterAppearance = "' . $appString . '" ';
        $luaCode .= 'plr:LoadCharacter() ';
       
       
        $luaCode .= 'local bc = plr.Character:FindFirstChild("Body Colors") or Instance.new("BodyColors", plr.Character) ';
        $luaCode .= 'bc.HeadColor = BrickColor.new(' . $newData['headColor'] . ') ';
        $luaCode .= 'bc.TorsoColor = BrickColor.new(' . $newData['torsoColor'] . ') ';
        $luaCode .= 'bc.LeftArmColor = BrickColor.new(' . $newData['lArmColor'] . ') ';
        $luaCode .= 'bc.RightArmColor = BrickColor.new(' . $newData['rArmColor'] . ') ';
        $luaCode .= 'bc.LeftLegColor = BrickColor.new(' . $newData['lLegColor'] . ') ';
        $luaCode .= 'bc.RightLegColor = BrickColor.new(' . $newData['rLegColor'] . ') ';

        if ($faceUrl !== "") {
            $luaCode .= 'local head = plr.Character:FindFirstChild("Head") ';
            $luaCode .= 'if head then ';
            $luaCode .= 'local existing = head:FindFirstChild("face") if existing then existing:Remove() end ';
            $luaCode .= 'local newFace = Instance.new("Decal") ';
            $luaCode .= 'newFace.Name = "face" ';
            $luaCode .= 'newFace.Face = 5 '; 
            $luaCode .= 'newFace.Texture = "' . $faceUrl . '" ';
            $luaCode .= 'newFace.Parent = head ';
            $luaCode .= 'end ';
        }

        $luaCode .= 'return game:GetService("ThumbnailGenerator"):Click("PNG", 420, 420, true)';
        
        $xml_exec = '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" xmlns:tns="urn:Roblox"><soap:Body><tns:Execute><tns:environmentID>' . $envID . '</tns:environmentID><tns:script xsi:type="xsd:string">' . htmlspecialchars($luaCode) . '</tns:script></tns:Execute></soap:Body></soap:Envelope>';
        $xml_exec = str_replace(["\r", "\n", "  "], "", $xml_exec);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_exec);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "SOAPAction: Execute"));
        $res_exec = curl_exec($ch);
        
        preg_match('/(iVBOR\w+.*?)<\//', $res_exec, $imgMatches);
        if (isset($imgMatches[1])) {
            file_put_contents($thumbDir . $targetId . ".png", base64_decode($imgMatches[1]));
            echo json_encode(["success" => true, "msg" => "Updated.", "username" => $newData['username']]);
        } else {
            echo json_encode(["success" => false, "msg" => "GCC Could not render the avatar, try again."]);
        }
    } else {
        echo json_encode(["success" => false, "msg" => "Either GCC failed, isn't open, or there's a problem with the code. Try checking your taskbar to see if it's open, and if it is, check the response it gives. But if not, open it."]);
    }
    curl_close($ch);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div id="Body">
    <div id="Header">
        <div style="font-size:16px; font-weight:bold;">Dashboard</div>
        <div style="display:flex; align-items:center; gap:10px;">
            <?php if($loggedInId): ?>
                <span id="welcomeText">haiii <b><?php echo htmlspecialchars($userData['username']); ?></b></span>
                <a class="Button PlayButton" href="http://localhost/api/GenerateTicket.php" target="_blank">Play</a>
                <a class="Button" href="javascript:toggleTheme()">Theme</a>
                <a class="Button" href="?logout=1">Logout</a>
            <?php else: ?>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="login">
                    User ID: <input type="number" name="userId" value="1" style="width:50px;">
                    <button class="Button">Login</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <form id="avatarForm">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="userId" value="<?php echo $editId; ?>">
        
        <input type="hidden" id="val_head" name="headColor" value="<?php echo $userData['headColor']; ?>">
        <input type="hidden" id="val_torso" name="torsoColor" value="<?php echo $userData['torsoColor']; ?>">
        <input type="hidden" id="val_larm" name="lArmColor" value="<?php echo $userData['lArmColor']; ?>">
        <input type="hidden" id="val_rarm" name="rArmColor" value="<?php echo $userData['rArmColor']; ?>">
        <input type="hidden" id="val_lleg" name="lLegColor" value="<?php echo $userData['lLegColor']; ?>">
        <input type="hidden" id="val_rleg" name="rLegColor" value="<?php echo $userData['rLegColor']; ?>">

        <div id="left">
            <table cellspacing="0">
                <tr><th class="tablehead">Wardrobe</th></tr>
                <tr>
                    <td class="tablebody">
                        <fieldset>
                            <legend>Profile</legend>
                            <div class="input-group">
                                <label>Username:</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" class="auto-save">
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend>Wearables</legend>
                            <div class="input-group">
                                <label>T-Shirt ID:</label>
                                <input type="text" name="tshirt" value="<?php echo $userData['tshirt']; ?>" class="auto-save">
                                <select name="tmode" class="auto-save">
                                    <option value="proxy" <?php if($userData['tmode']=='proxy') echo 'selected';?>>ID</option>
                                    <option value="direct" <?php if($userData['tmode']=='direct') echo 'selected';?>>URL</option>
                                </select>
                            </div> 
                            <div class="input-group">
                                <label>Hat ID:</label>
                                <input type="text" name="hat" value="<?php echo $userData['hatid']; ?>" class="auto-save">
                                <select name="hatid" class="auto-save">
                                    <option value="proxy" <?php if($userData['hatmode']=='proxy') echo 'selected';?>>ID</option>
                                 
                                </select>
                            </div>
                            <div class="input-group">
                                <label>Face ID:</label>
                                <input type="text" name="face" value="<?php echo $userData['face']; ?>" class="auto-save">
                                <select name="fmode" class="auto-save">
                                    <option value="proxy" <?php if($userData['fmode']=='proxy') echo 'selected';?>>ID</option>
                                    <option value="direct" <?php if($userData['fmode']=='direct') echo 'selected';?>>URL</option>
                                </select>
                            </div>
                        </fieldset>
                        
                        <div style="font-size:10px; color:#888;">* Changes apply automatically.</div>
                    </td>
                </tr>
            </table>

            <br>
            <table cellspacing="0">
                <tr><th class="tablehead">Information</th></tr>
                <tr>
                    <td class="tablebody" style="text-align:center; height:100px; color:#666;">
                        <i>Character Customization</i>
                        <i></br></i>
                        <i>I added Hats recently, there are 8 of them currently</i>
                        <i></br></i>
                        <i>You can also wear tshirts, that's it lol</i>
                    </td>
                </tr>
            </table>
        </div>

        <div id="right">
            <table cellspacing="0">
                <tr><th class="tablehead">My Character</th></tr>
                <tr>
                    <td class="tablebody" style="text-align:center; position:relative;">
                        <div id="loadingOverlay">Redrawing...</div>
                        <img id="charRender" src="/thumbs/<?php echo $editId; ?>.png?r=<?php echo rand(); ?>" width="280" height="280" alt="Character">
                        <br>
                        <a onclick="forceRedraw()" style="font-size:10px;">[ Redraw ]</a>
                    </td>
                </tr>
            </table>

            <br>

            <table cellspacing="0">
                <tr><th class="tablehead">Color Chooser</th></tr>
                <tr>
                    <td class="tablebody">
                        <div style="margin-bottom:10px;">Click a body part to change its color:</div>
                        
                        <div class="mannequin-container">
                            <div class="clickable" id="btn_head" onclick="openColorPanel('head', this)" 
                                 style="background-color: <?php echo getHexFromRobloxId($userData['headColor']); ?>;"></div>
                            <div class="seperator"></div>
                            
                            <div class="clickable2" id="btn_rarm" onclick="openColorPanel('rarm', this)"
                                 style="background-color: <?php echo getHexFromRobloxId($userData['rArmColor']); ?>;"></div>
                            <div class="clickable3" id="btn_torso" onclick="openColorPanel('torso', this)"
                                 style="background-color: <?php echo getHexFromRobloxId($userData['torsoColor']); ?>;"></div>
                            <div class="clickable2" id="btn_larm" onclick="openColorPanel('larm', this)"
                                 style="background-color: <?php echo getHexFromRobloxId($userData['lArmColor']); ?>;"></div>
                            <div class="seperator"></div>
                            
                            <div class="clickable2" id="btn_rleg" onclick="openColorPanel('rleg', this)"
                                 style="background-color: <?php echo getHexFromRobloxId($userData['rLegColor']); ?>;"></div>
                            <div class="clickable2" id="btn_lleg" onclick="openColorPanel('lleg', this)"
                                 style="background-color: <?php echo getHexFromRobloxId($userData['lLegColor']); ?>;"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>
    </form>
</div>

<div id="colorPanel" class="popupControl">
    <table cellspacing="0" style="border:0;">
        <?php
        $palette = [
            [1, 208, 194, 199, 26, 21, 24, 226],
            [23, 107, 102, 11, 45, 135, 106, 105],
            [141, 28, 37, 119, 29, 210, 38, 192],
            [104, 9, 101, 5, 153, 217, 18, 125]
        ];
        
        foreach ($palette as $row) {
            echo "<tr>";
            foreach ($row as $cid) {
                $hex = getHexFromRobloxId($cid);
                echo "<td><div class='ColorPickerItem' style='background-color:$hex;' onclick='selectColor($cid, \"$hex\")' title='ID: $cid'></div></td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
</div>

<script>
    function toggleTheme() {
        const body = document.body;
        const current = body.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        body.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    }
    if(localStorage.getItem('theme') === 'dark') document.body.setAttribute('data-theme', 'dark');

    let saveTimeout;
    const form = document.getElementById('avatarForm');
    const inputs = document.querySelectorAll('.auto-save');

    function triggerSave() {
        clearTimeout(saveTimeout);
        document.getElementById('loadingOverlay').style.display = 'block';
        saveTimeout = setTimeout(submitData, 1000);
    }

    inputs.forEach(input => {
        input.addEventListener('input', triggerSave);
        input.addEventListener('change', triggerSave);
    });

    function forceRedraw() {
        triggerSave();
    }

    function submitData() {
        const formData = new FormData(form);
        fetch('bender.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            document.getElementById('loadingOverlay').style.display = 'none';
            if (data.success) {
                const img = document.getElementById('charRender');
                img.src = img.src.split('?')[0] + '?r=' + new Date().getTime();
                if(data.username) {
                    document.getElementById('welcomeText').innerHTML = "Hello, <b>" + data.username + "</b>";
                }
            } else {
                alert("Render Failed: " + data.msg);
            }
        })
        .catch(e => {
            document.getElementById('loadingOverlay').style.display = 'none';
            // alert("Connection Error");
        });
    }

    let currentPart = null;
    const colorPanel = document.getElementById('colorPanel');

    function openColorPanel(partName, element) {
        currentPart = partName; 
        const rect = element.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        colorPanel.style.display = 'block';
        colorPanel.style.top = (rect.top + scrollTop) + 'px';
        colorPanel.style.left = (rect.right + scrollLeft + 5) + 'px';
    }

    function selectColor(colorId, hexColor) {
        if(!currentPart) return;
        document.getElementById('val_' + currentPart).value = colorId;
        document.getElementById('btn_' + currentPart).style.backgroundColor = hexColor;
        colorPanel.style.display = 'none';
        triggerSave();
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.clickable') && 
            !e.target.closest('.clickable2') && 
            !e.target.closest('.clickable3') && 
            !e.target.closest('.popupControl')) {
            colorPanel.style.display = 'none';
        }
    });
</script>

</body>

</html>
