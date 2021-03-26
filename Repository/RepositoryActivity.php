<?php

namespace Learn\Repository;

use Doctrine\ORM\EntityRepository;

class RepositoryActivity extends EntityRepository
{
    //Add dql

    /*
    public function getPartsFromTuto($id)
    {
        $query = "SELECT * FROM " . $this->tableName . " WHERE tutorial_id = ?";
        $lines = DataBaseManager::getSharedInstance()->getAll($query, [intval($id)]);
        $result = [];
        foreach ($lines as $resource)
            array_push($result, $this->prepareObject($resource));

        return $result;
    }
    public function cleanParts($id)
    {
        $query = "DELETE FROM  " . $this->tableName . "  WHERE  tutorial_id=?";
        DataBaseManager::getSharedInstance()->exec($query, [$id]);
    }
    public function cleanPart($id)
    {
        $query = "DELETE FROM  " . $this->tableName . "  WHERE  id=?";
        $this->dbManager->exec($query, [$id]);
    }
    */
}
