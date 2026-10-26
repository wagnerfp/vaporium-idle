-- Vaporium Idle — seed (conteudo inicial, nomes PT-BR sem acentos)
USE vaporium_idle;

INSERT INTO vocations (id, name, descricao, hp_base, hp_pl, atk_base, atk_pl, def_base, def_pl, regen) VALUES
(1, 'Combatente de Ferro', 'Linha de frente de Vapor Alto. Muita vida, boa defesa, dano corpo a corpo.', 150, 25, 10, 2, 8, 2, 3),
(2, 'Atirador de Precisao', 'Olho firme e dedo leve. Dano a distancia equilibrado com defesa.', 110, 18, 12, 3, 6, 1, 2),
(3, 'Canalizador da Brasa', 'Dobra o vapor em fogo. Maior dano do jogo, mas corpo fragil.', 90, 12, 16, 4, 4, 1, 1),
(4, 'Alquimista de Campo', 'Misturas e vapores curativos. Dano magico e a melhor regeneracao.', 100, 15, 13, 3, 5, 1, 4);

INSERT INTO areas (id, name, min_level, descricao) VALUES
(1, 'Tubulacoes', 1, 'Tuneis de vapor sob Vapor Alto, infestados de pragas corroidas.'),
(2, 'Minas Abandonadas', 5, 'Galerias onde os operarios um dia extrairam minerio de ferro.'),
(3, 'Ermo Corroido', 12, 'Terras mortas alem dos muros, tomadas pela Corrosao.'),
(4, 'Forja Antiga', 20, 'O coracao industrial da era antes da Corrosao, agora habitado por automatos.'),
(5, 'Ninho do Draco', 30, 'Onde os Dracos de Vapor fazem seus ninhos entre caldeiras gigantes.');

INSERT INTO monsters (id, name, hp, attack, defense, experience, gold_min, gold_max) VALUES
(1, 'Rato das Tubulacoes', 30, 6, 2, 12, 1, 5),
(2, 'Serpente das Tubulacoes', 45, 9, 3, 20, 2, 8),
(3, 'Inseto das Tubulacoes', 60, 12, 4, 30, 3, 10),
(4, 'Rato das Minas', 90, 16, 6, 45, 4, 12),
(5, 'Lobo das Minas', 120, 20, 8, 60, 5, 15),
(6, 'Urso das Minas', 160, 24, 10, 85, 8, 20),
(7, 'Errante do Ermo', 220, 30, 12, 130, 10, 25),
(8, 'Batedor do Ermo', 260, 35, 14, 160, 12, 30),
(9, 'Arcanista do Ermo', 200, 45, 10, 190, 15, 35),
(10, 'Golem de Sucata', 380, 48, 22, 280, 20, 45),
(11, 'Devorador de Sucata', 340, 55, 18, 320, 22, 50),
(12, 'Automato Senhor de Guerra', 450, 60, 26, 400, 30, 60),
(13, 'Filhote de Draco de Caldeira', 600, 75, 30, 600, 40, 80),
(14, 'Draco de Caldeira', 800, 90, 38, 850, 60, 120),
(15, 'Draco de Vapor', 1000, 110, 45, 1200, 80, 150);

INSERT INTO area_monsters (area_id, monster_id, weight) VALUES
(1, 1, 5), (1, 2, 3), (1, 3, 2),
(2, 4, 5), (2, 5, 3), (2, 6, 2),
(3, 7, 5), (3, 8, 3), (3, 9, 2),
(4, 10, 5), (4, 11, 3), (4, 12, 2),
(5, 13, 5), (5, 14, 3), (5, 15, 2);

-- armas: melee (voc 1), distancia (voc 2), magicas (voc 3 e 4); armaduras: todas
INSERT INTO items (id, name, slot, attack, defense, price, min_level, vocation_id) VALUES
(1, 'Espada de Ferro', 'arma', 8, 0, 100, 1, 1),
(2, 'Lamina Corroida', 'arma', 14, 0, 400, 5, 1),
(3, 'Claymore da Forja', 'arma', 22, 0, 1200, 12, 1),
(4, 'Lamina do Rei Esquecido', 'arma', 32, 0, 3000, 20, 1),
(5, 'Cortador de Vapor', 'arma', 45, 0, 8000, 30, 1),
(6, 'Arco Curto', 'arma', 8, 0, 100, 1, 2),
(7, 'Arco de Precisao', 'arma', 14, 0, 400, 5, 2),
(8, 'Besta de Engrenagens', 'arma', 22, 0, 1200, 12, 2),
(9, 'Arco Longo do Ermo', 'arma', 32, 0, 3000, 20, 2),
(10, 'Ballista de Bolso', 'arma', 45, 0, 8000, 30, 2),
(11, 'Varinha de Brasa', 'arma', 9, 0, 100, 1, 3),
(12, 'Cajado de Fuligem', 'arma', 15, 0, 400, 5, 3),
(13, 'Varinha do Ermo', 'arma', 24, 0, 1200, 12, 3),
(14, 'Cajado da Corrosao', 'arma', 34, 0, 3000, 20, 3),
(15, 'Conduit de Vapor', 'arma', 48, 0, 8000, 30, 3),
(16, 'Armadura de Couro', 'armadura', 0, 5, 80, 1, NULL),
(17, 'Cota de Placas', 'armadura', 0, 9, 350, 5, NULL),
(18, 'Armadura de Engrenagens', 'armadura', 0, 14, 1000, 12, NULL),
(19, 'Couraca do Automato', 'armadura', 0, 20, 2500, 20, NULL),
(20, 'Armadura Draconica de Vapor', 'armadura', 0, 28, 7000, 30, NULL),
(21, 'Varinha de Brasa', 'arma', 9, 0, 100, 1, 4),
(22, 'Cajado de Fuligem', 'arma', 15, 0, 400, 5, 4),
(23, 'Varinha do Ermo', 'arma', 24, 0, 1200, 12, 4),
(24, 'Cajado da Corrosao', 'arma', 34, 0, 3000, 20, 4),
(25, 'Conduit de Vapor', 'arma', 48, 0, 8000, 30, 4);

-- loot: monstros de cada faixa dropam itens do tier seguinte (4%)
INSERT INTO monster_loot (monster_id, item_id, chance) VALUES
(2, 2, 4), (2, 7, 4), (2, 12, 4), (2, 22, 4), (3, 17, 4),
(5, 3, 4), (5, 8, 4), (5, 13, 4), (5, 23, 4), (6, 18, 4),
(8, 4, 4), (8, 9, 4), (8, 14, 4), (8, 24, 4), (9, 19, 4),
(11, 5, 4), (11, 10, 4), (11, 15, 4), (11, 25, 4), (12, 20, 4),
(14, 5, 3), (14, 10, 3), (14, 15, 3), (14, 25, 3), (15, 20, 3);
