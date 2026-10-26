<?php
require_once __DIR__ . '/game.php';

function process_char(array $char): array {
    if (empty($char['last_tick_at'])) {
        return $char;
    }
    $ticks = (int) floor((time() - strtotime($char['last_tick_at'])) / TICK_SECONDS);
    if ($ticks <= 0) {
        return $char;
    }
    $ticks = min($ticks, (int) (OFFLINE_CAP_SECONDS / TICK_SECONDS));

    $hunting = $char['area_id'] !== null;
    $stats = char_stats($char);
    $monsters = $hunting ? area_monsters((int) $char['area_id']) : [];
    $lootCache = [];

    $level = (int) $char['level'];
    $exp = (int) $char['experience'];
    $gold = (int) $char['gold'];
    $hp = min((int) $char['hp_cur'], $stats['hp_max']);
    $ps = (int) $char['potions_s'];
    $pm = (int) $char['potions_m'];
    $logs = [];
    $itemsGained = [];
    $lastEnemy = null;

    for ($i = 0; $i < $ticks; $i++) {
        if (!$hunting) {
            $hp = min($stats['hp_max'], $hp + $stats['regen']);
            continue;
        }

        $m = pick_monster($monsters);
        $mhp = (int) $m['hp'];
        $rounds = 0;
        while ($mhp > 0 && $hp > 0 && $rounds < 30) {
            $rounds++;
            if ($hp < 0.3 * $stats['hp_max']) {
                if ($pm > 0) {
                    $pm--;
                    $hp = min($stats['hp_max'], $hp + 180);
                    $logs[] = 'Voce usou uma Pocao de Brasa (+180 HP).';
                } elseif ($ps > 0) {
                    $ps--;
                    $hp = min($stats['hp_max'], $hp + 60);
                    $logs[] = 'Voce usou uma Pocao de Vapor (+60 HP).';
                }
            }
            $dmg = max(1, (int) round($stats['atk'] * random_int(85, 115) / 100 - $m['defense']));
            $mhp -= $dmg;
            if ($mhp <= 0) {
                break;
            }
            $hp -= max(0, (int) round($m['attack'] * random_int(85, 115) / 100 - $stats['def']));
        }

        if ($hp <= 0) {
            $hunting = false;
            $hp = (int) (0.3 * $stats['hp_max']);
            $logs[] = "Voce foi derrotado por {$m['name']} e arrastado de volta a Vapor Alto.";
            break;
        }
        $lastEnemy = (int) $m['id'];
        if ($mhp > 0) {
            $logs[] = "{$m['name']} resistiu demais — voce recuou para se recompor.";
            continue;
        }

        $exp += (int) $m['experience'];
        $g = random_int((int) $m['gold_min'], (int) $m['gold_max']);
        $gold += $g;
        $msg = "Voce derrotou {$m['name']} (+{$m['experience']} exp, +{$g} ouro).";
        if (!isset($lootCache[$m['id']])) {
            $lootCache[$m['id']] = loot_for((int) $m['id']);
        }
        foreach ($lootCache[$m['id']] as $loot) {
            if (random_int(1, 100) <= (int) $loot['chance']) {
                $itemsGained[] = (int) $loot['id'];
                $msg .= " Drop: {$loot['name']}!";
            }
        }
        $logs[] = $msg;

        while ($exp >= exp_next($level)) {
            $level++;
            $stats = char_stats(array_merge($char, ['level' => $level]));
            $hp = $stats['hp_max'];
            $logs[] = "LEVEL UP! Voce alcancou o nivel {$level} e se recuperou por completo.";
        }
    }

    $db = db();
    $db->beginTransaction();
    $db->prepare(
        'UPDATE characters SET level=?, experience=?, gold=?, hp_cur=?, area_id=?,
         last_enemy_id=?, hunt_since=?, potions_s=?, potions_m=?, last_tick_at=NOW() WHERE id=?'
    )->execute([
        $level, $exp, $gold, $hp,
        $hunting ? $char['area_id'] : null,
        $hunting ? $lastEnemy : null,
        $hunting ? $char['hunt_since'] : null,
        $ps, $pm, $char['id'],
    ]);
    foreach ($itemsGained as $itemId) {
        $db->prepare(
            'INSERT INTO character_items (character_id, item_id, equipped) VALUES (?, ?, 0)
             ON DUPLICATE KEY UPDATE item_id = item_id'
        )->execute([$char['id'], $itemId]);
    }
    foreach (array_slice($logs, -LOG_KEEP * 2) as $l) {
        add_log((int) $char['id'], $l);
    }
    $db->commit();

    $st = $db->prepare('SELECT * FROM characters WHERE id = ?');
    $st->execute([$char['id']]);
    return $st->fetch();
}
