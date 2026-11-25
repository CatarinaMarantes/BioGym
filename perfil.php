<?php
require_once __DIR__ . '/utils/session.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/utilizador.php';
require_once __DIR__ . '/templates/common.php';

requerLogin();

$db = getDatabaseConnection();
$utilizador = getUtilizadorPorId($db, $_SESSION['id']);
$temFoto = temFotografia($db, $_SESSION['id']);

drawHeader('O meu perfil');
drawCabecalhoPagina('Conta', 'O meu perfil', 'Os teus dados, a tua fotografia e a tua palavra-passe. O email não pode ser alterado.');
?>

<section class="formulario largo">
    <h2>Dados pessoais</h2>

    <?php drawAvatar($_SESSION['id'], $temFoto, $utilizador['nome']); ?>

    <form action="/actions/action_perfil.php" method="post" enctype="multipart/form-data">
        <?php drawCsrf(); ?>

        <fieldset class="campos">
            <p>
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= e($utilizador['nome']) ?>" required>
            </p>
            <p>
                <label for="email">Email</label>
                <input type="email" id="email" value="<?= e($utilizador['email']) ?>" disabled>
            </p>
            <p>
                <label for="telemovel">Telemóvel</label>
                <input type="tel" id="telemovel" name="telemovel" value="<?= e($utilizador['telemovel']) ?>" pattern="9[0-9]{8}" required>
            </p>
        </fieldset>

        <label for="biografia">Biografia</label>
        <textarea id="biografia" name="biografia" rows="4" maxlength="500"><?= e($utilizador['biografia']) ?></textarea>
        <small class="ajuda">Máximo 500 caracteres. Os treinadores aparecem com esta biografia na página de treinadores.</small>

        <label for="fotografia">Fotografia</label>
        <input type="file" id="fotografia" name="fotografia" accept="image/jpeg,image/png,image/webp">
        <small class="ajuda">JPEG, PNG ou WebP, até 1 MB.</small>

        <?php if ($temFoto) { ?>
            <label class="opcao">
                <input type="checkbox" name="removerFoto" value="1">
                Remover a fotografia atual
            </label>
        <?php } ?>

        <button type="submit">Guardar</button>
    </form>
</section>

<section class="formulario largo">
    <h2>Alterar palavra-passe</h2>

    <form action="/actions/action_password.php" method="post">
        <?php drawCsrf(); ?>

        <label for="passwordAtual">Palavra-passe atual</label>
        <input type="password" id="passwordAtual" name="passwordAtual" autocomplete="current-password" required>

        <fieldset class="campos">
            <p>
                <label for="passwordNova">Nova palavra-passe</label>
                <input type="password" id="passwordNova" name="passwordNova" minlength="8" autocomplete="new-password" required>
            </p>
            <p>
                <label for="confirmacao">Confirmar nova palavra-passe</label>
                <input type="password" id="confirmacao" name="confirmacao" minlength="8" autocomplete="new-password" required>
            </p>
        </fieldset>
        <small class="ajuda">Mínimo 8 caracteres.</small>

        <button type="submit">Alterar palavra-passe</button>
    </form>
</section>

<?php drawFooter(); ?>
