CREATE PROCEDURE refactor_gl_data()
BEGIN

    CREATE TABLE IF NOT EXISTS gift_logs_history LIKE gift_logs;

    INSERT INTO gift_logs_history
    SELECT L.*
    FROM gift_logs L
    WHERE L.Summited = 0
      AND DATE(L.created_at) <= LAST_DAY(CURDATE() - INTERVAL 1 MONTH);

    CREATE TEMPORARY TABLE _gift_logs_temp
    SELECT L.*
    FROM gift_logs_history L
             LEFT JOIN gifts G
                       ON L.giftId = G.id
    WHERE L.Summited = 0
      AND DATE(L.created_at) <= LAST_DAY(CURDATE() - INTERVAL 1 MONTH);

    update gift_logs_history set Summited = 1 where Summited = 0;

    DELETE L
    FROM gift_logs L
             LEFT JOIN gifts G
                       ON L.giftId = G.id
    WHERE L.Summited = 0
      AND DATE(L.created_at) <= LAST_DAY(CURDATE() - INTERVAL 1 MONTH);


    INSERT INTO gift_logs (type, giftId, roomowner_id, giftName, giftNum, giftPrice,
                           sender_id, receiver_id, is_play, platform_obtain, receiver_obtain, roomowner_obtain,
                           union_id, created_at, updated_at, sender_family_id, receiver_family_id, agency_id,
                           agency_obtain, pk, Summited)
    SELECT G.type,
           T.giftId,
           T.roomowner_id,
           G.name,
           SUM(T.giftNum),
           SUM(T.giftPrice),
           T.sender_id,
           T.receiver_id,
           '0'  AS is_play,
           NULL AS platform_obtain,
           NULL AS receiver_obtain,
           NULL AS roomowner_obtain,
           NULL AS union_id,
           DATE(T.created_at),
           DATE(T.updated_at),
           T.sender_family_id,
           T.receiver_family_id,
           T.agency_id,
           SUM(T.agency_obtain),
           T.pk,
           '1'  AS Summited
    FROM _gift_logs_temp T
             LEFT JOIN gifts G
                       ON T.giftId = G.id
    GROUP BY G.type, G.name, T.giftId, T.roomowner_id, G.name, T.sender_id,
             T.receiver_id, DATE(T.created_at), DATE(T.updated_at),
             T.sender_family_id, T.receiver_family_id, T.agency_id, T.pk;

    DROP TABLE _gift_logs_temp;
END;
