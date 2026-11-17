<?php
// A ligação é criada uma só vez por pedido e reutilizada
function getDatabaseConnection() {
    static $db = null;
    if ($db !== null) return $db;

    $db = new PDO('sqlite:' . __DIR__ . '/biogym.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->setAttribute(PDO::ATTR_TIMEOUT, 5);
    $db->exec('PRAGMA foreign_keys = ON');
    return $db;
}

// Executa $funcao dentro de uma transação que bloqueia a escrita desde o início,
// para que duas inscrições em simultâneo não ultrapassem as vagas.
// $funcao devolve null se correu bem (COMMIT) ou um texto de erro (ROLLBACK).
// Devolve o que a função devolveu.
function emTransacao($db, $funcao) {
    $db->exec('BEGIN IMMEDIATE');
    try {
        $erro = $funcao();
        $db->exec($erro === null ? 'COMMIT' : 'ROLLBACK');
        return $erro;
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        throw $e;
    }
}
