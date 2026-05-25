<?php

namespace App\Domain\Database;

interface TransactionManagerInterface
{
    /**
     * Exécute une opération à l'intérieur d'une transaction SQL.
     * Si l'opération réussit, les données sont commitées.
     * Si une exception est levée, la transaction est annulée (rollback).
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed;
}
