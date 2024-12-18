<?php

namespace Catalog\Model;

use System\Engine\Model;

class BaseModel extends Model
{

    public function event(): array|false
    {
        $sql = "SELECT * FROM trEvent ORDER BY id";
        $statement = $this->pdo->query($sql);
        $response = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $response ?: false;
    }

    public function createEvent(array $args = []): int|false
    {
        $sql = "INSERT INTO V1_URETIM.dbo.trEvent (title,start,className,description) VALUES (:title,:start,:className,:description)";
        $statement = $this->pdo->prepare($sql);
        $statement->execute($args);
        $response = $this->pdo->lastInsertId();
        return $response ?: false;
    }

    public function editEvent(array $args = []): int|false
    {
        $sql = "UPDATE V1_URETIM.dbo.trEvent SET title=:title,start=:start,className=:className,description=:description WHERE id=:id";
        $statement = $this->pdo->prepare($sql);
        $statement->execute($args);
        $response = $statement->rowCount();
        return $response ?: false;
    }
}
