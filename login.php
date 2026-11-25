<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/templates/common.php';

if (isset($_SESSION['id'])) redirecionar('/index.php');

drawHeader('Entrar');
?>
<section class="login-introducao">
    <h1>Bem-vindo ao BioGym</h1>
    <p>O teu treino começa aqui.</p>
</section>

<section class="formulario formulario-login">
    <h2>Entrar</h2>
    <form action="/actions/action_login.php" method="post">
        <?php drawCsrf(); ?>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Palavra-passe</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Entrar</button>
    </form>
    <p>Ainda não tem conta? <a href="/registo.php">Registar</a></p>
</section>
<?php drawCsrf(); ?>