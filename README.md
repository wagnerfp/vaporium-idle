# Vaporium Idle

RPG idle de navegador inspirado em Tibia (estilo Huntera), com a identidade
Vaporium: dark-steampunk, PT-BR, sem download, roda no PC e no celular.

## Stack

- PHP 8.3 puro (sem framework) + MySQL + JS vanilla + CSS proprio
- Sem websocket/cron: progressao calculada por *lazy ticks* no servidor
- Deploy em `/mnt/otdata/www/idle/` (docroot = `public/`)

## Estrutura

```
public/          docroot (index.php, api.php, assets/)
src/             config, db, auth, game (stats), hunt (motor idle)
sql/             schema.sql + seed.sql (4 vocacoes, 5 areas, 15 monstros, 25 itens)
env.php          DEPLOY-ONLY, fora do git — define IDLE_DB_PASS
```

## Mecanica

- Tick de 6s = 1 encontro; progressao offline limitada a 8h
- Combate por rounds com variancia 85-115%; pocoes automaticas em HP < 30%
- Level up: `exp_next = floor(100 * nivel^1.7)`, cura total ao subir
- Derrota: caca encerra, HP volta a 30%, sem perda de exp
- Loja: armas por vocacao (5 tiers), armaduras, pocoes; venda por 50%
- Ranking top 50 por nivel/experiencia

## Deploy

```bash
sudo rsync -a --delete --exclude env.php . /mnt/otdata/www/idle/
sudo chown -R www-data:www-data /mnt/otdata/www/idle
```

O `env.php` so existe no servidor (criado manualmente, chmod 600).

## Timezone

PHP roda com `date_default_timezone_set('America/Sao_Paulo')` para alinhar
com o MySQL (SYSTEM). Nao remover — sem isso os ticks de caça calculam errado.
