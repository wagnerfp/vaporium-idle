<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/hunt.php';

$action = $_GET['action'] ?? '';
$db = db();

switch ($action) {

case 'register': {
    $in = post_json();
    $email = trim($in['email'] ?? '');
    $pass = $in['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_error('E-mail invalido.');
    if (strlen($pass) < 6) json_error('A senha precisa de ao menos 6 caracteres.');
    $st = $db->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([$email]);
    if ($st->fetch()) json_error('E-mail ja cadastrado.');
    $db->prepare('INSERT INTO users (email, pass_hash) VALUES (?, ?)')
       ->execute([$email, password_hash($pass, PASSWORD_DEFAULT)]);
    start_session();
    $_SESSION['user_id'] = (int) $db->lastInsertId();
    json_out(['ok' => true]);
}

case 'login': {
    $in = post_json();
    $st = $db->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([trim($in['email'] ?? '')]);
    $user = $st->fetch();
    if (!$user || !password_verify($in['password'] ?? '', $user['pass_hash'])) {
        json_error('E-mail ou senha incorretos.', 401);
    }
    start_session();
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['char_id']);
    $st = $db->prepare('SELECT id FROM characters WHERE user_id = ?');
    $st->execute([$user['id']]);
    $char = $st->fetch();
    if ($char) $_SESSION['char_id'] = (int) $char['id'];
    json_out(['ok' => true, 'has_char' => (bool) $char]);
}

case 'logout': {
    start_session();
    session_destroy();
    json_out(['ok' => true]);
}

case 'create_character': {
    $user = current_user();
    if (!$user) json_error('Nao autenticado.', 401);
    $st = $db->prepare('SELECT id FROM characters WHERE user_id = ?');
    $st->execute([$user['id']]);
    if ($st->fetch()) json_error('Voce ja tem um personagem.');

    $in = post_json();
    $name = trim($in['name'] ?? '');
    $vocId = (int) ($in['vocation'] ?? 0);
    if (!preg_match('/^[A-Za-z][A-Za-z ]{2,29}$/', $name)) {
        json_error('Nome invalido: 3-30 caracteres, apenas letras e espacos.');
    }
    if ($vocId < 1 || $vocId > 4) json_error('Vocacao invalida.');
    $st = $db->prepare('SELECT id FROM characters WHERE name = ?');
    $st->execute([$name]);
    if ($st->fetch()) json_error('Nome ja em uso.');

    $voc = get_vocation($vocId);
    $db->prepare(
        'INSERT INTO characters (user_id, name, vocation_id, hp_cur, last_tick_at)
         VALUES (?, ?, ?, ?, NOW())'
    )->execute([$user['id'], $name, $vocId, $voc['hp_base']]);
    $charId = (int) $db->lastInsertId();

    $starterWeapon = [1 => 1, 2 => 6, 3 => 11, 4 => 21][$vocId];
    foreach ([$starterWeapon, 16] as $itemId) {
        $db->prepare('INSERT INTO character_items (character_id, item_id, equipped) VALUES (?, ?, 1)')
           ->execute([$charId, $itemId]);
    }
    add_log($charId, 'Voce chegou a Vapor Alto. A Brasa aguarda seu trabalho, forjado.');

    start_session();
    $_SESSION['char_id'] = $charId;
    json_out(['ok' => true]);
}

case 'state': {
    $char = current_char();
    if (!$char) json_error('Sem personagem.', 401);
    $char = process_char($char);
    $stats = char_stats($char);

    $st = $db->prepare('SELECT message, ts FROM hunt_log WHERE character_id = ? ORDER BY id DESC LIMIT 50');
    $st->execute([$char['id']]);
    $log = array_reverse($st->fetchAll());

    $st = $db->prepare(
        'SELECT i.*, ci.equipped FROM character_items ci JOIN items i ON i.id = ci.item_id
         WHERE ci.character_id = ? ORDER BY ci.equipped DESC, i.price'
    );
    $st->execute([$char['id']]);
    $inventory = $st->fetchAll();

    $enemy = null;
    if ($char['area_id'] !== null && $char['last_enemy_id'] !== null) {
        $st = $db->prepare('SELECT id, name, hp FROM monsters WHERE id = ?');
        $st->execute([$char['last_enemy_id']]);
        $m = $st->fetch();
        if ($m) {
            $enemy = [
                'id' => (int) $m['id'],
                'name' => $m['name'],
                'img' => "assets/img/tibia/monsters/{$m['id']}.png",
            ];
        }
    }

    json_out([
        'char' => [
            'name' => $char['name'],
            'vocation' => $stats['vocation']['name'],
            'vocation_id' => (int) $char['vocation_id'],
            'level' => (int) $char['level'],
            'exp' => (int) $char['experience'],
            'exp_next' => exp_next((int) $char['level']),
            'hp' => (int) $char['hp_cur'],
            'hp_max' => $stats['hp_max'],
            'atk' => $stats['atk'],
            'def' => $stats['def'],
            'gold' => (int) $char['gold'],
            'potions_s' => (int) $char['potions_s'],
            'potions_m' => (int) $char['potions_m'],
            'hunting' => $char['area_id'] !== null,
            'area_id' => $char['area_id'] !== null ? (int) $char['area_id'] : null,
        ],
        'enemy' => $enemy,
        'log' => $log,
        'inventory' => $inventory,
        'areas' => $db->query('SELECT * FROM areas ORDER BY min_level')->fetchAll(),
        'shop' => $db->query('SELECT * FROM items ORDER BY slot, price')->fetchAll(),
    ]);
}

case 'hunt_start': {
    $char = current_char();
    if (!$char) json_error('Sem personagem.', 401);
    $char = process_char($char);
    $in = post_json();
    $areaId = (int) ($in['area_id'] ?? 0);
    $st = $db->prepare('SELECT * FROM areas WHERE id = ?');
    $st->execute([$areaId]);
    $area = $st->fetch();
    if (!$area) json_error('Area inexistente.');
    if ((int) $char['level'] < (int) $area['min_level']) {
        json_error("Nivel {$area['min_level']} necessario para {$area['name']}.");
    }
    if ($char['area_id'] !== null && (int) $char['area_id'] === $areaId) {
        json_error('Voce ja esta cacando nesta area.');
    }
    $db->prepare('UPDATE characters SET area_id = ?, hunt_since = NOW(), last_tick_at = NOW() WHERE id = ?')
       ->execute([$areaId, $char['id']]);
    add_log((int) $char['id'], "Voce partiu para {$area['name']}. A caca comecou.");
    json_out(['ok' => true]);
}

case 'hunt_stop': {
    $char = current_char();
    if (!$char) json_error('Sem personagem.', 401);
    $char = process_char($char);
    $db->prepare('UPDATE characters SET area_id = NULL, hunt_since = NULL, last_tick_at = NOW() WHERE id = ?')
       ->execute([$char['id']]);
    add_log((int) $char['id'], 'Voce voltou a Vapor Alto para descansar.');
    json_out(['ok' => true]);
}

case 'equip': case 'unequip': {
    $char = current_char();
    if (!$char) json_error('Sem personagem.', 401);
    $char = process_char($char);
    $in = post_json();
    $itemId = (int) ($in['item_id'] ?? 0);
    $st = $db->prepare(
        'SELECT ci.*, i.slot, i.vocation_id, i.min_level, i.name FROM character_items ci
         JOIN items i ON i.id = ci.item_id WHERE ci.character_id = ? AND ci.item_id = ?'
    );
    $st->execute([$char['id'], $itemId]);
    $row = $st->fetch();
    if (!$row) json_error('Item nao esta na mochila.');

    if ($action === 'equip') {
        if ($row['vocation_id'] !== null && (int) $row['vocation_id'] !== (int) $char['vocation_id']) {
            json_error('Sua vocacao nao usa este item.');
        }
        if ((int) $char['level'] < (int) $row['min_level']) {
            json_error("Nivel {$row['min_level']} necessario.");
        }
        $db->prepare(
            'UPDATE character_items ci JOIN items i ON i.id = ci.item_id
             SET ci.equipped = 0 WHERE ci.character_id = ? AND i.slot = ?'
        )->execute([$char['id'], $row['slot']]);
        $db->prepare('UPDATE character_items SET equipped = 1 WHERE character_id = ? AND item_id = ?')
           ->execute([$char['id'], $itemId]);
    } else {
        $db->prepare('UPDATE character_items SET equipped = 0 WHERE character_id = ? AND item_id = ?')
           ->execute([$char['id'], $itemId]);
    }
    json_out(['ok' => true]);
}

case 'sell': {
    $char = current_char();
    if (!$char) json_error('Sem personagem.', 401);
    $char = process_char($char);
    $in = post_json();
    $itemId = (int) ($in['item_id'] ?? 0);
    $st = $db->prepare(
        'SELECT ci.equipped, i.price, i.name FROM character_items ci
         JOIN items i ON i.id = ci.item_id WHERE ci.character_id = ? AND ci.item_id = ?'
    );
    $st->execute([$char['id'], $itemId]);
    $row = $st->fetch();
    if (!$row) json_error('Item nao esta na mochila.');
    if ($row['equipped']) json_error('Desequipe o item antes de vender.');
    $value = (int) floor($row['price'] / 2);
    $db->prepare('DELETE FROM character_items WHERE character_id = ? AND item_id = ?')
       ->execute([$char['id'], $itemId]);
    $db->prepare('UPDATE characters SET gold = gold + ? WHERE id = ?')->execute([$value, $char['id']]);
    add_log((int) $char['id'], "Voce vendeu {$row['name']} por {$value} ouro.");
    json_out(['ok' => true]);
}

case 'buy': {
    $char = current_char();
    if (!$char) json_error('Sem personagem.', 401);
    $char = process_char($char);
    $in = post_json();

    if (isset($in['potion'])) {
        $kind = $in['potion'] === 'm' ? 'm' : 's';
        $price = $kind === 'm' ? 80 : 25;
        if ((int) $char['gold'] < $price) json_error('Ouro insuficiente.');
        $col = $kind === 'm' ? 'potions_m' : 'potions_s';
        $db->prepare("UPDATE characters SET gold = gold - ?, $col = $col + 1 WHERE id = ?")
           ->execute([$price, $char['id']]);
        json_out(['ok' => true]);
    }

    $itemId = (int) ($in['item_id'] ?? 0);
    $st = $db->prepare('SELECT * FROM items WHERE id = ?');
    $st->execute([$itemId]);
    $item = $st->fetch();
    if (!$item) json_error('Item inexistente.');
    if ($item['vocation_id'] !== null && (int) $item['vocation_id'] !== (int) $char['vocation_id']) {
        json_error('Sua vocacao nao usa este item.');
    }
    if ((int) $char['level'] < (int) $item['min_level']) {
        json_error("Nivel {$item['min_level']} necessario.");
    }
    if ((int) $char['gold'] < (int) $item['price']) json_error('Ouro insuficiente.');
    $st = $db->prepare('SELECT 1 FROM character_items WHERE character_id = ? AND item_id = ?');
    $st->execute([$char['id'], $itemId]);
    if ($st->fetch()) json_error('Voce ja possui este item.');
    $db->prepare('UPDATE characters SET gold = gold - ? WHERE id = ?')->execute([$item['price'], $char['id']]);
    $db->prepare('INSERT INTO character_items (character_id, item_id, equipped) VALUES (?, ?, 0)')
       ->execute([$char['id'], $itemId]);
    add_log((int) $char['id'], "Voce comprou {$item['name']} por {$item['price']} ouro.");
    json_out(['ok' => true]);
}

case 'ranking': {
    $st = $db->query(
        'SELECT c.name, v.name AS vocation, c.level, c.experience
         FROM characters c JOIN vocations v ON v.id = c.vocation_id
         ORDER BY c.level DESC, c.experience DESC LIMIT 50'
    );
    json_out(['ranking' => $st->fetchAll()]);
}

case 'vocations': {
    json_out(['vocations' => $db->query('SELECT * FROM vocations')->fetchAll()]);
}

default:
    json_error('Acao desconhecida.', 404);
}
