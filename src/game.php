<?php
require_once __DIR__ . '/db.php';

function exp_next(int $level): int {
    return (int) floor(100 * pow($level, 1.7));
}

function get_vocation(int $id): array {
    $st = db()->prepare('SELECT * FROM vocations WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch();
}

function equipped_items(int $charId): array {
    $st = db()->prepare(
        'SELECT i.* FROM character_items ci JOIN items i ON i.id = ci.item_id
         WHERE ci.character_id = ? AND ci.equipped = 1'
    );
    $st->execute([$charId]);
    return $st->fetchAll();
}

function char_stats(array $char): array {
    $voc = get_vocation((int) $char['vocation_id']);
    $level = (int) $char['level'];
    $hpMax = $voc['hp_base'] + ($level - 1) * $voc['hp_pl'];
    $atk = $voc['atk_base'] + ($level - 1) * $voc['atk_pl'];
    $def = $voc['def_base'] + ($level - 1) * $voc['def_pl'];
    foreach (equipped_items((int) $char['id']) as $item) {
        $atk += (int) $item['attack'];
        $def += (int) $item['defense'];
    }
    return ['hp_max' => $hpMax, 'atk' => $atk, 'def' => $def, 'regen' => (int) $voc['regen'], 'vocation' => $voc];
}

function add_log(int $charId, string $message): void {
    $db = db();
    $st = $db->prepare('INSERT INTO hunt_log (character_id, message) VALUES (?, ?)');
    $st->execute([$charId, $message]);
    $keep = LOG_KEEP;
    $db->prepare(
        "DELETE FROM hunt_log WHERE character_id = ? AND id NOT IN
         (SELECT id FROM (SELECT id FROM hunt_log WHERE character_id = ? ORDER BY id DESC LIMIT $keep) t)"
    )->execute([$charId, $charId]);
}

function area_monsters(int $areaId): array {
    $st = db()->prepare(
        'SELECT m.*, am.weight FROM area_monsters am JOIN monsters m ON m.id = am.monster_id
         WHERE am.area_id = ?'
    );
    $st->execute([$areaId]);
    return $st->fetchAll();
}

function pick_monster(array $monsters): array {
    $total = array_sum(array_column($monsters, 'weight'));
    $roll = random_int(1, $total);
    foreach ($monsters as $m) {
        $roll -= (int) $m['weight'];
        if ($roll <= 0) {
            return $m;
        }
    }
    return $monsters[0];
}

function loot_for(int $monsterId): array {
    $st = db()->prepare(
        'SELECT i.*, ml.chance FROM monster_loot ml JOIN items i ON i.id = ml.item_id
         WHERE ml.monster_id = ?'
    );
    $st->execute([$monsterId]);
    return $st->fetchAll();
}
