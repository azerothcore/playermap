# AzerothCore Playermap

![Eastern Kingdom](https://raw.githubusercontent.com/azerothcore/playermap/master/img/showcase/eastern_kingdom.png)
![Outland](https://raw.githubusercontent.com/azerothcore/playermap/master/img/showcase/outland.png)
![Northrend](https://raw.githubusercontent.com/azerothcore/playermap/master/img/showcase/northrend.png)

## Configure

Copy  **config/playermap_config.php.conf** into **config/playermap_config.php** and configure it.

Open **pomm_conf.php** and set the **realmd_id**.

Done!

## Language

Set **$language** in **config/playermap_config.php** to one of the shipped languages:
**english**, **german** or **chinese**. The value is the middle part of the matching
`map_<language>.php` / `zone_names_<language>.php` file names, so adding a translation
is a matter of dropping in that pair of files. Unknown values fall back to english.

## Credits

- Dmitry Koterov (original author)
- Helias (maintainer)
