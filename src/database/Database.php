<?php

declare(strict_types=1);

namespace zsallazar\ffa\database;

use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use zsallazar\ffa\FFA;

final class Database{
    private DataConnector $connector;

    public function __construct(
        private readonly FFA $plugin
    ) {
        $this->connector = libasynql::create($plugin, $plugin->getConfig()->get("database"), [
            "sqlite" => "sqlite.sql",
            "mysql" => "mysql.sql",
        ]);
        $this->connector->executeGeneric("init", onError: function(SqlError $error): void{
            $this->plugin->getLogger()->error($error->getMessage());
        });
        $this->connector->waitAll();
    }

    public function getConnector(): DataConnector{ return $this->connector; }
}