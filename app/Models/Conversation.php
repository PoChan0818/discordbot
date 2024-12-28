<?php

namespace App\Models;

use CodeIgniter\Model;

class Conversation extends Model
{
    protected $table = 'conversation as c';
    public function __construct()
    {
        $this->dbt = db_connect();
        $this->builder = $this->dbt->table($this->table);
    }

    public function getAll($userid)
    {
        return $this->builder
            ->where("c.userid", $userid)
            ->orderBy("c.time", 'ASC')
            ->get()->getResultArray();
    }

    public function store($data)
    {
        return $this->builder->insert($data);
    }
}
