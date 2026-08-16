CREATE PROCEDURE _SP_calculate_users_salaries
(
    IN _month INT,
    IN _year INT,
    IN target_check BOOLEAN
)
BEGIN




    CREATE TEMPORARY TABLE _days_calculation(


                                                id bigint unsigned NOT NULL ,
                                                monthly_diamond_received bigint NOT NULL DEFAULT '0',
                                                hours varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                                                monthly_days smallint NOT NULL DEFAULT '0',
                                                agency_id int(10) unsigned DEFAULT 0,
                                                target BIGINT DEFAULT 0,
                                                target_days INT DEFAULT 0,
                                                target_hours INT DEFAULT 0,
                                                target_usd INT DEFAULT 0,
                                                user_usd DOUBLE(8,2) DEFAULT 0,
                                                target_share DOUBLE(8,2) DEFAULT 0,
                                                agency_share_usd DOUBLE(8,2) DEFAULT 0


    );


#insert_user_data

    INSERT INTO _days_calculation  (id, monthly_diamond_received, hours ,
                                    monthly_days , agency_id )

    SELECT  U.id , U.monthly_diamond_received , COALESCE(SUM(LT.hours), 0) AS total_hours ,
            U.monthly_days , U.agency_id

    FROM users U
             LEFT JOIN live_times LT
                       ON U.id=LT.uid
                           AND YEAR(LT.created_at) = _year
                           AND MONTH(LT.created_at) = _month

    WHERE U.salary_is_updated =1 AND U.type_user <> 0
    GROUP BY U.id , U.monthly_diamond_received , U.monthly_days , U.agency_id
    ;

#turn_salary_is_updated_to_zero

    UPDATE users U
        JOIN
        ( SELECT
              id

          FROM _days_calculation

        ) AS calc  ON U.id = calc.id

    SET
        U.salary_is_updated=0 ;



#calculate_targets

    UPDATE _days_calculation AS daily
        CROSS JOIN  (
            SELECT
                diamonds,
                days AS target_days,
                hours AS target_hours,
                agency_share as target_share,
                usd as target_usd
            FROM
                targets
        ) AS  T ON  T.diamonds <= daily.monthly_diamond_received
    SET
        daily.target =(
            SELECT MAX(T.diamonds)
            FROM targets T
            WHERE
                    T.diamonds <= daily.monthly_diamond_received
        ),

        daily.target_days = (
            SELECT T.days
            FROM targets T
            WHERE T.diamonds IN
                  (
                      SELECT MAX(T.diamonds)
                      FROM targets T
                      WHERE T.diamonds <= daily.monthly_diamond_received)
        ),

        daily.target_hours = (
            SELECT T.hours
            FROM targets T
            WHERE T.diamonds IN
                  (
                      SELECT MAX(T.diamonds)
                      FROM targets T
                      WHERE T.diamonds <= daily.monthly_diamond_received)),

        daily.target_usd = (
            SELECT T.usd
            FROM targets T
            WHERE T.diamonds IN
                  (
                      SELECT MAX(T.diamonds)
                      FROM targets T
                      WHERE T.diamonds <= daily.monthly_diamond_received)),


        daily.target_share = (
            SELECT T.agency_share
            FROM targets T
            WHERE T.diamonds IN
                  (
                      SELECT MAX(T.diamonds)
                      FROM targets T
                      WHERE T.diamonds <= daily.monthly_diamond_received))
    ;


#calculate_user_usd


    UPDATE _days_calculation AS t

    SET
        t.user_usd = t.target_usd * 0.5 + CASE
                                              WHEN hours >= target_hours THEN 0.2 * target_usd
                                              ELSE 0
            END + CASE
                      WHEN monthly_days >= target_days THEN 0.3 * target_usd
                      ELSE 0
                         END,


        t.user_usd = CASE
                         WHEN (t.user_usd < t.target_usd) AND (target_check = 1) THEN 0 else t.user_usd
            END,

        t.agency_share_usd = t.user_usd * (target_share / 100)

    ;





#update_users_salaries

    UPDATE user_sallaries us
        JOIN
        ( SELECT
              id,
              monthly_diamond_received,
              hours,
              monthly_days,
              agency_id,
              target,
              target_days,
              target_hours,
              user_usd,
              agency_share_usd

          FROM _days_calculation

        ) AS calc  ON us.user_id = calc.id AND us.user_agency_id = calc.agency_id

    SET
        us.hours= CONCAT(calc.hours, ' / ', calc.target_hours),
        us.days = CONCAT(calc.monthly_days, ' / ', calc.target_days),
        us.sallary= calc.user_usd,
        us.agency_sallary= calc.agency_share_usd,
        us.updated_at= CURRENT_TIMESTAMP(),
        us.diamond= CONCAT(calc.monthly_diamond_received, ' / ', calc.target)

    WHERE
            us.user_id = calc.id AND
            us.month=_month     AND           #----------------MONTH(NOW())
            us.year=_year AND
            us.user_agency_id = calc.agency_id
    ;





#insert_into_users_salaries

    INSERT INTO user_sallaries
    ( user_id , hours , days , sallary, agency_sallary, cut_amount, month, year ,
      is_paid, user_agency_id , created_at , updated_at, diamond , extras )

    SELECT D.id , CONCAT(D.hours, ' / ', D.target_hours),
           CONCAT(D.monthly_days, ' / ', D.target_days),
           D.user_usd , D.agency_share_usd, '0' AS 'cut_amount',  _month ,
           _year ,'0' AS 'is_paid' , D.agency_id , CURRENT_TIMESTAMP() AS 'created_at' ,
           CURRENT_TIMESTAMP() AS 'updated_at' , CONCAT(D.monthly_diamond_received, ' / ', D.target) ,
           NULL AS 'extras'

    FROM   _days_calculation D
    WHERE

            D.id NOT IN (SELECT user_id FROM user_sallaries S
                         where D.agency_id = S.user_agency_id AND S.month = _month AND S.year = _year )

    ;





    CREATE TEMPORARY TABLE _calculate_agency_salaries

    SELECT  U.user_agency_id ,sum(U.agency_sallary) AS 'agency_sallary'
    FROM user_sallaries U
    WHERE U.month= _month AND U.year= _year
    GROUP BY U.user_agency_id;


#update_agency_salaries

    UPDATE agency_sallaries S
        JOIN _calculate_agency_salaries temp ON S.agency_id  = temp.user_agency_id
    SET S.sallary = temp.agency_sallary,
        S.updated_at=CURRENT_TIMESTAMP()
    WHERE
            S.month=_month   AND           #----------------MONTH(NOW())
            S.year=_year
    ;


#insert_into_agency_salaries

    INSERT INTO agency_sallaries
    ( agency_id , sallary , cut_amount , month, year, is_paid, created_at, updated_at )

    SELECT temp.user_agency_id , temp.agency_sallary,'0' AS 'cut_amount', _month AS 'month' , _year AS 'year',
           '0' AS 'is_paid'   , CURRENT_TIMESTAMP() AS 'created_at' ,
           CURRENT_TIMESTAMP() AS 'updated_at'


    FROM  _calculate_agency_salaries temp
    WHERE

            temp.user_agency_id NOT IN (SELECT A.agency_id FROM agency_sallaries A
                                        where temp.user_agency_id = A.agency_id AND A.month = _month  AND A.year = _year )

    ;


DROP TABLE _days_calculation;
DROP TABLE _calculate_agency_salaries;




END;
