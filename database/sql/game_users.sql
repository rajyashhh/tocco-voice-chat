CREATE PROCEDURE _SP_update_coin_game_users_tbl()
BEGIN


    CREATE TEMPORARY TABLE _coin_game_users_temp

    SELECT C.user_id,
           SUM(C.coins)      AS 'coins',
           C.type,
           C.game_id,
           MIN(C.created_at) AS 'created_at',
           MIN(C.updated_at) AS 'updated_at'

    FROM coin_game_users C
    WHERE YEAR(C.created_at) < YEAR(CURDATE())
       OR (YEAR(C.created_at) = YEAR(CURDATE()) AND MONTH(C.created_at) < MONTH(CURDATE()))
    GROUP BY C.user_id, C.game_id, C.type;


    DELETE
    FROM coin_game_users
    WHERE YEAR(created_at) < YEAR(CURDATE())
       OR (YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) < MONTH(CURDATE()));


    set foreign_key_checks = 0;

    INSERT INTO coin_game_users (user_id, coins, type, game_id,
                                 created_at, updated_at)
    SELECT T.user_id, T.coins, T.type, T.game_id, T.created_at, T.updated_at
    FROM _coin_game_users_temp T;

    set foreign_key_checks = 1;

    SELECT * FROM coin_game_users;
    DROP TABLE _coin_game_users_temp;

END;
