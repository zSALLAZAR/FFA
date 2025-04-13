-- #!sqlite
-- #{ init
CREATE TABLE IF NOT EXISTS ffa (
    uuid TEXT PRIMARY KEY,
    name TEXT,
    kills INTEGER,
    deaths INTEGER,
    kdr FLOAT,
    highestKillStreak INTEGER
);
-- #}
-- #{ player
-- #	:uuid string
-- #	:name string
INSERT OR IGNORE INTO ffa (uuid, name, kills, deaths, kdr, highestKillStreak) VALUES (:uuid, :name, 0, 0, 0.0, 0);
-- #}
-- #{ update
-- #	:uuid string
-- #	:stat string
-- #	:value int
UPDATE ffa SET :stat = :value WHERE uuid = :uuid;
-- #}
-- #{ updateKdr
-- #	:uuid string
-- #	:value float
UPDATE ffa SET kdr = :value WHERE uuid = :uuid;
-- #}
-- #{ statsByUuid
-- #	:uuid string
SELECT uuid, name, kills, deaths, kdr, highestKillStreak FROM ffa WHERE uuid = :uuid;
-- #}
-- #{ statsByName
-- #	:name string
SELECT uuid, name, kills, deaths, kdr, highestKillStreak FROM ffa WHERE name = :name;
-- #}
-- #{ top
-- #	:order string
SELECT uuid, name, kills, deaths, kdr, highestKillStreak FROM ffa ORDER BY :order DESC LIMIT 10;
-- #}