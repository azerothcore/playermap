<?php

require_once("defines.php");
require_once("pomm_conf.php");
require_once("func.php");
require_once("map_english.php");

$_RESULT = [];

$maps_count = isset($lang_defs['maps_names']) ? count($lang_defs['maps_names']) : 0;

$Horde_races = 0x2B2;
$Alliance_races = 0x44D;
$outland_inst   = [540,542,543,544,545,546,547,548,550,552,553,554,555,556,557,558,559,562,564,565];
$northrend_inst = [533,574,575,576,578,599,600,601,602,603,604,608,615,616,617,619,624,631,632,649,650,658,668,724];

require_once "libs/js/JsHttpRequest/Php.php";
$JsHttpRequest = new Subsys_JsHttpRequest_Php("utf-8");

$realm_db = new DBLayer($hostr, $userr, $passwordr, $dbr);
if (!$realm_db->isValid()) {
    $_RESULT['status']['online'] = 2;
    exit();
}
$realm_db->query("SET NAMES $database_encoding");

$gm_online = 0;
$gm_accounts = [];

$query = null;
if ($server_type == 1) { // if AzerothCore/TrinityCore
    $query = $realm_db->query("SELECT GROUP_CONCAT(`id` SEPARATOR ' ') FROM `account_access` WHERE `gmlevel` > 0");
} else {
    $query = $realm_db->query("SELECT GROUP_CONCAT(`id` SEPARATOR ' ') FROM `account` WHERE `gmlevel` > 0");
}

if ($query && ($result = $realm_db->fetch_row($query))) {
    $gm_accounts = explode(' ', $result[0]);
}

$groups = [];
$characters_db = new DBLayer($host, $user, $password, $db);
if (!$characters_db->isValid()) {
    $_RESULT['status']['online'] = 2;
    exit();
}
$characters_db->query("SET NAMES $database_encoding");
$query = $characters_db->query("
    SELECT gm.`memberGuid`, g.`leaderGuid`
    FROM `group_member` gm
    JOIN `groups` g ON g.`guid` = gm.`guid`
    WHERE gm.`memberGuid` IN (
        SELECT `guid` 
        FROM `characters` 
        WHERE `online` = 1
    )
");

if ($query) {
    while ($result = $characters_db->fetch_assoc($query)) {
        $groups[$result['memberGuid']] = $result['leaderGuid'];
    }
}

$Count = array_fill(0, $maps_count, [0, 0]); // Default array for Count initialization
$arr = [];
$i = $maps_count;

$query = $characters_db->query("SELECT `account`, `name`, `class`, `race`, `level`, `gender`, `position_x`, `position_y`, `map`, `zone`, `extra_flags` FROM `characters` WHERE `online` = 1 ORDER BY `name`");

while ($result = $characters_db->fetch_assoc($query)) {
    $Extention = 0;
    if (($result['map'] == 530 && $result['position_y'] > -1000) || in_array($result['map'], $outland_inst)) {
        $Extention = 1;
    } elseif ($result['map'] == 571 || in_array($result['map'], $northrend_inst)) {
        $Extention = 2;
    }

    $gm_player = false;
    $show_player = true;
    if (in_array($result['account'], $gm_accounts)) {
        $gm_player = true;
        $show_player = false;
        if ($gm_show_online == 1) {
            $show_player = true;
            if (($result['extra_flags'] & 0x1) != 0 && $gm_show_online_only_gmoff == 1) {
                $show_player = false;
            }
            if (($result['extra_flags'] & 0x10) != 0 && $gm_show_online_only_gmvisible == 1) {
                $show_player = false;
            }
            if ($gm_add_suffix && $show_player) {
                $result['name'] .= ' <small style="color: #EABA28;">{GM}</small>';
            }
        }
    }

    if (!$gm_player || ($gm_player && $gm_include_online == 1)) {
        if ($Horde_races & (0x1 << ($result['race'] - 1))) {
            $Count[$Extention][1]++;
        } elseif ($Alliance_races & (0x1 << ($result['race'] - 1))) {
            $Count[$Extention][0]++;
        }
    }

    if ($gm_player && !$show_player) {
        continue;
    }

    $char_data = 0;
    $char_dead = ($char_data & 0x11) ? 1 : 0;
    $arr[$i] = [
        'x' => $result['position_x'],
        'y' => $result['position_y'],
        'dead' => $char_dead,
        'name' => $result['name'],
        'map' => $result['map'],
        'zone' => get_zone_name($result['zone']),
        'cl' => $result['class'],
        'race' => $result['race'],
        'level' => $result['level'],
        'gender' => $result['gender'],
        'Extention' => $Extention,
        'leaderGuid' => $groups[$char_data] ?? 0
    ];
    $i++;
}

$characters_db->close();
unset($characters_db);

if (empty($arr) && !test_realm()) {
    $res['online'] = null;
} else {
    usort($arr, "sort_players");
    $arr = array_merge($Count, $arr);
    $res['online'] = $arr;
}

if ($show_status) {
    $query = $realm_db->query("SELECT UNIX_TIMESTAMP(), `starttime`, `maxplayers` FROM `uptime` WHERE `starttime` = (SELECT MAX(`starttime`) FROM `uptime`)");
    if ($result = $realm_db->fetch_row($query)) {
        $status = [
            'online' => test_realm() ? 1 : 0,
            'uptime' => $result[0] - $result[1],
            'maxplayers' => $result[2],
            'gmonline' => $gm_online
        ];
    } else {
        $status = null;
    }
} else {
    $status = null;
}

$realm_db->close();
unset($realm_db);

$res['status'] = $status;

$_RESULT = $res;
