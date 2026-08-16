CREATE PROCEDURE _SP_update_monthly_coin_game_users()
BEGIN

    SET FOREIGN_KEY_CHECKS = 0;

    CREATE TEMPORARY TABLE _monthly_coin_game_users

    SELECT C.user_id,
           SUM(C.coins)      AS 'coins',
           C.type,
           C.game_id,
           MIN(C.created_at) AS 'created_at',
           MIN(C.updated_at) AS 'updated_at'

    FROM coin_game_users C
    WHERE YEAR(C.created_at) = YEAR(CURDATE())
      AND MONTH(C.created_at) = MONTH(CURDATE())

    GROUP BY C.user_id, C.game_id, C.type, WEEK(C.created_at), WEEK(C.updated_at);


    DELETE FROM coin_game_users
    WHERE YEAR(created_at) = YEAR(CURDATE())
      AND MONTH(created_at) = MONTH(CURDATE());


    INSERT INTO coin_game_users (user_id, coins, type, game_id,
                                 created_at, updated_at)
    SELECT T.user_id, T.coins, T.type, T.game_id, T.created_at, T.updated_at
    FROM _monthly_coin_game_users T;


    DROP TABLE _monthly_coin_game_users;

    SET FOREIGN_KEY_CHECKS = 1;

END;
