-- Выполнение второй части тестового задания:

-- Получить минимальную, максимальную и среднюю стоимость всех рабов весом более 60 кг.
SELECT MAX(price), MIN(price), AVG(price)
    FROM slave
    WHERE weight > 60;


-- Выбрать категории, в которых больше 10 рабов.
SELECT category.title, COUNT(category.id) as slavesCount
    FROM slave
    JOIN slave_category ON slave_category.slaveId = slave.id
    JOIN category ON slave_category.categoryId = category.id
    GROUP BY category.id HAVING COUNT(category.id) > 1;


-- Выбрать категорию с наибольшей суммарной стоимостью рабов.
SELECT category.title, SUM(slave.price) as maxPriceSum
    FROM slave
    JOIN slave_category ON slave_category.slaveId = slave.id
    JOIN category ON slave_category.categoryId = category.id
    GROUP BY category.id
    ORDER BY maxPriceSum DESC
    LIMIT 1;


-- Выбрать категории, в которых мужчин больше чем женщин.
-- PS: Соответствует заданию, но выбирает еще категории содержащие только мужчин. Категории без женщин можно отфильтровать дополнительным условием "AND COUNT( if(slave.gender = 'female', 1, null) > 0 )".
SELECT category.id, category.title
    FROM slave
    JOIN slave_category ON slave_category.slaveId = slave.id
    JOIN category ON slave_category.categoryId = category.id
    GROUP BY category.id
    HAVING COUNT( if(slave.gender = 'male', 1, null) ) > COUNT( if(slave.gender = 'female', 1, null) );

-- Количество рабов в категории "Для кухни" (включая все вложенные категории).
SELECT COUNT(DISTINCT slaveId)
    FROM slave_category
    WHERE categoryId IN (
        SELECT node.id
            FROM category AS node
            JOIN category AS parent
            WHERE node.left BETWEEN parent.left AND parent.right
                AND parent.title = 'Для кухни'
            ORDER BY node.left
    );
