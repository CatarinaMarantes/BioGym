<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/templates/common.php';

if (isset($_SESSION['id'])) redirecionar('/index.php');

// Valores escritos antes de um erro (guardados por action_registo.php)
$valores = isset($_SESSION['formRegisto']) ? $_SESSION['formRegisto'] : array();
unset($_SESSION['formRegisto']);
$v = function ($campo) use ($valores) {
    return isset($valores[$campo]) ? $valores[$campo] : '';
};

drawHeader('Registar');
?>
<section class="formulario">
    <h1>Criar conta</h1>
    <form action="/actions/action_registo.php" method="post">
        <?php drawCsrf(); ?>

        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" value="<?= e($v('nome')) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($v('email')) ?>" required>

        <label for="telemovel">Telemóvel</label>
        <input type="tel" id="telemovel" name="telemovel" value="<?= e($v('telemovel')) ?>" pattern="9[0-9]{8}" placeholder="912345678" required>

        <label for="dataNascimento">Data de nascimento</label>
        <input type="date" id="dataNascimento" name="dataNascimento" value="<?= e($v('dataNascimento')) ?>" max="<?= date('Y-m-d') ?>" required>

        <label for="password">Palavra-passe (mínimo 8 caracteres)</label>
        <input type="password" id="password" name="password" minlength="8" required>

        <label for="confirmacao">Confirmar palavra-passe</label>
        <input type="password" id="confirmacao" name="confirmacao" minlength="8" required>

        <button type="submit">Registar</button>
    </form>
    <p>Já tem conta? <a href="/login.php">Entrar</a></p>
</section>
<?php drawFooter(); ?>
